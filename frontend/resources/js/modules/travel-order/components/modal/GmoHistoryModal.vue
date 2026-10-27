<script>
const scheduleCache = {}
</script>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useConfirm } from '../../../../composables/useConfirm'

const { confirm } = useConfirm()

const props = defineProps({
  modelValue: Boolean,
  title: { type: String, default: 'GMO Processing History' },
  order: { type: Object, default: null },
  savedBooking: { type: Object, default: null },
  savedEmail: { type: [Object, Array], default: null },
  loading: Boolean,
  processing: Boolean,
})

const emit = defineEmits(['update:modelValue', 'close', 'resend-email', 'cancel-ticket', 'cancel-accommodation', 'view-pdf', 'update-schedule', 'update-booking', 'update-refund', 'save-refund'])

const order = computed(() => props.order || {})
const booking = computed(() => props.savedBooking || order.value.booking || {})
const ticket = computed(() => order.value.ticket || {})
const accommodation = computed(() => order.value.accommodation || {})
const advance = computed(() => order.value.advance || {})
const isGroupTrip = computed(() => order.value.trip_type === 'group')
const groupMembers = computed(() => (order.value.group?.members || [])
  .filter(member => String(member.user_id ?? member.user?.id) !== String(order.value.user_id))
  .map(member => ({
    key: member.id ?? member.user_id,
    name: member.user?.name || member.name || '-',
    department: member.department_name || member.user?.department?.name || '-',
  })))

const currencyOptions = ['IDR', 'SGD', 'USD', 'EUR', 'MYR', 'JPY']
const noDecimalCurrencies = ['IDR', 'JPY']
const refundStatusOptions = ['Fully Refund', 'Partially Refunded', 'Pending Refund', 'No Refund']

function displayValue(value) {
  return value === null || value === undefined || value === '' ? '-' : value
}

function formatDate(value) {
  if (!value) return '-'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'Asia/Jakarta' })
}

function toLocalDateInput(value) {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return String(value).slice(0, 10)

  const parts = new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Jakarta', year: 'numeric', month: '2-digit', day: '2-digit' }).formatToParts(date)
  const get = type => parts.find(part => part.type === type)?.value

  return `${get('year')}-${get('month')}-${get('day')}`
}

function formatEmailDate(value) {
  if (!value) return '-'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Jakarta' })
}

function formatAmount(value) {
  if (value === '' || value == null) return '-'
  const number = Number(value)
  return Number.isNaN(number) ? value : number.toLocaleString('id-ID')
}

function toDisplayAmount(raw, currency) {
  if (raw === '' || raw == null) return ''
  const [intPart = '', decPart = ''] = String(raw).split('.')
  const grouped = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.')
  if (noDecimalCurrencies.includes(currency) || !decPart) return grouped
  return `${grouped},${decPart.slice(0, 2)}`
}

function toRawAmount(text, currency) {
  const allowDecimal = !noDecimalCurrencies.includes(currency)
  const cleaned = String(text || '').replace(/[^\d,]/g, '')
  const [intRaw = '', ...rest] = cleaned.split(',')
  const intPart = intRaw.replace(/^0+(?=\d)/, '')
  if (allowDecimal && cleaned.includes(',')) return `${intPart || '0'}.${rest.join('').slice(0, 2)}`
  return intPart
}

function applyAmountInput(event, currency, assign) {
  const raw = toRawAmount(event.target.value, currency)
  assign(raw)
  const display = toDisplayAmount(raw, currency)
  const keepComma = !noDecimalCurrencies.includes(currency) && /,$/.test(event.target.value) && !display.includes(',')
  event.target.value = keepComma ? `${display},` : display
}

function asText(value) {
  if (Array.isArray(value)) return value.join(', ')
  if (typeof value !== 'string') return ''
  const trimmed = value.trim()
  if (!trimmed.startsWith('[')) return trimmed
  try {
    const parsed = JSON.parse(trimmed)
    return Array.isArray(parsed) ? parsed.join(', ') : trimmed
  } catch {
    return trimmed
  }
}

function normalizeEmail(raw) {
  if (!raw || typeof raw !== 'object') return null
  const to = asText(raw.to || raw.recipient)
  const cc = asText(raw.cc)
  const subject = raw.subject || ''
  const message = raw.message || raw.body || ''
  if (!to && !subject && !message) return null
  return { id: raw.id, to, cc, subject, message, status: raw.status || '', sent_at: raw.sent_at || null, created_at: raw.created_at || null }
}

const emailHistory = computed(() => {
  const candidates = [...(Array.isArray(props.savedEmail) ? props.savedEmail : props.savedEmail ? [props.savedEmail] : []), ...(Array.isArray(order.value.emails) ? order.value.emails : [])]
  const unique = new Map()

  for (const raw of candidates) {
    const item = normalizeEmail(raw)
    if (!item) continue

    const key = item.id ?? [item.to, item.cc, item.subject, item.message, item.sent_at, item.created_at].join('|')

    if (!unique.has(key)) unique.set(key, item)
  }

  return [...unique.values()].sort((a, b) => new Date(b.sent_at || b.created_at || 0) - new Date(a.sent_at || a.created_at || 0))
})

const email = reactive({ to: '', cc: '', subject: '', message: '', status: '', sent_at: null })

watch(() => emailHistory.value[0], latest => {
  Object.assign(email, { to: latest?.to || '', cc: latest?.cc || '', subject: latest?.subject || '', message: latest?.message || '', status: latest?.status || '', sent_at: latest?.sent_at || null })
}, { immediate: true })

async function resendEmail() {
  if (props.processing || props.loading || !email.to.trim() || !email.subject.trim() || !email.message.trim()) return

  const ok = await confirm({ title: 'Resend Booking Email', message: 'Are you sure you want to resend the booking confirmation email?' })

  if (!ok) return

  emit('resend-email', { orderId: order.value.id, to: email.to, cc: email.cc, subject: email.subject, message: email.message })
}

const isTicketBooked = computed(() => String(ticket.value.arrangement || order.value.ticket_arrangement || '').trim().toLowerCase() === 'booked by company')
const isAccommodationBooked = computed(() => String(accommodation.value.arrangement || order.value.accommodation_arrangement || '').trim().toLowerCase() === 'booked by company')
const isTicketCancelled = computed(() => Boolean(booking.value.ticket_cancelled))
const isAccommodationCancelled = computed(() => Boolean(booking.value.accommodation_cancelled))
const canCancelTicket = computed(() => isTicketBooked.value && !isTicketCancelled.value)
const canCancelAccommodation = computed(() => isAccommodationBooked.value && !isAccommodationCancelled.value)
const isCancelled = computed(() => order.value.status === 'cancelled' || isTicketCancelled.value || isAccommodationCancelled.value)

const cancellations = computed(() => order.value.booking_cancellations || [])

