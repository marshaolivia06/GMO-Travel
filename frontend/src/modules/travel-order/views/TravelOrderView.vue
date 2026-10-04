<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ApprovalFlowModal from '../components/modal/ApprovalFlowModal.vue'
import NoteModal from '../components/modal/NoteModal.vue'
import PdfViewerModal from '../components/modal/PdfViewerModal.vue'
import PickTypeModal from '../components/modal/PickTypeModal.vue'
import TripFormModal from '../components/modal/TripFormModal.vue'
import { getTravelOrders, getTravelOrder, getTravelOrderPdf, createTravelOrder, updateTravelOrder, approveTravelOrder } from '../services/travelOrderService'
import { useConfirm } from '../../../composables/useConfirm'
import { useToastStore } from '../../../stores/toast'

const route = useRoute()
const router = useRouter()
const { confirm } = useConfirm()
const toast = useToastStore()

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
const noteDialog = ref(false)
const noteAction = ref('reject')
const filters = reactive({ search: '', status: null })

const statusOptions = ['Awaiting Approval', 'Awaiting Approval Manager', 'Awaiting Approval Director', 'Awaiting Approval President Director', 'Awaiting Approval GMO', 'Awaiting GMO Processing', 'Awaiting GMO Booking Preparation', 'Awaiting GMO Document Issuance', 'Processed by GMO', 'Approved']

const statusColor = {
  'Awaiting Approval': 'warning', 'Processed by GMO': 'success', 'Approved': 'success',
  'Awaiting Approval Manager': 'warning', 'Awaiting Approval Director': 'warning',
  'Awaiting Approval President Director': 'warning', 'Awaiting Approval GMO': 'warning',
  'Revision Required': 'warning', 'Cancelled': 'error',
}

const statusLabel = {
  awaiting_approval: 'Awaiting Approval', awaiting_approval_manager: 'Awaiting Approval Manager',
  awaiting_approval_director: 'Awaiting Approval Director', awaiting_approval_predir: 'Awaiting Approval President Director',
  awaiting_approval_gmo: 'Awaiting Approval GMO', approved: 'Approved', revision_required: 'Revision Required',
  cancelled: 'Cancelled', awaiting_gmo_processing: 'Awaiting GMO Processing',
  awaiting_gmo_booking_preparation: 'Awaiting GMO Booking Preparation',
  awaiting_gmo_document_issuance: 'Awaiting GMO Document Issuance', processed_by_gmo: 'Processed by GMO',
}

const typeColor = { 'Business Trip - Individual': 'primary', 'Business Trip - Group Trip': 'info', 'Annual Trip': 'success' }
const typeLabel = { individual: 'Business Trip - Individual', group: 'Business Trip - Group Trip', annual: 'Annual Trip' }
const headerClass = 'px-3 py-3 text-caption font-weight-bold text-medium-emphasis bg-grey-lighten-4 text-no-wrap'
const cellClass = 'px-3 py-3'
const col = (title, key, extra = {}) => ({ title, key, sortable: false, headerProps: { class: headerClass }, cellProps: { class: cellClass }, ...extra })

const headers = [
  col('ACTION', 'action', { align: 'center', width: 160 }), col('ID', 'id'), col('REQUEST TYPE', 'type'),
  col('SUBJECT', 'subject'), col('PERIOD', 'period'), col('ARRANGEMENT', 'arrangement'), col('STATUS', 'status'),
]

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

function updateStatus(status, message, type = 'success') {
  const orderNumber = selected.value?.order_number
  const index = orders.value.findIndex(order => order.order_number === orderNumber)
  if (index !== -1) orders.value[index] = { ...orders.value[index], status }
  detailDialog.value = false
  approvalDialog.value = false
  showMessage(message, type)
}

async function openApproval(row) {
  selected.value = row.raw
  approvalDialog.value = true
  approvalLoading.value = true
  try { await refreshDetail(row.id) } finally { approvalLoading.value = false }
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
    status: 'cancelled', message: 'Travel order has been cancelled.', type: 'success',
    confirmTitle: 'Reject Travel Order',
    confirmMessage: order => `Are you sure you want to reject ${order}? This order will be cancelled.`,
  },
  revision: {
    title: 'Return for Revision', label: 'What needs to be revised', button: 'Revision', color: 'warning',
    status: 'revision_required', message: 'Travel order has been returned for revision.', type: 'warning',
    confirmTitle: 'Return for Revision', confirmMessage: order => `Are you sure you want to return ${order} for revision?`,
  },
}

const currentNoteMeta = computed(() => noteMeta[noteAction.value])
function openNote(action) { noteAction.value = action; noteDialog.value = true }
const handleReject = () => openNote('reject')
const handleRevision = () => openNote('revision')

