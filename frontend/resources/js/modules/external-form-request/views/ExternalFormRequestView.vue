<script setup>
import { computed, onMounted, ref } from 'vue'
import { getExternalFormRequests, getExternalFormRequestHistory, getExternalFormRequestPdf, getDivHeads, approveExternalFormRequest, releaseExternalFormRequest, resendExternalFormRequestEmail, revisionExternalFormRequest, rejectExternalFormRequest } from '../services/externalFormRequestService'
import { useToastStore } from '../../../stores/toast'
import { useAuthStore } from '../../../stores/auth'
import { useConfirm } from '../../../composables/useConfirm'
import PickTypeModal from '../../travel-order/components/modal/PickTypeModal.vue'
import PdfViewerModal from '../../travel-order/components/modal/PdfViewerModal.vue'
import NoteModal from '../../travel-order/components/modal/NoteModal.vue'
import AirplaneFerryFormModal from '../components/modal/AirplaneFerryFormModal.vue'
import HotelReservationFormModal from '../components/modal/HotelReservationFormModal.vue'
import HistoryLogModal from '../components/modal/HistoryLogModal.vue'
import GmoReviewModal from '../components/modal/GmoReviewModal.vue'
import AttachmentModal from '../components/modal/AttachmentModal.vue'

const toast = useToastStore()
const auth = useAuthStore()
const { confirm } = useConfirm()
const requests = ref([])
const loading = ref(false)
const search = ref('')
const statusTab = ref('all')
const showPickType = ref(false)
const showAirplaneFerryForm = ref(false)
const showHotelReservationForm = ref(false)
const selectedType = ref(null)
const editingItem = ref(null)
const historyDialog = ref(false)
const historyLoading = ref(false)
const historyLogs = ref([])

const showGmoReview = ref(false)
const gmoSubmitting = ref(false)
const pendingReviewPayload = ref(null)
const divHeadItems = ref([])
const divHeadsLoading = ref(false)

const historyChip = {
  submit: { text: 'Requested', color: 'primary' },
  draft: { text: 'Draft Saved', color: 'secondary' },
  checked: { text: 'Reviewed', color: 'success' },
  approved: { text: 'Approved', color: 'success' },
  revision: { text: 'Revision Required', color: 'warning' },
  rejected: { text: 'Rejected', color: 'error' },
  processed: { text: 'Processed by GMO', color: 'success' },
  booking_payment_updated: { text: 'Booking & Payment Updated', color: 'info' },
  email_sent: { text: 'Email Sent', color: 'primary' },
  email_failed: { text: 'Email Failed', color: 'error' },
  email_resent: { text: 'Email Resent', color: 'primary' },
  released: { text: 'Released', color: 'success' },
}

const formTypes = [
  {
    key: 'airplane_ferry',
    badge: 'AF',
    title: 'Airplane / Ferry Request',
    desc: 'Request external guest transportation by airplane or ferry.',
    points: ['Airplane ticket', 'Ferry ticket', 'Travel schedule'],
  },
  {
    key: 'hotel_reservation',
    badge: 'HR',
    title: 'Hotel Reservation Request',
    desc: 'Request external guest accommodation / hotel reservation.',
    points: ['Hotel booking', 'Stay period', 'Guest information'],
  },
]

function openPickType() {
  selectedType.value = null
  showPickType.value = true
}

function backToPickType() {
  showAirplaneFerryForm.value = false
  showHotelReservationForm.value = false
  editingItem.value = null
  showPickType.value = true
}

function onTypeSelected(key) {
  selectedType.value = key
  editingItem.value = null
  showPickType.value = false

  if (key === 'airplane_ferry')
    showAirplaneFerryForm.value = true
  else
    showHotelReservationForm.value = true
}

function editDraft(row) {
  if (!auth.can('external-form-request.create')) return

  editingItem.value = row.raw

  if (row.raw.type === 'airplane_ferry')
    showAirplaneFerryForm.value = true
  else
    showHotelReservationForm.value = true
}

async function openHistory(row) {
  historyDialog.value = true
  historyLoading.value = true
  historyLogs.value = []

  try {
    const response = await getExternalFormRequestHistory(row.id)

    historyLogs.value = response?.data ?? []
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to load history log.')
  } finally {
    historyLoading.value = false
  }
}