function cancellationInfo(itemType) {
  const item = cancellations.value.find(row => row.item_type === itemType)

  if (!item) return null

  const amount = value => `${item.currency} ${formatAmount(value)}`

  return [
    { label: 'Cancellation Status', value: 'Completed' },
    { label: 'Refund Status', value: item.refund_status },
    { label: itemType === 'ticket' ? 'Original Ticket Amount' : 'Original Accommodation Amount', value: amount(item.original_amount) },
    { label: 'Refund Amount', value: amount(item.refund_amount) },
    { label: 'Non-Refundable Amount', value: amount(item.non_refundable_amount) },
    { label: 'Cancelled By', value: item.processor?.name },
    { label: 'Cancellation Date', value: formatEmailDate(item.created_at).replace(',', '') },
    { label: 'Refund Confirmation', value: '', file: item.confirmation_file },
    { label: 'Cancellation Reason', value: item.reason, cols: 12 },
  ]
}

const ticketCancellation = computed(() => cancellationInfo('ticket'))
const accommodationCancellation = computed(() => cancellationInfo('accommodation'))
function fileUrl(path) {
  return `${import.meta.env.VITE_STORAGE_URL}/${path}`
}

const previewDialog = ref(false)
const previewUrl = ref('')
const previewTitle = ref('')
const isPreviewImage = computed(() => /\.(jpe?g|png)$/i.test(previewUrl.value))

function openRefundFile(path) {
  previewUrl.value = fileUrl(path)
  console.log('REFUND FILE URL:', previewUrl.value)
  previewDialog.value = true
}
const editingRefundType = ref(null)

const refundEditForm = reactive({
  reason: '',
  refundStatus: 'Pending Refund',
  refundAmount: '',
  confirmationFile: null,
})

function editRefund(itemType) {
  const item = cancellations.value.find(
    row => row.item_type?.toLowerCase() === itemType.toLowerCase(),
  )

  if (!item) {
    console.warn('Cancellation data not found:', {
      itemType,
      cancellations: cancellations.value,
    })
    return
  }

  Object.assign(refundEditForm, {
    reason: item.reason || '',
    refundStatus: item.refund_status || 'Pending Refund',
    refundAmount: String(item.refund_amount ?? 0),
    confirmationFile: null,
  })

  editingRefundType.value = itemType
}

function cancelEditRefund() {
  editingRefundType.value = null

  Object.assign(refundEditForm, {
    reason: '',
    refundStatus: 'Pending Refund',
    refundAmount: '',
    confirmationFile: null,
  })
}

const refundEditOriginalAmount = computed(() => {
  const item = cancellations.value.find(
    row => row.item_type === editingRefundType.value,
  )

  return Number(item?.original_amount) || 0
})

const refundEditAmount = computed(() => Math.min(
  Math.max(Number(refundEditForm.refundAmount) || 0, 0),
  refundEditOriginalAmount.value,
))

const refundEditNonRefundable = computed(() =>
  refundEditOriginalAmount.value - refundEditAmount.value,
)

const refundEditCurrency = computed(() => {
  const item = cancellations.value.find(
    row => row.item_type === editingRefundType.value,
  )

  return item?.currency || (
    editingRefundType.value === 'accommodation'
      ? accommodationCurrency.value
      : ticketCurrency.value
  )
})

const refundEditOriginalAmountText = computed(
  () => `${refundEditCurrency.value} ${formatAmount(refundEditOriginalAmount.value)}`,
)

const refundEditNonRefundableText = computed(
  () => `${refundEditCurrency.value} ${formatAmount(refundEditNonRefundable.value)}`,
)

const refundEditAmountText = computed(
  () => toDisplayAmount(refundEditForm.refundAmount, refundEditCurrency.value),
)

function onRefundEditAmountInput(event) {
  applyAmountInput(event, refundEditCurrency.value, raw => { refundEditForm.refundAmount = raw })
}

function saveRefund() {
  if (
    props.processing ||
    !editingRefundType.value ||
    !refundEditForm.refundStatus
  ) return

  emit('save-refund', {
    orderId: order.value.id,
    itemType: editingRefundType.value,
    reason: refundEditForm.reason.trim(),
    refundStatus: refundEditForm.refundStatus,
    refundAmount: refundEditAmount.value,
    nonRefundableAmount: refundEditNonRefundable.value,
    confirmationFile: refundEditForm.confirmationFile,
  })
}

watch(() => order.value.booking_cancellations, () => {
  if (editingRefundType.value) cancelEditRefund()
})

const ticketType = computed(() => ticket.value.ticket_type || order.value.ticket_type || '-')
const ticketArrangement = computed(() => ticket.value.arrangement || order.value.ticket_arrangement || '-')
const accommodationArrangement = computed(() => accommodation.value.arrangement || order.value.accommodation_arrangement || '-')
const ticketPaymentMethod = computed(() => ticket.value.payment_method || order.value.ticket_payment_method || '-')
const accommodationPaymentMethod = computed(() => accommodation.value.payment_method || order.value.accommodation_payment_method || '-')

function isDirectPayment(value) {
  return String(value || '').trim().toLowerCase() === 'direct payment'
}

const ticketReimbursable = computed(() => {
  const arrangement = String(ticket.value?.arrangement || order.value?.ticket_arrangement || '').trim().toLowerCase()

  return arrangement === 'direct payment' || arrangement === 'reimbursable'
})

const accommodationReimbursable = computed(() => {
  const arrangement = String(accommodation.value?.arrangement || order.value?.accommodation_arrangement || '').trim().toLowerCase()

  return arrangement === 'direct payment' || arrangement === 'reimbursable'
})

const hasTicketInfo = computed(() => ticketArrangement.value !== '-' || ticketPaymentMethod.value !== '-')
const hasAccommodationInfo = computed(() => accommodationArrangement.value !== '-' || accommodationPaymentMethod.value !== '-')
const ticketAmount = computed(() => booking.value.ticket_amount ?? '')
const accommodationAmount = computed(() => booking.value.accommodation_amount ?? '')
const ticketCurrency = computed(() => booking.value.ticket_currency || 'IDR')
const accommodationCurrency = computed(() => booking.value.accommodation_currency || 'IDR')

const scheduleOverride = ref(null)
const srcDepartureDate = computed(() => order.value.departure_date || booking.value.departure_date || '')
const srcDepartureTime = computed(() => order.value.departure_time || booking.value.departure_time || '')
const srcReturnDate = computed(() => order.value.return_date || booking.value.return_date || '')
const srcReturnTime = computed(() => order.value.return_time || booking.value.return_time || '')
const departureDate = computed(() => scheduleOverride.value?.departure_date ?? srcDepartureDate.value)
const departureTime = computed(() => scheduleOverride.value?.departure_time ?? srcDepartureTime.value)
const returnDate = computed(() => scheduleOverride.value?.return_date ?? srcReturnDate.value)
const returnTime = computed(() => scheduleOverride.value?.return_time ?? srcReturnTime.value)

