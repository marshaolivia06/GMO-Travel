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
import { getTravelOrders, getTravelOrder, getTravelOrderGroup, getTravelOrderPdf, getTravelOrderHistory, createTravelOrder, updateTravelOrder, approveTravelOrder, rejectTravelOrder, revisionTravelOrder, completeGmoProcessing, cancelTravelOrder, getGroupTripUsers, updateGmoSchedule, updateGmoBookingItems, cancelGmoBookingItem, resendGmoBookingEmail, createTravelOrderGroup, getTravelOrderGroups } from '../services/travelOrderService'
import { useConfirm } from '../../../composables/useConfirm'
import { useToastStore } from '../../../stores/toast'
import { useAuthStore } from '../../../stores/auth'
import GmoAttachmentsModal from '../components/modal/GmoAttachmentsModal.vue'
import GmoHistoryModal from '../components/modal/GmoHistoryModal.vue'
import GroupTripForm from '../components/modal/GroupTripForm.vue'
import GroupTripDetailModal from '../components/modal/GroupTripDetailModal.vue'

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
const editingDraftId = ref(null)
const detailMode = ref('view')
const pdfUrl = ref('')
const pdfLoading = ref(false)
const pdfFromGmoHistory = ref(false)
const approvalDialog = ref(false)
const approvalLoading = ref(false)
const processDialog = ref(false)
const gmoHistoryDialog = ref(false)
const gmoHistoryLoading = ref(false)
const gmoHistoryOrder = ref(null)
const gmoHistoryBooking = ref(null)
const gmoHistoryEmail = ref(null)
const gmoHistoryProcessing = ref(false)
const processLoading = ref(false)
const processingOrderId = ref(null)
const bookingDialog = ref(false)
const bookingLoading = ref(false)
const bookingStage = ref('booking')
const savedBooking = ref(null)
const noteDialog = ref(false)
const noteAction = ref('reject')
const noteSource = ref('approval')
const noteReturnTo = ref('process')
const historyDialog = ref(false)
const historyLoading = ref(false)
const historyLogs = ref([])
const filters = reactive({ search: '', status: null })
const gmoTab = ref('awaiting')
const groupTrips = ref([])
const groupTripDialog = ref(false)
const groupTripSubmitting = ref(false)
const selectedGroupTrip = ref(null)
const memberOptions = ref([])
const groupTripViewDialog = ref(false)
const groupTripViewLoading = ref(false)
const processMode = ref('processing')
const processSubmitting = ref(false)
const attachmentsDialog = ref(false)
const attachmentsLoading = ref(false)
const selectedBooking = ref(null)
const selectedOrder = ref(null)

const gmoAwaitingStatuses = ['awaiting_gmo_processing', 'awaiting_gmo_booking_document_issuance']
const gmoHistoryStatuses = ['processed_by_gmo', 'cancelled']
const statusOptions = ['Awaiting Approval', 'Awaiting Approval GMO', 'Awaiting GMO Processing', 'Awaiting GMO Booking & Document Issuance', 'Processed by GMO']
const statusColor = { 'Awaiting Approval': 'warning', 'Processed by GMO': 'success', 'Awaiting Approval GMO': 'warning', 'Awaiting GMO Processing': 'warning', 'Awaiting GMO Booking & Document Issuance': 'warning', 'Revision Required': 'warning', 'Cancelled': 'error' }
const groupStatusColor = { draft: 'success', open: 'warning', closed: 'error' }
const statusLabel = { awaiting_approval: 'Awaiting Approval', awaiting_approval_gmo: 'Awaiting Approval GMO', revision_required: 'Revision Required', draft: 'Draft', cancelled: 'Cancelled', rejected: 'Rejected', revision: 'Revision Required', awaiting_gmo_processing: 'Awaiting GMO Processing', awaiting_gmo_booking_document_issuance: 'Awaiting GMO Booking & Document Issuance', processed_by_gmo: 'Processed by GMO' }
const typeColor = { 'Business Trip - Individual': 'primary', 'Business Trip - Group Trip': 'info', 'Annual Trip': 'success' }
const typeLabel = { individual: 'Business Trip - Individual', group: 'Business Trip - Group Trip', annual: 'Annual Trip' }
const headerClass = 'px-3 py-3 text-caption font-weight-bold text-medium-emphasis bg-grey-lighten-4 text-no-wrap'
const cellClass = 'px-3 py-3'
const col = (title, key, extra = {}) => ({ title, key, sortable: false, headerProps: { class: headerClass }, cellProps: { class: cellClass }, ...extra })

const headers = computed(() => [
  ...(isAdminGmo.value && gmoTab.value === 'history' ? [col('ATTACHMENT', 'attachment', { align: 'center', width: 130 })] : isAdminGmo.value ? [] : [col('ATTACHMENT', 'attachment', { align: 'center', width: 130 })]),
  col('ACTION', 'action', { align: 'center', width: 240 }),
  col('STATUS', 'status', { align: 'center' }),
  ...(authStore.user?.name === 'Admin' ? [col('ID', 'id')] : []),
  col('REQUEST TYPE', 'type', { align: 'center' }),
  col('SUBJECT', 'subject'),
  col('PERIOD', 'period', { align: 'center' }),
  col('ARRANGEMENT', 'arrangement', { align: 'center' }),
])

