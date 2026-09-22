import 'remixicon/fonts/remixicon.css'
import { h } from 'vue'
import { createVuetify } from 'vuetify'

const appTheme = {
  dark: false,
  colors: {
    primary: '#1E4F8A',
    'primary-darken-1': '#173F6E',
    secondary: '#124A80',
    success: '#16A34A',
    error: '#DC2626',
    warning: '#F59E0B',
    info: '#0EA5E9',
    background: '#F8FBFF',
    surface: '#FFFFFF',
    'on-primary': '#FFFFFF',
    'on-secondary': '#FFFFFF',
  },
}

export default createVuetify({
  theme: {
    defaultTheme: 'light',
    themes: { light: appTheme },
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
    // Buttons & interactive
    VBtn: { rounded: 'md', elevation: 0, style: 'text-transform: none; font-weight: 500;' },
    VChip: { rounded: 'pill' },
    VTooltip: { location: 'top' },

    // Forms
    VTextField: { variant: 'outlined', density: 'comfortable', rounded: 'lg', color: 'primary' },
    VSelect: { variant: 'outlined', density: 'comfortable', rounded: 'lg', color: 'primary' },
    VAutocomplete: { variant: 'outlined', density: 'comfortable', rounded: 'lg', color: 'primary' },
    VTextarea: { variant: 'outlined', density: 'comfortable', rounded: 'lg', color: 'primary' },
    VCheckbox: { color: 'primary', density: 'comfortable' },
    VRadioGroup: { color: 'primary' },
    VSwitch: { color: 'primary', density: 'comfortable' },

    // Surfaces
    VCard: { rounded: 'lg', elevation: 1 },
    VDialog: { rounded: 'lg' },
    VSheet: { rounded: 'lg' },

    // Layout — ini yang paling sering kelupaan dan bikin beda dari Tailwind
    VAppBar: { elevation: 0, class: 'border-b' },
    VNavigationDrawer: { elevation: 0, class: 'border-e' },
    VFooter: { class: 'border-t' },

    // Data display
    VTable: { density: 'comfortable' },
    VDataTable: { density: 'comfortable' },
    VList: { rounded: 'lg' },
    VListItem: { rounded: 'lg' },

    // Feedback
    VAlert: { rounded: 'lg', variant: 'tonal' },
    VSnackbar: { rounded: 'lg' },
    VProgressCircular: { color: 'primary' },
    VProgressLinear: { color: 'primary', rounded: true },
  },
})