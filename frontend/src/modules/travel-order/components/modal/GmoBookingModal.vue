<script setup>
import { computed, reactive, ref, watch } from 'vue'

const props = defineProps({
  modelValue: Boolean, title: String, status: String,
  stage: { type: String, default: 'booking' },
  order: { type: Object, default: null },
  savedBooking: { type: Object, default: null },
  loading: Boolean, processing: Boolean,
})

const emit = defineEmits(['update:modelValue', 'cancel', 'submit-booking', 'submit-eticket', 'revision'])

const activeTab = ref('booking')
const eTicket = ref(null)
const hotelReservation = ref(null)

const adjustTime = reactive({
  departure_date: '', departure_time: '', return_date: '', return_time: '',
})

const bookingItems = reactive({
  airplane_currency: 'IDR', airplane_amount: 0, airplane_attachment: null,
  accommodation_currency: 'IDR', accommodation_amount: 0, accommodation_attachment: null,
})

const isCompleted = computed(() => props.status === 'processed_by_gmo')
const isBookingSaved = computed(() => !!(props.savedBooking || props.order?.booking))
const isReadOnly = computed(() => isCompleted.value || props.stage !== 'booking' || isBookingSaved.value)
const showBookingInformation = computed(() => isBookedByCompany.value)

const isTicketBookedByCompany = computed(() => {
  const order = props.order ?? {}
  return order.ticket?.arrangement === 'Booked by Company' || order.ticket_arrangement === 'Booked by Company'
})

const isAccommodationBookedByCompany = computed(() => {
  const order = props.order ?? {}
  return order.accommodation?.arrangement === 'Booked by Company' || order.accommodation_arrangement === 'Booked by Company'
})

const canOpenDocuments = computed(() =>
  isBookingSaved.value || isCompleted.value
)
const isBookedByCompany = computed(() => isTicketBookedByCompany.value || isAccommodationBookedByCompany.value)
const travelRegion = computed(() => props.order?.travel_region || '')
const dialogWidth = computed(() => activeTab.value === 'booking' ? 1100 : 600)

const bookingCurrencyItems = computed(() => {
  const region = String(travelRegion.value).toLowerCase()
  if (['domestic', 'indonesia'].includes(region)) return ['IDR']
  if (region === 'singapore') return ['SGD']
  return ['USD', 'EUR', 'MYR', 'JPY']
})

const savedAirplaneAttachment = computed(() => props.savedBooking?.airplane_attachment_name || '')
const savedAccommodationAttachment = computed(() => props.savedBooking?.accommodation_attachment_name || '')

const savedETicket = computed(() => {
  const booking = props.savedBooking ?? props.order?.booking
  return booking?.e_ticket_name || booking?.e_ticket_path?.split('/').pop() || ''
})

const savedHotelReservation = computed(() => {
  const booking = props.savedBooking ?? props.order?.booking
  return booking?.hotel_reservation_name || booking?.hotel_reservation_path?.split('/').pop() || ''
})

const formatAmount = value => value == null || value === '' ? '' : Number(value).toLocaleString('id-ID')
const parseAmount = value => {
  const digits = String(value ?? '').replace(/\D/g, '')
  return digits ? Number(digits) : 0
}
const normalizeTime = value => {
  const match = value && String(value).match(/^(\d{2}):(\d{2})/)
  return match ? `${match[1]}:${match[2]}` : ''
}
const formatDate = value => value
  ? new Date(value).toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' })
  : ''

