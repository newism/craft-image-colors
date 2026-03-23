<?php

namespace newism\imagecolors\models;

use Illuminate\Support\Collection;
use newism\imagecolors\services\ColorExtractionService;

/**
 * A collection of Palette objects keyed by region name.
 *
 * Extends Illuminate\Support\Collection so all standard collection
 * methods (map, filter, each, first, get, keys, etc.) are available.
 *
 * @extends Collection<string, Palette>
 */
class PaletteCollection extends Collection
{
    public readonly ?array $focalPoint;

    public function __construct(array $palettes = [], ?array $focalPoint = null)
    {
        parent::__construct($palettes);
        $this->focalPoint = $focalPoint;
    }

    public static function fromArray(array $data): self
    {
        $palettes = [];
        foreach (ColorExtractionService::REGIONS as $name) {
            if (isset($data[$name]) && is_array($data[$name])) {
                $palettes[$name] = Palette::fromArray($data[$name]);
            }
        }

        return new self($palettes, $data['_focalPoint'] ?? null);
    }
}