const editingSchedule = ref(false)
const editingBooking = ref(false)
const scheduleForm = reactive({ departure_date: '', departure_time: '', return_date: '', return_time: '' })
const bookingForm = reactive({ ticket_amount: '', ticket_currency: 'IDR', accommodation_amount: '', accommodation_currency: 'IDR', e_ticket: null, hotel_reservation: null, airplane_attachment: null, accommodation_attachment: null })

function currentScheduleValues() {
  return {
    departure_date: toLocalDateInput(departureDate.value), departure_time: String(departureTime.value || '').slice(0, 5),
    return_date: toLocalDateInput(returnDate.value), return_time: String(returnTime.value || '').slice(0, 5),
  }
}

function syncBookingForm() {
  Object.assign(bookingForm, {
    ticket_amount: ticketAmount.value === null ? '' : String(ticketAmount.value), ticket_currency: ticketCurrency.value,
    accommodation_amount: accommodationAmount.value === null ? '' : String(accommodationAmount.value), accommodation_currency: accommodationCurrency.value,
    e_ticket: null, hotel_reservation: null, airplane_attachment: null, accommodation_attachment: null,
  })
}

function editSchedule() {
  Object.assign(scheduleForm, currentScheduleValues())
  editingSchedule.value = true
}

function cancelSchedule() {
  Object.assign(scheduleForm, currentScheduleValues())
  editingSchedule.value = false
}

function saveSchedule() {
  if (props.processing || !order.value.id || !scheduleForm.departure_date || !scheduleForm.departure_time) return

  emit('update-schedule', { orderId: order.value.id, ...scheduleForm })
}

function editBooking() {
  syncBookingForm()
  editingBooking.value = true
}

function cancelEditBooking() {
  syncBookingForm()
  editingBooking.value = false
}

function saveBooking() {
  const toNumber = value => (value === '' || value == null ? '' : Number(value))
  emit('update-booking', { orderId: order.value.id, ...bookingForm, ticket_amount: toNumber(bookingForm.ticket_amount), accommodation_amount: toNumber(bookingForm.accommodation_amount) })
  editingBooking.value = false
}

const ticketAmountText = computed(() => toDisplayAmount(bookingForm.ticket_amount, bookingForm.ticket_currency))
const accommodationAmountText = computed(() => toDisplayAmount(bookingForm.accommodation_amount, bookingForm.accommodation_currency))

function onAmountInput(kind, event) {
  applyAmountInput(event, bookingForm[`${kind}_currency`], raw => { bookingForm[`${kind}_amount`] = raw })
}

watch(() => order.value.id, () => {
  scheduleOverride.value = scheduleCache[order.value.id] ?? null
  editingSchedule.value = false
  editingBooking.value = false
  syncBookingForm()
  Object.assign(scheduleForm, currentScheduleValues())
}, { immediate: true })

watch([departureDate, departureTime, returnDate, returnTime, () => order.value.id], () => {
  if (editingSchedule.value) return
  Object.assign(scheduleForm, currentScheduleValues())
}, { immediate: true })

watch([ticketAmount, ticketCurrency, accommodationAmount, accommodationCurrency], () => {
  if (!editingBooking.value) syncBookingForm()
})

watch(() => bookingForm.ticket_currency, currency => {
  if (noDecimalCurrencies.includes(currency)) bookingForm.ticket_amount = String(bookingForm.ticket_amount).split('.')[0]
})

watch(() => bookingForm.accommodation_currency, currency => {
  if (noDecimalCurrencies.includes(currency)) bookingForm.accommodation_amount = String(bookingForm.accommodation_amount).split('.')[0]
})

function fileName(originalName, storedName, path) {
  return originalName || storedName || ''
}

const eTicketName = computed(() => fileName(booking.value.e_ticket_original_name, booking.value.e_ticket_name, booking.value.e_ticket_path))
const hotelReservationName = computed(() => fileName(booking.value.hotel_reservation_original_name, booking.value.hotel_reservation_name, booking.value.hotel_reservation_path))

const travelInformation = computed(() => [
  { label: 'Request Type', value: order.value.request_type_label || order.value.request_type || 'Business Trip' },
  { label: 'Name', value: order.value.user?.name || order.value.employee_name || order.value.employee?.name },
  { label: 'Section', value: order.value.section?.name || order.value.section_name },
  { label: 'Department', value: order.value.department?.name || order.value.department_name },
  { label: 'Travel Region', value: order.value.travel_region },
  { label: 'Country', value: order.value.country || (order.value.travel_region === 'Domestic' ? 'Indonesia' : order.value.travel_region === 'Singapore' ? 'Singapore' : '') },
  { label: 'Travel From', value: order.value.travel_from },
  { label: 'Travel To', value: order.value.travel_to },
  { label: 'Departure Date', value: formatDate(departureDate.value) },
  { label: 'Departure Time', value: departureTime.value },
  { label: 'Return Date', value: formatDate(returnDate.value) },
  { label: 'Return Time', value: returnTime.value },
  { label: 'Travelling Purpose', value: order.value.purpose, cols: 6 },
  { label: 'Remarks', value: order.value.remarks, cols: 6 },
])

const showCancelForm = ref(false)
const cancelForm = reactive({ reason: '', refundStatus: 'Pending Refund', partialRefundAmount: '', file: null })
const showAccommodationCancelForm = ref(false)
const accommodationCancelForm = reactive({ reason: '', refundStatus: 'Pending Refund', partialRefundAmount: '', file: null })

const originalTicketNumber = computed(() => Number(ticketAmount.value) || 0)
const isFullRefund = computed(() => ['Full Refund', 'Fully Refund'].includes(cancelForm.refundStatus))
const isPartialRefund = computed(() => ['Partial Refund', 'Partially Refunded'].includes(cancelForm.refundStatus))
const refundAmountNumber = computed(() => {
  if (isFullRefund.value) return originalTicketNumber.value
  if (isPartialRefund.value) return Math.min(Math.max(Number(cancelForm.partialRefundAmount) || 0, 0), originalTicketNumber.value)
  return 0
})
const nonRefundableNumber = computed(() => originalTicketNumber.value - refundAmountNumber.value)
const partialRefundText = computed(() => toDisplayAmount(cancelForm.partialRefundAmount, ticketCurrency.value))

