import App from './App.vue'
import { registerPlugins } from './@core/utils/plugins'
import { createApp } from 'vue'
import { refreshCsrfCookie } from './plugins/axios'

// Styles
import '@core-scss/template/index.scss'
import './styles/app.css'

// Ambil CSRF cookie saat app dimuat agar semua request mutasi selalu punya token
refreshCsrfCookie().catch(() => {})

// Create vue app
const app = createApp(App)

// Register plugins (pinia, vuetify, router, iconify, webfontloader, casl, layouts)
registerPlugins(app)

// Mount vue app
app.mount('#app')
