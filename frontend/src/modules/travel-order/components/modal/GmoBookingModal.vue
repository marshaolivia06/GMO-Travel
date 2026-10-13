<script setup>
import { computed, reactive, ref, watch } from 'vue'

const props = defineProps({
  modelValue: Boolean, title: String, status: String,
  stage: { type: String, default: 'booking' },
  order: { type: Object, default: null },
  savedBooking: { type: Object, default: null },
  loading: Boolean, processing: Boolean,
})

const emit = defineEmits(['update:modelValue', 'cancel', 'back', 'reject', 'revision', 'submit-booking', 'submit-eticket', 'continue-eticket'])

const eTicket = ref(null)
const adjustTime = reactive({ departure_date: '', departure_time: '', return_date: '', return_time: '' })
const bookingItems = reactive({
  airplane_currency: 'IDR', airplane_amount: 0, airplane_attachment: null,
  accommodation_currency: 'IDR', accommodation_amount: 0, accommodation_attachment: null,
})

const isReadOnly = computed(() => props.stage !== 'booking')
const isBookedByCompany = computed(() => {
  const o = props.order ?? {}
  return o.ticket?.arrangement === 'Booked by Company' || o.accommodation?.arrangement === 'Booked by Company' || o.ticket_arrangement === 'Booked by Company' || o.ferry_arrangement === 'Booked by Company' || o.accommodation_arrangement === 'Booked by Company'
})
const travelRegion = computed(() => props.order?.travel_region || '')
const bookingCurrencyItems = computed(() => {
  const region = String(travelRegion.value).toLowerCase()
  if (['domestic', 'indonesia'].includes(region)) return ['IDR']
  if (region === 'singapore') return ['SGD']
  return ['USD', 'EUR', 'MYR', 'JPY']
})
const savedAirplaneAttachment = computed(() => props.savedBooking?.airplane_attachment_name || '')
const savedAccommodationAttachment = computed(() => props.savedBooking?.accommodation_attachment_name || '')

function formatAmount(value) {
  return value === null || value === undefined || value === '' ? '' : Number(value).toLocaleString('id-ID')
}
function parseAmount(value) {
  const digits = String(value ?? '').replace(/\D/g, '')
  return digits ? Number(digits) : 0
}
function normalizeTime(value) {
  if (!value) return ''
  const match = String(value).match(/^(\d{2}):(\d{2})/)
  return match ? `${match[1]}:${match[2]}` : ''
}

function loadBookingData(order) {
  if (!order) return

  adjustTime.departure_date = order.departure_date?.slice(0, 10) || ''
  adjustTime.departure_time = normalizeTime(order.departure_time)
  adjustTime.return_date = order.return_date?.slice(0, 10) || ''
  adjustTime.return_time = normalizeTime(order.return_time)

  const saved = props.savedBooking
  if (saved) {
    Object.assign(bookingItems, {
      airplane_currency: saved.airplane_currency || 'IDR',
      airplane_amount: saved.airplane_amount ?? 0,
      airplane_attachment: null,
      accommodation_currency: saved.accommodation_currency || 'IDR',
      accommodation_amount: saved.accommodation_amount ?? 0,
      accommodation_attachment: null,
    })
    Object.assign(adjustTime, saved.adjustTime || {})
  } else {
    const currency = bookingCurrencyItems.value[0] || 'IDR'
    Object.assign(bookingItems, {
      airplane_currency: currency, airplane_amount: 0, airplane_attachment: null,
      accommodation_currency: currency, accommodation_amount: 0, accommodation_attachment: null,
    })
  }
}

watch(() => [props.order, props.savedBooking], ([order]) => {
  if (order) loadBookingData(order)
}, { immediate: true, deep: true })

watch(bookingCurrencyItems, items => {
  if (props.savedBooking) return
  bookingItems.airplane_currency = items[0] || 'IDR'
  bookingItems.accommodation_currency = items[0] || 'IDR'
}, { immediate: true })

function setAirplaneFile(event) { bookingItems.airplane_attachment = event.target.files?.[0] ?? null }
function setAccommodationFile(event) { bookingItems.accommodation_attachment = event.target.files?.[0] ?? null }
function setETicketFile(event) { eTicket.value = event.target.files?.[0] ?? null }

function submitBooking() {
  emit('submit-booking', { adjustTime: { ...adjustTime }, bookingItems: { ...bookingItems } })
}
function continueToETicket() { emit('continue-eticket') }
function submitETicket() {
  if (!eTicket.value) return
  emit('submit-eticket', eTicket.value)
}
</script>