const originalAccommodationNumber = computed(() => Number(accommodationAmount.value) || 0)
const isAccommodationFullRefund = computed(() => ['Full Refund', 'Fully Refund'].includes(accommodationCancelForm.refundStatus))
const isAccommodationPartialRefund = computed(() => ['Partial Refund', 'Partially Refunded'].includes(accommodationCancelForm.refundStatus))
const accommodationRefundAmountNumber = computed(() => {
  if (isAccommodationFullRefund.value) return originalAccommodationNumber.value
  if (isAccommodationPartialRefund.value) return Math.min(Math.max(Number(accommodationCancelForm.partialRefundAmount) || 0, 0), originalAccommodationNumber.value)
  return 0
})
const accommodationNonRefundableNumber = computed(() => originalAccommodationNumber.value - accommodationRefundAmountNumber.value)
const accommodationPartialRefundText = computed(() => toDisplayAmount(accommodationCancelForm.partialRefundAmount, accommodationCurrency.value))
const accommodationSelectedFile = computed(() => Array.isArray(accommodationCancelForm.file) ? accommodationCancelForm.file[0] || null : accommodationCancelForm.file)
const canSubmitAccommodationCancel = computed(() => accommodationCancelForm.reason.trim().length > 0 && !!accommodationCancelForm.refundStatus)
const accommodationRefundConfirmation = computed({ get: () => accommodationSelectedFile.value, set: value => { accommodationCancelForm.file = value } })

const selectedFile = computed(() => (Array.isArray(cancelForm.file) ? cancelForm.file[0] || null : cancelForm.file))
const refundConfirmation = computed({ get: () => selectedFile.value, set: value => { cancelForm.file = value } })
const canSubmitCancel = computed(() => cancelForm.reason.trim().length > 0 && !!cancelForm.refundStatus)

function onPartialRefundInput(event) {
  applyAmountInput(event, ticketCurrency.value, raw => { cancelForm.partialRefundAmount = raw })
}

function discardCancel() {
  Object.assign(cancelForm, { reason: '', refundStatus: 'Pending Refund', partialRefundAmount: '', file: null })
  showCancelForm.value = false
}

function submitCancel() {
  if (props.processing || !canSubmitCancel.value) return
  emit('cancel-ticket', {
    orderId: order.value.id, reason: cancelForm.reason.trim(), refundStatus: cancelForm.refundStatus, currency: ticketCurrency.value,
    originalAmount: originalTicketNumber.value, refundAmount: refundAmountNumber.value, nonRefundableAmount: nonRefundableNumber.value, confirmationFile: selectedFile.value,
  })
}

function onAccommodationPartialRefundInput(event) {
  applyAmountInput(event, accommodationCurrency.value, raw => { accommodationCancelForm.partialRefundAmount = raw })
}

function discardAccommodationCancel() {
  Object.assign(accommodationCancelForm, { reason: '', refundStatus: 'Pending Refund', partialRefundAmount: '', file: null })
  showAccommodationCancelForm.value = false
}

function submitAccommodationCancel() {
  if (props.processing || !canSubmitAccommodationCancel.value) return

  emit('cancel-accommodation', {
    orderId: order.value.id, reason: accommodationCancelForm.reason.trim(), refundStatus: accommodationCancelForm.refundStatus, currency: accommodationCurrency.value,
    originalAmount: originalAccommodationNumber.value, refundAmount: accommodationRefundAmountNumber.value, nonRefundableAmount: accommodationNonRefundableNumber.value, confirmationFile: accommodationSelectedFile.value,
  })
}

function close() {
  previewDialog.value = false
  discardCancel()
  discardCancel()
  discardAccommodationCancel()
  cancelSchedule()
  cancelEditBooking()
  emit('close')
  emit('update:modelValue', false)
}
</script>

