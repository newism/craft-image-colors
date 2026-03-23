---
title: Image Colors
description: Extract beautiful, weighted color palettes from your Craft CMS images.
layout: home
hero:
    name: Image Colors
    text: For Craft CMS
    icon: /logo.svg
    tagline: Extract beautiful color palettes from your images. Know exactly how dominant each color is. Build dynamic, image-aware designs.
    image:
        src: /screenshots/hero.png
        alt: Image Colors field showing weighted color palettes for each region on an asset edit page
    actions:
        - text: Get Started
          link: ./features
        - text: View on Plugin Store
          link: https://plugins.craftcms.com/image-colors
          theme: alt
features:
    - title: Weighted Color Palettes
      details: Every color includes its pixel proportion, so you know exactly how dominant it is. Up to 4 colors per region, sorted by weight — no guessing.
      link: ./features#weighted-colors
      icon: 🎨
    - title: 6 Extraction Regions
      details: Overall, focal point, top, right, bottom, and left. Each region gets its own palette, giving you precise control over which part of the image drives your design.
      link: ./features#color-extraction
      icon: 🔲
    - title: Focal Point Aware
      details: The focal region follows the asset's user-defined focal point. Move the focal point, and the palette updates automatically.
      link: ./features#focal-point-awareness
      icon: 🎯
    - title: Drop-in Field Type
      details: The Image Colors field renders proportional color bars with click-to-copy hex values. Add it to any asset field layout — no configuration required.
      link: ./setup
      icon: ✨
    - title: Automatic Extraction
      details: Colors are extracted automatically on upload, file replace, and focal point change. No manual steps needed — your palettes are always fresh.
      link: ./setup#automatic-extraction
      icon: ⚡
    - title: Full Twig API
      details: Access palettes, regions, hex values, RGB arrays, weights, and Craft's ColorData utilities directly in your templates. Build image-aware designs with ease.
      link: ./templating
      icon: 🛠️
    - title: GraphQL Support
      details: Query color palettes, individual colors, weights, and focal points through Craft's native GraphQL API.
      link: ./graphql
      icon: 📡
    - title: Asset Index Swatches
      details: Color palettes appear in asset index table and card views. See your image colors at a glance without opening individual assets.
      link: ./features#card-and-table-previews
      icon: 👁️
    - title: Bulk Extraction
      details: Extract colors for your entire library via console command with optional volume filtering. Queue-based for reliable processing at scale.
      link: ./console-commands
      icon: 🚀
---
