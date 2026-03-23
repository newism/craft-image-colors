---
outline: deep
---

# GraphQL

## Overview

The Image Colors field exposes a rich `ImageColors` GraphQL type, giving you full query support for all regions and color data -- perfect for building dynamic, color-aware front ends!

## Query Example

```graphql
{
  assets {
    colorData {
      overall { hex rgb weight percentage }
      focal { hex rgb weight percentage }
      top { hex rgb weight percentage }
      right { hex rgb weight percentage }
      bottom { hex rgb weight percentage }
      left { hex rgb weight percentage }
      focalPoint { x y }
    }
  }
}
```

Each region returns its own palette, so you can target exactly the part of the image that matters most to your design.

## Types

### ImageColors

Top-level type returned by the field.

| Field | Type | Description |
|---|---|---|
| `overall` | `[ImageColorsPaletteEntry]` | Colors for the full image |
| `focal` | `[ImageColorsPaletteEntry]` | Colors from the focal region |
| `top` | `[ImageColorsPaletteEntry]` | Colors from the top strip |
| `right` | `[ImageColorsPaletteEntry]` | Colors from the right strip |
| `bottom` | `[ImageColorsPaletteEntry]` | Colors from the bottom strip |
| `left` | `[ImageColorsPaletteEntry]` | Colors from the left strip |
| `focalPoint` | `ImageColorsFocalPoint` | The asset's focal point coordinates |

### ImageColorsPaletteEntry

| Field | Type | Description |
|---|---|---|
| `hex` | `String` | Hex color value (e.g. `#C7BA21`) |
| `rgb` | `[Int]` | RGB array `[r, g, b]` |
| `weight` | `Float` | Proportion of pixels in the region (0--1) |
| `percentage` | `Float` | Weight as a rounded percentage |

### ImageColorsFocalPoint

| Field | Type | Description |
|---|---|---|
| `x` | `Float` | Horizontal focal point (0--1) |
| `y` | `Float` | Vertical focal point (0--1) |
