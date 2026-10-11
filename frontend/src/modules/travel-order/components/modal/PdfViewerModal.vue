<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  modelValue: Boolean,
  title: String,
  pdfUrl: String,
  loading: Boolean,
  canDecide: Boolean,
  approving: Boolean,
})

const emit = defineEmits(['update:modelValue', 'back', 'reject', 'revision', 'approve'])
const agreed = ref(false)
const pdfReady = ref(false)

watch(() => [props.modelValue, props.pdfUrl], () => {
  agreed.value = false
  pdfReady.value = false
})
</script>

<template>
  <VDialog :model-value="modelValue" max-width="1100" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <VCardItem class="px-4 py-2">
        <div class="d-flex align-center ga-1">
          <VBtn v-if="canDecide" icon="ri-arrow-left-line" size="small" variant="text" @click="emit('back')" />
          <VCardTitle class="pa-0 text-subtitle-1 font-weight-bold">{{ title }}</VCardTitle>
        </div>
        <template #append>
          <VBtn icon="ri-close-line" size="small" variant="text" @click="emit('update:modelValue', false)" />
        </template>
      </VCardItem>

      <VDivider />

      <VCardText class="bg-grey-lighten-3 pa-0">
        <VProgressLinear v-if="loading || (pdfUrl && !pdfReady)" indeterminate />
        <iframe v-if="pdfUrl" :key="pdfUrl" :src="pdfUrl" title="Travel Order PDF" height="750" class="d-block w-100 border-0" @load="pdfReady = true" />
        <div v-else-if="!loading" class="pa-6 text-center text-medium-emphasis">PDF is not available.</div>
      </VCardText>

<template v-if="canDecide && pdfUrl && pdfReady && !loading">
  <VDivider />
  <VCardText class="px-8 py-5">
    <div class="d-flex align-start ga-3">
      <input v-model="agreed" type="checkbox" class="mt-1 flex-shrink-0">
      <div class="text-body-2">I confirm that I have reviewed the Travel Order document and agree that it is ready for approval.</div>
    </div>
  </VCardText>
  <VCardActions v-if="agreed" class="px-8 pb-5 pt-0 justify-end ga-3">
    <VBtn variant="flat" color="error" :disabled="approving" @click="emit('reject')">Reject</VBtn>
    <VBtn variant="flat" color="warning" :disabled="approving" @click="emit('revision')">Revision</VBtn>
    <VBtn variant="flat" color="success" :loading="approving" @click="emit('approve')">Approve</VBtn>
  </VCardActions>
</template>

    </VCard>
  </VDialog>
</template>

