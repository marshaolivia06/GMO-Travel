<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ApprovalFlowModal from '../components/modal/ApprovalFlowModal.vue'
import HistoryLogModal from '../components/modal/HistoryLogModal.vue'
import NoteModal from '../components/modal/NoteModal.vue'
import PdfViewerModal from '../components/modal/PdfViewerModal.vue'
import PickTypeModal from '../components/modal/PickTypeModal.vue'
import TripFormModal from '../components/modal/TripFormModal.vue'
import GmoProcessModal from '../components/modal/GmoProcessModal.vue'
import GmoBookingModal from '../components/modal/GmoBookingModal.vue'
import { getTravelOrders, getTravelOrder, getTravelOrderPdf, getTravelOrderHistory, createTravelOrder, updateTravelOrder, approveTravelOrder, rejectTravelOrder, revisionTravelOrder } from '../services/travelOrderService'
import { useConfirm } from '../../../composables/useConfirm'
import { useToastStore } from '../../../stores/toast'
import { useAuthStore } from '../../../stores/auth'

const route = useRoute()
const router = useRouter()
const { confirm } = useConfirm()
const toast = useToastStore()
const authStore = useAuthStore()
const isAdmin = computed(() => authStore.user?.role?.name === 'Admin')
const isAdminGmo = computed(() => authStore.user?.roles?.includes('Admin-GMO'))
const page = ref(1)
const orders = ref([])
const loading = ref(false)
const errorMessage = ref('')
const detailDialog = ref(false)
const requestDialog = ref(false)
const businessDialog = ref(false)
const dialog = ref(false)
const selected = ref(null)
const dialogType = ref(null)
const submitting = ref(false)
const approving = ref(false)
const editingDraft = ref(false)
const draftLoading = ref(false)
const detailMode = ref('view')
const pdfUrl = ref('')
const pdfLoading = ref(false)
const approvalDialog = ref(false)
const approvalLoading = ref(false)
const processDialog = ref(false)
const processLoading = ref(false)
const bookingDialog = ref(false)
const bookingLoading = ref(false)
const noteDialog = ref(false)
const noteAction = ref('reject')
const historyDialog = ref(false)
const historyLoading = ref(false)
const historyLogs = ref([])
const filters = reactive({ search: '', status: null })

const statusOptions = ['Awaiting Approval', 'Awaiting Approval GMO', 'Awaiting GMO Processing', 'Awaiting GMO Booking Preparation', 'Awaiting GMO Document Issuance', 'Processed by GMO']

const statusColor = {
  'Awaiting Approval': 'warning', 'Processed by GMO': 'success',
  'Awaiting Approval GMO': 'warning', 'Awaiting GMO Processing': 'warning',
  'Awaiting GMO Booking Preparation': 'warning', 'Awaiting GMO Document Issuance': 'warning',
  'Revision Required': 'warning', 'Cancelled': 'error',
}

const statusLabel = {
  awaiting_approval: 'Awaiting Approval', awaiting_approval_gmo: 'Awaiting Approval GMO',
  revision_required: 'Revision Required', draft: 'Draft', cancelled: 'Cancelled',
  rejected: 'Rejected', revision: 'Revision Required',
  awaiting_gmo_processing: 'Awaiting GMO Processing',
  awaiting_gmo_booking_preparation: 'Awaiting GMO Booking Preparation',
  awaiting_gmo_document_issuance: 'Awaiting GMO Document Issuance',
  processed_by_gmo: 'Processed by GMO',
}

const typeColor = { 'Business Trip - Individual': 'primary', 'Business Trip - Group Trip': 'info', 'Annual Trip': 'success' }
const typeLabel = { individual: 'Business Trip - Individual', group: 'Business Trip - Group Trip', annual: 'Annual Trip' }
const headerClass = 'px-3 py-3 text-caption font-weight-bold text-medium-emphasis bg-grey-lighten-4 text-no-wrap'
const cellClass = 'px-3 py-3'
const col = (title, key, extra = {}) => ({ title, key, sortable: false, headerProps: { class: headerClass }, cellProps: { class: cellClass }, ...extra })

const headers = computed(() => [
  col('ACTION', 'action', { align: 'center', width: 240 }),
  col('STATUS', 'status', { align: 'center' }),
  ...(authStore.user?.name === 'Admin' ? [col('ID', 'id')] : []),
  col('REQUEST TYPE', 'type', { align: 'center' }),
  col('SUBJECT', 'subject'),
  col('PERIOD', 'period', { align: 'center' }),
  col('ARRANGEMENT', 'arrangement'),
])

