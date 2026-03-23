<?php

namespace newism\imagecolors\gql\types;

use craft\gql\base\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use newism\imagecolors\models\Color;

/**
 * GraphQL type for a single colour palette entry.
 *
 * Source data is a Color value object.
 */
class ImageColorsPaletteEntryType extends ObjectType
{
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        if (!$source instanceof Color) {
            return null;
        }

        return match ($resolveInfo->fieldName) {
            'hex' => $source->hex,
            'rgb' => $source->rgb,
            'weight' => $source->weight,
            'percentage' => $source->getPercentage(),
            default => null,
        };
    }
}
