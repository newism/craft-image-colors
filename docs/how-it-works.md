---
outline: deep
---

# How It Works

## The Algorithm

The extraction uses a reimplementation of the MMCQ (Modified Median Cut Quantization) algorithm, originally ported to PHP by [color-thief-php](https://github.com/ksubileau/color-thief-php) and based on [Color Thief](https://lokeshdhakar.com/projects/color-thief/) by Lokesh Dhakar.

### Why reimplement?

The original library discards pixel count data during extraction — the information needed for accurate weights. To get weights, we'd need to scan every pixel twice per region. For large image libraries across 6 regions, that doubles the work.

Our implementation exposes VBox population counts directly, giving accurate weights for free.

### Step by step

1. **Histogram** — iterate pixels, quantise each to a 5-bit-per-channel bucket (32,768 possible buckets). Skip transparent and near-white pixels.
2. **Initial VBox** — create a single colour volume spanning the histogram bounds.
3. **First pass** — split the most populated VBox along its longest colour axis until reaching 75% of the target count.
4. **Second pass** — switch to population x volume sorting and continue splitting until the target count is reached.
5. **Output** — each VBox yields a colour (weighted average) and a weight (pixel count / total).

Colors below 1% weight are filtered out.

## Performance

- **1 pixel scan per region** — the histogram build is the only pixel-level pass
- **1 GD image load per asset** — shared across all 6 regions
- **No per-pixel object allocation** — `imagecolorat()` returns a packed int, decoded with bitwise ops
- **Weights are free** — derived from VBox population counts the algorithm already computes
- **GD only** — single C function call per pixel, faster than Imagick's object-per-pixel approach