const requestTypes = [
  { key: 'annual', badge: 'AL', title: 'Annual Leave', desc: 'Submit your annual leave request.', points: ['Leave period selection', 'Leave balance check'] },
  { key: 'business', badge: 'BT', title: 'Business Trip', desc: 'Create an individual trip or a group trip request.', points: ['Individual Trip', 'Group Trip', 'Travel region and travel advance'] },
]

const businessTypes = [
  { key: 'individual', badge: 'IT', title: 'Individual Trip', desc: 'Business trip for one person.', points: ['Route and travel dates', 'Ticket and accommodation', 'Travel advance'] },
  { key: 'group', badge: 'GT', title: 'Group Trip', desc: 'Business trip for multiple people.', points: ['Traveler list', 'Route and travel dates', 'Ticket and accommodation'] },
]

const groupTripHeaders = [
  col('ACTION', 'action', { align: 'center'}),
  col('STATUS', 'status', { align: 'center' }),
  col('TRIP NAME', 'trip_name'),
  col('ROUTE', 'route'),
  col('TRAVEL REGION', 'travel_region'),
  col('PERIOD', 'period'),
  col('DEPARTMENTS', 'departments'),
]

const noteMeta = {
  approved: { action: 'approved', title: 'Approve Travel Order', label: 'Remark (Optional)', button: 'Approve', color: 'success', message: 'Travel order approved.', type: 'success', requireEvidence: false, confirmTitle: 'Approve Travel Order', confirmMessage: order => `Are you sure you want to approve ${order}?` },
  reject: { action: 'reject', title: 'Reject Travel Order', label: 'Remark', button: 'Reject', color: 'error', message: 'Travel order has been rejected.', type: 'success', confirmTitle: 'Reject Travel Order', confirmMessage: order => `Are you sure you want to reject ${order}?`, requireEvidence: false },
  revision: { action: 'revision', title: 'Return for Revision', label: 'Remark', button: 'Revision', color: 'warning', message: 'Travel order has been returned for revision.', type: 'warning', confirmTitle: 'Return for Revision', confirmMessage: order => `Are you sure you want to return ${order} for revision?`, requireEvidence: false },
  cancelled: { action: 'cancelled', title: 'Cancel Travel Order', label: 'Remark', button: 'Cancel', color: 'error', message: 'Travel order has been cancelled.', type: 'success', confirmTitle: 'Cancel Travel Order', confirmMessage: order => `Are you sure you want to cancel ${order}?`, requireEvidence: true },
}

const approvalChip = {
  submitted: { text: 'Requested', color: 'primary', caption: 'Requested by' },
  approved: { text: 'Approved', color: 'success', caption: 'Approved by' },
  rejected: { text: 'Rejected', color: 'error', caption: 'Rejected by' },
  revision: { text: 'Revision', color: 'warning', caption: 'Revision requested by' },
  pending: { text: 'Waiting', color: 'grey', caption: 'Awaiting approval' },
  cancelled: { text: 'Cancelled', color: 'grey', caption: 'Not processed' },
}

const historyChip = {
  submitted: { text: 'Requested', color: 'primary' },
  approved: { text: 'Approved', color: 'success' },
  rejected: { text: 'Rejected', color: 'error' },
  revision: { text: 'Revision', color: 'warning' },
  cancelled: { text: 'Cancelled', color: 'error' },
  gmo_booking_saved: { text: 'Ticket Booking Completed', color: 'info' },
  gmo_processing_completed: { text: 'GMO Processing Completed', color: 'success' },
  booking_schedule_updated: { text: 'Booking Schedule Updated', color: 'info' },
  booking_confirmation_email_sent: { text: 'Booking Confirmation Email Sent', color: 'info' },
}

function showMessage(text, type = 'success') { toast[type]?.(text) }
function toStatusLabel(status) { return status ? statusLabel[status] || status : 'Awaiting Approval Manager' }

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
  const a = order.ticket_arrangement
  const b = order.accommodation_arrangement
  if (!a && !b) return '-'
  if (!b || a === b) return a || b
  if (!a) return b
  if (b === 'Not Required') return a
  return 'Mixed'
}

function groupDepartments(group) {
  return group.departments || []
}

function groupSubtitle(group) {
  const orders = group.travel_orders_count || 0
  const status = group.status ? group.status.charAt(0).toUpperCase() + group.status.slice(1) : '-'
  return `${status} · ${orders ? `${orders} Travel Order${orders > 1 ? 's' : ''}` : 'No Travel Orders'}`
}