const pdfDialog = ref(false)
const pdfLoading = ref(false)
const pdfUrl = ref(null)
const pdfRow = ref(null)
const pdfReviewMode = ref('view')
const pdfCanDecide = computed(() => pdfReviewMode.value === 'decide')
const isReviewStep = computed(() => pdfRow.value?.raw?.pending_action === 'Review')
const isContinueFlow = computed(() => isReviewStep.value && ['airplane_ferry', 'hotel_reservation'].includes(pdfRow.value?.raw?.type))
const isReleaseFlow = computed(() => pdfRow.value?.raw?.can_process === true)
const canResend = computed(() => pdfRow.value?.raw?.can_resend === true)
const resending = ref(false)
const deciding = ref(false)

async function handleResendEmail() {
  if (resending.value || !pdfRow.value) return

  const raw = pdfRow.value.raw

  const documentLabel = raw.type === 'hotel_reservation'
    ? 'reservation confirmation document'
    : 'booking document'

  const ok = await confirm({
    title: 'Resend Email',
    text: `The released ${documentLabel} will be sent to ${raw.resend_to || 'the requester'}.`,
    confirmText: 'Resend Email',
    color: 'primary',
  })

  if (!ok) return

  resending.value = true

  try {
    const response = await resendExternalFormRequestEmail(pdfRow.value.id)

    toast.success(response?.message || 'Release email resent successfully.')
    pdfDialog.value = false
    await fetchRequests()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to resend the release email.')
  } finally {
    resending.value = false
  }
}

const showGmoRelease = ref(false)
const releasing = ref(false)
const attachmentDialog = ref(false)
const attachmentUrl = ref(null)

function openAttachment(item) {
  attachmentUrl.value = item.raw.attachment_url
  attachmentDialog.value = true
}

const releasePrefill = computed(() => {
  const raw = pdfRow.value?.raw
  if (!raw) return {}

  return {
    departure: { operator: raw.operator ?? '', flight_vessel_no: raw.flight_vessel_no ?? '', atd: raw.departure_atd ?? '', ata: raw.departure_ata ?? '' },
    return: { operator: raw.return_operator ?? '', flight_vessel_no: raw.return_flight_vessel_no ?? '', atd: raw.return_atd ?? '', ata: raw.return_ata ?? '' },
    hotel: raw.hotel ?? '',
    room_type: raw.room_type ?? '',
  }
})

const noteDialog = ref(false)
const noteAction = ref('revision')

const noteMeta = computed(() => {
  if (noteAction.value === 'reject')
    return { action: 'reject', title: 'Reject External Form Request', label: 'Remark', button: 'Rejected', color: 'error' }

  if (noteAction.value === 'review')
    return { action: 'approved', title: 'Review External Form Request', label: 'Remark (Optional)', button: 'Reviewed', color: 'primary', placeholder: 'Enter remark (optional)' }

  if (noteAction.value === 'approve')
    return { action: 'approved', title: 'Approve External Form Request', label: 'Remark (Optional)', button: 'Approve', color: 'primary', placeholder: 'Enter remark (optional)' }

  return { action: 'revision', title: 'Return for Revision', label: 'Remark', button: 'Revision Required', color: 'warning' }
})

function openNote(action) {
  noteAction.value = action
  noteDialog.value = true
}

async function handleDecision(action, remark = null) {
  if (deciding.value || !pdfRow.value) return

  deciding.value = true

  try {
    const api = action === 'revision' ? revisionExternalFormRequest : rejectExternalFormRequest

    const response = action === 'approve'
      ? await approveExternalFormRequest(pdfRow.value.id, { remark })
      : await api(pdfRow.value.id, remark)

    toast.success(response?.message || 'Decision recorded successfully.')
    pdfDialog.value = false
    noteDialog.value = false
    await fetchRequests()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to record decision.')
  } finally {
    deciding.value = false
  }
}

async function handleNoteSubmit({ text }) {
  if (noteAction.value === 'review') {
    const ok = await confirm({
      title: 'Review Request',
      text: 'Are you sure you want to mark this external form request as reviewed?',
      confirmText: 'Reviewed',
      color: 'primary',
    })

    if (!ok) return

    await submitReview(pendingReviewPayload.value, text || null)

    return
  }

  if (noteAction.value === 'approve') {
    const ok = await confirm({
      title: 'Approve Request',
      text: 'Are you sure you want to approve this external form request?',
      confirmText: 'Approve',
      color: 'primary',
    })

    if (!ok) return

    await handleDecision('approve', text || null)

    return
  }

  const isReject = noteAction.value === 'reject'

  const ok = await confirm({
    title: isReject ? 'Reject Request' : 'Return for Revision',
    text: isReject
      ? 'Are you sure you want to reject this external form request?'
      : 'Are you sure you want to return this external form request for revision?',
    confirmText: isReject ? 'Rejected' : 'Revision Required',
    color: isReject ? 'error' : 'warning',
  })

  if (!ok) return

  await handleDecision(noteAction.value, text || null)
}

