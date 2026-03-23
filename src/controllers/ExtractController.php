<?php

namespace newism\imagecolors\controllers;

use Craft;
use craft\elements\Asset;
use craft\web\Controller;
use newism\imagecolors\fields\ImageColorsField;
use newism\imagecolors\ImageColors;
use Throwable;
use yii\web\Response;

class ExtractController extends Controller
{
    /**
     * Extract colours from a single asset and save immediately.
     */
    public function actionExtractColors(): ?Response
    {
        $this->requirePostRequest();

        $elementId = $this->request->getRequiredBodyParam('elementId');
        $asset = Asset::find()->id($elementId)->one();

        if (!$asset) {
            return $this->asFailure('Asset not found.');
        }

        $assetHandler = ImageColors::getInstance()->getAssetHandler();

        if (!$assetHandler->isExtractableImage($asset)) {
            return $this->asFailure('Asset is not an extractable image.');
        }

        $handle = ImageColorsField::findHandle($asset);
        if (!$handle) {
            return $this->asFailure('No Image Colors field on this asset\'s field layout.');
        }

        $tempPath = null;

        try {
            $tempPath = $asset->getCopyOfFile();
            $service = ImageColors::getInstance()->getColorExtraction();
            $focalPoint = $asset->getFocalPoint();
            $regions = $service->extractAllRegions($tempPath, focalPoint: $focalPoint);

            if (empty($regions)) {
                return $this->asFailure('No colours could be extracted.');
            }

            $regions['_focalPoint'] = $focalPoint;

            $asset->setFieldValue($handle, $regions);
            Craft::$app->getElements()->saveElement($asset);

            Craft::info("Extracted colours for asset {$asset->id} via action menu", ImageColors::LOG);
            return $this->asModelSuccess(
                $asset,
                'Colours extracted.',
                redirect: $asset->getCpEditUrl(),
            );
        } catch (Throwable $e) {
            Craft::error("ExtractController: Failed for asset {$asset->id}: {$e->getMessage()}", ImageColors::LOG);
            return $this->asFailure('Colour extraction failed.');
        } finally {
            if ($tempPath && file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }

    /**
     * Clear colour data from a single asset.
     */
    public function actionClearColors(): ?Response
    {
        $this->requirePostRequest();

        $elementId = $this->request->getRequiredBodyParam('elementId');
        $asset = Asset::find()->id($elementId)->one();

        if (!$asset) {
            return $this->asFailure('Asset not found.');
        }

        $handle = ImageColorsField::findHandle($asset);
        if (!$handle) {
            return $this->asFailure('No Image Colors field on this asset\'s field layout.');
        }

        $asset->setFieldValue($handle, null);
        Craft::$app->getElements()->saveElement($asset);

        Craft::info("Cleared colours for asset {$asset->id} via action menu", ImageColors::LOG);
        return $this->asModelSuccess(
            $asset,
            'Colours cleared.',
            redirect: $asset->getCpEditUrl(),
        );
    }
}