<template>
  <VDialog :model-value="modelValue" max-width="1100" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <VCardItem class="px-4 py-2">
        <VCardTitle class="pa-0 text-subtitle-1 font-weight-bold">{{ title }}</VCardTitle>
        <template #append><VBtn icon="ri-close-line" size="small" variant="text" :disabled="processing" @click="close" /></template>
      </VCardItem>
      <VDivider />
      <VProgressLinear v-if="loading" indeterminate color="primary" />
      <VCardText v-else class="bg-grey-lighten-5 pa-5">
        <VCard rounded="lg" elevation="0" border class="mb-4">
          <div class="bg-grey-lighten-4 px-4 py-3 d-flex align-center justify-space-between"><div class="text-subtitle-2 font-weight-bold">Travel Order Information</div><VBtn color="primary" variant="flat" size="small" :disabled="loading || !order?.id" @click="emit('view-pdf', order.id)">PDF</VBtn></div>
          <VDivider />
          <VCardText class="pa-4">
            <VRow dense>
              <VCol v-for="field in travelInformation" :key="field.label" cols="12" sm="6" md="4" :lg="field.cols === 6 ? 6 : 3">
                <div class="rounded-lg border pa-3 h-100">
                  <div class="text-caption text-medium-emphasis mb-1">{{ field.label }}</div>
                  <div v-if="field.label === 'Request Type'" class="text-body-2 font-weight-bold"><VChip size="small" color="primary" variant="tonal">{{ displayValue(field.value) }}</VChip></div>
                  <div v-else class="text-body-2 font-weight-bold text-high-emphasis" style="white-space: pre-wrap; overflow-wrap: anywhere">{{ displayValue(field.value) }}</div>
                </div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <VCard v-if="isGroupTrip" rounded="lg" elevation="0" border class="mb-4">
          <div class="bg-grey-lighten-4 px-4 py-3 d-flex align-center justify-space-between">
            <div class="text-subtitle-2 font-weight-bold">Other Employees in Group Trip</div>
            <div class="text-caption text-medium-emphasis">{{ groupMembers.length }} members</div>
          </div>
          <VDivider />
          <VCardText class="pa-4">
            <VTable density="compact" class="border rounded">
              <thead>
                <tr>
                  <th class="text-center font-weight-bold bg-grey-lighten-3" style="width: 80px">No</th>
                  <th class="font-weight-bold bg-grey-lighten-3">Employee</th>
                  <th class="text-center font-weight-bold bg-grey-lighten-3">Department</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(member, index) in groupMembers" :key="member.key">
                  <td class="text-center text-body-2">{{ index + 1 }}</td>
                  <td class="text-body-2">{{ member.name }}</td>
                  <td class="text-center text-body-2">{{ member.department }}</td>
                </tr>
                <tr v-if="!groupMembers.length">
                  <td colspan="3" class="text-center text-medium-emphasis py-4">No group members found.</td>
                </tr>
              </tbody>
            </VTable>
          </VCardText>
        </VCard>

        <VCard rounded="lg" elevation="0" border class="mb-4">
          <div class="bg-grey-lighten-4 px-4 py-3 text-subtitle-2 font-weight-bold">Ticket, Accommodation & Travel Advance</div>
          <VDivider />
          <VCardText class="pa-4">
            <VRow dense>
              <VCol cols="12" md="6">
                <div class="rounded-lg border pa-3 h-100">
                  <div class="d-flex align-start justify-space-between ga-3 mb-3">
                    <div>
                      <div class="d-flex align-center ga-2 mb-1">
                        <span class="text-body-2 font-weight-bold">{{ ticketType === 'Airplane' ? 'Airplane Ticket' : ticketType === 'Ferry' ? 'Ferry Ticket' : 'Ticket' }}</span>
                        <VTooltip location="top"><template #activator="{ props: tip }"><VIcon v-bind="tip" icon="ri-information-line" size="18" color="medium-emphasis" /></template>Transport type, arranger, and payer</VTooltip>
                      </div>
                      <div class="text-caption text-medium-emphasis">Transport type, arranger, and payer.</div>
                    </div>
                    <VChip v-if="hasTicketInfo" :color="ticketReimbursable ? 'success' : 'error'" variant="tonal" size="small" class="flex-shrink-0">{{ ticketReimbursable ? 'Reimbursable' : 'Not Reimbursable' }}</VChip>
                  </div>
                  <VRow dense>
                    <VCol cols="12" sm="6"><div class="rounded-lg border pa-3 h-100"><div class="text-caption text-medium-emphasis mb-1">Ticket Type</div><div class="text-body-2 font-weight-bold text-high-emphasis">{{ displayValue(ticketType) }}</div></div></VCol>
                    <VCol cols="12" sm="6"><div class="rounded-lg border pa-3 h-100"><div class="text-caption text-medium-emphasis mb-1">Arrangement</div><div class="text-body-2 font-weight-bold text-high-emphasis">{{ displayValue(ticketArrangement) }}</div></div></VCol>
                  </VRow>
                </div>
              </VCol>
              <VCol cols="12" md="6">
                <div class="rounded-lg border pa-3 h-100">
                  <div class="d-flex align-start justify-space-between ga-3 mb-3">
                    <div>
                      <div class="d-flex align-center ga-2 mb-1">
                        <span class="text-body-2 font-weight-bold">Accommodation</span>
                        <VTooltip location="top"><template #activator="{ props: tip }"><VIcon v-bind="tip" icon="ri-information-line" size="18" color="medium-emphasis" /></template>Accommodation arrangement</VTooltip>
                      </div>
                      <div class="text-caption text-medium-emphasis">Accommodation arrangement.</div>
                    </div>
                    <VChip v-if="hasAccommodationInfo" :color="accommodationReimbursable ? 'success' : 'error'" variant="tonal" size="small" class="flex-shrink-0">{{ accommodationReimbursable ? 'Reimbursable' : 'Not Reimbursable' }}</VChip>
                  </div>
                  <div class="rounded-lg border pa-3"><div class="text-caption text-medium-emphasis mb-1">Arrangement</div><div class="text-body-2 font-weight-bold text-high-emphasis">{{ displayValue(accommodationArrangement) }}</div></div>
                </div>
              </VCol>
              <VCol v-if="['Singapore', 'Overseas'].includes(order.travel_region)" cols="12" md="6">
                <div class="rounded-lg border pa-3 h-100">
                  <div class="mb-3">
                    <div class="d-flex align-center ga-2 mb-1"><span class="text-body-2 font-weight-bold">Travel Advance</span><VTooltip location="top"><template #activator="{ props: tip }"><VIcon v-bind="tip" icon="ri-information-line" size="18" color="medium-emphasis" /></template>Meal allowance and pocket money</VTooltip></div>
                    <div class="text-caption text-medium-emphasis">Meal allowance and pocket money.</div>
                  </div>
                  <VRow dense>
                    <VCol cols="12" sm="6"><div class="rounded-lg border pa-3 h-100"><div class="text-caption text-medium-emphasis mb-1">Meal Allowance</div><div class="text-body-2 font-weight-bold text-high-emphasis">{{ formatAmount(advance.meal_allowance ?? order.meal_allowance) }} {{ advance.currency || order.currency || '' }}</div></div></VCol>
                    <VCol cols="12" sm="6"><div class="rounded-lg border pa-3 h-100"><div class="text-caption text-medium-emphasis mb-1">Pocket Money</div><div class="text-body-2 font-weight-bold text-high-emphasis">{{ formatAmount(advance.pocket_money ?? order.pocket_money) }} {{ advance.currency || order.currency || '' }}</div></div></VCol>
                  </VRow>
                </div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <VCard rounded="lg" elevation="0" border class="mb-4">
          <div class="bg-grey-lighten-4 px-4 py-3 d-flex align-center justify-space-between">
            <div class="text-subtitle-2 font-weight-bold">Adjust Time</div>
            <div class="d-flex ga-2">
              <template v-if="editingSchedule">
                <VBtn size="small" variant="text" :disabled="processing" @click="cancelSchedule">Cancel</VBtn>
                <VBtn size="small" color="primary" variant="flat" :loading="processing" :disabled="processing" @click="saveSchedule">Save Changes</VBtn>
              </template>
              <VBtn v-else-if="!isCancelled" size="small" color="primary" variant="tonal" prepend-icon="ri-edit-line" :disabled="processing" @click="editSchedule">Edit</VBtn>
            </div>
          </div>
          <VDivider />
          <VCardText class="pa-4">
            <VRow dense>
              <VCol cols="12" sm="6" md="3"><div class="text-caption text-medium-emphasis mb-1">Departure</div><VTextField v-model="scheduleForm.departure_date" type="date" variant="outlined" density="compact" hide-details :readonly="!editingSchedule" :disabled="processing" /></VCol>
              <VCol cols="12" sm="6" md="3"><div class="text-caption text-medium-emphasis mb-1">Departure Time</div><VTextField v-model="scheduleForm.departure_time" type="time" variant="outlined" density="compact" hide-details :readonly="!editingSchedule" :disabled="processing" /></VCol>
              <VCol cols="12" sm="6" md="3"><div class="text-caption text-medium-emphasis mb-1">Return</div><VTextField v-model="scheduleForm.return_date" type="date" variant="outlined" density="compact" hide-details :readonly="!editingSchedule" :disabled="processing" /></VCol>
              <VCol cols="12" sm="6" md="3"><div class="text-caption text-medium-emphasis mb-1">Return Time</div><VTextField v-model="scheduleForm.return_time" type="time" variant="outlined" density="compact" hide-details :readonly="!editingSchedule" :disabled="processing" /></VCol>
            </VRow>
          </VCardText>
        </VCard>

        <VCard rounded="lg" elevation="0" border class="mb-4">
          <div class="bg-grey-lighten-4 px-4 py-3 d-flex align-center justify-space-between">
            <div class="text-subtitle-2 font-weight-bold">Booking Items</div>
            <div class="d-flex ga-2">
              <template v-if="editingBooking">
                <VBtn size="small" variant="text" :disabled="processing" @click="cancelEditBooking">Cancel</VBtn>
                <VBtn size="small" color="primary" variant="flat" :loading="processing" :disabled="processing" @click="saveBooking">Save Changes</VBtn>
              </template>
              <VBtn v-else-if="!isCancelled" size="small" color="primary" variant="tonal" prepend-icon="ri-edit-line" :disabled="processing" @click="editBooking">Edit</VBtn>
            </div>
          </div>
          <VDivider />
          <VCardText class="pa-4">
            <VRow dense>
              <VCol v-if="isTicketBooked" cols="12" md="6">
                <div class="rounded-lg border pa-4 h-100">
                  <div class="text-subtitle-2 font-weight-bold mb-4">{{ ticketType === 'Ferry' ? 'Ferry Ticket' : ticketType === 'Airplane' ? 'Airplane Ticket' : 'Ticket' }}</div>
                  <div class="text-body-2 mb-2">Booking Amount</div>
                  <VRow dense>
                    <VCol cols="4"><VTextField v-model="bookingForm.ticket_currency" variant="outlined" density="compact" readonly bg-color="grey-lighten-3" hide-details /></VCol>
                    <VCol cols="8"><VTextField class="amount-input" :model-value="ticketAmountText" type="text" inputmode="decimal" autocomplete="off" placeholder="0" variant="outlined" density="compact" :readonly="!editingBooking" hide-details @input="onAmountInput('ticket', $event)" /></VCol>
                  </VRow>
                  <div class="text-body-2 font-weight-medium mt-4 mb-2">E-Ticket / Travel Document</div>
                  <div class="d-flex align-center flex-wrap ga-3">
                    <input v-if="editingBooking" type="file" accept=".pdf,.jpg,.jpeg,.png" :disabled="processing" @change="bookingForm.e_ticket = $event.target.files?.[0] || null" />
                    <template v-else>
                      <VBtn size="small" variant="outlined" disabled>Choose file</VBtn>
                      <span class="text-body-2">{{ eTicketName || 'No file chosen' }}</span>
                    </template>
                  </div>
                </div>
              </VCol>
              <VCol v-if="isAccommodationBooked" cols="12" md="6">
                <div class="rounded-lg border pa-4 h-100">
                  <div class="text-subtitle-2 font-weight-bold mb-4">Accommodation</div>
                  <div class="text-body-2 mb-2">Booking Amount</div>
                  <VRow dense>
                    <VCol cols="4"><VTextField v-model="bookingForm.accommodation_currency" variant="outlined" density="compact" readonly bg-color="grey-lighten-3" hide-details /></VCol>
                    <VCol cols="8"><VTextField class="amount-input" :model-value="accommodationAmountText" type="text" inputmode="decimal" autocomplete="off" placeholder="0" variant="outlined" density="compact" :readonly="!editingBooking" hide-details @input="onAmountInput('accommodation', $event)" /></VCol>
                  </VRow>
                  <div class="text-body-2 font-weight-medium mt-4 mb-2">Accommodation / Hotel Document</div>
                  <div class="d-flex align-center flex-wrap ga-3">
                    <input v-if="editingBooking" type="file" accept=".pdf,.jpg,.jpeg,.png" :disabled="processing" @change="bookingForm.hotel_reservation = $event.target.files?.[0] || null" />
                    <template v-else>
                      <VBtn size="small" variant="outlined" disabled>Choose file</VBtn>
                      <span class="text-body-2">{{ hotelReservationName || 'No file chosen' }}</span>
                    </template>
                  </div>
                </div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <VCard v-if="canCancelTicket" rounded="lg" elevation="1" class="mb-4">
          <div class="bg-grey-lighten-4 px-4 py-3 d-flex align-center justify-space-between ga-3">
            <div><div class="text-subtitle-2 font-weight-bold">Ticket Cancellation & Refund</div><div class="text-caption text-medium-emphasis">Available for company-booked tickets after GMO processing.</div></div>
            <VBtn v-if="!showCancelForm" color="error" variant="flat" size="small" class="flex-shrink-0" @click="showCancelForm = true">Cancel Ticket</VBtn>
          </div>
          <template v-if="showCancelForm">
            <VDivider />
            <VCardText class="pa-4">
              <VRow dense>
                <VCol cols="12"><div class="text-caption font-weight-medium mb-1">Cancellation Reason <span class="text-error">*</span></div><VTextarea v-model="cancelForm.reason" placeholder="e.g. Flight cancelled due to force majeure" variant="outlined" density="compact" rows="3" hide-details /></VCol>
                <VCol cols="12" md="6"><div class="text-caption font-weight-medium mb-1">Refund Status <span class="text-error">*</span></div><VSelect v-model="cancelForm.refundStatus" :items="refundStatusOptions" variant="outlined" density="compact" hide-details /></VCol>
                <VCol cols="12" md="6"><div class="text-caption font-weight-medium mb-1">Original Ticket Amount</div><VTextField class="amount-input" :model-value="`${ticketCurrency} ${formatAmount(originalTicketNumber)}`" variant="outlined" density="compact" bg-color="grey-lighten-4" readonly hide-details /></VCol>
                <VCol cols="12" md="6">
                <div class="text-caption font-weight-medium mb-1">Refund Amount</div>
                  <VTextField v-if="isPartialRefund" class="amount-input" :model-value="partialRefundText" type="text" inputmode="decimal" autocomplete="off" placeholder="0" variant="outlined" density="compact" hide-details @input="onPartialRefundInput" />
                  <VTextField v-else class="amount-input" :model-value="formatAmount(refundAmountNumber)" variant="outlined" density="compact" bg-color="grey-lighten-4" readonly hide-details />
                </VCol>
                <VCol cols="12" md="6"><div class="text-caption font-weight-medium mb-1">Non-refundable Amount</div><VTextField class="amount-input" :model-value="`${ticketCurrency} ${formatAmount(nonRefundableNumber)}`" variant="outlined" density="compact" bg-color="grey-lighten-4" readonly hide-details /></VCol>
                <VCol cols="12">
                  <div class="text-caption font-weight-medium mb-1">Refund Confirmation</div>
                  <div class="d-flex align-center ga-2 rounded-lg border pa-2"><input type="file" accept=".pdf,.jpg,.jpeg,.png" :disabled="processing" @change="refundConfirmation = $event.target.files?.[0] || null" /></div>
                </VCol>
              </VRow>
              <div class="d-flex justify-end ga-2 mt-4">
                <VBtn variant="text" size="small" :disabled="processing" @click="discardCancel">Cancel</VBtn>
                <VBtn color="error" variant="flat" size="small" :loading="processing" :disabled="!canSubmitCancel || processing" @click="submitCancel">Confirm Cancellation</VBtn>
              </div>
            </VCardText>
          </template>
        </VCard>

        <VCard v-if="canCancelAccommodation" rounded="lg" elevation="1" class="mb-4">
          <div class="bg-grey-lighten-4 px-4 py-3 d-flex align-center justify-space-between ga-3">
            <div><div class="text-subtitle-2 font-weight-bold">Accommodation Cancellation & Refund</div><div class="text-caption text-medium-emphasis">Available for company-booked accommodation after GMO processing.</div></div>
            <VBtn v-if="!showAccommodationCancelForm" color="error" variant="flat" size="small" class="flex-shrink-0" @click="showAccommodationCancelForm = true">Cancel Accommodation</VBtn>
          </div>
          <template v-if="showAccommodationCancelForm">
            <VDivider />
            <VCardText class="pa-4">
              <VRow dense>
                <VCol cols="12"><div class="text-caption font-weight-medium mb-1">Cancellation Reason <span class="text-error">*</span></div><VTextarea v-model="accommodationCancelForm.reason" placeholder="e.g. Hotel reservation cancelled due to change of plans" variant="outlined" density="compact" rows="3" hide-details /></VCol>
                <VCol cols="12" md="6"><div class="text-caption font-weight-medium mb-1">Refund Status <span class="text-error">*</span></div><VSelect v-model="accommodationCancelForm.refundStatus" :items="refundStatusOptions" variant="outlined" density="compact" hide-details /></VCol>
                <VCol cols="12" md="6"><div class="text-caption font-weight-medium mb-1">Original Accommodation Amount</div><VTextField class="amount-input" :model-value="`${accommodationCurrency} ${formatAmount(originalAccommodationNumber)}`" variant="outlined" density="compact" bg-color="grey-lighten-4" readonly hide-details /></VCol>
                <VCol cols="12" md="6">
                  <div class="text-caption font-weight-medium mb-1">Refund Amount</div>
                  <VTextField v-if="isAccommodationPartialRefund" class="amount-input" :model-value="accommodationPartialRefundText" type="text" inputmode="decimal" autocomplete="off" placeholder="0" variant="outlined" density="compact" hide-details @input="onAccommodationPartialRefundInput" />
                  <VTextField v-else class="amount-input" :model-value="formatAmount(accommodationRefundAmountNumber)" variant="outlined" density="compact" bg-color="grey-lighten-4" readonly hide-details />
                </VCol>
                <VCol cols="12" md="6"><div class="text-caption font-weight-medium mb-1">Non-refundable Amount</div><VTextField class="amount-input" :model-value="`${accommodationCurrency} ${formatAmount(accommodationNonRefundableNumber)}`" variant="outlined" density="compact" bg-color="grey-lighten-4" readonly hide-details /></VCol>
                <VCol cols="12">
                  <div class="text-caption font-weight-medium mb-1">Refund Confirmation</div>
                  <div class="d-flex align-center ga-2 rounded-lg border pa-2"><input type="file" accept=".pdf,.jpg,.jpeg,.png" :disabled="processing" @change="accommodationRefundConfirmation = $event.target.files?.[0] || null" /></div>
                </VCol>
              </VRow>
              <div class="d-flex justify-end ga-2 mt-4">
                <VBtn variant="text" size="small" :disabled="processing" @click="discardAccommodationCancel">Cancel</VBtn>
                <VBtn color="error" variant="flat" size="small" :loading="processing" :disabled="!canSubmitAccommodationCancel || processing" @click="submitAccommodationCancel">Confirm Cancellation</VBtn>
              </div>
            </VCardText>
          </template>
        </VCard>

        <VCard v-if="ticketCancellation" rounded="lg" elevation="1" class="mb-4">
          <div class="bg-grey-lighten-4 px-4 py-3 d-flex align-center justify-space-between ga-3">
            <div>
              <div class="text-subtitle-2 font-weight-bold">Ticket Cancellation & Refund</div>
              <div class="text-caption text-medium-emphasis">Available for company-booked tickets after GMO processing.</div>
            </div>

            <div class="d-flex ga-2">
              <template v-if="editingRefundType === 'ticket'">
                <VBtn size="small" variant="text" :disabled="processing" @click="cancelEditRefund">Cancel</VBtn>
                <VBtn size="small" color="primary" variant="flat" :loading="processing" :disabled="processing || !refundEditForm.refundStatus" @click="saveRefund">Save Changes</VBtn>
              </template>
              <VBtn v-else color="primary" variant="flat" size="small" :disabled="processing || editingRefundType !== null" @click="editRefund('ticket')">Update Refund</VBtn>
            </div>
          </div>

          <VDivider />

          <VCardText class="pa-4">
            <VRow v-if="editingRefundType === 'ticket'" dense>
              <VCol cols="12">
                <div class="text-caption font-weight-medium mb-1">Cancellation Reason</div>
                <VTextarea v-model="refundEditForm.reason" placeholder="Enter cancellation reason" variant="outlined" density="compact" rows="3" hide-details :disabled="processing" />
              </VCol>

              <VCol cols="12" md="6">
                <div class="text-caption font-weight-medium mb-1">Refund Status</div>
                <VSelect v-model="refundEditForm.refundStatus" :items="refundStatusOptions" variant="outlined" density="compact" hide-details :disabled="processing" />
              </VCol>

              <VCol cols="12" md="6">
                <div class="text-caption font-weight-medium mb-1">Original Ticket Amount</div>
                <VTextField class="amount-input" :model-value="refundEditOriginalAmountText" variant="outlined" density="compact" readonly bg-color="grey-lighten-4" hide-details />
              </VCol>

              <VCol cols="12" md="6">
                <div class="text-caption font-weight-medium mb-1">Refund Amount</div>
                <VTextField class="amount-input" :model-value="refundEditAmountText" type="text" inputmode="decimal" autocomplete="off" placeholder="0" variant="outlined" density="compact" hide-details :disabled="processing" @input="onRefundEditAmountInput" />
              </VCol>

              <VCol cols="12" md="6">
                <div class="text-caption font-weight-medium mb-1">Non-refundable Amount</div>
                <VTextField class="amount-input" :model-value="refundEditNonRefundableText" variant="outlined" density="compact" readonly bg-color="grey-lighten-4" hide-details />
              </VCol>

              <VCol cols="12">
                <div class="text-caption font-weight-medium mb-1">Refund Confirmation</div>
                <input type="file" accept=".pdf,.jpg,.jpeg,.png" :disabled="processing" @change="refundEditForm.confirmationFile = $event.target.files?.[0] || null" />
              </VCol>
            </VRow>

            <VRow v-else dense>
              <VCol v-for="field in ticketCancellation" :key="field.label" cols="12" :md="field.cols || 3">
                <div class="rounded-lg border pa-3 h-100">
  <div class="text-caption text-uppercase text-medium-emphasis">{{ field.label }}</div>
  <VBtn v-if="field.file" size="small" variant="flat" color="primary" @click="openRefundFile(field.file)">View</VBtn>
  <div v-else class="text-body-2 font-weight-bold text-break">{{ displayValue(field.value) }}</div>
