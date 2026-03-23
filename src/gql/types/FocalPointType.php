<?php

namespace newism\imagecolors\gql\types;

use craft\gql\base\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * GraphQL type for a focal point coordinate pair.
 *
 * Source data is an associative array: ['x' => 0.5, 'y' => 0.5]
 */
class FocalPointType extends ObjectType
{
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        return $source[$resolveInfo->fieldName] ?? null;
    }
}