const requestTypes = [
  { key: 'annual', badge: 'AL', title: 'Annual Leave', desc: 'Submit your annual leave request.', points: ['Leave period selection', 'Leave balance check'] },
  { key: 'business', badge: 'BT', title: 'Business Trip', desc: 'Create an individual trip or a group trip request.', points: ['Individual Trip', 'Group Trip', 'Travel region and travel advance'] },
]

const businessTypes = [
  { key: 'individual', badge: 'IT', title: 'Individual Trip', desc: 'Business trip for one person.', points: ['Route and travel dates', 'Ticket and accommodation', 'Travel advance'] },
  { key: 'group', badge: 'GT', title: 'Group Trip', desc: 'Business trip for multiple people.', points: ['Traveler list', 'Route and travel dates', 'Ticket and accommodation'] },
]

async function continueDraft(row) {
  if (draftLoading.value) return
  draftLoading.value = true
  try {
    const response = await getTravelOrder(row.id)
    const order = response?.data?.data ?? response?.data ?? response
    selected.value = order
    dialogType.value = order.trip_type || 'individual'
    editingDraft.value = true
    dialog.value = true
  } catch (error) {
    console.error('Load draft error:', error)
    showMessage(error?.response?.data?.message || 'Failed to load draft Travel Order.', 'error')
  } finally {
    draftLoading.value = false
  }
}
function backToApproval() { detailDialog.value = false; approvalDialog.value = true }
function showMessage(text, type = 'success') { toast[type]?.(text) }
function toStatusLabel(status) { return status ? statusLabel[status] || status : 'Awaiting Approval Manager' }
const canApprove = computed(() => selected.value?.can_approve === true)
const canDecide = computed(() => detailMode.value === 'review' && canApprove.value)

function formatDate(value) {
  if (!value) return '-'
  const date = new Date(value)
  return isNaN(date.getTime()) ? '-' : date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

function formatDateTime(value) {
  if (!value) return ''
  const date = new Date(value)
  if (isNaN(date.getTime())) return ''
  return date.toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false }).replace(',', '')
}

function resolveArrangement(order) {
  const a = order.ferry_arrangement ?? order.ticket_arrangement
  const b = order.accommodation_arrangement
  if (!a && !b) return '-'
  if (!b || a === b) return a || b
  if (!a) return b
  if (b === 'Not Required') return a
  return 'Mixed'
}

function normalize(order) {
  return {
    raw: order, id: order.id, type: typeLabel[order.trip_type] || 'Business Trip - Individual',
    subject: order.subject || order.user?.name || '-', department: order.department?.name || order.department_name || '',
    period: `${formatDate(order.departure_date)} – ${formatDate(order.return_date)}`,
    arrangement: resolveArrangement(order), status: toStatusLabel(order.status),
  }
}

const rows = computed(() => orders.value.map(normalize))

const filteredRows = computed(() => {
  const query = filters.search.trim().toLowerCase()
  return rows.value.filter(row => (!query || `${row.id} ${row.subject}`.toLowerCase().includes(query)) && (!filters.status || row.status === filters.status))
})

function clearFilters() { filters.search = ''; filters.status = null }
function openNewRequest() { requestDialog.value = true }

function selectRequest(type) {
  if (type === 'annual') {
    requestDialog.value = false
    showMessage('Annual Leave form is not available yet.', 'info')
    return
  }
  requestDialog.value = false
  businessDialog.value = true
}

function backToRequest() { businessDialog.value = false; requestDialog.value = true }
function backToBusiness() { dialog.value = false; dialogType.value = null; businessDialog.value = true }
function selectBusinessType(type) { businessDialog.value = false; dialogType.value = type; dialog.value = true }
function closeDialog() { dialog.value = false; dialogType.value = null }

async function refreshDetail(id) {
  try {
    const response = await getTravelOrder(id)
    selected.value = response?.data?.data ?? response?.data ?? response
  } catch (error) {
    console.error('Load detail error:', error)
  }
}

async function openDetail(row, mode = 'view') {
  detailMode.value = mode
  selected.value = row.raw
  detailDialog.value = true
  await Promise.all([refreshDetail(row.id), loadPdf(row.id)])
}

async function loadPdf(id) {
  pdfLoading.value = true
  try {
    const blob = await getTravelOrderPdf(id)
    revokePdf()
    const name = selected.value?.order_number || `travel-order-${id}`
    const file = new File([blob], `${name}.pdf`, { type: 'application/pdf' })
    pdfUrl.value = URL.createObjectURL(file)
  } catch (error) {
    console.error('Load PDF error:', error)
    showMessage('Failed to load PDF.', 'error')
  } finally {
    pdfLoading.value = false
  }
}

