<?php

namespace newism\imagecolors\jobs;

use Craft;
use craft\elements\Asset;
use craft\queue\BaseJob;
use newism\imagecolors\fields\ImageColorsField;
use newism\imagecolors\ImageColors;
use Throwable;

class ExtractColorsJob extends BaseJob
{
    public int $assetId;

    public function execute($queue): void
    {
        $asset = Asset::find()->id($this->assetId)->one();

        if (!$asset) {
            Craft::warning("ExtractColorsJob: Asset {$this->assetId} not found", ImageColors::LOG);
            return;
        }

        if (!ImageColors::getInstance()->getAssetHandler()->isExtractableImage($asset)) {
            Craft::warning("ExtractColorsJob: Asset {$this->assetId} is not an extractable image", ImageColors::LOG);
            return;
        }

        $handle = ImageColorsField::findHandle($asset);
        if (!$handle) {
            Craft::warning("ExtractColorsJob: No Image Colors field on asset {$this->assetId}'s field layout", ImageColors::LOG);
            return;
        }

        $tempPath = null;

        try {
            $tempPath = $asset->getCopyOfFile();
        } catch (Throwable $e) {
            Craft::error("ExtractColorsJob: Could not download file for asset {$this->assetId}: {$e->getMessage()}", ImageColors::LOG);
            return;
        }

        try {
            $service = ImageColors::getInstance()->getColorExtraction();
            $focalPoint = $asset->getFocalPoint();
            $regions = $service->extractAllRegions($tempPath, focalPoint: $focalPoint);

            if (empty($regions)) {
                Craft::warning("ExtractColorsJob: No colors extracted from asset {$this->assetId}", ImageColors::LOG);
                return;
            }

            // Store the focal point used for extraction so we can detect changes
            $regions['_focalPoint'] = $focalPoint;

            $asset->setFieldValue($handle, $regions);
            Craft::$app->getElements()->saveElement($asset);

            $regionNames = implode(', ', array_keys($regions));
            Craft::info("ExtractColorsJob: Extracted palettes ({$regionNames}) from asset {$this->assetId}", ImageColors::LOG);
        } catch (Throwable $e) {
            Craft::error("ExtractColorsJob: Failed for asset {$this->assetId}: {$e->getMessage()}", ImageColors::LOG);
        } finally {
            if ($tempPath && file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }

    protected function defaultDescription(): ?string
    {
        return Craft::t('image-colors', 'Extracting colors from asset {assetId}', [
            'assetId' => $this->assetId,
        ]);
    }
}
