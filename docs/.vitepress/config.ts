import { defineConfig } from 'vitepress'
import { configPlugin } from '../../../docs/plugins/configPlugin'
import { consoleCommandPlugin } from '../../../docs/plugins/consoleCommandPlugin'

export default defineConfig({
  base: '/image-colors/',
  srcDir: '.',
  title: 'Image Colors',
  description: 'Extract beautiful, weighted color palettes from your Craft CMS images.',
  ignoreDeadLinks: true,

  srcExclude: [
    'node_modules/**',
    'plans/**',
  ],

  markdown: {
    config(md) {
      md.use(configPlugin)
      md.use(consoleCommandPlugin)
    },
  },

  themeConfig: {
    logo: '/logo.svg',

    nav: [
      { text: 'View on Plugin Store', link: 'https://plugins.craftcms.com/image-colors' },
      { text: 'All Plugins', link: 'https://plugins.newism.com.au/', target: '_self' },
    ],

    sidebar: [
      {
        text: 'Getting Started',
        items: [
          { text: 'Features', link: '/features' },
          { text: 'Installation', link: '/installation' },
          { text: 'Setup', link: '/setup' },
        ],
      },
      {
        text: 'Template Guides',
        items: [
          { text: 'Templating', link: '/templating' },
        ],
      },
      {
        text: 'Developers',
        items: [
          { text: 'GraphQL', link: '/graphql' },
          { text: 'Console Commands', link: '/console-commands' },
          { text: 'Logging', link: '/logging' },
          { text: 'How It Works', link: '/how-it-works' },
        ],
      },
      { text: 'Support', link: '/support' },
    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com/newism' },
      { icon: 'linkedin', link: 'https://www.linkedin.com/company/newism' },
    ],
  },
})