function loadBookingData(order) {
  if (!order) return

  adjustTime.departure_date = formatDate(order.departure_date)
  adjustTime.departure_time = normalizeTime(order.departure_time)
  adjustTime.return_date = formatDate(order.return_date)
  adjustTime.return_time = normalizeTime(order.return_time)

  const saved = props.savedBooking ?? order.booking

  if (saved) {
    Object.assign(bookingItems, {
      airplane_currency: saved.ticket_currency || 'IDR',
      airplane_amount: saved.ticket_amount ?? 0,
      airplane_attachment: null,
      accommodation_currency: saved.accommodation_currency || 'IDR',
      accommodation_amount: saved.accommodation_amount ?? 0,
      accommodation_attachment: null,
    })

    Object.assign(adjustTime, {
      departure_date: formatDate(saved.departure_date) || adjustTime.departure_date,
      departure_time: normalizeTime(saved.departure_time) || adjustTime.departure_time,
      return_date: formatDate(saved.return_date) || adjustTime.return_date,
      return_time: normalizeTime(saved.return_time) || adjustTime.return_time,
    })
    return
  }

  const currency = bookingCurrencyItems.value[0] || 'IDR'
  Object.assign(bookingItems, {
    airplane_currency: currency, airplane_amount: 0, airplane_attachment: null,
    accommodation_currency: currency, accommodation_amount: 0, accommodation_attachment: null,
  })
}

watch(() => [props.order, props.savedBooking], ([order]) => {
  if (order) loadBookingData(order)
}, { immediate: true, deep: true })

watch(bookingCurrencyItems, items => {
  if (props.savedBooking) return
  bookingItems.airplane_currency = items[0] || 'IDR'
  bookingItems.accommodation_currency = items[0] || 'IDR'
}, { immediate: true })

watch(() => props.modelValue, open => {
  if (!open) return

  activeTab.value = props.stage === 'final-documents' && canOpenDocuments.value
    ? 'documents'
    : 'booking'

  eTicket.value = null
  hotelReservation.value = null
})

watch(canOpenDocuments, allowed => {
  if (!allowed && activeTab.value === 'documents') {
    activeTab.value = 'booking'
  }
})

const setAirplaneFile = event => { bookingItems.airplane_attachment = event.target.files?.[0] ?? null }
const setAccommodationFile = event => { bookingItems.accommodation_attachment = event.target.files?.[0] ?? null }
const setETicketFile = event => { eTicket.value = event.target.files?.[0] ?? null }
const setHotelReservationFile = event => { hotelReservation.value = event.target.files?.[0] ?? null }

function submitBooking() {
  emit('submit-booking', { adjustTime: { ...adjustTime }, bookingItems: { ...bookingItems } })
}

function submitETicket() {
  if (props.processing) return
  if (isTicketBookedByCompany.value && !eTicket.value) return
  if (isAccommodationBookedByCompany.value && !hotelReservation.value) return

  emit('submit-eticket', {
    eTicket: isTicketBookedByCompany.value ? eTicket.value : null,
    hotelReservation: isAccommodationBookedByCompany.value ? hotelReservation.value : null,
  })
}
</script>

<template>
  <VDialog :model-value="modelValue" :max-width="dialogWidth" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <VCardItem class="px-4 py-2">
        <VCardTitle class="pa-0 text-subtitle-1 font-weight-bold">{{ title }}</VCardTitle>
        <template #append>
          <VBtn icon="ri-close-line" size="small" variant="text" :disabled="processing" @click="emit('cancel')" />
        </template>
      </VCardItem>

      <VDivider />
      <VProgressLinear v-if="loading" indeterminate />
      <VTabs v-model="activeTab" color="primary" grow>
  <VTab value="booking">Step 1 — Booking Ticket</VTab>
  <VTab value="documents" :disabled="!canOpenDocuments">
    Step 2 — Final Travel Documents
  </VTab>
