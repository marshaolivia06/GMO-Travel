<script setup>
import { computed, reactive, ref, watch } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  ticket: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  processing: { type: Boolean, default: false },
  canEditSchedule: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'update-schedule', 'add-documents', 'cancel-ticket', 'update-refund', 'resend-email', 'view-pdf', 'cancel-request'])

const legHeaders = [
  { title: 'TRAVELER', key: 'name', sortable: false },
  { title: 'DATE', key: 'date', sortable: false },
  { title: 'ROUTE', key: 'route', sortable: false },
  { title: 'ROUTE STATUS', key: 'routeStatus', sortable: false },
]

const formatDate = date => date ? new Intl.DateTimeFormat('en-GB', { timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(date)) : '-'
const formatDateNumeric = date => date ? new Intl.DateTimeFormat('id-ID', { timeZone: 'Asia/Jakarta', day: '2-digit', month: '2-digit', year: 'numeric' }).format(new Date(date)) : '-'
const norm = value => (value || '').trim().toLowerCase()

function legRouteStatus(leg) {
  const destination = props.ticket?.employee?.eligible_destination?.city_name ?? ''
  const origin = props.ticket?.employee?.office_location ?? ''
  const expectedFrom = leg.direction === 'departure' ? origin : destination
  const expectedTo = leg.direction === 'departure' ? destination : origin
  return norm(leg.travel_from) === norm(expectedFrom) && norm(leg.travel_to) === norm(expectedTo) ? 'Same Route' : 'Different Route'
}

function mapLegs(direction) {
  const legs = props.ticket?.legs?.filter(leg => leg.direction === direction) ?? []
  return legs.map(leg => ({ id: leg.id, name: leg.name, role: leg.relationship || 'Employee', date: formatDate(leg.reschedule_date ?? leg.travel_date), route: `${leg.travel_from} - ${leg.travel_to}`, routeStatus: legRouteStatus(leg) }))
}

const departureLegs = computed(() => mapLegs('departure'))
const returnLegs = computed(() => mapLegs('return'))
const departureFare = computed(() => props.ticket?.fare_references?.find(reference => reference.direction === 'departure'))
const returnFare = computed(() => props.ticket?.fare_references?.find(reference => reference.direction === 'return'))
const departureMax = computed(() => Number(departureFare.value?.fare_amount || 0) * departureLegs.value.length)
const returnMax = computed(() => Number(returnFare.value?.fare_amount || 0) * returnLegs.value.length)
const totalMax = computed(() => departureMax.value + returnMax.value)

const formatMoney = row => `${row?.currency ?? 'IDR'} ${new Intl.NumberFormat('id-ID').format(Number(row?.fare_amount || 0))}`
const formatIdr = value => 'IDR ' + new Intl.NumberFormat('id-ID').format(Number(value || 0))
const formatBookingAmount = value => new Intl.NumberFormat('id-ID').format(Number(value || 0))

const travelPeriod = computed(() => {
  const dates = [...new Set((props.ticket?.legs ?? []).map(leg => leg.reschedule_date ?? leg.travel_date).filter(Boolean).map(formatDate))].sort()
  if (!dates.length) return '-'
  return dates.length > 1 ? `${dates[0]} → ${dates[dates.length - 1]}` : dates[0]
})

const arrangementLabel = computed(() => {
  if (!props.ticket?.ticket_arrangement) return '-'
  return props.ticket.ticket_arrangement === 'Direct Payment' ? 'Direct Payment - Reimbursable' : `Booked by Company - ${props.ticket.route_condition === 'Different Route' ? 'Excess Potential' : 'Not Reimbursable'}`
})

const arrangementNotice = computed(() => {
  if (props.ticket?.ticket_arrangement === 'Direct Payment') return { color: 'success', title: 'Direct Payment by Employee', text: 'The employee may only "REIMBURSE" up to the maximum covered budget.' }
  if (!props.ticket) return null
  return props.ticket.route_condition === 'Different Route' ? { color: 'warning', title: 'Booked by Company', text: 'If the actual company booking exceeds the maximum covered budget, the employee must pay "EXCESS".' } : { color: 'warning', title: 'Booked by Company', text: 'NOT Reimbursable' }
})

const summaryInformation = computed(() => [
  { label: 'Employee', value: props.ticket?.employee?.name },
  { label: 'Department', value: props.ticket?.department_name },
  { label: 'Office Location', value: props.ticket?.employee?.office_location || '-' },
  { label: 'Travel Period', value: travelPeriod.value },
  { label: 'Route Condition', value: props.ticket?.route_condition },
  { label: 'Eligible Travel Destination', value: props.ticket?.employee?.eligible_destination?.city_name },
  { label: 'Ticket Arrangement', value: arrangementLabel.value, tooltip: arrangementNotice.value ? `${arrangementNotice.value.title} — ${arrangementNotice.value.text}` : '' },
])

const editingSchedule = ref(false)
const scheduleForm = reactive({})

const scheduleHeaders = [
  { title: 'TRAVELER', key: 'name', sortable: false },
  { title: 'ORIGINAL DATE', key: 'originalDate', sortable: false },
  { title: 'RESCHEDULE DATE', key: 'rescheduleDate', sortable: false },
]

function rawDate(value) {
  if (!value) return ''
  return new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Jakarta', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date(value))
}

function scheduleRows(direction) {
  return (props.ticket?.legs ?? []).filter(leg => leg.direction === direction).map(leg => ({ id: leg.id, name: leg.name, role: leg.relationship || 'Employee', originalDate: formatDateNumeric(leg.travel_date), rawOriginal: rawDate(leg.travel_date), rescheduleDate: formatDateNumeric(leg.reschedule_date ?? leg.travel_date), isRescheduled: !!leg.reschedule_date, rawReschedule: rawDate(leg.reschedule_date ?? leg.travel_date) }))
}

const departureScheduleRows = computed(() => scheduleRows('departure'))
const returnScheduleRows = computed(() => scheduleRows('return'))

const terLastClaimDate = computed(() => {
  const latest = (props.ticket?.legs ?? []).map(leg => rawDate(leg.reschedule_date ?? leg.travel_date)).filter(Boolean).sort().at(-1)
  if (!latest) return '-'
  const date = new Date(latest)
  date.setDate(date.getDate() + 7)
  return date.toISOString().slice(0, 10)
})

function syncScheduleForm() {
  for (const key of Object.keys(scheduleForm)) delete scheduleForm[key]
  for (const leg of props.ticket?.legs ?? []) scheduleForm[leg.id] = rawDate(leg.reschedule_date ?? leg.travel_date)
}

watch(() => [props.modelValue, props.ticket], ([open]) => { if (open) { editingSchedule.value = false; syncScheduleForm() } }, { immediate: true })

function editSchedule() { syncScheduleForm(); editingSchedule.value = true }
function cancelSchedule() { syncScheduleForm(); editingSchedule.value = false }

function saveSchedule() {
  if (props.processing) return
  const reschedules = (props.ticket?.legs ?? []).map(leg => ({ leg_id: leg.id, reschedule_date: scheduleForm[leg.id] || null }))
  emit('update-schedule', { ticketId: props.ticket?.id, reschedules })
}

watch(() => props.processing, value => { if (value) { editingSchedule.value = false; editingDocuments.value = false; cancelForm.legId = null; refundForm.legId = null } })

const editingDocuments = ref(false)
const newDocuments = ref([])
const documentInput = ref(null)

const showBookingItems = computed(() => props.canEditSchedule && (!!props.ticket?.booking || !!(props.ticket?.booking_documents?.length) || (props.ticket?.status === 'processed_by_gmo' && props.ticket?.ticket_arrangement === 'Booked by Company')))
const issuedDocuments = computed(() => (props.ticket?.booking_documents ?? []).map(doc => ({ id: doc.booking_document_id, name: doc.original_name || doc.file_path?.split('/').pop() || '-', url: doc.file_url })))

function editDocuments() { newDocuments.value = []; if (documentInput.value) documentInput.value.value = ''; editingDocuments.value = true }
function cancelDocuments() { newDocuments.value = []; if (documentInput.value) documentInput.value.value = ''; editingDocuments.value = false }
function onDocumentsChange(event) { newDocuments.value = Array.from(event.target.files ?? []) }

function saveDocuments() {
  if (props.processing || !newDocuments.value.length) return
  emit('add-documents', { ticketId: props.ticket?.id, documents: newDocuments.value })
}

const refundStatusOptions = ['Fully Refund', 'Partially Refunded', 'Pending Refund', 'No Refund']
const cancelForm = reactive({ legId: null, reason: '', refundStatus: 'Pending Refund', originalAmount: '', partialRefundAmount: '', confirmation: null })
const cancelConfirmationInput = ref(null)

const cancellationHeaders = [
  { title: 'TRAVELER', key: 'traveler', sortable: false },
  { title: 'DIRECTION', key: 'direction', sortable: false },
  { title: 'DATE', key: 'date', sortable: false },
  { title: 'ROUTE', key: 'route', sortable: false },
  { title: 'TICKET STATUS', key: 'ticketStatus', sortable: false },
  { title: 'REFUND STATUS', key: 'refundStatus', sortable: false },
  { title: 'ACTION', key: 'action', sortable: false },
]

const cancellationRows = computed(() => (props.ticket?.legs ?? []).map(leg => { const cancellation = (props.ticket?.leg_cancellations ?? []).find(item => item.leg_id === leg.id); return { id: leg.id, traveler: { name: leg.name, role: leg.relationship || 'Employee' }, direction: leg.direction, date: formatDate(leg.reschedule_date ?? leg.travel_date), route: `${leg.travel_from} - ${leg.travel_to}`, isCancelled: !!cancellation, refundStatus: cancellation?.refund_status || '-' } }))

const showCancellation = computed(() => props.canEditSchedule && props.ticket?.ticket_arrangement === 'Booked by Company')
const originalAmountNumber = computed(() => Number(String(cancelForm.originalAmount).replace(/[^\d]/g, '')) || 0)
const originalAmountText = computed(() => (originalAmountNumber.value ? formatBookingAmount(originalAmountNumber.value) : ''))
const bookingCurrency = computed(() => props.ticket?.booking?.amount_currency ?? 'IDR')
const isFullRefund = computed(() => cancelForm.refundStatus === 'Fully Refund')
const isPartialRefund = computed(() => cancelForm.refundStatus === 'Partially Refunded')

const refundAmountNumber = computed(() => {
  if (isFullRefund.value) return originalAmountNumber.value
  if (isPartialRefund.value) { const value = Number(String(cancelForm.partialRefundAmount).replace(/[^\d]/g, '')) || 0; return Math.min(Math.max(value, 0), originalAmountNumber.value) }
  return 0
})

const nonRefundableNumber = computed(() => Math.max(0, originalAmountNumber.value - refundAmountNumber.value))
const partialRefundText = computed(() => (refundAmountNumber.value ? formatBookingAmount(refundAmountNumber.value) : ''))
const canSubmitCancel = computed(() => cancelForm.reason.trim().length > 0 && !!cancelForm.refundStatus && originalAmountNumber.value > 0)

function onOriginalAmountInput(value) { const digits = String(value ?? '').replace(/[^\d]/g, ''); cancelForm.originalAmount = digits ? Number(digits) : 0 }

function openCancelForm(row) {
  discardRefund()
  cancelForm.legId = row.id
  cancelForm.reason = ''
  cancelForm.refundStatus = 'Pending Refund'
  cancelForm.originalAmount = ''
  cancelForm.partialRefundAmount = ''
  cancelForm.confirmation = null
  if (cancelConfirmationInput.value) cancelConfirmationInput.value.value = ''
}

function discardCancel() {
  cancelForm.legId = null
  cancelForm.reason = ''
  cancelForm.refundStatus = 'Pending Refund'
  cancelForm.originalAmount = ''
  cancelForm.partialRefundAmount = ''
  cancelForm.confirmation = null
  if (cancelConfirmationInput.value) cancelConfirmationInput.value.value = ''
}

function onPartialRefundInput(value) { const digits = String(value ?? '').replace(/[^\d]/g, ''); cancelForm.partialRefundAmount = digits ? Number(digits) : 0 }

function submitCancel() {
  if (props.processing || !canSubmitCancel.value) return
  emit('cancel-ticket', { ticketId: props.ticket?.id, legId: cancelForm.legId, reason: cancelForm.reason.trim(), refundStatus: cancelForm.refundStatus, originalAmount: originalAmountNumber.value, refundAmount: refundAmountNumber.value, nonRefundableAmount: nonRefundableNumber.value, confirmation: cancelForm.confirmation })
}

const refundForm = reactive({ legId: null, reason: '', refundStatus: 'Pending Refund', refundAmount: '', confirmation: null })
const refundConfirmationInput = ref(null)

const editingCancellation = computed(() => (props.ticket?.leg_cancellations ?? []).find(item => item.leg_id === refundForm.legId) || null)
const refundOriginalNumber = computed(() => Number(editingCancellation.value?.original_amount || 0))
const refundCurrency = computed(() => editingCancellation.value?.currency || bookingCurrency.value)
const isFullRefundEdit = computed(() => refundForm.refundStatus === 'Fully Refund')
const isPartialRefundEdit = computed(() => refundForm.refundStatus === 'Partially Refunded')

const refundEditAmountNumber = computed(() => {
  if (isFullRefundEdit.value) return refundOriginalNumber.value
  if (isPartialRefundEdit.value) { const value = Number(String(refundForm.refundAmount).replace(/[^\d]/g, '')) || 0; return Math.min(Math.max(value, 0), refundOriginalNumber.value) }
  return 0
})

const refundEditNonRefundableNumber = computed(() => Math.max(0, refundOriginalNumber.value - refundEditAmountNumber.value))
const refundEditAmountText = computed(() => formatBookingAmount(refundEditAmountNumber.value))
const canSubmitRefund = computed(() => refundForm.reason.trim().length > 0 && !!refundForm.refundStatus)

function openRefundForm(row) {
  const cancellation = (props.ticket?.leg_cancellations ?? []).find(item => item.leg_id === row.id)
  if (!cancellation) return
  discardCancel()
  refundForm.legId = row.id
  refundForm.reason = cancellation.reason || ''
  refundForm.refundStatus = cancellation.refund_status || 'Pending Refund'
  refundForm.refundAmount = Number(cancellation.refund_amount ?? 0) || 0
  refundForm.confirmation = null
  if (refundConfirmationInput.value) refundConfirmationInput.value.value = ''
}

function discardRefund() {
  refundForm.legId = null
  refundForm.reason = ''
  refundForm.refundStatus = 'Pending Refund'
  refundForm.refundAmount = ''
  refundForm.confirmation = null
  if (refundConfirmationInput.value) refundConfirmationInput.value.value = ''
}

function onRefundAmountInput(value) { const digits = String(value ?? '').replace(/[^\d]/g, ''); refundForm.refundAmount = digits ? Number(digits) : 0 }

function submitRefund() {
  if (props.processing || !canSubmitRefund.value) return
  emit('update-refund', { ticketId: props.ticket?.id, legId: refundForm.legId, reason: refundForm.reason.trim(), refundStatus: refundForm.refundStatus, refundAmount: refundEditAmountNumber.value, nonRefundableAmount: refundEditNonRefundableNumber.value, confirmation: refundForm.confirmation })
}

function asText(value) {
  if (Array.isArray(value)) return value.join(', ')
  if (typeof value !== 'string') return ''
  const trimmed = value.trim()
  if (!trimmed.startsWith('[')) return trimmed
  try { const parsed = JSON.parse(trimmed); return Array.isArray(parsed) ? parsed.join(', ') : trimmed } catch { return trimmed }
}

const emailState = reactive({ to: '', cc: '', subject: '', message: '', status: '', sent_at: null })

const emailHistory = computed(() => (props.ticket?.emails ?? []).map(raw => ({ id: raw.id, to: asText(raw.to), cc: asText(raw.cc), subject: raw.subject || '', message: raw.message || '', status: raw.status || '', sent_at: raw.sent_at || null, created_at: raw.created_at || null })).filter(item => item.to || item.subject || item.message).sort((a, b) => new Date(b.sent_at || b.created_at || 0) - new Date(a.sent_at || a.created_at || 0)))

const showEmployeeEmail = computed(() => props.canEditSchedule && props.ticket?.ticket_arrangement === 'Booked by Company')
const canResendEmail = computed(() => emailState.to.trim() && emailState.subject.trim() && emailState.message.trim())

watch(() => emailHistory.value[0], latest => { Object.assign(emailState, { to: latest?.to || '', cc: latest?.cc || '', subject: latest?.subject || '', message: latest?.message || '', status: latest?.status || '', sent_at: latest?.sent_at || null }) }, { immediate: true })

function formatEmailDate(value) {
  if (!value) return '-'
  const date = new Date(value)
  return isNaN(date.getTime()) ? '-' : date.toLocaleString('en-GB', { timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function resendEmail() {
  if (props.processing || !canResendEmail.value) return
  emit('resend-email', { ticketId: props.ticket?.id, to: emailState.to, cc: emailState.cc, subject: emailState.subject, message: emailState.message })
}

const canRequestCancellation = computed(() => !!props.ticket?.is_owner && props.ticket?.status === 'processed_by_gmo')
const close = () => emit('update:modelValue', false)
</script>

<template>
  <VDialog :model-value="modelValue" max-width="1100" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <VCardItem class="px-6 py-4">
        <div class="d-flex align-center flex-wrap ga-2"><VCardTitle class="pa-0 text-subtitle-1 font-weight-bold">Annual Ticket Summary</VCardTitle></div>
        <template #append><VBtn icon="ri-close-line" size="small" variant="text" @click="close" /></template>
      </VCardItem>
      <VDivider />
      <VProgressLinear v-if="loading" indeterminate />
      <VCardText class="px-6 py-5">
        <VCard rounded="lg" elevation="0" border class="mb-5">
          <div class="bg-grey-lighten-4 px-4 py-3 d-flex align-center justify-space-between"><div class="text-subtitle-2 font-weight-bold">Annual Ticket Information</div><VBtn color="primary" variant="flat" size="small" :disabled="!ticket?.id" @click="emit('view-pdf', ticket?.id)">PDF</VBtn></div>
          <VDivider />
          <VCardText class="pa-4">
            <VRow dense>
              <VCol v-for="field in summaryInformation" :key="field.label" cols="12" sm="6" md="4" lg="3">
                <div class="rounded-lg border pa-3 h-100">
                  <div class="text-caption text-medium-emphasis mb-1">{{ field.label }}</div>
                  <div class="text-body-2 font-weight-bold text-high-emphasis" style="white-space: pre-wrap; overflow-wrap: anywhere">
                    <VTooltip v-if="field.tooltip" location="top" max-width="320"><template #activator="{ props: tooltipProps }"><span v-bind="tooltipProps" class="cursor-pointer">{{ field.value || '-' }}</span></template><span>{{ field.tooltip }}</span></VTooltip>
                    <template v-else>{{ field.value || '-' }}</template>
                  </div>
                </div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <div class="text-subtitle-1 font-weight-bold mb-2">Travel Legs</div>
        <VSheet color="grey-lighten-5" border rounded="lg" class="pa-4 mb-5">
          <VRow>
            <VCol cols="12" md="6">
              <div class="text-subtitle-2 font-weight-bold mb-2">Departure Legs</div>
              <VDataTable :headers="legHeaders" :items="departureLegs" hide-default-footer density="comfortable" no-data-text="No departure legs" class="bg-white rounded-lg">
                <template #item.name="{ item }"><div class="text-body-2 font-weight-bold">{{ item.name }}</div><div class="text-caption text-medium-emphasis">{{ item.role }}</div></template>
                <template #item.date="{ item }"><span class="text-caption text-medium-emphasis text-no-wrap">{{ item.date }}</span></template>
                <template #item.route="{ item }"><div class="text-body-2 font-weight-medium text-no-wrap">{{ item.route }}</div></template>
                <template #item.routeStatus="{ item }"><VChip :color="item.routeStatus === 'Same Route' ? 'success' : 'warning'" variant="tonal" size="small" class="font-weight-bold">{{ item.routeStatus }}</VChip></template>
              </VDataTable>
            </VCol>
            <VCol cols="12" md="6">
              <div class="text-subtitle-2 font-weight-bold mb-2">Return Legs</div>
              <VDataTable :headers="legHeaders" :items="returnLegs" hide-default-footer density="comfortable" no-data-text="No return legs" class="bg-white rounded-lg">
                <template #item.name="{ item }"><div class="text-body-2 font-weight-bold">{{ item.name }}</div><div class="text-caption text-medium-emphasis">{{ item.role }}</div></template>
                <template #item.date="{ item }"><span class="text-caption text-medium-emphasis text-no-wrap">{{ item.date }}</span></template>
                <template #item.route="{ item }"><div class="text-body-2 font-weight-medium text-no-wrap">{{ item.route }}</div></template>
                <template #item.routeStatus="{ item }"><VChip :color="item.routeStatus === 'Same Route' ? 'success' : 'warning'" variant="tonal" size="small" class="font-weight-bold">{{ item.routeStatus }}</VChip></template>
              </VDataTable>
            </VCol>
          </VRow>
        </VSheet>

        <template v-if="ticket?.fare_references?.length">
          <div class="text-subtitle-1 font-weight-bold mb-2">Fare Reference</div>
          <VRow class="mb-2">
            <VCol cols="12" md="6">
              <VSheet color="teal-lighten-5" border rounded="lg" class="pa-4 h-100">
                <div class="text-subtitle-2 font-weight-bold mb-3">Departure</div>
                <div class="d-flex justify-space-between py-2 text-body-2"><span class="text-medium-emphasis">Fare / Traveler</span><span class="font-weight-bold">{{ formatMoney(departureFare) }}</span></div>
                <VDivider />
                <div class="d-flex justify-space-between py-2 text-body-2"><span class="text-medium-emphasis">Operator</span><span class="font-weight-bold">{{ departureFare?.operator_name || '-' }}</span></div>
                <VDivider />
                <div class="d-flex justify-space-between py-2 text-body-2"><span class="text-medium-emphasis">Departure Legs</span><span class="font-weight-bold">{{ departureLegs.length }}</span></div>
                <VDivider />
                <div class="d-flex justify-space-between py-2 text-body-2"><span class="text-medium-emphasis">Departure Maximum</span><span class="font-weight-bold">{{ formatIdr(departureMax) }}</span></div>
              </VSheet>
            </VCol>
            <VCol cols="12" md="6">
              <VSheet color="teal-lighten-5" border rounded="lg" class="pa-4 h-100">
                <div class="text-subtitle-2 font-weight-bold mb-3">Return</div>
                <div class="d-flex justify-space-between py-2 text-body-2"><span class="text-medium-emphasis">Fare / Traveler</span><span class="font-weight-bold">{{ formatMoney(returnFare) }}</span></div>
                <VDivider />
                <div class="d-flex justify-space-between py-2 text-body-2"><span class="text-medium-emphasis">Operator</span><span class="font-weight-bold">{{ returnFare?.operator_name || '-' }}</span></div>
                <VDivider />
                <div class="d-flex justify-space-between py-2 text-body-2"><span class="text-medium-emphasis">Return Legs</span><span class="font-weight-bold">{{ returnLegs.length }}</span></div>
                <VDivider />
                <div class="d-flex justify-space-between py-2 text-body-2"><span class="text-medium-emphasis">Return Maximum</span><span class="font-weight-bold">{{ formatIdr(returnMax) }}</span></div>
              </VSheet>
            </VCol>
          </VRow>
          <VSheet color="green-lighten-5" border rounded="lg" class="d-flex align-center justify-space-between pa-4 mb-5"><span class="text-subtitle-1 font-weight-medium">Total Maximum Covered Budget</span><span class="text-h6 font-weight-bold text-success">{{ formatIdr(totalMax) }}</span></VSheet>
        </template>

        <VCard v-if="showBookingItems" rounded="lg" elevation="0" border class="mb-5">
          <div class="bg-grey-lighten-4 px-4 py-3 d-flex align-center justify-space-between">
            <div class="text-subtitle-2 font-weight-bold">Booking Items</div>
            <div class="d-flex ga-2">
              <template v-if="editingDocuments"><VBtn size="small" variant="text" :disabled="processing" @click="cancelDocuments">Cancel</VBtn><VBtn size="small" color="primary" variant="flat" :disabled="processing || !newDocuments.length" @click="saveDocuments">Save Changes</VBtn></template>
              <VBtn v-else-if="canEditSchedule" size="small" color="primary" variant="tonal" prepend-icon="ri-edit-line" :disabled="processing" @click="editDocuments">Edit</VBtn>
            </div>
          </div>
          <VDivider />
          <VCardText class="pa-4">
            <div class="rounded-lg border pa-4">
              <div class="text-subtitle-2 font-weight-bold mb-4">Ticket</div>
              <div class="text-body-2 mb-2">Booking Amount</div>
              <div class="d-flex ga-2">
                <VTextField :model-value="ticket.booking?.amount_currency ?? 'IDR'" variant="outlined" density="compact" readonly bg-color="grey-lighten-3" hide-details class="currency-field" />
                <VTextField :model-value="ticket.booking ? formatBookingAmount(ticket.booking.amount) : ''" type="text" variant="outlined" density="compact" readonly hide-details class="fare-input" />
              </div>
              <div class="text-body-2 font-weight-medium mt-4 mb-2">E-Ticket / Travel Document</div>
              <template v-if="editingDocuments">
                <div class="d-flex align-center w-100" style="background:#fff;border:1px solid #bdbdbd;border-radius:8px;padding:8px 10px;min-height:48px"><input ref="documentInput" type="file" accept=".pdf,.jpg,.jpeg,.png" multiple class="w-100" style="font-size:13px;border:0;background:transparent;outline:none" @change="onDocumentsChange" /></div>
                <div class="text-caption text-medium-emphasis mt-1">New documents will be added — existing documents are kept.</div>
              </template>
              <div v-else class="d-flex align-center flex-wrap ga-3 locked-control"><VBtn size="small" variant="outlined" disabled>Choose file</VBtn><span v-if="!issuedDocuments.length" class="text-body-2">No file chosen</span></div>
              <div v-if="issuedDocuments.length" class="d-flex flex-column ga-2 mt-3">
                <div v-for="doc in issuedDocuments" :key="doc.id" class="d-flex align-center justify-space-between border rounded-lg pa-2 pr-3 bg-white"><span class="text-body-2 text-truncate mr-3">{{ doc.name }}</span><VBtn size="small" variant="tonal" color="primary" :href="doc.url" target="_blank" rel="noopener" prepend-icon="ri-eye-line" class="flex-shrink-0">View</VBtn></div>
              </div>
            </div>
          </VCardText>
        </VCard>

        <VCard v-if="canEditSchedule" rounded="lg" elevation="0" border>
          <div class="bg-grey-lighten-4 px-4 py-3 d-flex align-center justify-space-between">
            <div class="text-subtitle-2 font-weight-bold">Travel Schedule Update</div>
            <div class="d-flex ga-2">
              <template v-if="editingSchedule"><VBtn size="small" variant="text" :disabled="processing" @click="cancelSchedule">Cancel</VBtn><VBtn size="small" color="primary" variant="flat" :loading="processing" :disabled="processing" @click="saveSchedule">Save Changes</VBtn></template>
              <VBtn v-else size="small" color="primary" variant="tonal" prepend-icon="ri-edit-line" :disabled="processing" @click="editSchedule">Edit</VBtn>
            </div>
          </div>
          <VDivider />
          <VCardText class="pa-4">
            <VRow>
              <VCol cols="12" md="6">
                <div class="text-subtitle-2 font-weight-bold mb-2">Departure Schedule</div>
                <VDataTable :headers="scheduleHeaders" :items="departureScheduleRows" hide-default-footer density="comfortable" no-data-text="No departure schedule" class="bg-grey-lighten-4 rounded-lg">
                  <template #item.name="{ item }"><div class="text-body-2 font-weight-bold">{{ item.name }}</div><div class="text-caption text-medium-emphasis">{{ item.role }}</div></template>
                  <template #item.originalDate="{ item }"><span class="text-body-2 text-no-wrap">{{ item.originalDate }}</span></template>
                  <template #item.rescheduleDate="{ item }"><VTextField v-if="editingSchedule" v-model="scheduleForm[item.id]" type="date" variant="outlined" density="compact" hide-details :disabled="processing" /><span v-else class="text-body-2 text-no-wrap">{{ item.rescheduleDate || '-' }}</span></template>
                </VDataTable>
              </VCol>
              <VCol cols="12" md="6">
                <div class="text-subtitle-2 font-weight-bold mb-2">Return Schedule</div>
                <VDataTable :headers="scheduleHeaders" :items="returnScheduleRows" hide-default-footer density="comfortable" no-data-text="No return schedule" class="bg-grey-lighten-4 rounded-lg">
                  <template #item.name="{ item }"><div class="text-body-2 font-weight-bold">{{ item.name }}</div><div class="text-caption text-medium-emphasis">{{ item.role }}</div></template>
                  <template #item.originalDate="{ item }"><span class="text-body-2 text-no-wrap">{{ item.originalDate }}</span></template>
                  <template #item.rescheduleDate="{ item }"><VTextField v-if="editingSchedule" v-model="scheduleForm[item.id]" type="date" variant="outlined" density="compact" hide-details :disabled="processing" /><span v-else class="text-body-2 text-no-wrap">{{ item.rescheduleDate || '-' }}</span></template>
                </VDataTable>
              </VCol>
            </VRow>
            <VAlert type="warning" variant="tonal" density="compact" :icon="false" class="mt-4 text-black"><div class="text-body-2"><span class="font-weight-bold">TER Last Claim Date: {{ terLastClaimDate }}</span><span> · 7 calendar days after the latest Departure or Return Date</span></div></VAlert>
          </VCardText>
        </VCard>

        <VCard v-if="showCancellation" rounded="lg" elevation="0" border class="mt-5">
          <div class="bg-grey-lighten-4 px-4 py-3"><div class="text-subtitle-2 font-weight-bold">Ticket Cancellation &amp; Refund</div><div class="text-caption text-medium-emphasis">Available for company-booked tickets after GMO processing.</div></div>
          <VDivider />
          <VCardText class="pa-4">
            <VDataTable :headers="cancellationHeaders" :items="cancellationRows" hide-default-footer density="comfortable" no-data-text="No travel legs" class="bg-grey-lighten-4 rounded-lg">
              <template #item.traveler="{ item }"><div class="text-body-2 font-weight-bold">{{ item.traveler.name }}</div><div class="text-caption text-medium-emphasis">{{ item.traveler.role }}</div></template>
              <template #item.direction="{ item }"><VChip size="small" variant="tonal" :color="item.direction === 'departure' ? 'primary' : 'info'" class="text-capitalize">{{ item.direction }}</VChip></template>
              <template #item.date="{ item }"><span class="text-body-2 text-no-wrap">{{ item.date }}</span></template>
              <template #item.route="{ item }"><span class="text-body-2 text-no-wrap">{{ item.route }}</span></template>
              <template #item.ticketStatus="{ item }"><VChip size="small" variant="tonal" :color="item.isCancelled ? 'error' : 'success'" class="font-weight-bold">{{ item.isCancelled ? 'Cancelled' : 'Active' }}</VChip></template>
              <template #item.refundStatus="{ item }"><span class="text-body-2">{{ item.refundStatus }}</span></template>
              <template #item.action="{ item }">
                <VBtn v-if="!item.isCancelled" size="small" color="error" variant="flat" :disabled="processing || !canEditSchedule" @click="openCancelForm(item)">Cancel Ticket</VBtn>
                <VBtn v-else-if="canEditSchedule" size="small" color="primary" variant="tonal" prepend-icon="ri-edit-line" :disabled="processing" @click="openRefundForm(item)">Update Refund</VBtn>
                <span v-else class="text-caption text-medium-emphasis">-</span>
              </template>
            </VDataTable>

            <template v-if="cancelForm.legId">
              <VDivider class="my-4" />
              <VRow dense>
                <VCol cols="12"><div class="text-caption font-weight-medium mb-1">Cancellation Reason <span class="text-error">*</span></div><VTextarea v-model="cancelForm.reason" placeholder="e.g. Flight cancelled due to force majeure" variant="outlined" density="compact" rows="3" hide-details :disabled="processing" /></VCol>
                <VCol cols="12" md="6"><div class="text-caption font-weight-medium mb-1">Refund Status <span class="text-error">*</span></div><VSelect v-model="cancelForm.refundStatus" :items="refundStatusOptions" variant="outlined" density="compact" hide-details :disabled="processing" /></VCol>
                <VCol cols="12" md="6"><div class="text-caption font-weight-medium mb-1">Original Ticket Amount <span class="text-error">*</span></div><VTextField :model-value="originalAmountText" type="text" inputmode="numeric" autocomplete="off" placeholder="0" variant="outlined" density="compact" hide-details :disabled="processing" @update:model-value="onOriginalAmountInput" /></VCol>
                <VCol cols="12" md="6">
                  <div class="text-caption font-weight-medium mb-1">Refund Amount</div>
                  <VTextField v-if="isPartialRefund" :model-value="partialRefundText" type="text" inputmode="decimal" autocomplete="off" placeholder="0" variant="outlined" density="compact" hide-details :disabled="processing" @update:model-value="onPartialRefundInput" />
                  <VTextField v-else :model-value="formatBookingAmount(refundAmountNumber)" variant="outlined" density="compact" bg-color="grey-lighten-4" readonly hide-details />
                </VCol>
                <VCol cols="12" md="6"><div class="text-caption font-weight-medium mb-1">Non-refundable Amount</div><VTextField :model-value="`${bookingCurrency} ${formatBookingAmount(nonRefundableNumber)}`" variant="outlined" density="compact" bg-color="grey-lighten-4" readonly hide-details /></VCol>
                <VCol cols="12"><div class="text-caption font-weight-medium mb-1">Refund Confirmation</div><div class="d-flex align-center ga-2 rounded-lg border pa-2"><input ref="cancelConfirmationInput" type="file" accept=".pdf,.jpg,.jpeg,.png" :disabled="processing" @change="cancelForm.confirmation = $event.target.files?.[0] || null" /></div></VCol>
              </VRow>
              <div class="d-flex justify-end ga-2 mt-4"><VBtn variant="text" size="small" :disabled="processing" @click="discardCancel">Cancel</VBtn><VBtn color="error" variant="flat" size="small" :loading="processing" :disabled="!canSubmitCancel || processing" @click="submitCancel">Confirm Cancellation</VBtn></div>
            </template>

            <template v-if="refundForm.legId">
              <VDivider class="my-4" />
              <VRow dense>
                <VCol cols="12"><div class="text-caption font-weight-medium mb-1">Cancellation Reason <span class="text-error">*</span></div><VTextarea v-model="refundForm.reason" placeholder="Enter cancellation reason" variant="outlined" density="compact" rows="3" hide-details :disabled="processing" /></VCol>
                <VCol cols="12" md="6"><div class="text-caption font-weight-medium mb-1">Refund Status <span class="text-error">*</span></div><VSelect v-model="refundForm.refundStatus" :items="refundStatusOptions" variant="outlined" density="compact" hide-details :disabled="processing" /></VCol>
                <VCol cols="12" md="6"><div class="text-caption font-weight-medium mb-1">Original Ticket Amount</div><VTextField :model-value="`${refundCurrency} ${formatBookingAmount(refundOriginalNumber)}`" variant="outlined" density="compact" bg-color="grey-lighten-4" readonly hide-details /></VCol>
                <VCol cols="12" md="6">
                  <div class="text-caption font-weight-medium mb-1">Refund Amount</div>
                  <VTextField v-if="isPartialRefundEdit" :model-value="refundEditAmountText" type="text" inputmode="decimal" autocomplete="off" placeholder="0" variant="outlined" density="compact" hide-details :disabled="processing" @update:model-value="onRefundAmountInput" />
                  <VTextField v-else :model-value="formatBookingAmount(refundEditAmountNumber)" variant="outlined" density="compact" bg-color="grey-lighten-4" readonly hide-details />
                </VCol>
                <VCol cols="12" md="6"><div class="text-caption font-weight-medium mb-1">Non-refundable Amount</div><VTextField :model-value="`${refundCurrency} ${formatBookingAmount(refundEditNonRefundableNumber)}`" variant="outlined" density="compact" bg-color="grey-lighten-4" readonly hide-details /></VCol>
                <VCol cols="12"><div class="text-caption font-weight-medium mb-1">Refund Confirmation</div><div class="d-flex align-center ga-2 rounded-lg border pa-2"><input ref="refundConfirmationInput" type="file" accept=".pdf,.jpg,.jpeg,.png" :disabled="processing" @change="refundForm.confirmation = $event.target.files?.[0] || null" /></div></VCol>
              </VRow>
              <div class="d-flex justify-end ga-2 mt-4"><VBtn variant="text" size="small" :disabled="processing" @click="discardRefund">Cancel</VBtn><VBtn color="primary" variant="flat" size="small" :loading="processing" :disabled="!canSubmitRefund || processing" @click="submitRefund">Save Changes</VBtn></div>
            </template>
          </VCardText>
        </VCard>

        <VCard v-if="showEmployeeEmail" rounded="lg" elevation="0" border class="mt-5">
          <div class="bg-grey-lighten-4 px-4 py-3 d-flex align-center justify-space-between"><div class="text-subtitle-2 font-weight-bold">Employee Email</div><VBtn v-if="canEditSchedule" color="primary" variant="flat" size="small" :disabled="processing || !canResendEmail" @click="resendEmail">Resend Email</VBtn></div>
          <VDivider />
          <VCardText class="pa-4">
            <div v-if="!emailHistory.length" class="text-medium-emphasis text-body-2">No email available.</div>
            <VRow v-else dense>
              <VCol cols="12" sm="6"><div class="text-body-2 text-medium-emphasis mb-2">To</div><VTextField :model-value="emailState.to" variant="outlined" density="compact" readonly bg-color="grey-lighten-4" hide-details /></VCol>
              <VCol cols="12" sm="6"><div class="text-body-2 text-medium-emphasis mb-2">CC</div><VTextField v-model="emailState.cc" variant="outlined" density="compact" hide-details /></VCol>
              <VCol cols="12"><div class="text-body-2 text-medium-emphasis mb-2">Subject</div><VTextField v-model="emailState.subject" variant="outlined" density="compact" hide-details /></VCol>
              <VCol cols="12"><div class="text-body-2 text-medium-emphasis mb-2">Message</div><VTextarea v-model="emailState.message" variant="outlined" density="compact" rows="3" auto-grow hide-details /></VCol>
              <VCol cols="12" sm="6"><div class="text-body-2 text-medium-emphasis mb-2">Status</div><VTextField :model-value="emailState.status || 'Sent'" variant="outlined" density="compact" readonly bg-color="grey-lighten-4" hide-details /></VCol>
              <VCol cols="12" sm="6"><div class="text-body-2 text-medium-emphasis mb-2">Last Sent</div><VTextField :model-value="formatEmailDate(emailState.sent_at)" variant="outlined" density="compact" readonly bg-color="grey-lighten-4" hide-details /></VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCardText>
      <VDivider />
      <VCardActions v-if="canRequestCancellation" class="px-6 py-4 justify-end"><VBtn color="error" variant="flat" :disabled="processing" @click="emit('cancel-request', ticket?.id)">Cancellation Request</VBtn></VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.fare-input :deep(.v-field__input) { text-align: right; }
.currency-field { max-width: 110px; }
.locked-control, .locked-control :deep(*) { cursor: not-allowed !important; }
</style>