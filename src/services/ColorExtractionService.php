<?php

namespace newism\imagecolors\services;

use craft\base\Component;
use GdImage;

/**
 * Extracts dominant colour palettes with weights from images.
 *
 * Implements the Modified Median Cut Quantization (MMCQ) algorithm directly,
 * removing the dependency on ksubileau/color-thief-php. This allows a single
 * pixel scan per region — the histogram drives both palette extraction and
 * weight calculation.
 */
class ColorExtractionService extends Component
{
    /**
     * Edge strip size as a fraction of the image dimension.
     */
    private const EDGE_FRACTION = 0.15;

    /**
     * 5-bit precision for RGB histogram buckets (matches MMCQ standard).
     */
    private const SIGBITS = 5;

    /**
     * Right shift to convert 8-bit RGB → 5-bit bucket.
     */
    private const RSHIFT = 3;

    /**
     * Pixels with alpha > this threshold are skipped (too transparent).
     */
    private const THRESHOLD_ALPHA = 62;

    /**
     * Pixels where all RGB channels exceed this are skipped (too white).
     */
    private const THRESHOLD_WHITE = 250;

    /**
     * First quantization pass uses this fraction of target colours.
     */
    private const FRACT_BY_POPULATION = 0.75;

    /**
     * Maximum median cut iterations.
     */
    private const MAX_ITERATIONS = 1000;

    /**
     * Region keys in extraction order.
     */
    /** @var string[] */
    public const REGIONS = ['overall', 'focal', 'top', 'right', 'bottom', 'left'];

    /**
     * Extract palettes for the overall image, focal point, and each edge region.
     *
     * Loads the image into GD once, then extracts each region using area bounds.
     * Each region requires a single pixel scan.
     *
     * @param array{x: float, y: float}|null $focalPoint Focal point as fractions (0-1).
     * @return array<string, array<array{hex: string, rgb: int[], weight: float}>>
     */
    public function extractAllRegions(string $imagePath, int $colorCount = 4, int $sampleQuality = 10, ?array $focalPoint = null): array
    {
        $data = file_get_contents($imagePath);
        if ($data === false) {
            return [];
        }

        $image = imagecreatefromstring($data);
        unset($data);
        if ($image === false) {
            return [];
        }

        $width = imagesx($image);
        $height = imagesy($image);

        $edgeW = max(1, (int)round($width * self::EDGE_FRACTION));
        $edgeH = max(1, (int)round($height * self::EDGE_FRACTION));

        $fp = $focalPoint ?? ['x' => 0.5, 'y' => 0.5];
        $focalX = max(0, min((int)round($fp['x'] * $width - $edgeW / 2), $width - $edgeW));
        $focalY = max(0, min((int)round($fp['y'] * $height - $edgeH / 2), $height - $edgeH));

        $regions = [
            'overall' => null,
            'focal' => ['x' => $focalX, 'y' => $focalY, 'w' => $edgeW, 'h' => $edgeH],
            'top' => ['x' => 0, 'y' => 0, 'w' => $width, 'h' => $edgeH],
            'right' => ['x' => $width - $edgeW, 'y' => 0, 'w' => $edgeW, 'h' => $height],
            'bottom' => ['x' => 0, 'y' => $height - $edgeH, 'w' => $width, 'h' => $edgeH],
            'left' => ['x' => 0, 'y' => 0, 'w' => $edgeW, 'h' => $height],
        ];

        $results = [];

        foreach ($regions as $name => $area) {
            $results[$name] = $this->extractFromGd($image, $colorCount, $sampleQuality, $area);
        }

        imagedestroy($image);

        return $results;
    }

    /**
     * Extract a palette from a file path.
     *
     * @return array<array{hex: string, rgb: int[], weight: float}>
     */
    public function extractColors(string $imagePath, int $colorCount = 4, int $sampleQuality = 10): array
    {
        $data = file_get_contents($imagePath);
        if ($data === false) {
            return [];
        }

        $image = imagecreatefromstring($data);
        unset($data);
        if ($image === false) {
            return [];
        }

        $result = $this->extractFromGd($image, $colorCount, $sampleQuality);
        imagedestroy($image);

        return $result;
    }