function normalize(order) {
  const organization = order.requester_role === 'Div Head' ? order.requester_division_name || '-' : order.department?.name || order.department_name || '-'
  return { raw: order, id: order.id, type: typeLabel[order.trip_type] || 'Business Trip - Individual', subject: order.subject || order.user?.name || '-', department: organization, period: `${formatDate(order.departure_date)} – ${formatDate(order.return_date)}`, arrangement: resolveArrangement(order), status: toStatusLabel(order.status) }
}

const canApprove = computed(() => selected.value?.can_approve === true)
const canDecide = computed(() => detailMode.value === 'review' && canApprove.value)
const rows = computed(() => orders.value.map(normalize))

const filteredRows = computed(() => {
  if (isAdminGmo.value && gmoTab.value === 'groups') return []
  const query = filters.search.trim().toLowerCase()
  return rows.value.filter(row => {
    const status = row.raw.status
    if (isAdminGmo.value && !(gmoTab.value === 'awaiting' ? gmoAwaitingStatuses : gmoHistoryStatuses).includes(status)) return false
    return (!query || `${row.id} ${row.subject}`.toLowerCase().includes(query)) && (!filters.status || row.status === filters.status)
  })
})

const filteredGroupTrips = computed(() => {
  const query = filters.search.trim().toLowerCase()
  return groupTrips.value.filter(group => !query || `${group.trip_name || ''} ${group.travel_from || ''} ${group.travel_to || ''}`.toLowerCase().includes(query))
})

const currentNoteMeta = computed(() => {
  const meta = noteMeta[noteAction.value] || noteMeta.reject
  return { ...meta, source: noteSource.value, ...(noteSource.value === 'gmo' && noteAction.value === 'revision' ? { title: 'Return Travel Order for Revision', label: 'Remark', requireEvidence: false } : {}) }
})

const approvalCards = computed(() => {
  const all = selected.value?.approvals || []
  const lastRound = all.map(step => step.status).lastIndexOf('submitted')
  const steps = lastRound > 0 ? all.slice(lastRound) : all
  const currentIndex = steps.findIndex(step => step.status === 'pending')
  const department = selected.value?.department?.name || ''
  const division = selected.value?.requester_division_name || selected.value?.department?.division_head?.division_name || ''
  const section = selected.value?.requester_section_name || ''

  return steps.map((step, index) => {
    const meta = approvalChip[step.status] || approvalChip.pending
    const role = step.role_name || '-'
    const displayRole = role.replace(' ', '.')
    const organization = role === 'Sect Head' ? section : role === 'Dept Head' ? department : role === 'Div Head' ? division : ''

    return {
      key: step.id, title: organization ? `${organization} (${displayRole})` : displayRole, chipText: meta.text, chipColor: meta.color, caption: meta.caption,
      name: ['pending', 'cancelled'].includes(step.status) ? '' : (step.name || '-'), time: formatDateTime(step.acted_at),
      note: ['rejected', 'revision'].includes(step.status) ? (step.note || '') : '', isCurrent: step.status === 'pending' && index === currentIndex,
    }
  })
})

const historyRounds = computed(() => {
  const groups = new Map()
  const department = selected.value?.department?.name || ''
  const division = selected.value?.requester_division_name || selected.value?.department?.division_head?.division_name || ''
  const section = selected.value?.requester_section_name || ''

  for (const log of historyLogs.value) {
    const round = log.round ?? 1
    const meta = historyChip[log.event] || { text: log.event, color: 'grey' }
    const isGmoActivity = ['gmo_booking_saved', 'gmo_processing_completed', 'booking_schedule_updated', 'booking_confirmation_email_sent'].includes(log.event)
    const role = (log.role || '-').trim()
    const normalizedRole = role.toLowerCase().replace(/[.\s_-]+/g, '')
    const displayRole = normalizedRole === 'admingmo' ? 'Admin-GMO' : normalizedRole === 'secthead' ? 'Sect.Head' : normalizedRole === 'depthead' ? 'Dept.Head' : ['divhead', 'director'].includes(normalizedRole) ? 'Div.Head' : role.replace(/\s+/g, '.')
    const organization = ['secthead', 'sectionhead'].includes(normalizedRole) ? section : ['depthead', 'departmenthead'].includes(normalizedRole) ? department : ['divhead', 'divisionhead', 'director'].includes(normalizedRole) ? division : ''

    if (!groups.has(round)) groups.set(round, [])

    groups.get(round).push({
      key: log.id, chipText: log.final ? 'Approved' : meta.text, chipColor: meta.color,
      role: isGmoActivity ? 'Admin-GMO' : organization ? `${organization} (${displayRole})` : displayRole,
      name: isGmoActivity || ['admingmo', 'admin'].includes(normalizedRole) ? '' : log.causer || '-',
      time: formatDateTime(log.created_at), note: log.remark || log.note || '', evidence: log.evidence_path || log.evidence || null,
    })
  }

  return [...groups.entries()].sort((a, b) => a[0] - b[0]).map(([round, items]) => ({ round, items }))
})

