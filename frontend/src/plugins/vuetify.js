import 'remixicon/fonts/remixicon.css'
import { h } from 'vue'
import { createVuetify } from 'vuetify'

const andTheme = {
  dark: false,
  colors: {
    primary: '#1E4F8A',
    secondary: '#124A80',
    success: '#16A34A',
    error: '#DC2626',
    warning: '#F59E0B',
    info: '#0EA5E9',
    background: '#F8FBFF',
  },
}

export default createVuetify({
  theme: {
    defaultTheme: 'light',
    themes: { light: andTheme },
  },
  icons: {
    defaultSet: 'ri',
    aliases: {},
    sets: {
      ri: {
        component: props => h(props.tag ?? 'i', { class: [props.icon] }),
      },
    },
  },
  defaults: {
    VBtn: { rounded: 'md', elevation: 0 },
    VCard: { rounded: 'lg', elevation: 1 },
    VTextField: { variant: 'outlined', density: 'comfortable', rounded: 'lg' },
    VSelect: { variant: 'outlined', density: 'comfortable', rounded: 'lg' },
    VAutocomplete: { variant: 'outlined', density: 'comfortable', rounded: 'lg' },
    VChip: { rounded: 'pill' },
  },
})
