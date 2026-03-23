<?php

namespace newism\imagecolors\models;

use craft\fields\data\ColorData;
use Illuminate\Contracts\Support\Arrayable;

class Color implements Arrayable
{
    private ?ColorData $_colorData = null;

    public function __construct(
        public readonly string $hex,
        public readonly array  $rgb,
        public readonly float  $weight,
    )
    {
    }

    /**
     * Weight as a rounded percentage (e.g. 39).
     */
    public function getPercentage(): float
    {
        return round($this->weight * 100);
    }

    /**
     * Craft's ColorData for hsl, luma, and other colour utilities.
     */
    public function getColorData(): ColorData
    {
        return $this->_colorData ??= new ColorData($this->hex);
    }

    public function toArray(): array
    {
        return [
            'hex' => $this->hex,
            'rgb' => $this->rgb,
            'weight' => $this->weight,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            hex: $data['hex'],
            rgb: $data['rgb'],
            weight: $data['weight'],
        );
    }
}
