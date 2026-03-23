<?php

namespace newism\imagecolors\services;

/**
 * Volume Box (VBox) for the Modified Median Cut Quantization algorithm.
 *
 * Represents a rectangular region in 5-bit RGB colour space. Each axis
 * ranges 0-31 (5 bits per channel). The histogram maps 15-bit bucket
 * indices to pixel counts.
 */
class VBox
{
    private const SIGBITS = 5;

    private ?int $cachedVolume = null;
    private ?int $cachedCount = null;
    /** @var int[]|null */
    private ?array $cachedAvg = null;

    /**
     * @param array<int, int> $histo Histogram mapping bucket index → pixel count.
     */
    public function __construct(
        public int            $r1,
        public int            $r2,
        public int            $g1,
        public int            $g2,
        public int            $b1,
        public int            $b2,
        public readonly array $histo,
    )
    {
    }

    public function volume(): int
    {
        return $this->cachedVolume ??= ($this->r2 - $this->r1 + 1)
            * ($this->g2 - $this->g1 + 1)
            * ($this->b2 - $this->b1 + 1);
    }

    /**
     * Total number of pixels in this VBox.
     */
    public function count(): int
    {
        if ($this->cachedCount !== null) {
            return $this->cachedCount;
        }

        $npix = 0;

        // Pick the fastest iteration strategy
        if ($this->volume() > count($this->histo)) {
            foreach ($this->histo as $idx => $count) {
                $bR = ($idx >> (2 * self::SIGBITS)) & 0x1F;
                $bG = ($idx >> self::SIGBITS) & 0x1F;
                $bB = $idx & 0x1F;
                if ($bR >= $this->r1 && $bR <= $this->r2
                    && $bG >= $this->g1 && $bG <= $this->g2
                    && $bB >= $this->b1 && $bB <= $this->b2) {
                    $npix += $count;
                }
            }
        } else {
            for ($r = $this->r1; $r <= $this->r2; $r++) {
                for ($g = $this->g1; $g <= $this->g2; $g++) {
                    for ($b = $this->b1; $b <= $this->b2; $b++) {
                        $idx = ($r << (2 * self::SIGBITS)) | ($g << self::SIGBITS) | $b;
                        $npix += $this->histo[$idx] ?? 0;
                    }
                }
            }
        }

        return $this->cachedCount = $npix;
    }

    /**
     * Weighted average colour as [R, G, B] in 0-255 space.
     *
     * @return int[]
     */
    public function avg(): array
    {
        if ($this->cachedAvg !== null) {
            return $this->cachedAvg;
        }

        $mult = 1 << (8 - self::SIGBITS);
        $ntot = 0;
        $rsum = $gsum = $bsum = 0.0;

        for ($r = $this->r1; $r <= $this->r2; $r++) {
            for ($g = $this->g1; $g <= $this->g2; $g++) {
                for ($b = $this->b1; $b <= $this->b2; $b++) {
                    $idx = ($r << (2 * self::SIGBITS)) | ($g << self::SIGBITS) | $b;
                    $hval = $this->histo[$idx] ?? 0;
                    $ntot += $hval;
                    $rsum += $hval * ($r + 0.5) * $mult;
                    $gsum += $hval * ($g + 0.5) * $mult;
                    $bsum += $hval * ($b + 0.5) * $mult;
                }
            }
        }

        if ($ntot === 0) {
            $ntot = 1;
        }

        return $this->cachedAvg = [
            min(255, (int)($rsum / $ntot)),
            min(255, (int)($gsum / $ntot)),
            min(255, (int)($bsum / $ntot)),
        ];
    }

    /**
     * Longest axis: 'r', 'g', or 'b'.
     *
     * @return 'r'|'g'|'b'
     */
    public function longestAxis(): string
    {
        $rRange = $this->r2 - $this->r1;
        $gRange = $this->g2 - $this->g1;
        $bRange = $this->b2 - $this->b1;

        if ($rRange >= $gRange && $rRange >= $bRange) {
            return 'r';
        }
        return $gRange >= $bRange ? 'g' : 'b';
    }

    public function copy(): self
    {
        return new self($this->r1, $this->r2, $this->g1, $this->g2, $this->b1, $this->b2, $this->histo);
    }

    /**
     * Invalidate caches after bounds change.
     */
    public function invalidate(): void
    {
        $this->cachedVolume = null;
        $this->cachedCount = null;
        $this->cachedAvg = null;
    }
}