async function handleNoteSubmit(text) {
  const meta = currentNoteMeta.value
  const ok = await confirm({ title: meta.confirmTitle, message: meta.confirmMessage(selected.value?.order_number) })
  if (!ok) return
  noteDialog.value = false
  updateStatus(meta.status, meta.message, meta.type)
}

const approvalChip = {
  submitted: { text: 'Requested', color: 'primary', caption: 'Requested by' },
  approved: { text: 'Approved', color: 'success', caption: 'Approved by' },
  rejected: { text: 'Rejected', color: 'error', caption: 'Rejected by' },
  revision: { text: 'Revision', color: 'warning', caption: 'Revision requested by' },
  pending: { text: 'Waiting', color: 'grey', caption: 'Awaiting approval' },
}

const approvalCards = computed(() => {
  const steps = selected.value?.approvals || []
  const currentIndex = steps.findIndex(step => step.status === 'pending')
  return steps.map((step, index) => {
    const meta = approvalChip[step.status] || approvalChip.pending
    const isPending = step.status === 'pending'
    const isCurrent = isPending && index === currentIndex
    return {
      key: step.id, title: step.role_name || '-', chipText: meta.text, chipColor: meta.color,
      caption: meta.caption, name: isPending ? '' : (step.name || '-'), time: formatDateTime(step.acted_at),
      isCurrent, hint: isPending ? (isCurrent ? `Awaiting ${step.name || '-'}` : 'Previous step pending') : '',
      hintClass: isCurrent ? 'text-info font-weight-bold' : 'text-medium-emphasis',
    }
  })
})

async function loadOrders() {
  loading.value = true
  errorMessage.value = ''
  try {
    const response = await getTravelOrders()
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

      <VDataTable class="orders-table" :headers="headers" :items="filteredRows" :loading="loading" :items-per-page="10" item-value="id" hover no-data-text="No travel orders found.">
        <template #item.id="{ item }"><span class="font-weight-bold text-body-2 text-no-wrap">{{ item.id }}</span></template>
        <template #item.type="{ item }"><VChip size="small" variant="tonal" :color="typeColor[item.type] || 'primary'" class="text-no-wrap">{{ item.type }}</VChip></template>
        <template #item.subject="{ item }"><div class="font-weight-bold text-body-2">{{ item.subject }}</div><div class="text-caption text-medium-emphasis">{{ item.department || '-' }}</div></template>
        <template #item.period="{ item }"><span class="text-body-2 text-no-wrap">{{ item.period }}</span></template>
        <template #item.arrangement="{ item }"><span class="text-body-2 text-no-wrap">{{ item.arrangement }}</span></template>
        <template #item.status="{ item }">
          <VChip size="small" variant="tonal" :color="statusColor[item.status] || 'primary'" class="text-no-wrap"><VIcon icon="ri-checkbox-blank-circle-fill" size="8" start />{{ item.status }}</VChip>
        </template>
        <template #item.action="{ item }">
          <div class="d-flex align-center justify-center ga-2">
            <VBtn v-if="item.raw.status === 'draft'" size="small" variant="flat" color="success" :loading="draftLoading" @click="continueDraft(item)">Edit</VBtn>
            <template v-else>
              <VBtn size="small" variant="flat" color="primary" @click="openDetail(item)">View</VBtn>
             <VBtn v-if="item.raw.can_approve" size="small" variant="flat" color="success" @click="openApproval(item)">Approval</VBtn>
            </template>
          </div>
        </template>
      </VDataTable>
    </VCard>

    <PickTypeModal v-model="requestDialog" title="New Travel Request" subtitle="Select the type of request you want to submit." :items="requestTypes" @select="selectRequest" />
    <PickTypeModal v-model="businessDialog" title="Business Trip" subtitle="How many people are traveling?" :items="businessTypes" show-back @back="backToRequest" @select="selectBusinessType" />
    <TripFormModal v-model="dialog" :type="dialogType" :loading="submitting" :initial-data="editingDraft ? selected : null" @back="backToBusiness" @close="closeDialog" @submit="submitOrder" />
    <PdfViewerModal v-model="detailDialog" :title="detailMode === 'review' ? 'Review Travel Order' : 'Travel Order Detail'" :pdf-url="pdfUrl" :loading="pdfLoading" :can-decide="canDecide" :approving="approving" @back="backToApproval" @reject="handleReject" @revision="handleRevision" @approve="handleApprove" />
    <ApprovalFlowModal v-model="approvalDialog" :loading="approvalLoading" :cards="approvalCards" :can-approve="canApprove" @review="openReview" />
    <NoteModal v-model="noteDialog" :meta="currentNoteMeta" @submit="handleNoteSubmit" />
  </div>
</template>