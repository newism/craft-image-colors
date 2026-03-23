import { h } from 'vue'
import type { Theme } from 'vitepress'
import DefaultTheme from 'vitepress/theme'
import '@newism/vitepress-shared/styles/custom.css'
import PluginHero from '@newism/vitepress-shared/components/PluginHero.vue'

export default {
  extends: DefaultTheme,
  Layout: () => {
    return h(DefaultTheme.Layout, null, {
      'home-hero-info-before': () => h(PluginHero),
    })
  },
  enhanceApp({ app }) {},
} satisfies Theme