</div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <VCard v-if="accommodationCancellation" rounded="lg" elevation="1" class="mb-4">
          <div class="bg-grey-lighten-4 px-4 py-3 d-flex align-center justify-space-between ga-3">
            <div>
              <div class="text-subtitle-2 font-weight-bold">Accommodation Cancellation & Refund</div>
              <div class="text-caption text-medium-emphasis">Available for company-booked accommodation after GMO processing.</div>
            </div>

            <div class="d-flex ga-2">
              <template v-if="editingRefundType === 'accommodation'">
                <VBtn size="small" variant="text" :disabled="processing" @click="cancelEditRefund">Cancel</VBtn>
                <VBtn size="small" color="primary" variant="flat" :loading="processing" :disabled="processing || !refundEditForm.refundStatus" @click="saveRefund">Save Changes</VBtn>
              </template>
              <VBtn v-else color="primary" variant="flat" size="small" :disabled="processing || editingRefundType !== null" @click="editRefund('accommodation')">Update Refund</VBtn>
            </div>
          </div>

          <VDivider />

          <VCardText class="pa-4">
            <VRow v-if="editingRefundType === 'accommodation'" dense>
              <VCol cols="12">
                <div class="text-caption font-weight-medium mb-1">Cancellation Reason</div>
                <VTextarea v-model="refundEditForm.reason" placeholder="Enter cancellation reason" variant="outlined" density="compact" rows="3" hide-details :disabled="processing" />
              </VCol>

              <VCol cols="12" md="6">
                <div class="text-caption font-weight-medium mb-1">Refund Status</div>
                <VSelect v-model="refundEditForm.refundStatus" :items="refundStatusOptions" variant="outlined" density="compact" hide-details :disabled="processing" />
              </VCol>

              <VCol cols="12" md="6">
                <div class="text-caption font-weight-medium mb-1">Original Accommodation Amount</div>
                <VTextField class="amount-input" :model-value="refundEditOriginalAmountText" variant="outlined" density="compact" readonly bg-color="grey-lighten-4" hide-details />
              </VCol>

              <VCol cols="12" md="6">
                <div class="text-caption font-weight-medium mb-1">Refund Amount</div>
                <VTextField class="amount-input" :model-value="refundEditAmountText" type="text" inputmode="decimal" autocomplete="off" placeholder="0" variant="outlined" density="compact" hide-details :disabled="processing" @input="onRefundEditAmountInput" />
              </VCol>

              <VCol cols="12" md="6">
                <div class="text-caption font-weight-medium mb-1">Non-refundable Amount</div>
                <VTextField class="amount-input" :model-value="refundEditNonRefundableText" variant="outlined" density="compact" readonly bg-color="grey-lighten-4" hide-details />
              </VCol>

              <VCol cols="12">
                <div class="text-caption font-weight-medium mb-1">Refund Confirmation</div>
                <input type="file" accept=".pdf,.jpg,.jpeg,.png" :disabled="processing" @change="refundEditForm.confirmationFile = $event.target.files?.[0] || null" />
              </VCol>
            </VRow>

            <VRow v-else dense>
              <VCol v-for="field in accommodationCancellation" :key="field.label" cols="12" :md="field.cols || 3">
                <div class="rounded-lg border pa-3 h-100">
  <div class="text-caption text-uppercase text-medium-emphasis">{{ field.label }}</div>
  <VBtn v-if="field.file" size="small" variant="flat" color="primary" @click="openRefundFile(field.file)">View</VBtn>
  <div v-else class="text-body-2 font-weight-bold text-break">{{ displayValue(field.value) }}</div>
