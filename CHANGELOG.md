# Release Notes for Image Colors

## 1.0.0 - 2026-04-08

### Added

- Image Colors field type with proportional color bars and click-to-copy hex values
- Automatic color extraction on image upload, file replace, and focal point change
- 6 extraction regions: overall, focal, top, right, bottom, left
- Up to 4 weighted colors per region, sorted by pixel dominance
- Extract Colors and Clear Colors asset action menu items
- Bulk Extract Colors element action for batch processing
- GraphQL support with `ImageColors`, `ImageColorsPaletteEntry`, and `ImageColorsFocalPoint` types
- Console command `craft image-colors/extract` with `--volume` filter
- `PaletteCollection`, `Palette`, and `Color` value objects with Collection API
- `Color.colorData` property providing Craft's `ColorData` for HSL, luma, and RGB utilities
- Color swatches in asset index table and card views
- Dynamic field handle lookup — any field handle works
- MMCQ color extraction algorithm reimplemented for weighted output
- Dedicated log target at `storage/logs/image-colors-*.log`