    /**
     * Extract a palette from a GD image with optional area constraint.
     *
     * Single pixel scan: builds a 5-bit histogram, runs MMCQ, and derives
     * weights from VBox population counts — all from one pass.
     *
     * @param array{x: int, y: int, w: int, h: int}|null $area
     * @return array<array{hex: string, rgb: int[], weight: float}>
     */
    private function extractFromGd(GdImage $image, int $colorCount = 4, int $sampleQuality = 10, ?array $area = null): array
    {
        // Step 1: Build histogram (single pixel scan)
        [$histo, $totalPixels] = $this->buildHistogram($image, $sampleQuality, $area);

        if ($totalPixels === 0 || empty($histo)) {
            return [];
        }

        // Step 2: Run MMCQ to get VBoxes (contains both colour and population)
        $vboxes = $this->quantize($totalPixels, $colorCount, $histo);

        if (empty($vboxes)) {
            return [];
        }

        // Step 3: Build results with weights derived from VBox counts
        $results = [];
        foreach ($vboxes as $vbox) {
            $rgb = $vbox->avg();
            $count = $vbox->count();
            $results[] = [
                'hex' => sprintf('#%02X%02X%02X', $rgb[0], $rgb[1], $rgb[2]),
                'rgb' => $rgb,
                'weight' => round($count / $totalPixels, 4),
            ];
        }

        // Sort by weight descending, exclude < 1%
        usort($results, fn($a, $b) => $b['weight'] <=> $a['weight']);

        return array_values(array_filter($results, fn($c) => $c['weight'] >= 0.01));
    }

    /**
     * Build a 5-bit RGB histogram from a GD image.
     *
     * @param array{x: int, y: int, w: int, h: int}|null $area
     * @return array{array<int, int>, int} [histogram, totalPixels]
     */
    private function buildHistogram(GdImage $image, int $quality, ?array $area = null): array
    {
        $startX = $area['x'] ?? 0;
        $startY = $area['y'] ?? 0;
        $regionW = $area['w'] ?? imagesx($image);
        $regionH = $area['h'] ?? imagesy($image);

        $histo = [];
        $totalPixels = 0;
        $pixelCount = $regionW * $regionH;
        $step = max(1, $quality);

        for ($i = 0; $i < $pixelCount; $i += $step) {
            $x = $startX + ($i % $regionW);
            $y = $startY + (int)($i / $regionW);

            $rgba = imagecolorat($image, $x, $y);

            // Check alpha (GD alpha: 0 = opaque, 127 = transparent)
            $alpha = ($rgba >> 24) & 0x7F;
            if ($alpha > self::THRESHOLD_ALPHA) {
                continue;
            }

            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;

            // Skip near-white pixels
            if ($r > self::THRESHOLD_WHITE && $g > self::THRESHOLD_WHITE && $b > self::THRESHOLD_WHITE) {
                continue;
            }

            $bucketIndex = (($r >> self::RSHIFT) << (2 * self::SIGBITS))
                | (($g >> self::RSHIFT) << self::SIGBITS)
                | ($b >> self::RSHIFT);

            $histo[$bucketIndex] = ($histo[$bucketIndex] ?? 0) + 1;
            $totalPixels++;
        }

        return [$histo, $totalPixels];
    }

    /**
     * Modified Median Cut Quantization.
     *
     * @param array<int, int> $histo
     * @return VBox[]
     */
    private function quantize(int $numPixels, int $maxColors, array $histo): array
    {
        if ($numPixels === 0 || empty($histo)) {
            return [];
        }

        // Find histogram bounds
        $rMin = $gMin = $bMin = 31;
        $rMax = $gMax = $bMax = 0;

        foreach ($histo as $idx => $_) {
            $r = ($idx >> (2 * self::SIGBITS)) & 0x1F;
            $g = ($idx >> self::SIGBITS) & 0x1F;
            $b = $idx & 0x1F;
            $rMin = min($rMin, $r);
            $rMax = max($rMax, $r);
            $gMin = min($gMin, $g);
            $gMax = max($gMax, $g);
            $bMin = min($bMin, $b);
            $bMax = max($bMax, $b);
        }

        $initialVBox = new VBox($rMin, $rMax, $gMin, $gMax, $bMin, $bMax, $histo);

        // First pass: sort by population
        $boxes = [$initialVBox];
        $target1 = (int)ceil(self::FRACT_BY_POPULATION * $maxColors);
        $this->iterateCut($boxes, $target1, $histo, 'count');

        // Second pass: sort by population × volume
        $this->iterateCut($boxes, $maxColors, $histo, 'countVolume');

        // Sort final boxes by population descending
        usort($boxes, fn(VBox $a, VBox $b) => $b->count() <=> $a->count());

        return $boxes;
    }