function clearFilters() { filters.search = ''; filters.status = null }
function openNewRequest() { requestDialog.value = true }
function backToRequest() { businessDialog.value = false; requestDialog.value = true }
function backToBusiness() { dialog.value = false; dialogType.value = null; businessDialog.value = true }
function closeDialog() { dialog.value = false; dialogType.value = null }
function handleCancelGmo(order) { selected.value = order; openNote('cancelled', 'gmo') }
function handleGmoReject() { openNote('cancelled', 'gmo') }
function handleGmoRevision(from = 'process') { noteReturnTo.value = from; openNote('revision', 'gmo') }
function handleApprove() { if (!approving.value) openNote('approved') }
function openNote(action, source = 'approval') { noteAction.value = action; noteSource.value = source; noteDialog.value = true }
const handleReject = () => openNote('reject')
const handleRevision = () => openNote('revision')

function selectRequest(type) {
  requestDialog.value = false
  if (type === 'annual') { showMessage('Annual Leave form is not available yet.', 'info'); return }
  businessDialog.value = true
}

function selectBusinessType(type) {
  businessDialog.value = false
  if (type === 'group') { groupTripDialog.value = true; return }
  dialogType.value = type
  dialog.value = true
}

function backToApproval() {
  detailDialog.value = false
  if (pdfFromGmoHistory.value) { pdfFromGmoHistory.value = false; return }
  approvalDialog.value = true
}

function backToGmoModal() {
  noteDialog.value = false
  if (noteReturnTo.value === 'booking') bookingDialog.value = true
  else processDialog.value = true
}

function revokePdf() {
  if (!pdfUrl.value) return
  URL.revokeObjectURL(pdfUrl.value)
  pdfUrl.value = ''
}

watch(detailDialog, open => { if (open) return; revokePdf(); pdfFromGmoHistory.value = false })

async function continueDraft(row) {
  if (editingDraftId.value !== null) return
  editingDraftId.value = row.id
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
    editingDraftId.value = null
  }
}

