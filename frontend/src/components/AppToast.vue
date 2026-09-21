<script setup>
import { useToastStore } from '../stores/toast'

const toast = useToastStore()
</script>

<template>
  <VSnackbar
    v-for="(t, index) in toast.queue"
    :key="t.id"
    :model-value="true"
    :color="t.color"
    location="top end"
    :style="{ marginTop: `${index * 56}px` }"
    :timeout="-1"
  >
    {{ t.message }}
  </VSnackbar>
</template>
