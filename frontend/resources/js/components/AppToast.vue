<script setup>
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()

const colorFor = type => ({ success: 'success', error: 'error', warning: 'warning', info: 'info' }[type] ?? 'info')
const iconFor = type => ({ success: 'ri-checkbox-circle-line', error: 'ri-close-circle-line', warning: 'ri-alert-line', info: 'ri-information-line' }[type] ?? 'ri-information-line')
</script>

<template>
  <VSnackbar
    v-for="(t, index) in toast.toasts"
    :key="t.id"
    :model-value="true"
    :color="colorFor(t.type)"
    :style="`transform: translateY(${index * -64}px)`"
    location="top end"
    timeout="-1"
    @update:model-value="toast.remove(t.id)"
  >
    <div class="d-flex align-center ga-2">
      <VIcon :icon="iconFor(t.type)" />
      <span>{{ t.message }}</span>
    </div>

    <template #actions>
      <VBtn
        icon
        variant="text"
        size="small"
        @click="toast.remove(t.id)"
      >
        <VIcon icon="ri-close-line" size="18" />
      </VBtn>
    </template>
  </VSnackbar>
</template>
