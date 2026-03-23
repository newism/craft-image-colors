<?php

namespace newism\imagecolors\gql\types\generators;

use Craft;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\ObjectType;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\Type;
use newism\imagecolors\gql\types\FocalPointType;
use newism\imagecolors\gql\types\ImageColorsGqlType;
use newism\imagecolors\gql\types\ImageColorsPaletteEntryType;
use newism\imagecolors\services\ColorExtractionService;

class ImageColorsTypeGenerator implements GeneratorInterface, SingleGeneratorInterface
{
    public static function generateTypes(mixed $context = null): array
    {
        return [static::generateType($context)];
    }

    public static function getName(): string
    {
        return 'ImageColors';
    }

    public static function generateType(mixed $context): ObjectType
    {
        $paletteEntryType = self::generatePaletteEntryType();
        $focalPointType = self::generateFocalPointType();

        $typeName = self::getName();

        return GqlEntityRegistry::getOrCreate($typeName, fn() => new ImageColorsGqlType([
            'name' => $typeName,
            'fields' => function () use ($typeName, $paletteEntryType, $focalPointType) {
                $fields = [];

                // Each region is a list of palette entries
                foreach (ColorExtractionService::REGIONS as $region) {
                    $fields[$region] = Type::listOf($paletteEntryType);
                }

                $fields['focalPoint'] = $focalPointType;

                return Craft::$app->getGql()->prepareFieldDefinitions($fields, $typeName);
            },
        ]));
    }

    private static function generatePaletteEntryType(): ObjectType
    {
        $typeName = 'ImageColorsPaletteEntry';

        return GqlEntityRegistry::getOrCreate($typeName, fn() => new ImageColorsPaletteEntryType([
            'name' => $typeName,
            'fields' => function () use ($typeName) {
                $fields = [
                    'hex' => Type::string(),
                    'rgb' => Type::listOf(Type::int()),
                    'weight' => Type::float(),
                    'percentage' => Type::float(),
                ];

                return Craft::$app->getGql()->prepareFieldDefinitions($fields, $typeName);
            },
        ]));
    }

    private static function generateFocalPointType(): ObjectType
    {
        $typeName = 'ImageColorsFocalPoint';

        return GqlEntityRegistry::getOrCreate($typeName, fn() => new FocalPointType([
            'name' => $typeName,
            'fields' => function () use ($typeName) {
                $fields = [
                    'x' => Type::float(),
                    'y' => Type::float(),
                ];

                return Craft::$app->getGql()->prepareFieldDefinitions($fields, $typeName);
            },
        ]));
    }
}
