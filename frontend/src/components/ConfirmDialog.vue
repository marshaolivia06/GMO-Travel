<script setup>
import { useConfirm } from '../composables/useConfirm'

const { state, respond } = useConfirm()

function onCancel() {
  respond(false)
}

function onConfirm() {
  respond(true)
}
</script>

<template>
  <VDialog v-model="state.show" max-width="420" persistent>
    <VCard>
      <VCardTitle class="pa-4">{{ state.title }}</VCardTitle>
      <VCardText v-if="state.text">{{ state.text }}</VCardText>
      <VCardActions class="justify-end gap-2 pa-4">
        <VBtn variant="tonal" @click="onCancel">Cancel</VBtn>
        <VBtn :color="state.color" @click="onConfirm">Confirm</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
