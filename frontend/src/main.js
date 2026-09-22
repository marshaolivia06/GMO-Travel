import 'vuetify/styles'
import './style.css'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import setupAxios from './plugins/axios'
import vuetify from './plugins/vuetify'

const app = createApp(App)

setupAxios()
app.use(createPinia())
app.use(vuetify)
app.use(router)

app.mount('#app')