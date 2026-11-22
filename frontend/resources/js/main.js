import App from './App.vue'
import { registerPlugins } from './@core/utils/plugins'
import { createApp } from 'vue'
import '@core-scss/template/index.scss'
import './styles/app.css'

const app = createApp(App)
registerPlugins(app)
app.mount('#app')
