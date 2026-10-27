<script setup>
import { useTheme } from 'vuetify'
import AppToast from '@/components/AppToast.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import ScrollToTop from '@core/components/ScrollToTop.vue'
import initCore from '@core/initCore'
import { initConfigStore } from '@core/stores/config'
import { hexToRgb } from '@core/utils/colorConverter'

const { global } = useTheme()

initCore()
initConfigStore()
</script>

<template>
  <VApp :style="`--v-global-theme-primary: ${hexToRgb(global.current.value.colors.primary)}`">
    <RouterView />
    <AppToast />
    <ConfirmDialog />
    <ScrollToTop />
  </VApp>
</template>