async function refreshDetail(id) {
  try {
    const response = await getTravelOrder(id)
    selected.value = response?.data?.data ?? response?.data ?? response
  } catch (error) {
    console.error('Load detail error:', error)
  }
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

async function openDetail(row, mode = 'view') {
  detailMode.value = mode
  selected.value = row.raw
  detailDialog.value = true
  await Promise.all([refreshDetail(row.id), loadPdf(row.id)])
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

async function openProcess(row) {
  if (processLoading.value) return
  selected.value = row.raw
  processingOrderId.value = row.id
  processLoading.value = true
  try {
    await refreshDetail(row.id)
    const order = selected.value
    const status = order?.status
    if (status === 'awaiting_gmo_processing') {
      processMode.value = 'direct-payment'
    } else if (status === 'awaiting_gmo_booking_document_issuance') {
      savedBooking.value = order?.booking || null
      processMode.value = 'booked-by-company'
    } else if (status === 'processed_by_gmo') {
      processMode.value = 'view-only'
    } else {
      return
    }
    await loadPdf(row.id)
    processDialog.value = true
  } catch (error) {
    console.error('Open GMO process error:', error)
    showMessage('Failed to open GMO process.', 'error')
  } finally {
    processLoading.value = false
    processingOrderId.value = null
  }
}

async function openGmoHistory(row) {
  const order = row.raw
  const hasCompanyBooking = order?.ticket_arrangement === 'Booked by Company' || order?.accommodation_arrangement === 'Booked by Company'
  if (!hasCompanyBooking) {
    selected.value = order
    processMode.value = 'view-only'
    await loadPdf(row.id)
    if (pdfUrl.value) processDialog.value = true
    return
  }
  gmoHistoryDialog.value = true
  gmoHistoryLoading.value = true
  gmoHistoryOrder.value = order
  gmoHistoryBooking.value = null
  gmoHistoryEmail.value = null
  try {
    const response = await getTravelOrder(row.id)
    const detail = response?.data?.data ?? response?.data ?? response
    gmoHistoryOrder.value = detail
    gmoHistoryBooking.value = detail?.booking || null
    gmoHistoryEmail.value = detail?.emails || []
  } catch (error) {
    console.error('Load GMO history error:', error)
    showMessage(error?.response?.data?.message || 'Failed to load GMO history.', 'error')
    gmoHistoryDialog.value = false
  } finally {
    gmoHistoryLoading.value = false
  }
}

async function openGmoHistoryPdf(id) {
  if (!id || pdfLoading.value) return
  selected.value = gmoHistoryOrder.value
  detailMode.value = 'view'
  pdfFromGmoHistory.value = true
  revokePdf()
  await loadPdf(id)
  if (pdfUrl.value) detailDialog.value = true
  else pdfFromGmoHistory.value = false
}

async function refreshGmoHistory(id = gmoHistoryOrder.value?.id) {
  if (!id) return

  const response = await getTravelOrder(id)
  const detail = response?.data?.data ?? response?.data ?? response

  gmoHistoryOrder.value = detail
  gmoHistoryBooking.value = detail?.booking || null
  gmoHistoryEmail.value = detail?.emails || []
}

async function handleUpdateGmoSchedule(payload) {
  if (gmoHistoryProcessing.value) return

  const id = payload.orderId || gmoHistoryOrder.value?.id
  if (!id) return

  gmoHistoryProcessing.value = true

  try {
    const { orderId, ...schedule } = payload

    await updateGmoSchedule(id, schedule)
    await refreshGmoHistory(id)
    await loadOrders()

    showMessage('Booking schedule updated successfully.')
  } catch (error) {
    showMessage(error?.response?.data?.errors ? Object.values(error.response.data.errors).flat()[0] : error?.response?.data?.message || 'Failed to update booking schedule.', 'error')
  } finally {
    gmoHistoryProcessing.value = false
  }
}

async function handleUpdateGmoBooking(payload) {
  if (gmoHistoryProcessing.value) return

  const id = payload.orderId || gmoHistoryOrder.value?.id
  if (!id) return

  gmoHistoryProcessing.value = true

  try {
    const formData = new FormData()

    ;['ticket_currency', 'ticket_amount', 'accommodation_currency', 'accommodation_amount'].forEach(key => {
      if (payload[key] !== null && payload[key] !== undefined) formData.append(key, payload[key])
    })

    const ticketFile = payload.e_ticket || payload.airplane_attachment
    const hotelFile = payload.hotel_reservation || payload.accommodation_attachment

    if (ticketFile instanceof File) formData.append('e_ticket', ticketFile)
    if (hotelFile instanceof File) formData.append('hotel_reservation', hotelFile)

    await updateGmoBookingItems(id, formData)
    await refreshGmoHistory(id)
    await loadOrders()
    showMessage('Booking items updated successfully.')
  } catch (error) {
    showMessage(error?.response?.data?.errors ? Object.values(error.response.data.errors).flat()[0] : error?.response?.data?.message || 'Failed to update booking items.', 'error')
  } finally {
    gmoHistoryProcessing.value = false
  }
}

async function handleCancelGmoBookingItem(payload, itemType) {
  if (gmoHistoryProcessing.value) return

  const id = payload.orderId || gmoHistoryOrder.value?.id
  if (!id) return

  const itemLabel = itemType === 'ticket' ? 'ticket' : 'accommodation'
  const ok = await confirm({ title: `Cancel ${itemLabel === 'ticket' ? 'Ticket' : 'Accommodation'}`, message: `Are you sure you want to record the ${itemLabel} cancellation?` })

  if (!ok) return

  gmoHistoryProcessing.value = true

  try {
    const formData = new FormData()

    ;['reason', 'refundStatus', 'currency', 'originalAmount', 'refundAmount', 'nonRefundableAmount'].forEach(key => {
      const value = payload[key]
      if (value !== null && value !== undefined && value !== '') {
        const field = key.replace(/[A-Z]/g, letter => `_${letter.toLowerCase()}`)
        formData.append(field === 'refund_status' ? 'refund_status' : field, value)
      }
    })

    if (payload.confirmationFile instanceof File) formData.append('confirmation_file', payload.confirmationFile)

    await cancelGmoBookingItem(id, itemType, formData)
    await refreshGmoHistory(id)
    await loadOrders()
    showMessage(`${itemLabel === 'ticket' ? 'Ticket' : 'Accommodation'} cancellation recorded successfully.`)
  } catch (error) {
    showMessage(error?.response?.data?.errors ? Object.values(error.response.data.errors).flat()[0] : error?.response?.data?.message || `Failed to record ${itemLabel} cancellation.`, 'error')
  } finally {
    gmoHistoryProcessing.value = false
  }
}

async function handleCancelTicket(payload) { return handleCancelGmoBookingItem(payload, 'ticket') }
async function handleCancelAccommodation(payload) { return handleCancelGmoBookingItem(payload, 'accommodation') }

async function handleResendGmoEmail(payload) {
  if (gmoHistoryProcessing.value) return

  const id = payload.orderId || gmoHistoryOrder.value?.id
  if (!id) return

  gmoHistoryProcessing.value = true

  try {
    const email = { ...payload }
    delete email.orderId

    await resendGmoBookingEmail(id, email)
    await refreshGmoHistory(id)
    await loadOrders()
    showMessage('Booking email resent successfully.')
  } catch (error) {
    showMessage(error?.response?.data?.errors ? Object.values(error.response.data.errors).flat()[0] : error?.response?.data?.message || 'Failed to resend booking email.', 'error')
  } finally {
    gmoHistoryProcessing.value = false
  }
}

async function openHistory(row) {
  selected.value = row.raw
  historyLogs.value = []
  historyDialog.value = true
  historyLoading.value = true
  try {
    const [detail, response] = await Promise.all([getTravelOrder(row.id), getTravelOrderHistory(row.id)])
    selected.value = detail?.data?.data ?? detail?.data ?? detail
    historyLogs.value = response?.data ?? []
  } catch (error) {
    console.error('Load history error:', error)
    showMessage(error?.response?.data?.message || 'Failed to load history log.', 'error')
  } finally {
    historyLoading.value = false
  }
}

async function openAttachments(row) {
  attachmentsDialog.value = true
  attachmentsLoading.value = true
  selectedBooking.value = null
  selectedOrder.value = null
  try {
    const response = await getTravelOrder(row.id)
    const order = response?.data?.data ?? response?.data ?? response
    selectedOrder.value = order
    selectedBooking.value = order?.booking || null
  } catch (error) {
    console.error('Load attachments error:', error)
    showMessage(error?.response?.data?.message || 'Failed to load attachments.', 'error')
    attachmentsDialog.value = false
  } finally {
    attachmentsLoading.value = false
  }
}

const loadGroupTripUsers = async () => {
  try {
    const response = await getGroupTripUsers()
    memberOptions.value = Array.isArray(response) ? response : response.data ?? []
  } catch (error) {
    console.error('Failed to load Group Trip users:', error)
    memberOptions.value = []
  }
}

async function loadGroupTrips() {
  try {
    const response = await getTravelOrderGroups({ per_page: 1000 })
    const data = response.data ?? response
    groupTrips.value = Array.isArray(data) ? data : data.data || []
  } catch (error) {
    console.error('Load group trips error:', error)
    groupTrips.value = []
  }
}

async function submitGroupTrip(payload) {
  if (groupTripSubmitting.value) return
  groupTripSubmitting.value = true
  try {
    await createTravelOrderGroup(payload)
    groupTripDialog.value = false
    await loadGroupTrips()
    if (isAdminGmo.value) gmoTab.value = 'groups'
    showMessage('Group Trip created successfully.')
  } catch (error) {
    const errors = error?.response?.data?.errors
    const firstError = errors ? Object.values(errors).flat()[0] : null
    showMessage(firstError || error?.response?.data?.message || 'Failed to create Group Trip.', 'error')
  } finally {
    groupTripSubmitting.value = false
  }
}

async function viewGroupTrip(group) {
  groupTripViewDialog.value = true
  groupTripViewLoading.value = true
  selectedGroupTrip.value = group

  try {
    const response = await getTravelOrderGroup(group.id)
    selectedGroupTrip.value = response?.data?.data ?? response?.data ?? response
  } catch (error) {
    console.error('Failed to load Group Trip:', error)
  } finally {
    groupTripViewLoading.value = false
  }
}

async function handleCompleteGmo(payload = {}) {
  if (processSubmitting.value || !selected.value?.id) return
  const isDirectPayment = processMode.value === 'direct-payment'
  const ok = await confirm({ title: 'Complete GMO Processing', message: 'Are you sure you want to complete GMO processing?' })
  if (!ok) return
  processSubmitting.value = true
  try {
    await completeGmoProcessing(selected.value.id, isDirectPayment ? {} : payload)
    processDialog.value = false
    revokePdf()
    await Promise.all([loadOrders(), refreshDetail(selected.value.id)])
    showMessage('GMO processing completed successfully.')
  } catch (error) {
    const errors = error?.response?.data?.errors
    const firstError = errors ? Object.values(errors).flat()[0] : null
    showMessage(firstError || error?.response?.data?.message || 'Failed to complete GMO processing.', 'error')
  } finally {
    processSubmitting.value = false
  }
}

async function handleNoteSubmit({ text, evidence }) {
  if (approving.value) return
  const meta = currentNoteMeta.value
  const ok = await confirm({ title: meta.confirmTitle, message: meta.confirmMessage(selected.value?.order_number) })
  if (!ok) return
  approving.value = true
  try {
    if (noteAction.value === 'approved') await approveTravelOrder(selected.value.id, text)
    else if (noteAction.value === 'cancelled') await cancelTravelOrder(selected.value.id, text, evidence)
    else if (noteAction.value === 'reject') await rejectTravelOrder(selected.value.id, text)
    else if (noteSource.value === 'gmo' && noteAction.value === 'revision') await revisionTravelOrder(selected.value.id, text)
    else await revisionTravelOrder(selected.value.id, text)
    noteDialog.value = false
    detailDialog.value = false
    approvalDialog.value = false
    await loadOrders()
    showMessage(meta.message, meta.type)
  } catch (error) {
    showMessage(error?.response?.data?.errors?.remark?.[0] || error?.response?.data?.errors?.evidence?.[0] || error?.response?.data?.message || 'Failed to process Travel Order.', 'error')
  } finally {
    approving.value = false
  }
}

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
    const requestPayload = { ...payload, request_type: 'business_trip', trip_type: dialogType.value, status: payload.status || 'submitted' }
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
  loadGroupTripUsers()
  if (isAdminGmo.value) loadGroupTrips()
  if (route.query.submitted) {
    showMessage('Travel order submitted successfully.')
    router.replace({ query: {} })
  }
})
</script>

