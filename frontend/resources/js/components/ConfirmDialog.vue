<script setup>
import { useConfirm } from '@/composables/useConfirm'

const { state, onConfirm, onCancel } = useConfirm()
</script>

<template>
  <VDialog
    v-model="state.isOpen"
    max-width="420"
    persistent
  >
    <VCard>
      <VCardTitle class="pa-4">
        {{ state.title }}
      </VCardTitle>
      <VCardText v-if="state.text">
        {{ state.text }}
      </VCardText>
      <VCardActions class="justify-end ga-2 pa-4">
        <VBtn
          variant="tonal"
          @click="onCancel"
        >
          {{ state.cancelText }}
        </VBtn>
        <VBtn
          :color="state.color"
          @click="onConfirm"
        >
          {{ state.confirmText }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
