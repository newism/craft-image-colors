<?php

namespace newism\imagecolors;

use Craft;
use craft\base\Element;
use craft\base\Plugin;
use craft\elements\Asset;
use craft\enums\MenuItemType;
use craft\events\AssetEvent;
use craft\events\DefineMenuItemsEvent;
use craft\events\ModelEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterElementActionsEvent;
use craft\log\MonologTarget;
use craft\services\Fields;
use Monolog\Formatter\LineFormatter;
use newism\imagecolors\actions\ExtractColorsAction;
use newism\imagecolors\fields\ImageColorsField;
use newism\imagecolors\services\AssetHandler;
use newism\imagecolors\services\ColorExtractionService;
use Psr\Log\LogLevel;
use yii\base\Event;

/**
 * ImageColors plugin
 *
 * Extracts a palette of dominant colors from uploaded images
 * and stores them as JSON in an Image Colors field.
 *
 * @method static ImageColors getInstance()
 * @property-read AssetHandler $assetHandler
 * @property-read ColorExtractionService $colorExtraction
 */
class ImageColors extends Plugin
{
    public const LOG = 'image-colors';

    public function getAssetHandler(): AssetHandler
    {
        return $this->get('assetHandler');
    }

    public function getColorExtraction(): ColorExtractionService
    {
        return $this->get('colorExtraction');
    }

    public static function config(): array
    {
        return [
            'components' => [
                'assetHandler' => AssetHandler::class,
                'colorExtraction' => ColorExtractionService::class,
            ],
        ];
    }

    public function init(): void
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->controllerNamespace = 'newism\\imagecolors\\console\\controllers';
        }

        $this->registerLogTarget();
        $this->attachEventHandlers();

        parent::init();
    }

    private function registerLogTarget(): void
    {
        $formatter = new LineFormatter(
            format: "%datetime% [%level_name%] %message%\n",
            dateFormat: 'Y-m-d H:i:s',
        );

        Craft::getLogger()->dispatcher->targets[] = new MonologTarget([
            'name' => self::LOG,
            'categories' => [self::LOG],
            'level' => LogLevel::INFO,
            'logContext' => false,
            'allowLineBreaks' => false,
            'maxFiles' => 14,
            'formatter' => $formatter,
        ]);
    }

    private function attachEventHandlers(): void
    {
        // Register the Image Colors field type
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = ImageColorsField::class;
            }
        );

        // Track assets receiving new files (upload or replace)
        Event::on(
            Asset::class,
            Asset::EVENT_BEFORE_HANDLE_FILE,
            function (AssetEvent $event) {
                $this->getAssetHandler()->trackAsset($event->asset);
            }
        );

        // After save, extract colours if the file changed or focal point moved
        Event::on(
            Asset::class,
            Element::EVENT_AFTER_SAVE,
            function (ModelEvent $event) {
                $this->getAssetHandler()->handleAssetSave($event->sender);
            }
        );

        // Register the Extract Colours bulk action
        Event::on(
            Asset::class,
            Asset::EVENT_REGISTER_ACTIONS,
            function (RegisterElementActionsEvent $event) {
                $event->actions[] = ExtractColorsAction::class;
            }
        );

        // Register the Extract Colours action in the asset edit `...` menu
        Event::on(
            Asset::class,
            Element::EVENT_DEFINE_ACTION_MENU_ITEMS,
            function (DefineMenuItemsEvent $event) {
                /** @var Asset $asset */
                $asset = $event->sender;

                if (!$this->getAssetHandler()->isExtractableImage($asset)) {
                    return;
                }

                $handle = ImageColorsField::findHandle($asset);
                if (!$handle) {
                    return;
                }

                $extractId = sprintf('action-extract-colours-%s', mt_rand());

                $event->items[] = ['type' => MenuItemType::HR];
                $event->items[] = [
                    'type' => MenuItemType::Button,
                    'id' => $extractId,
                    'icon' => 'palette',
                    'label' => Craft::t('image-colors', 'Extract Colours'),
                    'action' => 'image-colors/extract/extract-colors',
                    'params' => [
                        'elementId' => $asset->id,
                    ],
                ];

                $clearId = sprintf('action-clear-colours-%s', mt_rand());
                $event->items[] = [
                    'type' => MenuItemType::Button,
                    'id' => $clearId,
                    'icon' => 'xmark',
                    'label' => Craft::t('image-colors', 'Clear Colours'),
                    'destructive' => true,
                    'action' => 'image-colors/extract/clear-colors',
                    'params' => [
                        'elementId' => $asset->id,
                    ],
                ];
            });
    }
}
