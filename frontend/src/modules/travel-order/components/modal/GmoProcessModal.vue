<script setup>
import { reactive, watch } from 'vue'

const props = defineProps({
  modelValue: Boolean, title: String, status: String,
  order: { type: Object, default: null }, loading: Boolean, processing: Boolean,
})

const emit = defineEmits(['update:modelValue', 'cancel', 'reject', 'revision', 'complete'])
const adjustTime = reactive({ departure_date: '', departure_time: '', return_date: '', return_time: '' })

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
</script>

<template>
  <VDialog :model-value="modelValue" max-width="1100" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <VCardItem class="px-4 py-2">
        <VCardTitle class="pa-0 text-subtitle-1 font-weight-bold">{{ title }}</VCardTitle>
        <template #append><VBtn icon="ri-close-line" size="small" variant="text" @click="emit('update:modelValue', false)" /></template>
      </VCardItem>

      <VDivider />
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
              <VCol cols="12" md="6">
                <div class="text-caption font-weight-medium mb-1">Departure</div>
                <div class="d-flex ga-3">
                  <VTextField v-model="adjustTime.departure_date" type="date" density="comfortable" hide-details />
                  <VTextField v-model="adjustTime.departure_time" type="time" density="comfortable" hide-details />
                </div>
              </VCol>

              <VCol cols="12" md="6">
                <div class="text-caption font-weight-medium mb-1">Return</div>
                <div class="d-flex ga-3">
                  <VTextField v-model="adjustTime.return_date" type="date" density="comfortable" hide-details />
                  <VTextField v-model="adjustTime.return_time" type="time" density="comfortable" hide-details />
                </div>
              </VCol>
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
          <VBtn variant="flat" color="success" :loading="processing" @click="emit('complete')">Complete GMO Processing</VBtn>
        </VCardActions>
      </template>
    </VCard>
  </VDialog>
</template>