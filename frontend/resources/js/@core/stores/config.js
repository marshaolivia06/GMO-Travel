import { useTheme } from 'vuetify'
import { cookieRef } from '@layouts/stores/config'
import { themeConfig } from '@themeConfig'

// SECTION Store
export const useConfigStore = defineStore('config', () => {
  const theme = cookieRef('theme', themeConfig.app.theme)

  // ℹ️ Required by @core/initCore.js's _handleSkinChanges — keep even though
  // this app doesn't expose a skin switcher UI.
  const skin = cookieRef('skin', themeConfig.app.skin)

  return {
    theme,
    skin,
  }
})
// !SECTION
// SECTION Init
export const initConfigStore = () => {
  const userPreferredColorScheme = usePreferredColorScheme()
  const vuetifyTheme = useTheme()
  const configStore = useConfigStore()

  const resolveTheme = () => configStore.theme === 'system'
    ? (userPreferredColorScheme.value === 'dark' ? 'dark' : 'light')
    : configStore.theme

  watch([() => configStore.theme, userPreferredColorScheme], () => {
    vuetifyTheme.change(resolveTheme())
  })
  onMounted(() => {
    vuetifyTheme.change(resolveTheme())
  })
}
// !SECTION
