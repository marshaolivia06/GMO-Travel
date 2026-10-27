
<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({
  modelValue: Boolean,
  booking: { type: Object, default: null },
  order: { type: Object, default: null },
  loading: Boolean,
})

const emit = defineEmits(['update:modelValue'])
const activeTab = ref('')

const ticketBooked = computed(() =>
  props.order?.ticket_arrangement === 'Booked by Company',
)

const accommodationBooked = computed(() =>
  props.order?.accommodation_arrangement === 'Booked by Company',
)

const attachments = computed(() => [
  ...(ticketBooked.value
    ? [{ key: 'e-ticket', title: 'Final E-Ticket', path: props.booking?.e_ticket_path }]
    : []),
  ...(accommodationBooked.value
    ? [{ key: 'hotel', title: 'Final Hotel Reservation', path: props.booking?.hotel_reservation_path }]
    : []),
])

function getFileUrl(path) {
  if (!path) return ''
  if (/^https?:\/\//i.test(path)) return path

  const baseUrl = import.meta.env.VITE_API_BASE_URL.replace(/\/+$/, '')

  const filePath = String(path)
    .replace(/^\/+/, '')
    .replace(/^storage\//, '')

  return `${baseUrl}/storage/${filePath}`
}

function isImage(path) {
  return /\.(png|jpe?g|gif|webp|bmp|svg)(\?.*)?$/i.test(path || '')
}

function isPdf(path) {
  return /\.pdf(\?.*)?$/i.test(path || '')
}

watch(
  () => [props.modelValue, attachments.value],
  ([open, items]) => {
    if (open && !items.some(item => item.key === activeTab.value)) {
      activeTab.value = items[0]?.key || ''
    }
  },
  { immediate: true },
)
</script>

<template>
  <VDialog
    :model-value="modelValue"
    max-width="1000"
    scrollable
    @update:model-value="emit('update:modelValue', $event)"
  >
    <VCard rounded="lg">
      <VCardTitle class="d-flex align-center justify-space-between px-5 py-4">
        <div>
          <div class="text-h6 font-weight-semibold">Travel Order Attachments</div>
          <div class="text-body-2 text-medium-emphasis mt-1">
            View booking and final travel documents.
          </div>
        </div>
        <VBtn
          icon="ri-close-line"
          variant="text"
          color="default"
          size="small"
          @click="emit('update:modelValue', false)"
        />
      </VCardTitle>

      <VDivider />

      <VProgressLinear v-if="loading" indeterminate color="primary" />

      <template v-else-if="attachments.length">
        <VTabs v-model="activeTab" color="primary">
  <VTab
    v-for="item in attachments"
    :key="item.key"
    :value="item.key"
    width="50%"
    class="text-none"
  >
    {{ item.title }}
  </VTab>
</VTabs>

        <VDivider />

        <VCardText class="pa-4">
          <VWindow v-model="activeTab">
            <VWindowItem
              v-for="item in attachments"
              :key="item.key"
              :value="item.key"
            >
              <div
                v-if="!item.path"
                class="d-flex flex-column align-center justify-center pa-10"
              >
                <VIcon icon="ri-file-forbid-line" size="48" color="secondary" class="mb-3" />
                <div class="text-body-1 font-weight-medium">No attachment available</div>
                <div class="text-body-2 text-medium-emphasis mt-1">
                  {{ item.title }} has not been uploaded.
                </div>
              </div>

              <VImg
                v-else-if="isImage(item.path)"
                :src="getFileUrl(item.path)"
                :alt="item.title"
                max-height="70vh"
                contain
              >
                <template #error>
                  <VAlert type="error" variant="tonal" density="comfortable">
                    Failed to load image. Check the file URL and storage access.
                  </VAlert>
                </template>
              </VImg>

              <iframe
                v-else-if="isPdf(item.path)"
                :src="getFileUrl(item.path)"
                :title="item.title"
                width="100%"
                height="650"
                style="border: 0; display: block;"
              />

              <div
                v-else
                class="d-flex flex-column align-center justify-center pa-10"
              >
                <VIcon icon="ri-file-download-line" size="48" color="secondary" class="mb-3" />
                <div class="text-body-1 font-weight-medium">Preview is not supported</div>
                <VBtn
                  :href="getFileUrl(item.path)"
                  target="_blank"
                  rel="noopener noreferrer"
                  variant="tonal"
                  color="primary"
                  prepend-icon="ri-download-line"
                  class="mt-3"
                >
                  Download Document
                </VBtn>
              </div>
            </VWindowItem>
          </VWindow>
        </VCardText>
      </template>

      <VCardText v-else class="d-flex flex-column align-center justify-center pa-10">
        <VIcon icon="ri-file-forbid-line" size="48" color="secondary" class="mb-3" />
        <div class="text-body-1 font-weight-medium">No company-booked attachments</div>
        <div class="text-body-2 text-medium-emphasis mt-1">
          No documents are available for the current booking arrangement.
        </div>
      </VCardText>
    </VCard>
  </VDialog>
</template>