</div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <VCard rounded="lg" elevation="0" border class="mb-4">
          <div class="bg-grey-lighten-4 px-4 py-3 d-flex align-center justify-space-between">
            <div class="text-subtitle-2 font-weight-bold">Employee Email</div>
            <VBtn v-if="!isCancelled" color="primary" variant="flat" size="small" :disabled="processing || loading || !email" @click="resendEmail">Resend Email</VBtn>
          </div>
          <VDivider />
          <VCardText class="pa-4">
            <div v-if="loading" class="text-center py-4"><VProgressCircular indeterminate size="24" /></div>
            <div v-else-if="!email" class="text-medium-emphasis text-body-2">No email available.</div>
            <VRow v-else dense>
              <VCol cols="12" sm="6"><div class="text-body-2 text-medium-emphasis mb-2">To</div><VTextField :model-value="asText(email.to)" variant="outlined" density="compact" readonly bg-color="grey-lighten-4" hide-details /></VCol>
              <VCol cols="12" sm="6"><div class="text-body-2 text-medium-emphasis mb-2">CC</div><VTextField v-model="email.cc" variant="outlined" density="compact" hide-details /></VCol>
              <VCol cols="12"><div class="text-body-2 text-medium-emphasis mb-2">Subject</div><VTextField v-model="email.subject" variant="outlined" density="compact" hide-details /></VCol>
              <VCol cols="12"><div class="text-body-2 text-medium-emphasis mb-2">Message</div><VTextarea v-model="email.message" variant="outlined" density="compact" rows="3" auto-grow hide-details /></VCol>
              <VCol cols="12" sm="6"><div class="text-body-2 text-medium-emphasis mb-2">Status</div><VTextField :model-value="email.status || 'Sent'" variant="outlined" density="compact" readonly bg-color="grey-lighten-4" hide-details /></VCol>
              <VCol cols="12" sm="6"><div class="text-body-2 text-medium-emphasis mb-2">Last Sent</div><VTextField :model-value="formatEmailDate(email.sent_at)" variant="outlined" density="compact" readonly bg-color="grey-lighten-4" hide-details /></VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCardText>
      <VDivider />
      <VCardActions class="px-5 py-4 justify-end"><VBtn variant="text" :disabled="processing" @click="close">Close</VBtn></VCardActions>

<VDialog v-model="previewDialog" max-width="900" scrollable>
  <VCard rounded="lg">
    <VCardItem class="px-4 py-2">
      <VCardTitle class="pa-0 text-subtitle-1 font-weight-bold">Refund Confirmation</VCardTitle>
      <template #append><VBtn icon="ri-close-line" size="small" variant="text" @click="previewDialog = false" /></template>
    </VCardItem>
    <VDivider />
    <VCardText class="pa-0">
      <img v-if="isPreviewImage" :src="previewUrl" class="w-100 d-block" alt="Refund Confirmation" />
      <iframe v-else :src="previewUrl" class="w-100 border-0" height="600" />
    </VCardText>
  </VCard>
</VDialog>
</VCard>
</VDialog>
</template>

<style scoped>
.amount-input :deep(input) {
  text-align: right;
  font-variant-numeric: tabular-nums;
}
</style>