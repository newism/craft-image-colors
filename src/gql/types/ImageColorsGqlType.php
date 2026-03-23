<?php

namespace newism\imagecolors\gql\types;

use craft\gql\base\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use newism\imagecolors\models\PaletteCollection;

/**
 * GraphQL type for the full color data field value.
 *
 * Source data is a PaletteCollection with region palettes and focal point metadata.
 */
class ImageColorsGqlType extends ObjectType
{
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        if (!$source instanceof PaletteCollection) {
            return null;
        }

        $fieldName = $resolveInfo->fieldName;

        if ($fieldName === 'focalPoint') {
            return $source->focalPoint;
        }

        return $source->get($fieldName)?->all();
    }
}
