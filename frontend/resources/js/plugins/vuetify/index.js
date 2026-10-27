import { createVuetify } from 'vuetify'
import { VBtn } from 'vuetify/components/VBtn'
import defaults from './defaults'
import { icons } from './icons'
import { themes } from './theme'

// Styles
import '@core-scss/template/libs/vuetify/index.scss'
import 'vuetify/styles'
import 'remixicon/fonts/remixicon.css'

export default function (app) {
  const vuetify = createVuetify({
    aliases: {
      IconBtn: VBtn,
    },
    defaults,
    icons,
    theme: {
      defaultTheme: ['light', 'dark'].includes(localStorage.getItem('app-theme')) ? localStorage.getItem('app-theme') : 'light',
      themes,
    },
  })

  app.use(vuetify)
}
