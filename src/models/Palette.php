<?php

namespace newism\imagecolors\models;

use Illuminate\Support\Collection;

/**
 * An ordered collection of Colour objects for a single image region.
 *
 * @extends Collection<int, Color>
 */
class Palette extends Collection
{
    public static function fromArray(array $data): self
    {
        return new self(array_map(fn(array $c) => Color::fromArray($c), $data));
    }
}
