import { fileURLToPath } from 'node:url'
import vue from '@vitejs/plugin-vue'
import vueJsx from '@vitejs/plugin-vue-jsx'
import AutoImport from 'unplugin-auto-import/vite'
import Components from 'unplugin-vue-components/vite'
import { defineConfig } from 'vite'
import vuetify from 'vite-plugin-vuetify'
import svgLoader from 'vite-svg-loader'

export default defineConfig({
  plugins: [
    vue({
      template: {
        transformAssetUrls: { base: null, includeAbsolute: false },
      },
    }),
    vueJsx(),
    vuetify({
      styles: {
        configFile: 'resources/styles/variables/_vuetify.scss',
      },
    }),
    Components({
      dirs: ['resources/js/@core/components', 'resources/js/components'],
      dts: true,
    }),
    AutoImport({
      imports: ['vue', 'vue-router', '@vueuse/core', 'vue-i18n', 'pinia'],
      dirs: [
        './resources/js/@core/utils',
        './resources/js/@core/composable/',
        './resources/js/composables/',
        './resources/js/utils/',
        './resources/js/plugins/*/composables/*',
      ],
      vueTemplate: true,
      ignore: ['useCookies', 'useStorage'],
    }),
    svgLoader(),
  ],
  server: {
    host: true,
  },
  define: { 'process.env': {} },
  resolve: {
    alias: {
      '@core-scss': fileURLToPath(new URL('./resources/styles/@core', import.meta.url)),
      '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
      '@themeConfig': fileURLToPath(new URL('./themeConfig.js', import.meta.url)),
      '@core': fileURLToPath(new URL('./resources/js/@core', import.meta.url)),
      '@layouts': fileURLToPath(new URL('./resources/js/@layouts', import.meta.url)),
      '@images': fileURLToPath(new URL('./resources/images/', import.meta.url)),
      '@styles': fileURLToPath(new URL('./resources/styles/', import.meta.url)),
      '@configured-variables': fileURLToPath(new URL('./resources/styles/variables/_template.scss', import.meta.url)),
    },
  },
  build: {
    chunkSizeWarningLimit: 5000,
    outDir: 'dist',
  },
  optimizeDeps: {
    exclude: ['vuetify'],
    entries: ['./resources/js/**/*.vue'],
  },
})
