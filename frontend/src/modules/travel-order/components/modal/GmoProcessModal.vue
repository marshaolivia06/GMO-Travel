<script setup>
import { computed, reactive, ref, watch } from 'vue'

const props = defineProps({
  modelValue: Boolean, title: String, status: String,
  mode: { type: String, default: 'processing' },
  order: { type: Object, default: null },
  pdfUrl: { type: String, default: '' },
  loading: Boolean, processing: Boolean,
})

const emit = defineEmits(['update:modelValue', 'cancel', 'reject', 'revision', 'continue', 'complete'])

const adjustTime = reactive({ departure_date: '', departure_time: '', return_date: '', return_time: '' })
const agreed = ref(false)
const pdfReady = ref(false)
const isDirectPayment = computed(() => props.mode === 'direct-payment')
const isReview = computed(() => props.mode === 'review')

function normalizeTime(value) {
  if (!value) return ''
  const match = String(value).match(/^(\d{2}):(\d{2})/)
  return match ? `${match[1]}:${match[2]}` : ''
}

watch(() => props.order, order => {
  if (!order) return
  adjustTime.departure_date = order.departure_date?.slice(0, 10) || ''
  adjustTime.departure_time = normalizeTime(order.departure_time)
  adjustTime.return_date = order.return_date?.slice(0, 10) || ''
  adjustTime.return_time = normalizeTime(order.return_time)
}, { immediate: true })

watch(() => [props.modelValue, props.pdfUrl, props.mode], () => {
  agreed.value = false
  pdfReady.value = false
})
</script>

<template>
  <VDialog :model-value="modelValue" max-width="1100" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <VCardItem class="px-4 py-2">
        <VCardTitle class="pa-0 text-subtitle-1 font-weight-bold">{{ title }}</VCardTitle>
        <template #append><VBtn icon="ri-close-line" size="small" variant="text" :disabled="processing" @click="emit('cancel')" /></template>
      </VCardItem>
      <VDivider />

      <template v-if="isDirectPayment || isReview">
        <VCardText class="bg-grey-lighten-3 pa-0">
          <VProgressLinear v-if="loading || (pdfUrl && !pdfReady)" indeterminate />
          <iframe v-if="pdfUrl" :key="pdfUrl" :src="pdfUrl" title="Travel Order PDF" height="750" class="d-block w-100 border-0" @load="pdfReady = true"></iframe>
          <div v-else-if="!loading" class="pa-6 text-center text-medium-emphasis">PDF is not available.</div>
        </VCardText>

        <template v-if="pdfUrl && pdfReady && !loading">
          <VDivider />
          <VCardText class="px-8 py-5">
            <div class="d-flex align-start ga-3">
              <input v-model="agreed" type="checkbox" class="mt-1 flex-shrink-0">
              <div class="text-body-2">I confirm that I have reviewed the Travel Order document.</div>
            </div>
          </VCardText>
          <VCardActions v-if="agreed" class="px-8 pb-5 pt-0 justify-end ga-3">
            <VBtn variant="flat" color="error" :disabled="processing" @click="emit('reject')">Reject</VBtn>
            <VBtn variant="flat" color="warning" :disabled="processing" @click="emit('revision')">Revision</VBtn>
            <VBtn v-if="isReview" variant="flat" color="success" :disabled="processing" @click="emit('continue')">Continue</VBtn>
            <VBtn v-else variant="flat" color="success" :loading="processing" :disabled="processing" @click="emit('complete')">Complete GMO Processing</VBtn>
          </VCardActions>
        </template>
      </template>

      <template v-else>
        <VProgressLinear v-if="loading" indeterminate />
        <VCardText v-if="order" class="bg-grey-lighten-5 pa-5">
          <VCard rounded="lg" elevation="1">
            <div class="bg-grey-lighten-4 border-b px-4 py-3 d-flex align-center justify-space-between">
              <div class="text-subtitle-2 font-weight-bold">Adjust Time (Departure and Return)</div>
              <span class="text-caption text-medium-emphasis">Editable by GMO</span>
            </div>
            <VDivider />
            <VCardText class="pa-4">
              <VRow dense>
                <VCol cols="12" md="6"><div class="text-caption font-weight-medium mb-1">Departure</div><div class="d-flex ga-3"><VTextField v-model="adjustTime.departure_date" type="date" density="comfortable" hide-details /><VTextField v-model="adjustTime.departure_time" type="time" density="comfortable" hide-details /></div></VCol>
                <VCol cols="12" md="6"><div class="text-caption font-weight-medium mb-1">Return</div><div class="d-flex ga-3"><VTextField v-model="adjustTime.return_date" type="date" density="comfortable" hide-details /><VTextField v-model="adjustTime.return_time" type="time" density="comfortable" hide-details /></div></VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCardText>
        <VCardText v-else-if="!loading" class="pa-6 text-center text-medium-emphasis">Data is not available.</VCardText>

        <template v-if="status === 'awaiting_gmo_processing'">
          <VDivider />
          <VCardActions class="px-6 py-3 justify-end ga-2">
            <VBtn variant="flat" color="grey" :disabled="processing" @click="emit('cancel')">Cancel</VBtn>
            <VBtn variant="flat" color="error" :disabled="processing" @click="emit('reject')">Reject</VBtn>
            <VBtn variant="flat" color="warning" :disabled="processing" @click="emit('revision')">Request Revision</VBtn>
            <VBtn variant="flat" color="success" :loading="processing" :disabled="processing" @click="emit('complete')">Complete GMO Processing</VBtn>
          </VCardActions>
        </template>
      </template>
    </VCard>
  </VDialog>
</template>