async function handleApproveConfirm() {
  if (isReleaseFlow.value) {
    pdfDialog.value = false
    showGmoRelease.value = true

    return
  }

  if (isContinueFlow.value) {
    openGmoReview()

    return
  }

  noteAction.value = 'approve'
  noteDialog.value = true
}

async function loadDivHeads() {
  if (divHeadItems.value.length) return

  divHeadsLoading.value = true

  try {
    const rows = await getDivHeads()
    const users = Array.isArray(rows) ? rows : rows?.data ?? []

    divHeadItems.value = users.map(user => ({ name: user.name, id: user.id }))
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to load Div Head list.')
  } finally {
    divHeadsLoading.value = false
  }
}

function openGmoReview() {
  pdfDialog.value = false
  loadDivHeads()
  showGmoReview.value = true
}

function handleGmoBack() {
  showGmoReview.value = false
  pdfDialog.value = true
}

function handleGmoSubmit(payload) {
  pendingReviewPayload.value = payload
  noteAction.value = 'review'
  noteDialog.value = true
}

async function submitReview(payload, remark) {
  if (gmoSubmitting.value || !pdfRow.value || !payload) return

  gmoSubmitting.value = true

  try {
    const response = await approveExternalFormRequest(pdfRow.value.id, { ...payload, remark })

    toast.success(response?.message || 'External form request reviewed successfully.')
    noteDialog.value = false
    showGmoReview.value = false
    await fetchRequests()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to record decision.')
  } finally {
    gmoSubmitting.value = false
  }
}

function handleGmoReleaseBack() {
  showGmoRelease.value = false
  pdfDialog.value = true
}

async function handleGmoReleaseSubmit(payload) {
  if (releasing.value || !pdfRow.value || !payload) return

  const ok = await confirm({
    title: 'Release Request',
    text: 'Are you sure you want to release this external form request to the requester?',
    confirmText: 'Release',
    color: 'primary',
  })

  if (!ok) return

  releasing.value = true

  try {
    const response = await releaseExternalFormRequest(pdfRow.value.id, payload)

    toast.success(response?.message || 'External form request released successfully.')
    showGmoRelease.value = false
    await fetchRequests()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to release the request.')
  } finally {
    releasing.value = false
  }
}

async function openPdf(row, mode = 'view') {
  pdfRow.value = row
  pdfDialog.value = true
  pdfLoading.value = true
  pdfUrl.value = null
  pdfReviewMode.value = (row.raw.can_approve || row.raw.can_process) ? 'decide' : mode

  try {
    const blob = await getExternalFormRequestPdf(row.id)

    pdfUrl.value = URL.createObjectURL(blob)
  } catch (err) {
    pdfDialog.value = false

    const data = err?.response?.data

    const message = data instanceof Blob
      ? await data.text().then(text => { try { return JSON.parse(text)?.message } catch { return null } }).catch(() => null)
      : data?.message

    toast.error(message || 'Failed to load the PDF document.')
  } finally {
    pdfLoading.value = false
  }
}

const typeMeta = {
  Airplane: 'blue',
  Ferry: 'success-darken-1',
  Hotel: 'purple',
}

const tripColor = 'success-darken-1'

function buildType(item) {
  if (item.type === 'airplane_ferry') return item.transport_mode ?? 'Airplane / Ferry'

  if (item.type === 'hotel_reservation') return 'Hotel'

  return item.type ?? '-'
}

function buildTypeColor(item) {
  if (item.type === 'airplane_ferry') return item.transport_mode === 'Ferry' ? typeMeta.Ferry : typeMeta.Airplane

  if (item.type === 'hotel_reservation') return typeMeta.Hotel

  return typeMeta.Airplane
}

function buildTripLabel(item) {
  if (item.type !== 'airplane_ferry' || !item.trip_type) return null

  return item.trip_type === 'round_trip' ? 'Round Trip' : 'One Way'
}

