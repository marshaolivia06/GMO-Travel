import App from './App.vue'
import { registerPlugins } from './@core/utils/plugins'
import { createApp } from 'vue'

// Styles
import '@core-scss/template/index.scss'
import './styles/app.css'

// Create vue app
const app = createApp(App)

// Register plugins (pinia, vuetify, router, iconify, webfontloader, casl, layouts)
registerPlugins(app)

// Mount vue app
app.mount('#app')
