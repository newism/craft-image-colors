<?php

namespace newism\imagecolors\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class ImageColorsFieldAsset extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = __DIR__;

        $this->depends = [
            CpAsset::class,
        ];

        $this->css = [
            'image-colors-field.css',
        ];

        $this->js = [
            'image-colors-field.js',
        ];

        parent::init();
    }
}