<template>
  <div>
    <VCard rounded="lg" elevation="1">
      <VCardText class="px-6 py-4">
        <VAlert v-if="errorMessage" type="error" variant="tonal" class="mb-4">{{ errorMessage }}</VAlert>
        <div class="d-flex align-center justify-space-between ga-4">
          <VBtn color="success" prepend-icon="ri-add-line" @click="openNewRequest">New Request</VBtn>
          <div class="d-flex align-center ga-4">
            <VTabs v-if="isAdminGmo" v-model="gmoTab" color="primary" density="compact"><VTab value="awaiting" class="text-none">Awaiting</VTab><VTab value="history" class="text-none">History</VTab><VTab value="groups" class="text-none">Group Trips</VTab></VTabs>
            <VTextField v-model="filters.search" placeholder="Search" prepend-inner-icon="ri-search-line" variant="outlined" density="compact" hide-details clearable style="max-width: 300px; min-width: 250px" />
          </div>
        </div>
      </VCardText>
      <VDivider />
      <VDataTable v-if="!(isAdminGmo && gmoTab === 'groups')" class="orders-table" :headers="headers" :items="filteredRows" :loading="loading" show-current-page item-value="id" hover no-data-text="No travel orders found.">
        <template #item.id="{ item }"><span class="font-weight-bold text-body-2 text-no-wrap">{{ item.id }}</span></template>
        <template #item.type="{ item }"><VChip size="small" variant="tonal" :color="typeColor[item.type] || 'primary'" class="text-no-wrap">{{ item.type }}</VChip></template>
        <template #item.subject="{ item }"><div class="font-weight-bold text-body-2">{{ item.subject }}</div><div class="text-caption text-medium-emphasis">{{ item.department || '-' }}</div></template>
        <template #item.period="{ item }"><span class="text-body-2 text-no-wrap">{{ item.period }}</span></template>
        <template #item.arrangement="{ item }"><span class="text-body-2 text-no-wrap d-block text-center">{{ item.arrangement }}</span></template>
        <template #item.status="{ item }"><VChip size="small" variant="tonal" :color="statusColor[item.status] || 'primary'" class="text-no-wrap"><VIcon icon="ri-checkbox-blank-circle-fill" size="8" start />{{ item.status }}</VChip></template>
        <template #item.attachment="{ item }">
          <VBtn v-if="item.raw.status === 'processed_by_gmo' && (item.raw.ticket_arrangement === 'Booked by Company' || item.raw.accommodation_arrangement === 'Booked by Company')" color="deep-purple-darken-1" variant="tonal" @click="openAttachments(item)"><VIcon icon="ri-attachment-2" /><VTooltip activator="parent" location="top">View Attachments</VTooltip></VBtn>
          <span v-else>-</span>
        </template>
        <template #item.action="{ item }">
          <VBtnGroup density="compact" variant="tonal">
            <VBtn v-if="item.raw.status === 'draft'" color="green-darken-1" :loading="editingDraftId === item.id" @click="continueDraft(item)"><VIcon icon="ri-edit-line" /><VTooltip activator="parent" location="top">Edit Draft</VTooltip></VBtn>
            <template v-if="isAdminGmo">
              <template v-if="gmoTab === 'awaiting'"><VBtn color="teal-darken-1" :loading="processingOrderId === item.id" @click="openProcess(item)"><VIcon icon="ri-task-line" /><VTooltip activator="parent" location="top">Process</VTooltip></VBtn></template>
              <template v-else><VBtn color="blue-darken-1" :loading="gmoHistoryLoading && gmoHistoryOrder?.id === item.id" @click="openGmoHistory(item)"><VIcon icon="ri-eye-line" /><VTooltip activator="parent" location="top">View History</VTooltip></VBtn></template>
              <VBtn color="blue-grey-darken-1" @click="openHistory(item)"><VIcon icon="ri-history-line" /><VTooltip activator="parent" location="top">History Log</VTooltip></VBtn>
            </template>
            <template v-else-if="item.raw.can_approve === true">
              <VBtn color="green-darken-1" @click="openApproval(item)"><VIcon icon="ri-check-line" /><VTooltip activator="parent" location="top">Approval</VTooltip></VBtn>
              <VBtn color="blue-grey-darken-1" @click="openHistory(item)"><VIcon icon="ri-history-line" /><VTooltip activator="parent" location="top">History Log</VTooltip></VBtn>
            </template>
            <template v-else>
              <VBtn v-if="item.raw.status !== 'draft'" color="blue-darken-1" @click="openDetail(item)"><VIcon icon="ri-eye-line" /><VTooltip activator="parent" location="top">View PDF</VTooltip></VBtn>
              <VBtn color="blue-grey-darken-1" @click="openHistory(item)"><VIcon icon="ri-history-line" /><VTooltip activator="parent" location="top">History Log</VTooltip></VBtn>
            </template>
          </VBtnGroup>
        </template>
      </VDataTable>
      <VDataTable v-else class="orders-table" :headers="groupTripHeaders" :items="filteredGroupTrips" show-current-page item-value="id" hover no-data-text="No group trips found.">
        <template #item.action="{ item }">
  <VBtnGroup density="compact" variant="tonal">
    <VBtn color="blue-darken-1" :loading="groupTripViewLoading && selectedGroupTrip?.id === item.id" @click="viewGroupTrip(item)">
      <VIcon icon="ri-eye-line" />
      <VTooltip activator="parent" location="top">View</VTooltip>
    </VBtn>
  </VBtnGroup>
