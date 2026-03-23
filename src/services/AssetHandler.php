<?php

namespace newism\imagecolors\services;

use Craft;
use craft\base\Component;
use craft\elements\Asset;
use newism\imagecolors\fields\ImageColorsField;
use newism\imagecolors\ImageColors;
use newism\imagecolors\jobs\ExtractColorsJob;
use newism\imagecolors\models\PaletteCollection;

class AssetHandler extends Component
{
    /**
     * Assets awaiting file operations, keyed by asset ID or object ID.
     */
    private array $pendingAssets = [];

    /**
     * Track an asset that is about to receive a new file.
     */
    public function trackAsset(Asset $asset): void
    {
        $key = $asset->id ?? spl_object_id($asset);
        $this->pendingAssets[$key] = true;
    }

    /**
     * Handle an asset save — queue extraction if the file changed or focal point moved.
     */
    public function handleAssetSave(Asset $asset): void
    {
        if (!$this->isExtractableImage($asset)) {
            return;
        }

        $idKey = $asset->id;
        $objKey = spl_object_id($asset);
        $hasNewFile = isset($this->pendingAssets[$idKey]) || isset($this->pendingAssets[$objKey]);

        unset($this->pendingAssets[$idKey], $this->pendingAssets[$objKey]);

        if ($hasNewFile || $this->hasFocalPointChanged($asset)) {
            // Clear stale colour data when the file has been replaced
            $handle = ImageColorsField::findHandle($asset);
            if ($hasNewFile && $handle) {
                $asset->setFieldValue($handle, null);
                Craft::$app->getElements()->saveElement($asset);
            }

            Craft::info("Queuing color extraction for asset {$asset->id}", ImageColors::LOG);

            Craft::$app->getQueue()->push(new ExtractColorsJob([
                'assetId' => $asset->id,
            ]));
        }
    }

    /**
     * Check whether the asset's focal point differs from the one stored in the Image Colors field.
     */
    private function hasFocalPointChanged(Asset $asset): bool
    {
        $handle = ImageColorsField::findHandle($asset);
        if (!$handle) {
            return false;
        }

        $colorData = $asset->getFieldValue($handle);

        if (!$colorData instanceof PaletteCollection) {
            return false;
        }

        $storedFocalPoint = $colorData->focalPoint;
        $currentFocalPoint = $asset->getFocalPoint();

        // No stored focal point means data predates this feature — don't re-extract on every save
        if ($storedFocalPoint === null) {
            return false;
        }

        return $storedFocalPoint != $currentFocalPoint;
    }

    /**
     * Check whether an asset is an extractable raster image.
     *
     * Returns true for KIND_IMAGE assets that are not SVGs.
     */
    public function isExtractableImage(Asset $asset): bool
    {
        if ($asset->kind !== Asset::KIND_IMAGE) {
            return false;
        }

        if (strtolower($asset->getExtension()) === 'svg') {
            return false;
        }

        return true;
    }
}