const formatDate = value => value
  ? new Intl.DateTimeFormat('en-GB', { timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value))
  : null

function formatDateTime(value) {
  if (!value) return ''
  const date = new Date(value)
  return isNaN(date.getTime()) ? '' : date.toLocaleString('en-GB', { timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function buildHistoryRounds(logs) {
  const groups = new Map()
  for (const log of logs) {
    const round = log.round ?? 1
    if (!groups.has(round)) groups.set(round, { round, items: [] })
    const meta = historyChip[log.event] || { text: log.description || log.event, color: 'grey' }
    groups.get(round).items.push({ key: log.id, chipText: meta.text, chipColor: meta.color, time: formatDateTime(log.created_at), role: log.role || '-', name: log.causer || '', note: log.note || '', remark: log.remark || '', evidence: log.evidence || '', documents: Array.isArray(log.documents) ? log.documents : [] })
  }
  return [...groups.values()].sort((a, b) => a.round - b.round)
}

const historyRounds = computed(() => buildHistoryRounds(historyLogs.value))

function buildTraveler(item) {
  const names = (item.travelers ?? []).map(traveler => traveler.name).filter(Boolean)

  return names.length > 0 ? names.join('\n') : '-'
}

function buildRouteHotel(item) {
  if (item.type === 'airplane_ferry')
    return [item.travel_from, item.travel_to].filter(Boolean).join(' → ') || '-'

  return 'Hotel assigned by GMO'
}

function buildStayPeriod(item) {
  const parts = item.type === 'airplane_ferry'
    ? [item.departure_date, item.return_date]
    : [item.check_in_date, item.check_out_date]

  const dates = parts.map(formatDate).filter(Boolean)

  return dates.length > 0 ? dates.join(' - ') : '-'
}

async function fetchRequests() {
  loading.value = true

  try {
    const data = await getExternalFormRequests()
    const rows = Array.isArray(data) ? data : data?.data ?? []

    requests.value = rows.map(item => ({
      id: item.id,
      raw: item,
      status: item.status,
      type: buildType(item),
      type_color: buildTypeColor(item),
      trip_label: buildTripLabel(item),
      traveler: buildTraveler(item),
      route_hotel: buildRouteHotel(item),
      stay_period: buildStayPeriod(item),
    }))
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to load external form requests.')
  } finally {
    loading.value = false
  }
}

onMounted(fetchRequests)

const statusMeta = {
  draft: { title: 'Draft', color: 'secondary', icon: 'ri-draft-line' },
  revision_required: { title: 'Revision Required', color: 'info', icon: 'ri-edit-2-line' },
  awaiting_approval_dept_head: { title: 'Awaiting Approval - Dept Head', color: 'warning', icon: 'ri-user-star-line' },
  rejected: { title: 'Rejected', color: 'error', icon: 'ri-close-circle-line' },
  awaiting_review_gmo: { title: 'Awaiting Review - GMO', color: 'info', icon: 'ri-search-eye-line' },
  awaiting_approval_div_head: { title: 'Awaiting Approval - Div Head', color: 'warning', icon: 'ri-user-star-line' },
  booking_payment_in_progress: { title: 'Booking & Payment in Progress', color: 'info', icon: 'ri-wallet-3-line' },
  released: { title: 'Released', color: 'success', icon: 'ri-checkbox-circle-line' },
}

const headerClass = 'px-3 py-3 text-caption font-weight-bold text-medium-emphasis bg-grey-lighten-4 text-no-wrap'
const cellClass = 'px-3 py-3'
const col = (title, key, extra = {}) => ({ title, key, sortable: false, headerProps: { class: headerClass }, cellProps: { class: cellClass }, ...extra })

const headers = [
  col('ATTACHMENT', 'attachment', { align: 'center', width: 130 }),
  col('ACTION', 'action', { align: 'center', width: 160 }),
  col('STATUS', 'status', { align: 'center' }),
  col('TYPE', 'type', { align: 'center' }),
  col('TRAVELER / GUEST', 'traveler', { align: 'center' }),
  col('ROUTE / HOTEL', 'route_hotel', { align: 'center' }),
  col('TRAVEL / STAY DATE', 'stay_period', { align: 'center' }),
]

const statusTabs = computed(() => {
  const countOf = status => requests.value.filter(row => row.status === status).length

  return [
    { value: 'all', title: 'Total', icon: 'ri-stack-line', color: 'primary', count: requests.value.length },
    ...Object.entries(statusMeta).map(([value, meta]) => ({ value, ...meta, count: countOf(value) })),
  ]
})

const filteredRows = computed(() => {
  const query = search.value.trim().toLowerCase()

  return requests.value.filter(row => (statusTab.value === 'all' || row.status === statusTab.value) && (!query || `${row.id} ${row.traveler} ${row.route_hotel}`.toLowerCase().includes(query)))
})
</script>

<template>
  <BrandCard>
    <template #stats>
      <VRow dense>
        <VCol v-for="tab in statusTabs" :key="tab.value" cols="6" sm="4" lg="3">
          <VCard :variant="statusTab === tab.value ? 'tonal' : 'outlined'" :color="statusTab === tab.value ? tab.color : undefined" class="pa-3 d-flex align-center cursor-pointer stat-tile h-100" style="gap: 12px" @click="statusTab = tab.value">
            <VAvatar :color="tab.color" variant="tonal" rounded="lg" size="40">
              <VIcon :icon="tab.icon" size="20" />
            </VAvatar>
            <div>
              <div class="text-h6 font-weight-bold">{{ tab.count }}</div>
              <div class="text-caption text-medium-emphasis">{{ tab.title }}</div>
            </div>
          </VCard>
        </VCol>
      </VRow>
    </template>

    <template #action>
      <VTooltip :disabled="auth.can('external-form-request.create')" location="top">
        <template #activator="{ props: activatorProps }">
          <span v-bind="activatorProps" class="d-inline-block">
            <VBtn variant="flat" color="primary" prepend-icon="ri-add-line" :disabled="!auth.can('external-form-request.create')" v-bind="activatorProps" @click="openPickType">New External Form</VBtn>
          </span>
        </template>
        <span>You do not have permission to create an external form request.</span>
      </VTooltip>
    </template>

    <template #search>
      <VTextField v-model="search" prepend-inner-icon="ri-search-line" placeholder="Search external form..." variant="outlined" density="comfortable" hide-details clearable style="min-width: 260px; max-width: 320px" />
    </template>

    <VDataTable class="orders-table" :headers="headers" :items="filteredRows" :loading="loading" show-current-page item-value="id" hover no-data-text="No external form requests found.">
      <template #item.attachment="{ item }">
        <VBtn v-if="item.raw.attachment_url" color="grey-darken-1" variant="tonal" @click="openAttachment(item)">
          <VIcon icon="ri-attachment-2" />
          <VTooltip activator="parent" location="top">View Attachment</VTooltip>
        </VBtn>
        <span v-else>-</span>
      </template>
      <template #item.action="{ item }">
        <VBtnGroup density="compact" variant="tonal">
          <VBtn v-if="item.raw.status === 'draft' || item.raw.status === 'revision_required'" color="green-darken-1" @click="editDraft(item)"><VIcon icon="ri-edit-line" /><VTooltip activator="parent" location="top">Edit</VTooltip></VBtn>
          <VBtn v-else-if="item.raw.can_approve" color="grey-darken-1" @click="openPdf(item, 'decide')"><VIcon :icon="item.raw.pending_action === 'Review' ? 'ri-eye-line' : 'ri-checkbox-circle-line'" /><VTooltip activator="parent" location="top">{{ item.raw.pending_action === 'Review' ? 'Review' : 'Approval' }}</VTooltip></VBtn>
          <VBtn v-else-if="item.raw.can_process" color="orange-darken-3" @click="openPdf(item, 'decide')"><VIcon icon="ri-wallet-3-line" /><VTooltip activator="parent" location="top">Booking & Payment</VTooltip></VBtn>
          <VBtn v-else color="grey-darken-1" @click="openPdf(item)"><VIcon icon="ri-eye-line" /><VTooltip activator="parent" location="top">View</VTooltip></VBtn>
          <VBtn color="blue-grey-darken-1" @click="openHistory(item)"><VIcon icon="ri-history-line" /><VTooltip activator="parent" location="top">History Log</VTooltip></VBtn>
        </VBtnGroup>
      </template>
      <template #item.status="{ item }">
        <VChip size="small" variant="tonal" :color="statusMeta[item.status]?.color || 'primary'" class="text-no-wrap">
          <VIcon icon="ri-checkbox-blank-circle-fill" size="8" start /> {{ statusMeta[item.status]?.title || item.status }}
        </VChip>
      </template>
      <template #item.type="{ item }">
        <div class="d-flex ga-1 justify-center">
          <VChip size="small" variant="tonal" :color="item.type_color" class="text-no-wrap">{{ item.type }}</VChip>
          <VChip v-if="item.trip_label" size="small" variant="tonal" :color="tripColor" class="text-no-wrap">{{ item.trip_label }}</VChip>
        </div>
      </template>
      <template #item.traveler="{ item }"><span class="text-body-2" style="white-space: pre-line">{{ item.traveler }}</span></template>
      <template #item.route_hotel="{ item }"><span class="text-body-2">{{ item.route_hotel }}</span></template>
      <template #item.stay_period="{ item }"><span class="text-body-2 text-no-wrap">{{ item.stay_period }}</span></template>
    </VDataTable>
  </BrandCard>

  <PickTypeModal
    v-model="showPickType"
    title="New External Form Request"
    subtitle="Choose the type of external form request you want to submit."
    :items="formTypes"
    @select="onTypeSelected"
  />

  <AirplaneFerryFormModal v-model="showAirplaneFerryForm" :edit-item="editingItem" @submitted="fetchRequests" @back="backToPickType" />

  <HotelReservationFormModal v-model="showHotelReservationForm" :edit-item="editingItem" @submitted="fetchRequests" @back="backToPickType" />

  <HistoryLogModal v-model="historyDialog" :loading="historyLoading" :rounds="historyRounds" />

  <GmoReviewModal
    v-model="showGmoReview"
    :submitting="gmoSubmitting"
    :loading-div-heads="divHeadsLoading"
    :div-head-items="divHeadItems"
    :is-hotel="pdfRow?.raw?.type === 'hotel_reservation'"
    :departure-date="formatDate(pdfRow?.raw?.departure_date)"
    :return-date="formatDate(pdfRow?.raw?.return_date)"
    :check-in-date="formatDate(pdfRow?.raw?.check_in_date)"
    :check-out-date="formatDate(pdfRow?.raw?.check_out_date)"
    @back="handleGmoBack"
    @submit="handleGmoSubmit"
  />

  <GmoReviewModal
    v-model="showGmoRelease"
    mode="release"
    :submitting="releasing"
    :is-hotel="pdfRow?.raw?.type === 'hotel_reservation'"
    :departure-date="formatDate(pdfRow?.raw?.departure_date)"
    :return-date="formatDate(pdfRow?.raw?.return_date)"
    :check-in-date="formatDate(pdfRow?.raw?.check_in_date)"
    :check-out-date="formatDate(pdfRow?.raw?.check_out_date)"
    :prefill="releasePrefill"
    @back="handleGmoReleaseBack"
    @submit="handleGmoReleaseSubmit"
  />

  <AttachmentModal v-model="attachmentDialog" :attachment-url="attachmentUrl" />

  <PdfViewerModal
    v-model="pdfDialog"
    title="External Travel Purchase Request"
    :pdf-url="pdfUrl"
    :loading="pdfLoading"
    :can-decide="pdfCanDecide"
    :hide-back="true"
    :approving="deciding"
    confirm-text="I confirm that I have reviewed the External Form Request document and agree to proceed with my decision."
    @approve="handleDecision('approve')"
    @reject="handleDecision('reject')"
    @revision="handleDecision('revision')"
  >
    <template #decide="{ agreed }">
      <VCardActions v-if="agreed" class="px-8 pb-5 pt-0 justify-end ga-3">
        <VBtn v-if="!isReleaseFlow" variant="flat" color="warning" @click="openNote('revision')">Revision Required</VBtn>
        <VBtn v-if="!isReleaseFlow" variant="flat" color="error" @click="openNote('reject')">Rejected</VBtn>
        <VBtn variant="flat" color="primary" :loading="deciding" @click="handleApproveConfirm">{{ isContinueFlow || isReleaseFlow ? 'Continue' : (isReviewStep ? 'Reviewed' : 'Approve') }}</VBtn>
      </VCardActions>
    </template>
    <template #actions>
      <VDivider />
      <VCardActions v-if="canResend" class="px-8 py-5 justify-end ga-3">
        <VBtn variant="flat" color="primary" :loading="resending" @click="handleResendEmail">Resend Email</VBtn>
      </VCardActions>
    </template>
  </PdfViewerModal>

  <NoteModal v-model="noteDialog" :meta="noteMeta" @submit="handleNoteSubmit" />

</template>
