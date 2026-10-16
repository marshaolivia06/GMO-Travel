<script setup>
import { computed, ref, watch } from 'vue'

const isProcessed = computed(() => props.status === 'processed_by_gmo')
const props = defineProps({
  modelValue: Boolean, title: String, status: String,
  mode: { type: String, default: 'processing' },
  order: { type: Object, default: null },
  pdfUrl: { type: String, default: '' },
  loading: Boolean, processing: Boolean,
})

const emit = defineEmits(['update:modelValue', 'cancel', 'continue', 'complete', 'revision'])

const agreed = ref(false)
const pdfReady = ref(false)
const isDirectPayment = computed(() => props.mode === 'direct-payment')
const isReview = computed(() => props.mode === 'review')
const isCompleted = computed(() => props.status === 'processed_by_gmo')

watch(() => [props.modelValue, props.pdfUrl, props.mode, props.status], () => {
  agreed.value = isProcessed.value
  pdfReady.value = false
})
</script>

<template>
 <VDialog :model-value="modelValue" :max-width="isDirectPayment ? 450 : 1100" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <VCardItem class="px-4 py-2">
        <VCardTitle class="pa-0 text-subtitle-1 font-weight-bold">{{ title }}</VCardTitle>
        <template #append>
          <VBtn icon="ri-close-line" size="small" variant="text" :disabled="processing" @click="emit('cancel')" />
        </template>
      </VCardItem>
      <VDivider />

<template v-if="isDirectPayment">
  <VCardText class="px-6 pt-5 pb-4">
    <div class="text-body-2 text-medium-emphasis mb-5" style="line-height: 1.7">
      This trip uses Direct Payment and is not covered by company payment through GMO.
    </div>

    <label class="d-flex align-start ga-3 pa-3 rounded-lg border text-body-2" style="cursor: pointer; line-height: 1.6">
      <input v-model="agreed" type="checkbox" class="mt-1 flex-shrink-0" :disabled="processing || isProcessed" />
      <span>I confirm that all required GMO processing is complete.</span>
    </label>
  </VCardText>

  <VDivider />
  <VCardActions class="px-6 py-4 justify-end ga-2">
  <VBtn v-if="!isCompleted" color="warning" variant="flat" :disabled="processing" @click="emit('revision')"> Revision</VBtn>
  <VBtn v-if="agreed && !isProcessed" color="success" variant="flat" rounded="lg" :loading="processing" :disabled="processing" @click="emit('complete')">
    Complete GMO Processing
  </VBtn>
</VCardActions>
</template>

      <template v-else-if="isReview">
        <VCardText class="bg-grey-lighten-3 pa-0">
          <VProgressLinear v-if="loading || (pdfUrl && !pdfReady)" indeterminate />
          <iframe v-if="pdfUrl" :key="pdfUrl" :src="pdfUrl" title="Travel Order PDF" height="750" class="d-block w-100 border-0" @load="pdfReady = true"></iframe>
          <div v-else-if="!loading" class="pa-6 text-center text-medium-emphasis">PDF is not available.</div>
        </VCardText>
        <template v-if="pdfUrl && pdfReady && !loading && !isCompleted">
          <VDivider />
          <VCardText class="px-8 py-5">
            <div class="d-flex align-start ga-3">
              <input v-model="agreed" type="checkbox" class="mt-1 flex-shrink-0" />
              <div class="text-body-2">I confirm that I have reviewed the Travel Order document.</div>
            </div>
          </VCardText>
          <VCardActions v-if="agreed" class="px-8 pb-5 pt-0 justify-end ga-3">
            <VBtn variant="flat" color="success" :disabled="processing" @click="emit('continue')">Continue</VBtn>
          </VCardActions>
        </template>
      </template>
    </VCard>
  </VDialog>
</template>