<script setup>
import { computed, reactive, ref, watch } from 'vue'

const props = defineProps({
  modelValue: Boolean, title: String, status: String,
  order: { type: Object, default: null }, loading: Boolean, processing: Boolean,
})

const emit = defineEmits(['update:modelValue', 'cancel', 'reject', 'revision', 'complete'])
const booked = ref(false)
const eTicket = ref(null)
const adjustTime = reactive({ departure_date: '', departure_time: '', return_date: '', return_time: '' })
const bookingItems = reactive({
  airplane_currency: 'IDR', airplane_amount: 0, airplane_attachment: null,
  accommodation_currency: 'IDR', accommodation_amount: 0, accommodation_attachment: null,
})

function normalizeTime(value) {
  if (!value) return ''
  const match = String(value).match(/^(\d{2}):(\d{2})/)
  return match ? `${match[1]}:${match[2]}` : ''
}

watch(() => props.order, order => {
  if (!order) return
  booked.value = false
  eTicket.value = null
  adjustTime.departure_date = order.departure_date?.slice(0, 10) || ''
  adjustTime.departure_time = normalizeTime(order.departure_time)
  adjustTime.return_date = order.return_date?.slice(0, 10) || ''
  adjustTime.return_time = normalizeTime(order.return_time)
}, { immediate: true })

function setAirplaneFile(event) { bookingItems.airplane_attachment = event.target.files?.[0] ?? null }
function setAccommodationFile(event) { bookingItems.accommodation_attachment = event.target.files?.[0] ?? null }
function setETicketFile(event) { eTicket.value = event.target.files?.[0] ?? null }
function bookTicket() { booked.value = true }

const isBookedByCompany = computed(() => {
  const o = props.order ?? {}
  return o.ticket?.arrangement === 'Booked by Company' || o.accommodation?.arrangement === 'Booked by Company' ||
    o.ticket_arrangement === 'Booked by Company' || o.accommodation_arrangement === 'Booked by Company'
})
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
        <VCard rounded="lg" elevation="1" class="mb-4">
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

        <VCard v-if="!booked && isBookedByCompany" rounded="lg" elevation="1" class="mb-4">
          <div class="bg-grey-lighten-4 border-b px-4 py-3 text-subtitle-2 font-weight-bold">Booking Items</div>
          <VDivider />
          <VCardText class="pa-4">
            <VRow dense>
              <VCol cols="12" md="6">
                <VCard flat border rounded="lg">
                  <VCardItem class="py-3"><VCardTitle class="text-subtitle-2 font-weight-bold">Airplane Ticket</VCardTitle></VCardItem>
                  <VCardText class="pt-0">
                    <div class="text-caption font-weight-medium mb-1">Booking Amount <span class="text-error">*</span></div>
                    <div class="d-flex ga-2 mb-4"><VSelect v-model="bookingItems.airplane_currency" :items="['IDR', 'SGD', 'USD', 'EUR', 'JPY']" density="comfortable" hide-details style="max-width:95px" /><VTextField v-model="bookingItems.airplane_amount" type="number" min="0" density="comfortable" hide-details /></div>
                    <div class="text-caption font-weight-medium mb-1">Quotation / Booking Attachment <span class="text-error">*</span></div>
                    <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="setAirplaneFile" />
                  </VCardText>
                </VCard>
              </VCol>
              <VCol cols="12" md="6">
                <VCard flat border rounded="lg">
                  <VCardItem class="py-3"><VCardTitle class="text-subtitle-2 font-weight-bold">Accommodation</VCardTitle></VCardItem>
                  <VCardText class="pt-0">
                    <div class="text-caption font-weight-medium mb-1">Booking Amount <span class="text-error">*</span></div>
                    <div class="d-flex ga-2 mb-4"><VSelect v-model="bookingItems.accommodation_currency" :items="['IDR', 'SGD', 'USD', 'EUR', 'JPY']" density="comfortable" hide-details style="max-width:95px" /><VTextField v-model="bookingItems.accommodation_amount" type="number" min="0" density="comfortable" hide-details /></div>
                    <div class="text-caption font-weight-medium mb-1">Quotation / Booking Attachment <span class="text-error">*</span></div>
                    <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="setAccommodationFile" />
                  </VCardText>
                </VCard>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <VCard v-if="booked" rounded="lg" elevation="1" class="mb-4">
          <div class="bg-grey-lighten-4 border-b px-4 py-3 d-flex align-center justify-space-between">
            <div class="text-subtitle-2 font-weight-bold">Payment & Document Issuance</div>
            <VChip size="small" color="success" variant="tonal">Booking Completed</VChip>
          </div>
          <VDivider />
          <VCardText class="pa-4">
            <VAlert type="success" variant="tonal" density="comfortable" class="mb-4">Ticket booking has been completed and the payment request has been sent to Finance & Accounting.</VAlert>
            <div class="text-subtitle-2 font-weight-bold mb-1">E-Ticket / Reservation</div>
            <div class="text-caption text-medium-emphasis mb-3">Upload the e-ticket or reservation after payment has been completed by Finance & Accounting.</div>
            <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="setETicketFile" />
            <div v-if="eTicket" class="text-caption text-success mt-2">{{ eTicket.name }}</div>
          </VCardText>
        </VCard>
      </VCardText>

      <VCardText v-else-if="!loading" class="pa-6 text-center text-medium-emphasis">Data is not available.</VCardText>

      <template v-if="status === 'awaiting_gmo_booking_preparation'">
        <VDivider />
        <VCardActions class="px-6 py-3 justify-end ga-2">
          <VBtn variant="flat" color="grey" :disabled="processing" @click="emit('cancel')">Cancel</VBtn>
          <VBtn variant="flat" color="error" :disabled="processing" @click="emit('reject')">Reject</VBtn>
          <VBtn variant="flat" color="warning" :disabled="processing" @click="emit('revision')">Request Revision</VBtn>
          <VBtn v-if="!booked" variant="flat" color="success" :loading="processing" @click="bookTicket">Book Ticket</VBtn>
          <VBtn v-else variant="flat" color="success" :loading="processing" :disabled="!eTicket" @click="emit('complete')">Complete GMO Processing</VBtn>
        </VCardActions>
      </template>
    </VCard>
  </VDialog>
</template>