    /**
     * Repeatedly split the most splittable VBox until target count is reached.
     *
     * @param VBox[] $boxes
     * @param array<int, int> $histo
     */
    private function iterateCut(array &$boxes, int $target, array $histo, string $sortBy): void
    {
        $iterations = 0;

        while (count($boxes) < $target && $iterations < self::MAX_ITERATIONS) {
            $iterations++;

            // Sort and pick the last (largest) box to split
            usort($boxes, function (VBox $a, VBox $b) use ($sortBy) {
                if ($sortBy === 'countVolume') {
                    return ($a->count() * $a->volume()) <=> ($b->count() * $b->volume());
                }
                return $a->count() <=> $b->count();
            });

            $vbox = array_pop($boxes);

            if ($vbox->count() === 0) {
                $boxes[] = $vbox;
                break;
            }

            $split = $this->medianCut($vbox, $histo);

            if ($split === null) {
                $boxes[] = $vbox;
                break;
            }

            $boxes[] = $split[0];
            $boxes[] = $split[1];
        }
    }

    /**
     * Split a VBox along its longest axis at the median pixel.
     *
     * @param array<int, int> $histo
     * @return array{VBox, VBox}|null
     */
    private function medianCut(VBox $vbox, array $histo): ?array
    {
        $axis = $vbox->longestAxis();

        // Build partial sums along the longest axis
        [$total, $partialSum] = $this->sumAlongAxis($axis, $vbox, $histo);

        if ($total === 0) {
            return null;
        }

        $lookAlong = match ($axis) {
            'r' => [$vbox->r1, $vbox->r2],
            'g' => [$vbox->g1, $vbox->g2],
            'b' => [$vbox->b1, $vbox->b2],
        };

        // Find the median cut point
        $median = (int)($total / 2);
        for ($i = $lookAlong[0]; $i <= $lookAlong[1]; $i++) {
            if (($partialSum[$i] ?? 0) >= $median) {
                // Balance the split
                $left = $i - $lookAlong[0];
                $right = $lookAlong[1] - $i;
                $cutPoint = $left <= $right
                    ? min($i + (int)($right / 2), $lookAlong[1] - 1)
                    : max($i - 1 - (int)($left / 2), $lookAlong[0]);

                // Ensure progress
                while (!isset($partialSum[$cutPoint]) || $partialSum[$cutPoint] === 0) {
                    $cutPoint++;
                    if ($cutPoint > $lookAlong[1] - 1) {
                        break;
                    }
                }

                $vbox1 = $vbox->copy();
                $vbox2 = $vbox->copy();

                match ($axis) {
                    'r' => [$vbox1->r2, $vbox2->r1] = [$cutPoint, $cutPoint + 1],
                    'g' => [$vbox1->g2, $vbox2->g1] = [$cutPoint, $cutPoint + 1],
                    'b' => [$vbox1->b2, $vbox2->b1] = [$cutPoint, $cutPoint + 1],
                };

                $vbox1->invalidate();
                $vbox2->invalidate();

                return [$vbox1, $vbox2];
            }
        }

        return null;
    }

    /**
     * Compute partial sums of pixel counts along one axis.
     *
     * @param 'r'|'g'|'b' $axis
     * @param array<int, int> $histo
     * @return array{int, array<int, int>} [total, partialSum]
     */
    private function sumAlongAxis(string $axis, VBox $vbox, array $histo): array
    {
        $total = 0;
        $partialSum = [];

        // Iterate in axis-primary order
        $ranges = match ($axis) {
            'r' => [[$vbox->r1, $vbox->r2], [$vbox->g1, $vbox->g2], [$vbox->b1, $vbox->b2]],
            'g' => [[$vbox->g1, $vbox->g2], [$vbox->r1, $vbox->r2], [$vbox->b1, $vbox->b2]],
            'b' => [[$vbox->b1, $vbox->b2], [$vbox->r1, $vbox->r2], [$vbox->g1, $vbox->g2]],
        };

        for ($primary = $ranges[0][0]; $primary <= $ranges[0][1]; $primary++) {
            $sum = 0;
            for ($sec = $ranges[1][0]; $sec <= $ranges[1][1]; $sec++) {
                for ($ter = $ranges[2][0]; $ter <= $ranges[2][1]; $ter++) {
                    $idx = match ($axis) {
                        'r' => ($primary << (2 * self::SIGBITS)) | ($sec << self::SIGBITS) | $ter,
                        'g' => ($sec << (2 * self::SIGBITS)) | ($primary << self::SIGBITS) | $ter,
                        'b' => ($sec << (2 * self::SIGBITS)) | ($ter << self::SIGBITS) | $primary,
                    };
                    $sum += $histo[$idx] ?? 0;
                }
            }
            $total += $sum;
            $partialSum[$primary] = $total;
        }

        return [$total, $partialSum];
    }
}