function revokePdf() {
  if (pdfUrl.value) {
    URL.revokeObjectURL(pdfUrl.value)
    pdfUrl.value = ''
  }
}

watch(detailDialog, open => { if (!open) revokePdf() })

async function openApproval(row) {
  selected.value = row.raw
  approvalDialog.value = true
  approvalLoading.value = true
  try { await refreshDetail(row.id) } finally { approvalLoading.value = false }
}

async function openProcess(row) {
  selected.value = row.raw

  if (row.raw.status === 'awaiting_gmo_booking_preparation') {
    bookingDialog.value = true
    bookingLoading.value = true
    try { await refreshDetail(row.id) } finally { bookingLoading.value = false }
    return
  }

  processDialog.value = true
  processLoading.value = true
  try { await refreshDetail(row.id) } finally { processLoading.value = false }
}

async function openHistory(row) {
  selected.value = row.raw
  historyLogs.value = []
  historyDialog.value = true
  historyLoading.value = true
  try {
    const response = await getTravelOrderHistory(row.id)
    historyLogs.value = response?.data ?? []
  } catch (error) {
    console.error('Load history error:', error)
    showMessage(error?.response?.data?.message || 'Failed to load history log.', 'error')
  } finally {
    historyLoading.value = false
  }
}

function openReview() {
  approvalDialog.value = false
  openDetail({ id: selected.value.id, raw: selected.value }, 'review')
}

async function handleApprove() {
  if (approving.value) return
  const ok = await confirm({ title: 'Approve Travel Order', message: `Are you sure you want to approve ${selected.value?.order_number}?` })
  if (!ok) return
  approving.value = true
  try {
    await approveTravelOrder(selected.value.id)
    await loadOrders()
    detailDialog.value = false
    approvalDialog.value = false
    showMessage('Travel order approved.')
  } catch (error) {
    showMessage(error?.response?.data?.message || 'Failed to approve travel order.', 'error')
  } finally {
    approving.value = false
  }
}

const noteMeta = {
  reject: {
    title: 'Reject Travel Order', label: 'Reason for rejection', button: 'Reject', color: 'error',
    message: 'Travel order has been rejected.', type: 'success',
    confirmTitle: 'Reject Travel Order',
    confirmMessage: order => `Are you sure you want to reject ${order}?`,
  },
  revision: {
    title: 'Return for Revision', label: 'What needs to be revised', button: 'Revision', color: 'warning',
    message: 'Travel order has been returned for revision.', type: 'warning',
    confirmTitle: 'Return for Revision', confirmMessage: order => `Are you sure you want to return ${order} for revision?`,
  },
}

const currentNoteMeta = computed(() => noteMeta[noteAction.value])
function openNote(action) { noteAction.value = action; noteDialog.value = true }
const handleReject = () => openNote('reject')
const handleRevision = () => openNote('revision')

async function handleNoteSubmit(text) {
  if (approving.value) return
  const meta = currentNoteMeta.value
  const ok = await confirm({ title: meta.confirmTitle, message: meta.confirmMessage(selected.value?.order_number) })
  if (!ok) return

  approving.value = true
  try {
    const call = noteAction.value === 'reject' ? rejectTravelOrder : revisionTravelOrder
    await call(selected.value.id, text)
    noteDialog.value = false
    detailDialog.value = false
    approvalDialog.value = false
    await loadOrders()
    showMessage(meta.message, meta.type)
  } catch (error) {
    showMessage(error?.response?.data?.errors?.remark?.[0] || error?.response?.data?.message || 'Failed to process travel order.', 'error')
  } finally {
    approving.value = false
  }
}

const approvalChip = {
  submitted: { text: 'Requested', color: 'primary', caption: 'Requested by' },
  approved: { text: 'Approved', color: 'success', caption: 'Approved by' },
  rejected: { text: 'Rejected', color: 'error', caption: 'Rejected by' },
  revision: { text: 'Revision', color: 'warning', caption: 'Revision requested by' },
  pending: { text: 'Waiting', color: 'grey', caption: 'Awaiting approval' },
  cancelled: { text: 'Cancelled', color: 'grey', caption: 'Not processed' },
}

