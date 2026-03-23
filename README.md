<p align="center"><img src="src/icon.svg" width="100" height="100" alt="Image Colors icon"></p>
<h1 align="center">Image Colors for Craft CMS</h1>

Extract beautiful color palettes from your images.  
Know exactly how dominant each color is.  
Build dynamic, image-aware designs.  

This plugin extracts weighted color palettes from uploaded images and makes them available in templates, GraphQL, and the admin UI. Each palette is **weighted by pixel proportion**, so you know how dominant each color is within the sampled region.

![Color data field on asset edit page](docs/screenshots/asset-edit-color-data.png)

![Asset index card view with color swatches](docs/screenshots/asset-index-cards.png)

## Features

- **6 extraction regions** — overall, focal point, top, right, bottom, left
- **Weighted colors** — up to 4 colors per region, sorted by pixel dominance
- **Focal point aware** — the focal region follows the asset's focal point
- **Automatic extraction** — on upload, replace, and focal point change
- **Drop-in field type** — proportional color bars with click-to-copy hex values
- **Twig API** — access palettes, hex values, RGB, weights, and Craft's ColorData utilities
- **GraphQL support** — query all regions, colors, weights, and focal points
- **Asset index swatches** — color palettes in table and card views
- **Console command** — bulk extract with optional volume filtering
- **Free to use**

## Requirements

- Craft CMS 5.6+
- PHP 8.2+
- GD extension

## Installation

```bash
composer require newism/craft-image-colors
php craft plugin/install image-colors
```

## Documentation

Full documentation is available at https://plugins.newism.com.au/image-colors.

## Support

For support, please [open an issue on GitHub](https://github.com/newism/craft-image-colors/issues).