</template>
  <template #item.trip_name="{ item }">
    <div class="font-weight-bold text-body-2">{{ item.trip_name }}</div>
    <div class="text-caption text-medium-emphasis">{{ groupSubtitle(item) }}</div>
  </template>
  <template #item.route="{ item }"><span class="text-body-2 text-no-wrap">{{ item.travel_from }} - {{ item.travel_to }}</span></template>
  <template #item.travel_region="{ item }"><VChip size="small" variant="tonal" color="info" class="text-no-wrap">{{ item.travel_region }}</VChip></template>
  <template #item.period="{ item }"><span class="text-body-2 text-no-wrap">{{ formatDate(item.departure_date) }} - {{ formatDate(item.return_date) }}</span></template>
  <template #item.departments="{ item }">
  <div class="d-flex flex-wrap ga-1">
    <VChip v-for="name in groupDepartments(item)" :key="name" size="small" color="grey-lighten-3" variant="flat" class="text-no-wrap">{{ name }}</VChip>
    <span v-if="!groupDepartments(item).length" class="text-body-2">-</span>
  </div>
</template>
  <template #item.status="{ item }">
    <VChip size="small" variant="tonal" :color="groupStatusColor[item.status] || 'grey'" class="text-no-wrap text-capitalize"><VIcon icon="ri-checkbox-blank-circle-fill" size="8" start />{{ item.status }}</VChip>
  </template>