const approvalCards = computed(() => {
  const all = selected.value?.approvals || []
  // Hanya tampilkan putaran terakhir (mulai dari baris 'submitted' terbaru)
  const lastRound = all.map(step => step.status).lastIndexOf('submitted')
  const steps = lastRound > 0 ? all.slice(lastRound) : all
  const currentIndex = steps.findIndex(step => step.status === 'pending')

  return steps.map((step, index) => {
    const meta = approvalChip[step.status] || approvalChip.pending
    const isPending = step.status === 'pending'

    return {
      key: step.id, title: step.role_name || '-', chipText: meta.text, chipColor: meta.color,
      caption: meta.caption,
      name: ['pending', 'cancelled'].includes(step.status) ? '' : (step.name || '-'),
      time: formatDateTime(step.acted_at),
      note: ['rejected', 'revision'].includes(step.status) ? (step.note || '') : '',
      isCurrent: isPending && index === currentIndex,
    }
  })
})

const historyChip = {
  submitted: { text: 'Requested', color: 'primary' },
  approved: { text: 'Approved', color: 'success' },
  rejected: { text: 'Rejected', color: 'error' },
  revision: { text: 'Revision', color: 'warning' },
}

const historyRounds = computed(() => {
  const groups = new Map()

  for (const log of historyLogs.value) {
    const round = log.round ?? 1
    const meta = historyChip[log.event] || { text: log.event, color: 'grey' }

    if (!groups.has(round)) groups.set(round, [])

    groups.get(round).push({
      key: log.id,
      chipText: log.final ? 'Approved' : meta.text,
      chipColor: meta.color,
      role: log.role || '-',
      name: log.causer || '-',
      time: formatDateTime(log.created_at),
      note: log.remark || log.note || '',
    })
  }

  return [...groups.entries()]
  .sort((a, b) => a[0] - b[0])
    .map(([round, items]) => ({ round, items }))
})

async function loadOrders() {
  loading.value = true
  errorMessage.value = ''
  try {
    const response = await getTravelOrders({ per_page: 1000 })
    const data = response.data ?? response
    orders.value = Array.isArray(data) ? data : data.data || []
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Failed to load travel orders.'
  } finally {
    loading.value = false
  }
}

async function submitOrder(payload) {
  if (submitting.value) return
  submitting.value = true
  try {
    const requestPayload = { ...payload, status: payload.status || 'submitted' }
    if (editingDraft.value && selected.value?.id) await updateTravelOrder(selected.value.id, requestPayload)
    else await createTravelOrder(requestPayload)
    closeDialog()
    editingDraft.value = false
    selected.value = null
    await loadOrders()
    showMessage(requestPayload.status === 'draft' ? 'Travel order saved as draft.' : 'Travel order submitted successfully.')
  } catch (error) {
    console.log('Submit error:', error.response?.data)
    const errors = error?.response?.data?.errors
    const firstError = errors ? Object.values(errors).flat()[0] : null
    showMessage(firstError || error?.response?.data?.message || 'Failed to save travel order.', 'error')
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  loadOrders()
  if (route.query.submitted) {
    showMessage('Travel order submitted successfully.')
    router.replace({ query: {} })
  }
})
</script>

