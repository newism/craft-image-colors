---
outline: deep
---

# Console Commands

## Extract Colors

Extract color palettes for all image assets:

```sh
craft image-colors/extract
```

Limit to a specific volume:

```sh
craft image-colors/extract --volume=images
```

Each asset is processed as a queue job for reliable extraction at scale.
