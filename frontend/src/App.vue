<script setup>
import { RouterView } from 'vue-router'
import AppToast from './components/AppToast.vue'
import ConfirmDialog from './components/ConfirmDialog.vue'
</script>

<template>
  <VApp>
    <RouterView />

    <AppToast />
    <ConfirmDialog />
  </VApp>
</template>