<template>
  <div>
    <div class="mb-6">
      <div class="text-overline text-medium-emphasis">EMPLOYEE WORKSPACE</div>
      <div class="d-flex align-center justify-space-between">
        <div>
          <div class="text-h4 font-weight-bold mb-1">My Travel Orders</div>
          <div class="text-body-1 text-medium-emphasis">Track approval and GMO operational status using one current Travel Order status.</div>
        </div>
        <VBtn color="primary" prepend-icon="ri-add-line" @click="openNewRequest">New Request</VBtn>
      </div>
    </div>

    <VCard rounded="lg" elevation="1">
      <VCardItem class="px-6 py-4">
        <VCardTitle class="text-subtitle-1 font-weight-bold">Travel Order Inquiry</VCardTitle>
        <template #append><VChip size="small" variant="tonal" color="primary">{{ filteredRows.length }} requests</VChip></template>
      </VCardItem>
      <VDivider />
      <VCardText class="filter-section px-6 py-5">
        <VAlert v-if="errorMessage" type="error" variant="tonal" class="mb-4">{{ errorMessage }}</VAlert>
        <VRow align="end" dense>
          <VCol cols="12" md="4">
            <div class="text-caption font-weight-bold mb-1">Travel Order ID / Subject</div>
            <VTextField v-model="filters.search" placeholder="Search" prepend-inner-icon="ri-search-line" variant="outlined" density="compact" hide-details clearable />
          </VCol>
          <VCol cols="12" md="3">
            <div class="text-caption font-weight-bold mb-1">Status</div>
            <VSelect v-model="filters.status" :items="statusOptions" placeholder="All statuses" variant="outlined" density="compact" hide-details clearable />
          </VCol>
          <VCol cols="12" md="2">
            <VBtn variant="tonal" color="error" block height="40" @click="clearFilters">Clear</VBtn>
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />

      <VDataTable class="orders-table" :headers="headers" :items="filteredRows" :loading="loading" show-current-page item-value="id" hover no-data-text="No travel orders found.">
        <template #item.id="{ item }"><span class="font-weight-bold text-body-2 text-no-wrap">{{ item.id }}</span></template>
        <template #item.type="{ item }"><VChip size="small" variant="tonal" :color="typeColor[item.type] || 'primary'" class="text-no-wrap">{{ item.type }}</VChip></template>
        <template #item.subject="{ item }"><div class="font-weight-bold text-body-2">{{ item.subject }}</div><div class="text-caption text-medium-emphasis">{{ item.department || '-' }}</div></template>
        <template #item.period="{ item }"><span class="text-body-2 text-no-wrap">{{ item.period }}</span></template>
        <template #item.arrangement="{ item }"><span class="text-body-2 text-no-wrap">{{ item.arrangement }}</span></template>
        <template #item.status="{ item }">
          <VChip size="small" variant="tonal" :color="statusColor[item.status] || 'primary'" class="text-no-wrap"><VIcon icon="ri-checkbox-blank-circle-fill" size="8" start />{{ item.status }}</VChip>
        </template>
        <template #item.action="{ item }">
  <VBtnGroup density="comfortable" variant="tonal">
    <VBtn v-if="item.raw.status === 'draft'" color="success" :loading="draftLoading" @click="continueDraft(item)"><VIcon icon="ri-edit-line" /><VTooltip activator="parent" location="top">Edit</VTooltip></VBtn>
    <template v-if="item.raw.status !== 'draft'">
  <VBtn color="info" @click="openDetail(item)"><VIcon icon="ri-eye-line" /><VTooltip activator="parent" location="top">View</VTooltip></VBtn>
  <VBtn v-if="isAdminGmo" color="success" @click="openProcess(item)"><VIcon icon="ri-refresh-line" /><VTooltip activator="parent" location="top">Process</VTooltip></VBtn>
  <VBtn v-else color="success" @click="openApproval(item)"><VIcon icon="ri-check-line" /><VTooltip activator="parent" location="top">Approval</VTooltip></VBtn>
  <VBtn color="secondary" @click="openHistory(item)"><VIcon icon="ri-history-line" /><VTooltip activator="parent" location="top">History Log</VTooltip></VBtn>
</template>
  </VBtnGroup>
</template>
      </VDataTable>
    </VCard>

    <PickTypeModal v-model="requestDialog" title="New Travel Request" subtitle="Select the type of request you want to submit." :items="requestTypes" @select="selectRequest" />
    <PickTypeModal v-model="businessDialog" title="Business Trip" subtitle="How many people are traveling?" :items="businessTypes" show-back @back="backToRequest" @select="selectBusinessType" />
    <TripFormModal v-model="dialog" :type="dialogType" :loading="submitting" :initial-data="editingDraft ? selected : null" @back="backToBusiness" @close="closeDialog" @submit="submitOrder" />
    <PdfViewerModal v-model="detailDialog" :title="detailMode === 'review' ? 'Review Travel Order' : 'Travel Order Detail'" :pdf-url="pdfUrl" :loading="pdfLoading" :can-decide="canDecide" :approving="approving" @back="backToApproval" @reject="handleReject" @revision="handleRevision" @approve="handleApprove" />
    <ApprovalFlowModal v-model="approvalDialog" :loading="approvalLoading" :cards="approvalCards" :can-approve="canApprove" @review="openReview" />
    <HistoryLogModal v-model="historyDialog" :loading="historyLoading" :rounds="historyRounds" />
    <NoteModal v-model="noteDialog" :meta="currentNoteMeta" @submit="handleNoteSubmit" />
    <GmoProcessModal v-model="processDialog" title="GMO Travel Order Processing" :status="selected?.status" :order="selected" :loading="processLoading" :processing="false" @cancel="processDialog = false" />
<GmoBookingModal v-model="bookingDialog" title="GMO Travel Order Booking" :status="selected?.status" :order="selected" :loading="bookingLoading" :processing="false" @cancel="bookingDialog = false" />
  </div>
</template>