
<script setup>
import { computed, ref, reactive, watch } from 'vue'

const props = defineProps({
  modelValue: Boolean, title: String, status: String,
  mode: { type: String, default: 'processing' },
  order: { type: Object, default: null },
  pdfUrl: { type: String, default: '' },
  savedBooking: { type: Object, default: null },
  loading: Boolean, processing: Boolean,
})

const emit = defineEmits(['update:modelValue', 'cancel', 'continue', 'complete', 'revision', 'reject', 'submit-booking', 'submit-eticket', 'send-email'])

const step = ref(1)
const agreed = ref(false)
const pdfReady = ref(false)
const eTicket = ref(null)
const hotelReservation = ref(null)
const emailSent = ref(false)
const emailForm = reactive({ to: '', cc: '', subject: '', message: '' })
const adjustTime = reactive({ departure_date: '', departure_time: '', return_date: '', return_time: '' })
const bookingItems = reactive({ ticket_currency: 'IDR', ticket_amount: 0, accommodation_currency: 'IDR',accommodation_amount: 0,})

const isCompleted = computed(() => props.status === 'processed_by_gmo')
const isDirectPayment = computed(() => props.mode === 'direct-payment')
const isBookedByCompany = computed(() => props.mode === 'booked-by-company')
const isReview = computed(() => props.mode === 'review' || isBookedByCompany.value)
const isTicketBookedByCompany = computed(() => props.order?.ticket?.arrangement === 'Booked by Company' || props.order?.ticket_arrangement === 'Booked by Company')
const isAccommodationBookedByCompany = computed(() => props.order?.accommodation?.arrangement === 'Booked by Company' || props.order?.accommodation_arrangement === 'Booked by Company')
const bookingCurrencyItems = computed(() => ['domestic', 'indonesia'].includes(String(props.order?.travel_region || '').toLowerCase()) ? ['IDR'] : String(props.order?.travel_region || '').toLowerCase() === 'singapore' ? ['SGD'] : ['USD', 'EUR', 'MYR', 'JPY'])
const savedBooking = computed(() => props.savedBooking || props.order?.booking || null)
const savedETicket = computed(() => savedBooking.value?.e_ticket_name || savedBooking.value?.e_ticket_path?.split('/').pop() || '')
const savedHotelReservation = computed(() => savedBooking.value?.hotel_reservation_name || savedBooking.value?.hotel_reservation_path?.split('/').pop() || '')
const savedAirplaneAttachment = computed(() => savedBooking.value?.airplane_attachment_name || '')
const savedAccommodationAttachment = computed(() => savedBooking.value?.accommodation_attachment_name || '')
const emailButtonLabel = computed(() => emailSent.value ? 'Resend Email' : 'Send Email')
const canContinue = computed(() => agreed.value && pdfReady.value && !props.loading && !props.processing)
const canComplete = computed(() => {
  const emailValid = emailForm.to.trim() && emailForm.subject.trim() && emailForm.message.trim()
  const ticketValid = !isTicketBookedByCompany.value || (Number(bookingItems.ticket_amount) > 0 && (eTicket.value || savedETicket.value))
  const accommodationValid = !isAccommodationBookedByCompany.value || (Number(bookingItems.accommodation_amount) > 0 && (hotelReservation.value || savedHotelReservation.value))

  return emailValid && ticketValid && accommodationValid && !props.loading && !props.processing
})

const formatDate = value => value ? new Date(value).toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' }) : ''
const normalizeTime = value => { const match = value && String(value).match(/^(\d{2}):(\d{2})/); return match ? `${match[1]}:${match[2]}` : '' }
const formatAmount = value => value == null || value === '' ? '' : Number(value).toLocaleString('id-ID')
const parseAmount = value => { const digits = String(value ?? '').replace(/\D/g, ''); return digits ? Number(digits) : 0 }

function loadBookingData() {
  const order = props.order
  if (!order) return
  const saved = savedBooking.value

  Object.assign(adjustTime, {
    departure_date: formatDate(saved?.departure_date || order.departure_date),
    departure_time: normalizeTime(saved?.departure_time || order.departure_time),
    return_date: formatDate(saved?.return_date || order.return_date),
    return_time: normalizeTime(saved?.return_time || order.return_time),
  })

  Object.assign(bookingItems, {
  ticket_currency: saved?.ticket_currency || bookingCurrencyItems.value[0] || 'IDR',
  ticket_amount: saved?.ticket_amount ?? 0,
  accommodation_currency: saved?.accommodation_currency || bookingCurrencyItems.value[0] || 'IDR',
  accommodation_amount: saved?.accommodation_amount ?? 0,
})
}

function resetModal() {
  step.value = 1
  agreed.value = false
  pdfReady.value = false
  eTicket.value = null
  hotelReservation.value = null
  emailSent.value = false
  emailForm.to = ''
  emailForm.cc = ''
  emailForm.subject = ''
  emailForm.message = ''
  loadBookingData()
}

