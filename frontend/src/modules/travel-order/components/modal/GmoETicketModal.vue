<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  modelValue: Boolean,
  order: { type: Object, default: null },
  loading: Boolean,
  processing: Boolean,
})

const emit = defineEmits(['update:modelValue', 'cancel', 'back', 'submit'])
const eTicket = ref(null)

watch(() => props.modelValue, open => {
  if (open) eTicket.value = null
})

function submit() {
  if (!eTicket.value || props.processing) return
  emit('submit', eTicket.value)
}
</script>

<template>
  <VDialog :model-value="modelValue" max-width="620" persistent @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <VCardItem class="px-4 py-2">
        <template #prepend>
          <VBtn icon="ri-arrow-left-line" size="small" variant="text" :disabled="processing" @click="emit('back')" />
        </template>
        <VCardTitle class="pa-0 text-subtitle-1 font-weight-bold">Final Travel Documents</VCardTitle>
        <template #append>
          <VBtn icon="ri-close-line" size="small" variant="text" :disabled="processing" @click="emit('cancel')" />
        </template>
      </VCardItem>

      <VDivider />
      <VProgressLinear v-if="loading" indeterminate />

      <VCardText class="bg-grey-lighten-5 pa-5">
        <VCard rounded="lg" elevation="1">
          <div class="bg-grey-lighten-4 border-b px-4 py-3 text-subtitle-2 font-weight-bold">
            Final E-Ticket
          </div>
          <VDivider />
          <VCardText class="pa-4">
            <div class="text-caption font-weight-medium mb-1">
              Final Document <span class="text-error">*</span>
            </div>
            <input type="file" accept=".pdf,.jpg,.jpeg,.png" :disabled="processing" @change="eTicket = $event.target.files?.[0] ?? null" />
          </VCardText>
        </VCard>
      </VCardText>

      <VDivider />

      <VCardActions class="px-5 py-3 justify-end ga-2">
        <VBtn variant="tonal" :disabled="processing" @click="emit('cancel')">Cancel</VBtn>
        <VBtn color="success" variant="flat" :loading="processing" :disabled="processing || !eTicket" @click="submit">
          Complete GMO Processing
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
