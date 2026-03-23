<?php

namespace newism\imagecolors\actions;

use Craft;
use craft\base\ElementAction;
use craft\elements\Asset;
use craft\elements\db\ElementQueryInterface;
use newism\imagecolors\ImageColors;
use newism\imagecolors\jobs\ExtractColorsJob;

class ExtractColorsAction extends ElementAction
{
    public static function displayName(): string
    {
        return Craft::t('image-colors', 'Extract Colours');
    }

    public function getTriggerLabel(): string
    {
        return static::displayName();
    }

    public function performAction(ElementQueryInterface $query): bool
    {
        $assetHandler = ImageColors::getInstance()->getAssetHandler();
        $queued = 0;

        /** @var Asset $asset */
        foreach ($query->all() as $asset) {
            if (!$assetHandler->isExtractableImage($asset)) {
                continue;
            }

            Craft::$app->getQueue()->push(new ExtractColorsJob([
                'assetId' => $asset->id,
            ]));

            $queued++;
        }

        $this->setMessage(
            Craft::t('image-colors', 'Colour extraction queued for {count} asset(s).', ['count' => $queued])
        );

        return true;
    }
}