function continueToBooking() {
  if (!canContinue.value) return
  if (isDirectPayment.value) {
    emit('complete')
    return
  }
  step.value = 2
}

function submitBooking() {
  emit('submit-booking', { adjustTime: { ...adjustTime }, bookingItems: { ...bookingItems } })
}

function submitETicket() {
  if (props.processing) return
  if (isTicketBookedByCompany.value && !eTicket.value && !savedETicket.value) return
  if (isAccommodationBookedByCompany.value && !hotelReservation.value && !savedHotelReservation.value) return
  emit('submit-eticket', { eTicket: eTicket.value, hotelReservation: hotelReservation.value })
}

function sendEmail() {
  if (!emailForm.to || !emailForm.subject || !emailForm.message || props.processing) return
  emit('send-email', { ...emailForm })
  emailSent.value = true
}

function completeProcessing() {
  if (props.processing || !canComplete.value) return

  emit('complete', {
    adjustTime: { ...adjustTime },
    bookingItems: { ...bookingItems },
    eTicket: eTicket.value,
    hotelReservation: hotelReservation.value,
    emailForm: { ...emailForm },
  })
}

watch(() => [props.order, props.savedBooking], loadBookingData, { immediate: true, deep: true })
watch(() => props.modelValue, open => { if (open) resetModal() })
watch(() => [props.pdfUrl, props.mode, props.status], () => {
  pdfReady.value = false
  agreed.value = false
  step.value = 1
})
watch(bookingCurrencyItems, items => {
  if (!savedBooking.value) {
    bookingItems.ticket_currency = items[0] || 'IDR'
    bookingItems.accommodation_currency = items[0] || 'IDR'
  }
}, { immediate: true })
</script>

<template>
  <VDialog :model-value="modelValue" max-width="1100" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <VCardItem class="px-4 py-2">
        <template #prepend>
          <VBtn v-if="step === 2 && isBookedByCompany" icon="ri-arrow-left-line" size="small" variant="text" :disabled="processing" @click="step = 1" />
        </template>
        <VCardTitle class="pa-0 text-subtitle-1 font-weight-bold">{{ step === 1 ? title : 'Booking & GMO Processing' }}</VCardTitle>
        <template #append>
          <VBtn icon="ri-close-line" size="small" variant="text" :disabled="processing" @click="emit('cancel')" />
        </template>
      </VCardItem>
      <VDivider />

      <template v-if="step === 1">
        <VCardText class="bg-grey-lighten-3 pa-0">
          <VProgressLinear v-if="loading || (pdfUrl && !pdfReady)" indeterminate />
          <iframe v-if="pdfUrl" :key="pdfUrl" :src="pdfUrl" title="Travel Order PDF" :height="isDirectPayment ? 750 : 600" class="d-block w-100 border-0" @load="pdfReady = true" />
          <div v-else-if="!loading" class="pa-6 text-center text-medium-emphasis">PDF is not available.</div>
        </VCardText>

        <template v-if="pdfUrl && pdfReady && !loading && !isCompleted && mode !== 'view-only'">
  <VDivider />
  <VCardText class="px-6 py-4">
    <div class="d-flex align-start ga-3">
      <input
        v-model="agreed"
        type="checkbox"
        class="mt-1 flex-shrink-0"
        :disabled="processing"
      />
      <div class="text-body-2">
        {{ isDirectPayment
          ? 'I confirm that all required GMO processing is complete.'
          : 'I confirm that I have reviewed the Travel Order document.'
        }}
      </div>
    </div>
  </VCardText>
  <VCardActions v-if="agreed" class="px-6 pb-5 pt-0 justify-end ga-2">
    <VBtn color="error" variant="flat" :disabled="processing" @click="emit('reject')">Cancel</VBtn>
    <VBtn color="warning" variant="flat" :disabled="processing" @click="emit('revision')">Revision</VBtn>
    <VBtn color="primary" variant="flat" :loading="processing" :disabled="!canContinue" @click="continueToBooking">
      {{ isDirectPayment ? 'Complete GMO Processing' : 'Continue' }}
    </VBtn>
  </VCardActions>
