<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: Boolean,
  booking: { type: Object, default: null },
  loading: Boolean,
})

const emit = defineEmits(['update:modelValue'])

const attachments = computed(() => [
  { title: 'Booking Ticket', path: props.booking?.ticket_attachment },
  { title: 'Booking Accommodation', path: props.booking?.accommodation_attachment },
  { title: 'Final E-Ticket', path: props.booking?.e_ticket_path },
  { title: 'Final Hotel Reservation', path: props.booking?.hotel_reservation_path },
].filter(item => item.path))

function openAttachment(path) {
  const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000'
  const filePath = path.replace(/^\/?storage\//, '').replace(/^\/+/, '')
  window.open(`${baseUrl.replace(/\/$/, '')}/storage/${filePath}`, '_blank', 'noopener,noreferrer')
}
</script>

<template>
  <VDialog :model-value="modelValue" max-width="500" @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <VCardTitle class="d-flex align-center justify-space-between px-5 py-4">
        <div>
          <div class="text-h6 font-weight-semibold">Travel Order Attachments</div>
          <div class="text-body-2 text-medium-emphasis mt-1">View booking documents and final travel documents.</div>
        </div>
        <VBtn icon="ri-close-line" variant="text" color="default" size="small" @click="emit('update:modelValue', false)" />
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-4">
        <VProgressLinear v-if="loading" indeterminate color="primary" class="mb-3" />
        <VAlert v-if="!loading && !attachments.length" type="info" variant="tonal" density="comfortable">No attachments available.</VAlert>

        <VList v-else-if="attachments.length" class="pa-0" lines="one">
          <VListItem v-for="item in attachments" :key="item.title" class="px-2 mb-1" rounded="md">
            <template #prepend>
              <VAvatar color="primary" variant="tonal" rounded="md" size="38" class="me-3">
                <VIcon icon="ri-file-text-line" size="20" />
              </VAvatar>
            </template>
            <VListItemTitle class="text-body-2 font-weight-medium">{{ item.title }}</VListItemTitle>
            <template #append>
              <VBtn variant="tonal" color="primary" size="small" prepend-icon="ri-eye-line" @click="openAttachment(item.path)">View</VBtn>
            </template>
          </VListItem>
        </VList>
      </VCardText>
    </VCard>
  </VDialog>
</template>