</VTabs>
      <VDivider />

      <VWindow v-model="activeTab">
        <!-- Booking Ticket -->
        <VWindowItem value="booking">
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
                      <VTextField v-model="adjustTime.departure_date" type="date" density="comfortable" hide-details :disabled="isReadOnly" />
                      <VTextField v-model="adjustTime.departure_time" type="time" density="comfortable" hide-details :disabled="isReadOnly" />
                    </div>
                  </VCol>
                  <VCol cols="12" md="6">
                    <div class="text-caption font-weight-medium mb-1">Return</div>
                    <div class="d-flex ga-3">
                      <VTextField v-model="adjustTime.return_date" type="date" density="comfortable" hide-details :disabled="isReadOnly" />
                      <VTextField v-model="adjustTime.return_time" type="time" density="comfortable" hide-details :disabled="isReadOnly" />
                    </div>
                  </VCol>
                </VRow>
              </VCardText>
            </VCard>

            <VCard v-if="showBookingInformation" rounded="lg" elevation="1" class="mb-4">
              <div class="bg-grey-lighten-4 border-b px-4 py-3 text-subtitle-2 font-weight-bold">Booking Information</div>
              <VDivider />

              <VCardText class="pa-4">
                <VRow dense>
                  <VCol v-if="isTicketBookedByCompany" cols="12" md="6">
                    <VCard flat border rounded="lg">
                      <VCardItem class="py-3">
                        <VCardTitle class="text-subtitle-2 font-weight-bold">
                          {{ order.ticket_type === 'Ferry' ? 'Ferry Ticket' : 'Airplane Ticket' }}
                        </VCardTitle>
                      </VCardItem>

                      <VCardText class="pt-0">
                        <div class="text-caption font-weight-medium mb-1">Booking Amount</div>
                        <div class="d-flex ga-2 mb-4">
                          <VSelect v-model="bookingItems.airplane_currency" :items="bookingCurrencyItems" variant="outlined" density="comfortable" hide-details disabled style="max-width: 95px" />
                          <VTextField :model-value="formatAmount(bookingItems.airplane_amount)" variant="outlined" density="comfortable" hide-details inputmode="numeric" class="text-right-input" :disabled="isReadOnly" @update:model-value="value => bookingItems.airplane_amount = parseAmount(value)" />
                        </div>

                        <div class="text-caption font-weight-medium mb-1">
                          Quotation / Booking Attachment <span v-if="!isReadOnly" class="text-error"></span>
                        </div>
                        <div class="d-flex align-center ga-2">
                          <input type="file" accept=".pdf,.jpg,.jpeg,.png" :disabled="isReadOnly" @change="setAirplaneFile" />
                          <span v-if="savedAirplaneAttachment" class="text-body-2">
                            <VIcon icon="ri-attachment-line" size="small" /> {{ savedAirplaneAttachment }}
                          </span>
                        </div>
                      </VCardText>
                    </VCard>
                  </VCol>

                  <VCol v-if="isAccommodationBookedByCompany" cols="12" md="6">
                    <VCard flat border rounded="lg">
                      <VCardItem class="py-3">
                        <VCardTitle class="text-subtitle-2 font-weight-bold">Accommodation</VCardTitle>
                      </VCardItem>

                      <VCardText class="pt-0">
                        <div class="text-caption font-weight-medium mb-1">Booking Amount</div>
                        <div class="d-flex ga-2 mb-4">
                          <VSelect v-model="bookingItems.accommodation_currency" :items="bookingCurrencyItems" variant="outlined" density="comfortable" hide-details disabled style="max-width: 95px" />
                          <VTextField :model-value="formatAmount(bookingItems.accommodation_amount)" variant="outlined" density="comfortable" hide-details inputmode="numeric" class="text-right-input" :readonly="isReadOnly" @update:model-value="value => bookingItems.accommodation_amount = parseAmount(value)" />
                        </div>

                        <div class="text-caption font-weight-medium mb-1">
                          Quotation / Booking Attachment <span v-if="!isReadOnly" class="text-error"></span>
                        </div>
                        <div class="d-flex align-center ga-2">
                          <input type="file" accept=".pdf,.jpg,.jpeg,.png" :disabled="isReadOnly" @change="setAccommodationFile" />
                          <span v-if="savedAccommodationAttachment" class="text-body-2">
                            <VIcon icon="ri-attachment-line" size="small" /> {{ savedAccommodationAttachment }}
                          </span>
                        </div>
                      </VCardText>
                    </VCard>
                  </VCol>
                </VRow>
              </VCardText>
            </VCard>

            <VAlert v-if="!isBookedByCompany" type="info" variant="tonal" density="comfortable">
              No company booking information is required for this arrangement. You can continue to Final Travel Documents.
            </VAlert>
          </VCardText>

          <VCardText v-else-if="!loading" class="pa-6 text-center text-medium-emphasis">
            Data is not available.
          </VCardText>

          <VDivider />
          <VCardActions class="px-6 py-3 justify-end ga-2">
            <VBtn v-if="!isCompleted && !isBookingSaved" color="warning" variant="flat" :disabled="processing" @click="emit('revision')"> Revision </VBtn>
  <VBtn v-if="!isCompleted" color="success" variant="flat" :loading="processing" :disabled="processing || loading || isBookingSaved || !order || (isTicketBookedByCompany && stage === 'booking' && bookingItems.airplane_amount <= 0) || (isAccommodationBookedByCompany && stage === 'booking' && bookingItems.accommodation_amount <= 0) || (isTicketBookedByCompany && stage === 'booking' && !savedBooking && !bookingItems.airplane_attachment) || (isAccommodationBookedByCompany && stage === 'booking' && !savedBooking && !bookingItems.accommodation_attachment)" @click="submitBooking">Booking</VBtn>