</template>
      </template>

      <template v-else-if="step === 2 && isBookedByCompany">
        <VCardText class="bg-grey-lighten-5 pa-5">
          <VCard rounded="lg" elevation="1" class="mb-4">
            <div class="bg-grey-lighten-4 px-4 py-3 text-subtitle-2 font-weight-bold">Adjust Time</div>
            <VDivider />
            <VCardText class="pa-4">
              <VRow dense>
                <VCol cols="12" md="6">
                  <div class="text-body-2 font-weight-medium mb-2">Departure</div>
                  <div class="d-flex ga-3">
                    <VTextField v-model="adjustTime.departure_date" type="date" density="comfortable" hide-details :disabled="processing" />
                    <VTextField v-model="adjustTime.departure_time" type="time" density="comfortable" hide-details :disabled="processing" />
                  </div>
                </VCol>
                <VCol cols="12" md="6">
                  <div class="text-body-2 font-weight-medium mb-2">Return</div>
                  <div class="d-flex ga-3">
                    <VTextField v-model="adjustTime.return_date" type="date" density="comfortable" hide-details :disabled="processing" />
                    <VTextField v-model="adjustTime.return_time" type="time" density="comfortable" hide-details :disabled="processing" />
                  </div>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>

          <VCard rounded="lg" elevation="1" class="mb-4">
            <div class="bg-grey-lighten-4 px-4 py-3 text-subtitle-2 font-weight-bold">Booking Items</div>
            <VDivider />
            <VCardText class="pa-4">
              <VRow dense>
                <VCol v-if="isTicketBookedByCompany" cols="12" md="6">
                  <VCard rounded="lg" color="white" variant="flat" class="pa-4 h-100 border">
                    <div class="text-subtitle-2 font-weight-bold mb-4">{{ order?.ticket_type === 'Ferry' ? 'Ferry Ticket' : 'Airplane Ticket' }}</div>
                    <div class="text-body-2 font-weight-medium mb-1">Booking Amount </div>
                    <div class="d-flex ga-2 mb-4">
                      <VSelect v-model="bookingItems.ticket_currency" :items="bookingCurrencyItems" density="comfortable" hide-details disabled style="max-width: 110px" />
                      <VTextField :model-value="formatAmount(bookingItems.ticket_amount)" density="comfortable" hide-details inputmode="numeric" :disabled="processing" class="amount-field" @update:model-value="v => bookingItems.ticket_amount = parseAmount(v)" />
                    </div>
                    <div class="text-body-2 font-weight-medium mb-2">E-Ticket / Travel Document </div>
                    <input type="file" accept=".pdf,.jpg,.jpeg,.png" :disabled="processing" @change="eTicket = $event.target.files?.[0] || null" />
                  </VCard>
                </VCol>

                <VCol v-if="isAccommodationBookedByCompany" cols="12" md="6">
                  <VCard rounded="lg" color="white" variant="flat" class="pa-4 h-100 border">
                    <div class="text-subtitle-2 font-weight-bold mb-4">Accommodation</div>
                    <div class="text-body-2 font-weight-medium mb-1">Booking Amount </div>
                    <div class="d-flex ga-2 mb-4">
                      <VSelect v-model="bookingItems.accommodation_currency" :items="bookingCurrencyItems" density="comfortable" hide-details disabled style="max-width: 110px" />
                      <VTextField :model-value="formatAmount(bookingItems.accommodation_amount)" density="comfortable" hide-details inputmode="numeric" :disabled="processing" class="amount-field" @update:model-value="v => bookingItems.accommodation_amount = parseAmount(v)" />
                    </div>
                    <div class="text-body-2 font-weight-medium mb-2">Accommodation / Hotel Document </div>
                    <input type="file" accept=".pdf,.jpg,.jpeg,.png" :disabled="processing" @change="hotelReservation = $event.target.files?.[0] || null" />
                  </VCard>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>

          
<VCard rounded="lg" elevation="1">
  <div class="bg-grey-lighten-4 px-4 py-3 text-subtitle-2 font-weight-bold">Employee Email</div>
  <VDivider />
  <VCardText class="pa-4">
    <VRow dense>
      <VCol cols="12" md="6">
        <div class="text-body-2 font-weight-medium mb-2">To </div>
        <VTextField v-model="emailForm.to" type="email" placeholder="Enter recipient email" density="comfortable" hide-details :disabled="processing" />
      </VCol>
      <VCol cols="12" md="6">
        <div class="text-body-2 font-weight-medium mb-2">CC</div>
        <VTextField v-model="emailForm.cc" placeholder="Enter CC email" density="comfortable" hide-details :disabled="processing" />
      </VCol>
      <VCol cols="12">
        <div class="text-body-2 font-weight-medium mb-2">Subject </div>
        <VTextField v-model="emailForm.subject" placeholder="Enter email subject" density="comfortable" hide-details :disabled="processing" />
      </VCol>
      <VCol cols="12">
        <div class="text-body-2 font-weight-medium mb-2">Message </div>
        <VTextarea v-model="emailForm.message" placeholder="Enter email message" rows="4" density="comfortable" hide-details :disabled="processing" />
      </VCol>
    </VRow>
  </VCardText>
</VCard>
        </VCardText>

        <VDivider />
        <VCardActions class="px-5 pb-5 justify-end">
  <VBtn color="primary" variant="flat" :loading="processing" :disabled="!canComplete" @click="completeProcessing">
    Complete GMO Processing
  </VBtn>
</VCardActions>
      </template>
    </VCard>
  </VDialog>
</template>

<style scoped>
:deep(input) { max-width: 100%;}
.amount-field :deep(input) { text-align: right;}
</style>
