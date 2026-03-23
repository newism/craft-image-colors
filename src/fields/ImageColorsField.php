<?php

namespace newism\imagecolors\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\elements\Asset;
use craft\helpers\Html;
use craft\helpers\Json;
use GraphQL\Type\Definition\Type;
use newism\imagecolors\assets\ImageColorsFieldAsset;
use newism\imagecolors\gql\types\generators\ImageColorsTypeGenerator;
use newism\imagecolors\models\Color;
use newism\imagecolors\models\Palette;
use newism\imagecolors\models\PaletteCollection;
use yii\db\Schema;

/**
 * @property-read Type|array $contentGqlType
 */
class ImageColorsField extends Field implements PreviewableFieldInterface
{
    /**
     * Find the handle of the first ImageColorsField on an asset's field layout.
     * Returns null if no ImageColorsField is present.
     */
    public static function findHandle(Asset $asset): ?string
    {
        $fieldLayout = $asset->getFieldLayout();
        if (!$fieldLayout) {
            return null;
        }

        foreach ($fieldLayout->getCustomFields() as $field) {
            if ($field instanceof self) {
                return $field->handle;
            }
        }

        return null;
    }

    public static function displayName(): string
    {
        return Craft::t('image-colors', 'Image Colors');
    }

    public static function icon(): string
    {
        return 'palette';
    }

    public static function phpType(): string
    {
        return PaletteCollection::class . '|null';
    }

    public static function dbType(): array|string|null
    {
        return Schema::TYPE_JSON;
    }

    public function getContentGqlType(): Type|array
    {
        return ImageColorsTypeGenerator::generateType($this);
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value instanceof PaletteCollection) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $value = Json::decodeIfJson($value);
        }

        if (!is_array($value)) {
            return null;
        }

        return PaletteCollection::fromArray($value);
    }

    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value instanceof PaletteCollection) {
            if ($value->isEmpty()) {
                return null;
            }

            $data = $value->map(fn(Palette $p) => $p->toArray())->all();

            if ($value->focalPoint !== null) {
                $data['_focalPoint'] = $value->focalPoint;
            }

            return $data;
        }

        if (empty($value)) {
            return null;
        }

        return $value;
    }

    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(ImageColorsFieldAsset::class);

        return $view->renderTemplate(
            'image-colors/fields/image-colors/input',
            [
                'field' => $this,
                'value' => $value,
                'element' => $element,
            ],
        );
    }

    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        Craft::$app->getView()->registerAssetBundle(ImageColorsFieldAsset::class);

        if (!$value instanceof PaletteCollection) {
            return '';
        }

        $overall = $value->get('overall');
        if ($overall === null || count($overall) === 0) {
            return '';
        }

        return $this->renderSwatchBar($overall);
    }

    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        Craft::$app->getView()->registerAssetBundle(ImageColorsFieldAsset::class);

        $placeholder = new Palette([
            new Color('#FFFFFF', [255, 255, 255], 0.25),
            new Color('#FFFFFF', [255, 255, 255], 0.25),
            new Color('#FFFFFF', [255, 255, 255], 0.25),
            new Color('#FFFFFF', [255, 255, 255], 0.25),
        ]);

        return $this->renderSwatchBar($placeholder);
    }

    private function renderSwatchBar(Palette $palette): string
    {
        $segments = '';
        foreach ($palette as $color) {
            $hex = Html::encode($color->hex);
            $flex = (int)round($color->weight * 1000);
            $segments .= Html::tag('span', '', [
                'class' => 'image-colors-swatch',
                'style' => "flex:$flex;background:$hex;",
                'title' => $hex,
            ]);
        }

        return Html::tag('div', $segments, [
            'class' => 'image-colors-bar image-colors-bar--preview',
        ]);
    }
}