<template>
  <VDialog :model-value="modelValue" max-width="1100" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <VCardItem class="px-4 py-2">
        <template #prepend><VBtn icon="ri-arrow-left-line" size="small" variant="text" :disabled="processing" @click="emit('back')" /></template>
        <VCardTitle class="pa-0 text-subtitle-1 font-weight-bold">{{ title }}</VCardTitle>
        <template #append><VBtn icon="ri-close-line" size="small" variant="text" :disabled="processing" @click="emit('cancel')" /></template>
      </VCardItem>
      <VDivider />
      <VProgressLinear v-if="loading" indeterminate />

      <VCardText v-if="order" class="bg-grey-lighten-5 pa-5">
        <VCard rounded="lg" elevation="1" class="mb-4">
          <div class="bg-grey-lighten-4 border-b px-4 py-3 d-flex align-center justify-space-between">
            <div class="text-subtitle-2 font-weight-bold">Adjust Time (Departure and Return)</div>
            <span class="text-caption text-medium-emphasis">{{ isReadOnly ? 'Read only' : 'Editable by GMO' }}</span>
          </div>
          <VDivider />
          <VCardText class="pa-4">
            <VRow dense>
              <VCol cols="12" md="6">
                <div class="text-caption font-weight-medium mb-1">Departure</div>
                <div class="d-flex ga-3">
                  <VTextField v-model="adjustTime.departure_date" type="date" density="comfortable" hide-details :readonly="isReadOnly" />
                  <VTextField v-model="adjustTime.departure_time" type="time" density="comfortable" hide-details :readonly="isReadOnly" />
                </div>
              </VCol>
              <VCol cols="12" md="6">
                <div class="text-caption font-weight-medium mb-1">Return</div>
                <div class="d-flex ga-3">
                  <VTextField v-model="adjustTime.return_date" type="date" density="comfortable" hide-details :readonly="isReadOnly" />
                  <VTextField v-model="adjustTime.return_time" type="time" density="comfortable" hide-details :readonly="isReadOnly" />
                </div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <VCard v-if="stage === 'booking' && isBookedByCompany" rounded="lg" elevation="1" class="mb-4">
          <div class="bg-grey-lighten-4 border-b px-4 py-3 d-flex align-center justify-space-between">
            <div class="text-subtitle-2 font-weight-bold">Booking Information</div>
          </div>
          <VDivider />
          <VCardText class="pa-4">
            <VRow dense>
              <VCol cols="12" md="6">
                <VCard flat border rounded="lg">
                  <VCardItem class="py-3"><VCardTitle class="text-subtitle-2 font-weight-bold">Airplane Ticket</VCardTitle></VCardItem>
                  <VCardText class="pt-0">
                    <div class="text-caption font-weight-medium mb-1">Booking Amount <span v-if="!isReadOnly" class="text-error">*</span></div>
                    <div class="d-flex ga-2 mb-4">
                      <VSelect v-model="bookingItems.airplane_currency" :items="bookingCurrencyItems" variant="outlined" density="comfortable" hide-details disabled style="max-width:95px" />
                      <VTextField :model-value="formatAmount(bookingItems.airplane_amount)" variant="outlined" density="comfortable" hide-details inputmode="numeric" class="text-right-input" :readonly="isReadOnly" @update:model-value="value => bookingItems.airplane_amount = parseAmount(value)" />
                    </div>
                    <div class="text-caption font-weight-medium mb-1">Quotation / Booking Attachment <span v-if="!isReadOnly" class="text-error">*</span></div>
                    <div v-if="savedAirplaneAttachment" class="text-body-2 mb-2"><VIcon icon="ri-attachment-line" size="small" /> {{ savedAirplaneAttachment }}</div>
                    <input v-if="!isReadOnly" type="file" accept=".pdf,.jpg,.jpeg,.png" @change="setAirplaneFile" />
                    <div v-else-if="!savedAirplaneAttachment" class="text-body-2 text-medium-emphasis">Attachment name is unavailable in this FE simulation.</div>
                  </VCardText>
                </VCard>
              </VCol>
              <VCol cols="12" md="6">
                <VCard flat border rounded="lg">
                  <VCardItem class="py-3"><VCardTitle class="text-subtitle-2 font-weight-bold">Accommodation</VCardTitle></VCardItem>
                  <VCardText class="pt-0">
                    <div class="text-caption font-weight-medium mb-1">Booking Amount <span v-if="!isReadOnly" class="text-error">*</span></div>
                    <div class="d-flex ga-2 mb-4">
                      <VSelect v-model="bookingItems.accommodation_currency" :items="bookingCurrencyItems" variant="outlined" density="comfortable" hide-details disabled style="max-width:95px" />
                      <VTextField :model-value="formatAmount(bookingItems.accommodation_amount)" variant="outlined" density="comfortable" hide-details inputmode="numeric" class="text-right-input" :readonly="isReadOnly" @update:model-value="value => bookingItems.accommodation_amount = parseAmount(value)" />
                    </div>
                    <div class="text-caption font-weight-medium mb-1">Quotation / Booking Attachment <span v-if="!isReadOnly" class="text-error">*</span></div>
                    <div v-if="savedAccommodationAttachment" class="text-body-2 mb-2"><VIcon icon="ri-attachment-line" size="small" /> {{ savedAccommodationAttachment }}</div>
                    <input v-if="!isReadOnly" type="file" accept=".pdf,.jpg,.jpeg,.png" @change="setAccommodationFile" />
                    <div v-else-if="!savedAccommodationAttachment" class="text-body-2 text-medium-emphasis">Attachment name is unavailable in this FE simulation.</div>
                  </VCardText>
                </VCard>
              </VCol>
            </VRow>
            <VAlert v-if="savedBooking" type="info" variant="tonal" density="comfortable" class="mt-4">Booking information has been submitted and is read-only while waiting for Finance confirmation.</VAlert>
          </VCardText>
        </VCard>

        <VCard v-if="stage === 'waiting-finance'" rounded="lg" elevation="1" class="mb-4">
          <VCardText class="pa-4">
            <VAlert type="success" variant="tonal" density="comfortable" class="mb-3">Booking details have been submitted successfully.</VAlert>
            <VAlert type="info" variant="tonal" density="comfortable">The request is now awaiting Finance booking confirmation. You can close this window and continue later.</VAlert>
          </VCardText>
        </VCard>

        <VCard v-if="stage === 'eticket'" rounded="lg" elevation="1" class="mb-4">
          <div class="bg-grey-lighten-4 border-b px-4 py-3 text-subtitle-2 font-weight-bold">Payment & Document Issuance</div>
          <VDivider />
          <VCardText class="pa-4">
            <VAlert type="success" variant="tonal" density="comfortable" class="mb-4">Finance has completed the booking confirmation. Upload the E-Ticket to complete GMO processing.</VAlert>
            <div class="text-subtitle-2 font-weight-bold mb-1">E-Ticket / Reservation <span class="text-error">*</span></div>
            <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="setETicketFile" />
            <div v-if="eTicket" class="text-caption text-success mt-2">{{ eTicket.name }}</div>
          </VCardText>
        </VCard>
      </VCardText>

      <VCardText v-else-if="!loading" class="pa-6 text-center text-medium-emphasis">Data is not available.</VCardText>

      <template v-if="stage === 'booking' && status === 'awaiting_gmo_booking_document_issuance'">
        <VDivider />
        <VCardActions class="px-6 py-3 justify-end ga-2">
          <VBtn variant="flat" color="grey" :disabled="processing" @click="emit('cancel')">Cancel</VBtn>
          <VBtn variant="flat" color="success" :loading="processing" :disabled="processing || !isBookedByCompany || (bookingItems.airplane_amount <= 0 && bookingItems.accommodation_amount <= 0) || (!savedBooking && !bookingItems.airplane_attachment && !bookingItems.accommodation_attachment)" @click="submitBooking">{{ savedBooking ? 'Booking' : 'Booking' }}</VBtn>
        </VCardActions>
      </template>

      <template v-if="stage === 'waiting-finance'">
        <VDivider />
        <VCardActions class="px-6 py-3 justify-end ga-2">
          <VBtn variant="outlined" @click="emit('cancel')">Close</VBtn>
          <VBtn color="primary" variant="flat" @click="continueToETicket">Continue to E-Ticket</VBtn>
        </VCardActions>
      </template>

      <template v-if="stage === 'eticket'">
        <VDivider />
        <VCardActions class="px-6 py-3 justify-end ga-2">
          <VBtn variant="flat" color="grey" @click="emit('cancel')">Cancel</VBtn>
          <VBtn variant="flat" color="success" :loading="processing" :disabled="processing || !eTicket" @click="submitETicket">Complete GMO Processing</VBtn>
        </VCardActions>
      </template>
    </VCard>
  </VDialog>
</template>

<style scoped>
.text-right-input :deep(input) { text-align: right; }
</style>