</VDataTable>
    </VCard>

    <PickTypeModal v-model="requestDialog" title="New Travel Request" subtitle="Select the type of request you want to submit." :items="requestTypes" @select="selectRequest" />
    <PickTypeModal v-model="businessDialog" title="Business Trip" subtitle="How many people are traveling?" :items="businessTypes" show-back @back="backToRequest" @select="selectBusinessType" />
    <TripFormModal v-model="dialog" :type="dialogType" :loading="submitting" :initial-data="editingDraft ? selected : null" :member-options="memberOptions" @back="backToBusiness" @close="closeDialog" @submit="submitOrder" />
    <VDialog v-model="groupTripDialog" max-width="1000" scrollable><GroupTripForm :loading="groupTripSubmitting" :member-options="memberOptions" @cancel="groupTripDialog = false" @submit="submitGroupTrip" /></VDialog>
      <GroupTripDetailModal v-model="groupTripViewDialog" :group="selectedGroupTrip" :loading="groupTripViewLoading"/>
    <PdfViewerModal v-model="detailDialog" :title="detailMode === 'review' ? 'Review Travel Order' : 'Travel Order Detail'" :pdf-url="pdfUrl" :loading="pdfLoading" :can-decide="canDecide" :approving="approving" @back="backToApproval" @reject="handleReject" @revision="handleRevision" @approve="handleApprove" />
    <ApprovalFlowModal v-model="approvalDialog" :loading="approvalLoading" :cards="approvalCards" :can-approve="canApprove" @review="openReview" />
    <HistoryLogModal v-model="historyDialog" :loading="historyLoading" :rounds="historyRounds" />
    <NoteModal v-model="noteDialog" :meta="currentNoteMeta" @submit="handleNoteSubmit" @back="backToGmoModal" />
    <GmoProcessModal v-model="processDialog" :title="processMode === 'view-only' ? 'Travel Order PDF' : processMode === 'direct-payment' ? 'Direct Payment' : 'Booked by Company'" :mode="processMode" :status="selected?.status" :order="selected" :pdf-url="pdfUrl" :loading="processLoading || pdfLoading" :processing="processSubmitting" @cancel="processDialog = false" @reject="handleGmoReject" @revision="handleGmoRevision('process')" @continue="continueToBooking" @complete="handleCompleteGmo" />
    <GmoAttachmentsModal v-model="attachmentsDialog" :loading="attachmentsLoading" :order="selectedOrder" :booking="selectedBooking" />
    <GmoHistoryModal v-model="gmoHistoryDialog" title="GMO Processing History" :order="gmoHistoryOrder" :saved-booking="gmoHistoryBooking" :saved-email="gmoHistoryEmail" :loading="gmoHistoryLoading" :processing="gmoHistoryProcessing" @close="gmoHistoryDialog = false" @view-pdf="openGmoHistoryPdf" @update-schedule="handleUpdateGmoSchedule" @update-booking="handleUpdateGmoBooking" @cancel-ticket="handleCancelTicket" @cancel-accommodation="handleCancelAccommodation" @resend-email="handleResendGmoEmail" />
  </div>
</template>