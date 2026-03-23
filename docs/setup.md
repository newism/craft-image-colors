---
outline: deep
---

# Setup

Getting Image Colors up and running takes three steps.

## 1. Add the Field

In the Craft control panel, navigate to **Settings > Fields** and create a new field with the type **Image Colors**. You can use any handle you like (e.g. `extractedColors`).

Add the field to your asset volume's field layout. The plugin automatically finds the first Image Colors field on an asset's field layout, so no specific handle is required.

## 2. Extract Colors from Existing Images

Run the console command to extract colors for all existing images in your library:

```sh
craft image-colors/extract
```

You can also limit extraction to a specific volume:

```sh
craft image-colors/extract --volume=images
```

Each asset is processed as a queue job for reliable extraction at scale. See [Console Commands](./console-commands) for more details.

## 3. Done!

From this point on, colors are extracted automatically whenever:

- An image is **uploaded**
- An image is **replaced** (stale data is cleared immediately before re-extraction)
- The **focal point** is changed

No manual steps needed — your palettes are always fresh.

## Manual Actions

The action menu on individual asset edit pages provides two options:

- **Extract Colors** — extracts color data synchronously and reloads the page so you can see results instantly.
- **Clear Colors** — removes all color data from the asset.

**Extract Colors** is also available as a bulk action in the element index toolbar, making it easy to batch-process an entire volume. Bulk extractions are queued via jobs so they won't block the control panel while processing.