</VCardActions>
        </VWindowItem>

        <!-- Final Travel Documents -->
        <VWindowItem value="documents">
          <VCardText class="bg-grey-lighten-5 pa-5" style="min-height: 150px">
            <VRow dense>
              <VCol v-if="isTicketBookedByCompany" cols="12">
                <VCard rounded="lg" elevation="1">
                  <div class="bg-grey-lighten-4 border-b px-4 py-3 text-subtitle-2 font-weight-bold">Final E-Ticket</div>
                  <VDivider />
                  <VCardText class="pa-4">
                    <div class="text-caption font-weight-medium mb-1">
                      Final Document <span v-if="!isCompleted" class="text-error"></span>
                    </div>
                    <input type="file" accept=".pdf,.jpg,.jpeg,.png" :disabled="processing || isCompleted" @change="setETicketFile" />
                  </VCardText>
                </VCard>
              </VCol>

              <VCol v-if="isAccommodationBookedByCompany" cols="12">
                <VCard rounded="lg" elevation="1">
                  <div class="bg-grey-lighten-4 border-b px-4 py-3 text-subtitle-2 font-weight-bold">Final Hotel Reservation</div>
                  <VDivider />
                  <VCardText class="pa-4">
                    <div class="text-caption font-weight-medium mb-1">
                      Final Document <span v-if="!isCompleted" class="text-error"></span>
                    </div>
                    <input type="file" accept=".pdf,.jpg,.jpeg,.png" :disabled="processing || isCompleted" @change="setHotelReservationFile" />
                  </VCardText>
                </VCard>
              </VCol>

              <VCol v-if="!isBookedByCompany" cols="12">
                <VAlert type="info" variant="tonal" density="comfortable">
                  No final booking documents are required because the ticket and accommodation are not booked by the company.
                </VAlert>
              </VCol>
            </VRow>
          </VCardText>

          <VDivider />
          <VCardActions class="px-6 py-3 justify-end ga-2">
            <VBtn variant="tonal" :disabled="processing" @click="emit('cancel')">Cancel</VBtn>
            <VBtn
              v-if="!isCompleted"
              color="success"
              variant="flat"
              :loading="processing"
              :disabled="processing || (!eTicket && !hotelReservation) ||
                (isTicketBookedByCompany && !eTicket) ||
                (isAccommodationBookedByCompany && !hotelReservation)"
              @click="submitETicket"
            >Complete GMO Processing</VBtn>
          </VCardActions>
        </VWindowItem>
      </VWindow>
    </VCard>
  </VDialog>
</template>

<style scoped>
.text-right-input :deep(input) { text-align: right; }
</style>