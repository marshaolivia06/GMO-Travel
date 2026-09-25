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

<style>
html,
body,
#app {
  height: 100%;
  margin: 0;
  overflow: hidden;
}

.v-application,
.v-application__wrap {
  height: 100%;
}

.v-main {
  height: 100%;
  overflow-y: auto;
}
</style>