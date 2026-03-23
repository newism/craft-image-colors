<?php

namespace newism\imagecolors\console\controllers;

use Craft;
use craft\console\Controller;
use craft\elements\Asset;
use craft\helpers\Console;
use craft\helpers\Json;
use newism\imagecolors\ImageColors;
use newism\imagecolors\jobs\ExtractColorsJob;
use yii\console\ExitCode;

/**
 * ImageColors console commands.
 *
 * Usage:
 *   craft image-colors/extract                            Queue all volumes
 *   craft image-colors/extract --volume=images            Specific volume
 *   craft image-colors/extract --dry-run                  Show what would be queued
 *   craft image-colors/extract --output-format=json       JSON output
 */
class ExtractController extends Controller
{
    public ?string $volume = null;
    public bool $dryRun = false;
    public string $outputFormat = 'table';

    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), [
            'volume',
            'dryRun',
            'outputFormat',
        ]);
    }

    /**
     * Queue color extraction jobs for image assets (excluding SVGs).
     */
    public function actionIndex(): int
    {
        $query = Asset::find()->kind(Asset::KIND_IMAGE);

        if ($this->volume) {
            $query->volume($this->volume);
        }

        $assets = $query->all();
        $queued = 0;
        $skipped = 0;
        $items = [];

        foreach ($assets as $asset) {
            if (!ImageColors::getInstance()->getAssetHandler()->isExtractableImage($asset)) {
                $skipped++;
                continue;
            }

            if (!$this->dryRun) {
                Craft::$app->getQueue()->push(new ExtractColorsJob([
                    'assetId' => $asset->id,
                ]));
            }

            $queued++;
            $items[] = [
                'id' => $asset->id,
                'filename' => $asset->filename,
                'volume' => $asset->getVolume()->handle,
            ];
        }

        $labels = $this->dryRun
            ? ['queued' => 'Would queue', 'skipped' => 'Would skip (not extractable)']
            : ['queued' => 'Queued', 'skipped' => 'Skipped (not extractable)'];

        return $this->outputResult(
            ['dryRun' => $this->dryRun, 'summary' => ['queued' => $queued, 'skipped' => $skipped], 'items' => $items],
            ['ID', 'Filename', 'Volume'],
            fn($item) => [$item['id'], $item['filename'], $item['volume']],
            $labels,
        );
    }

    // ── Output Helper ─────────────────────────────────────────────────

    private function outputResult(array $result, array $tableHeaders, callable $rowMapper, array $labels): int
    {
        if ($this->outputFormat === 'json') {
            $this->stdout(Json::encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
            return ExitCode::OK;
        }

        if (!empty($result['items'])) {
            $this->stdout(PHP_EOL);
            $this->table($tableHeaders, array_map($rowMapper, $result['items']));
        }

        $this->stdout(PHP_EOL);
        $summaryParts = [];
        foreach ($result['summary'] as $key => $count) {
            $label = $labels[$key] ?? $key;
            $this->stdout("  {$label}: {$count}\n", Console::FG_GREEN);
            $summaryParts[] = "{$label}: {$count}";
        }
        $this->stdout(PHP_EOL);

        Craft::info(
            ($result['dryRun'] ? '[DRY RUN] ' : '') . implode(', ', $summaryParts),
            ImageColors::LOG,
        );

        return ExitCode::OK;
    }
}
