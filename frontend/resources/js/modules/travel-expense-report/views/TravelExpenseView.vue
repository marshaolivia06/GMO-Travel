<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../../../stores/auth.js'
import { useToastStore } from '../../../stores/toast'
import { getTravelOrders } from '../../travel-order/services/travelOrderService.js'
import { getAnnualTickets } from '../../annual-ticket/services/annualTicketService.js'
import { createTravelExpenseReport, getTravelExpenseReportHistory, getTravelExpenseReports, submitTravelExpenseReport, updateTravelExpenseReportDraft } from '../services/travelExpenseReportService.js'
import TravelExpenseReportModal from '../components/modal/TravelExpenseReportModal.vue'
import AnnualTicketClaimModal from '../components/modal/AnnualTicketClaimModal.vue'
import TerClaimDetailModal from '../components/modal/TerClaimDetailModal.vue'
import HistoryLogModal from '../../annual-ticket/components/modal/HistoryLogModal.vue'

const authStore = useAuthStore()
const toast = useToastStore()
const search = ref('')
const items = ref([])
const loading = ref(false)
const showDetail = ref(false)
const selectedItem = ref(null)
const showTicketClaim = ref(false)
const ticketClaimRaw = ref(null)
const claimInitial = ref(null)
const claimTerId = ref(null)
const claimProcessing = ref(false)
const showClaimDetail = ref(false)
const claimDetail = ref(null)
const historyDialog = ref(false)
const historyLoading = ref(false)
const historyLogs = ref([])

const headers = [
  { title: 'ACTION', key: 'action', sortable: false, width: '80px', align: 'center' },
  { title: 'STATUS', key: 'status', sortable: false, width: '100px', align: 'center' },
  { title: 'TRIP TYPE', key: 'trip_type', sortable: false, width: '130px', align: 'center' },
  { title: 'PURPOSE / TRAVEL ORDER', key: 'purpose', sortable: false, minWidth: '250px', align: 'center' },
  { title: 'TRAVEL REGION', key: 'travel_region', sortable: false, width: '150px', align: 'center' },
  { title: 'PERIOD', key: 'period', sortable: false, width: '180px', align: 'center' },
  { title: 'TOTAL CLAIM', key: 'total_claim', sortable: false, width: '190px', align: 'center' },
]

const PROCESSED_BY_GMO = 'processed_by_gmo'
const CLAIMABLE_TICKET_STATUSES = ['processed_by_gmo', 'partially_cancelled']
const tripTypeLabels = { group: 'Group Trip', individual: 'Individual Trip', annual_ticket: 'Annual Ticket' }
const normalize = value => String(value ?? '').trim().toLowerCase().replaceAll(' ', '_')
const tripTypeLabel = type => tripTypeLabels[normalize(type)] ?? type ?? '-'
const tripTypeColor = type => {
  const key = normalize(type)
  return key === 'group' ? 'info' : key === 'annual_ticket' ? 'success' : 'primary'
}
// Status tile di atas tabel - kategori sama seperti module lain
const statusTab = ref('all')
const statusMeta = {
  new: { title: 'New', color: 'warning', icon: 'ri-add-circle-line' },
  draft: { title: 'Draft', color: 'secondary', icon: 'ri-draft-line' },
  approval_progress: { title: 'Approval Progress', color: 'warning', icon: 'ri-progress-4-line' },
  revision_required: { title: 'Revision Required', color: 'info', icon: 'ri-edit-2-line' },
  approved: { title: 'Approved', color: 'success', icon: 'ri-checkbox-circle-line' },
  not_eligible: { title: 'Not Eligible', color: 'error', icon: 'ri-forbid-line' },
}

// Peta status backend ke kategori tile - submitted & semua tahap awaiting masuk approval progress
const categoryMap = { draft: 'draft', submitted: 'approval_progress', revision_required: 'revision_required', approved: 'approved' }
const rowCategory = item => {
  const key = normalize(item.category ?? item.status)
  return categoryMap[key] ?? (key.startsWith('awaiting_') ? 'approval_progress' : key)
}
const currentUserId = computed(() => authStore.user?.id)

const formatDate = date => {
  if (!date) return '-'
  const parsed = new Date(date)
  return Number.isNaN(parsed.getTime()) ? '-' : parsed.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

const formatPeriod = item => {
  const start = item.departure_date ? new Date(item.departure_date) : null
  const end = item.return_date ? new Date(item.return_date) : null
  if (!start || !end || Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) return `${formatDate(item.departure_date)} - ${formatDate(item.return_date)}`
  const sameMonth = start.getMonth() === end.getMonth() && start.getFullYear() === end.getFullYear()
  const day = date => date.toLocaleDateString('en-GB', { day: '2-digit' })
  const monthYear = date => date.toLocaleDateString('en-GB', { month: 'short', year: 'numeric' })
  return sameMonth ? `${day(start)}–${day(end)} ${monthYear(start)}` : `${formatDate(start)} – ${formatDate(end)}`
}

async function load() {
  loading.value = true
  try {
    const [ordersRes, ticketsRes, tersRes] = await Promise.all([getTravelOrders(), getAnnualTickets(), getTravelExpenseReports()])

    const orders = Array.isArray(ordersRes.data) ? ordersRes.data : Array.isArray(ordersRes.data?.data) ? ordersRes.data.data : Array.isArray(ordersRes) ? ordersRes : []
    const tickets = Array.isArray(ticketsRes) ? ticketsRes : Array.isArray(ticketsRes?.data) ? ticketsRes.data : []
    const ters = Array.isArray(tersRes) ? tersRes : Array.isArray(tersRes?.data) ? tersRes.data : []

    const orderItems = orders.filter(to => String(to.user_id) === String(currentUserId.value) && normalize(to.status) === PROCESSED_BY_GMO).map(to => ({
      id: to.id,
      status: 'new',
      category: 'new',
      trip_type: to.trip_type,
      purpose: to.purpose,
      to_number: to.to_number ?? to.order_number ?? to.id,
      travel_region: to.travel_region,
      travel_from: to.travel_from ?? to.origin,
      travel_to: to.travel_to ?? to.destination,
      departure_date: to.departure_date,
      return_date: to.return_date,
      etd: to.etd ?? to.departure_time,
      eta: to.eta ?? to.arrival_time,
      total_claim: null,
    }))

    // Tiket yang sudah punya TER tidak tampil lagi sebagai claimable - nilainya ada di baris TER
    const terRows = ters.map(ter => {
      const ticket = ter.annual_ticket ?? {}
      const dates = (ticket.legs ?? []).map(leg => leg.reschedule_date ?? leg.travel_date).filter(Boolean).sort()
      const country = String(ticket.employee?.eligible_destination?.country ?? '').trim().toLowerCase()
      const region = country === 'indonesia' ? 'Domestic' : country === 'singapore' ? 'Singapore' : country ? 'Overseas' : null
      return {
        id: ter.id,
        ter_id: ter.id,
        status: ter.status,
        category: ter.status,
        ter,
        trip_type: 'annual_ticket',
        purpose: `${ticket.route_condition} - ${ticket.ticket_arrangement} ${ticket.ticket_arrangement === 'Direct Payment' ? 'Reimbursement' : 'Excess'}`,
        to_number: ticket.document_number,
        travel_region: region,
        departure_date: dates[0] ?? null,
        return_date: dates.at(-1) ?? null,
        // Reimbursement: total claim tampil setelah approved. Excess: nominal fixed, langsung tampil sejak submit
        total_claim: normalize(ter.status) === 'approved'
          ? (ter.settlement_amount ?? ter.employee_excess ?? null)
          : (ter.claim_type === 'excess_settlement' && normalize(ter.status) === 'submitted' ? (ter.employee_excess ?? null) : null),
        currency: ter.currency,
        is_excess: ter.claim_type === 'excess_settlement',
        claim_kind: ter.claim_type === 'ticket_reimbursement' ? 'Reimbursable' : 'Excess Potential',
        annual_ticket_id: ticket.id,
        raw: ticket,
        prefill: {
          total_actual_amount: ter.total_actual_amount,
          has_proof: !!ter.proof_of_payment_url,
          // File yang sudah terupload di draft - ditampilkan sebagai chips, tidak bisa diprefill ke input file
          attachments: (ter.attachments ?? []).map(file => ({ name: file.original_name ?? 'attachment', url: file.file_url })),
        },
      }
    })

    const ticketItems = tickets
      .filter(ticket => String(ticket.employee_id) === String(currentUserId.value))
      .filter(ticket => CLAIMABLE_TICKET_STATUSES.includes(normalize(ticket.status)))
      .filter(ticket => ticket.ticket_arrangement === 'Direct Payment' || ticket.route_condition === 'Different Route')
      .filter(ticket => !ters.some(ter => String(ter.annual_ticket_id) === String(ticket.id)))
      .map(ticket => {
        const dates = (ticket.legs ?? []).map(leg => leg.reschedule_date ?? leg.travel_date).filter(Boolean).sort()
        const country = String(ticket.employee?.eligible_destination?.country ?? '').trim().toLowerCase()
        const region = country === 'indonesia' ? 'Domestic' : country === 'singapore' ? 'Singapore' : country ? 'Overseas' : null
        return {
          id: `annual-ticket-${ticket.id}`,
          status: 'new',
          category: 'new',
          trip_type: 'annual_ticket',
          purpose: `${ticket.route_condition} - ${ticket.ticket_arrangement} ${ticket.ticket_arrangement === 'Direct Payment' ? 'Reimbursement' : 'Excess'}`,
          to_number: ticket.document_number,
          travel_region: region,
          departure_date: dates[0] ?? null,
          return_date: dates.at(-1) ?? null,
          total_claim: null,
          claim_kind: ticket.ticket_arrangement === 'Direct Payment' ? 'Reimbursable' : 'Excess Potential',
          annual_ticket_id: ticket.id,
          raw: ticket,
        }
      })

    // Booked by Company + Same Route tidak bisa diclaim - tampil sebagai Not Eligible
    const notEligibleItems = tickets
      .filter(ticket => String(ticket.employee_id) === String(currentUserId.value))
      .filter(ticket => CLAIMABLE_TICKET_STATUSES.includes(normalize(ticket.status)))
      .filter(ticket => ticket.ticket_arrangement === 'Booked by Company' && ticket.route_condition === 'Same Route')
      .filter(ticket => !ters.some(ter => String(ter.annual_ticket_id) === String(ticket.id)))
      .map(ticket => {
        const dates = (ticket.legs ?? []).map(leg => leg.reschedule_date ?? leg.travel_date).filter(Boolean).sort()
        const country = String(ticket.employee?.eligible_destination?.country ?? '').trim().toLowerCase()
        const region = country === 'indonesia' ? 'Domestic' : country === 'singapore' ? 'Singapore' : country ? 'Overseas' : null
        return {
          id: `not-eligible-${ticket.id}`,
          status: 'not_eligible',
          category: 'not_eligible',
          trip_type: 'annual_ticket',
          purpose: `${ticket.route_condition} - ${ticket.ticket_arrangement}`,
          to_number: ticket.document_number,
          travel_region: region,
          departure_date: dates[0] ?? null,
          return_date: dates.at(-1) ?? null,
          total_claim: null,
          annual_ticket_id: ticket.id,
          raw: ticket,
        }
      })

    items.value = [...orderItems, ...terRows, ...ticketItems, ...notEligibleItems]
  } catch (error) {
    toast.error(error.response?.data?.message || 'Failed to load Travel Expense Reports.')
    items.value = []
  } finally {
    loading.value = false
  }
}

const statusTabs = computed(() => [
  { value: 'all', title: 'Total', color: 'primary', icon: 'ri-stack-line', count: items.value.length },
  ...Object.entries(statusMeta).map(([value, meta]) => ({ value, ...meta, count: items.value.filter(row => rowCategory(row) === value).length })),
])

const filteredRows = computed(() => statusTab.value === 'all' ? items.value : items.value.filter(row => rowCategory(row) === statusTab.value))

const openClaimDetail = item => {
  claimDetail.value = item.ter ?? null
  showClaimDetail.value = true
}

// Label chip timeline history log - aktivitas tercatat mulai dari submit
const historyChip = {
  submitted: { text: 'Requested', color: 'primary' },
  checked: { text: 'Reviewed', color: 'success' },
  approved: { text: 'Approved', color: 'success' },
  revision: { text: 'Revision', color: 'warning' },
}

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
    groups.get(round).items.push({ key: log.id, chipText: meta.text, chipColor: meta.color, time: formatDateTime(log.created_at), role: log.role || '-', name: log.causer || '', note: log.note || '', remark: log.remark || '' })
  }
  return [...groups.values()].sort((a, b) => a.round - b.round)
}

const historyRounds = computed(() => buildHistoryRounds(historyLogs.value))

async function openHistory(item) {
  if (!item.ter_id) return
  historyDialog.value = true
  historyLoading.value = true
  historyLogs.value = []
  try {
    const response = await getTravelExpenseReportHistory(item.ter_id)
    historyLogs.value = response?.data ?? []
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to load history log.')
  } finally {
    historyLoading.value = false
  }
}

function onContinue(item) {
  if (item.trip_type === 'annual_ticket') {
    ticketClaimRaw.value = item.raw ?? null
    claimInitial.value = item.prefill ?? null
    claimTerId.value = item.ter_id ?? null
    showTicketClaim.value = true
    return
  }
  selectedItem.value = item
  showDetail.value = true
}

const firstError = err => {
  const errors = err.response?.data?.errors
  return errors ? Object.values(errors).flat()[0] : null
}

async function onClaimDraft(payload) {
  claimProcessing.value = true
  try {
    if (claimTerId.value) await updateTravelExpenseReportDraft(claimTerId.value, payload.data, payload.attachments, payload.proof_of_payment)
    else await createTravelExpenseReport({ ...payload.data, action: 'draft' }, payload.attachments, payload.proof_of_payment)
    toast.success('Travel Expense Report draft has been saved successfully.')
    showTicketClaim.value = false
    await load()
  } catch (err) {
    toast.error(firstError(err) || err.response?.data?.message || 'Failed to save the draft.')
  } finally {
    claimProcessing.value = false
  }
}

async function onClaimSubmit(payload) {
  claimProcessing.value = true
  try {
    if (claimTerId.value) {
      // Draft yang sudah ada: update input terbaru dulu (termasuk file), baru submit finalisasi
      await updateTravelExpenseReportDraft(claimTerId.value, payload.data, payload.attachments, payload.proof_of_payment)
      await submitTravelExpenseReport(claimTerId.value)
    } else {
      await createTravelExpenseReport({ ...payload.data, action: 'submit' }, payload.attachments, payload.proof_of_payment)
    }
    toast.success('Travel Expense Report has been submitted successfully.')
    showTicketClaim.value = false
    await load()
  } catch (err) {
    toast.error(firstError(err) || err.response?.data?.message || 'Failed to submit the Travel Expense Report.')
  } finally {
    claimProcessing.value = false
  }
}

onMounted(load)
</script>

<template>
  <BrandCard>
    <template #action><span /></template>
    <template #stats>
      <VRow dense>
        <VCol v-for="tab in statusTabs" :key="tab.value" cols="6" sm="4" lg="3">
          <VCard :variant="statusTab === tab.value ? 'tonal' : 'outlined'" :color="statusTab === tab.value ? tab.color : undefined" class="pa-3 d-flex align-center cursor-pointer stat-tile h-100" style="gap: 12px" @click="statusTab = tab.value"><VAvatar :color="tab.color" variant="tonal" rounded="lg" size="40"><VIcon :icon="tab.icon" size="20" /></VAvatar><div><div class="text-h6 font-weight-bold">{{ tab.count }}</div><div class="text-caption text-medium-emphasis">{{ tab.title }}</div></div></VCard>
        </VCol>
      </VRow>
    </template>
    <template #search>
      <VTextField
        v-model="search"
        prepend-inner-icon="ri-search-line"
        placeholder="Search reports..."
        variant="outlined"
        density="comfortable"
        hide-details
        clearable
        style="min-width: 260px; max-width: 320px"
      />
    </template>

      <VDataTable :headers="headers" :items="filteredRows" :search="search" :loading="loading" item-value="id" hover class="ter-table" :items-per-page="10" :items-per-page-options="[10, 25, 50]" no-data-text="No Travel Expense Report found" loading-text="Loading travel reports...">
        <template #item.action="{ item }">
          <VBtnGroup density="compact" variant="tonal">
            <VBtn v-if="['new', 'draft'].includes(rowCategory(item))" color="grey-darken-1" @click="onContinue(item)">
              <VIcon icon="ri-edit-line" />
              <VTooltip activator="parent" location="top">Continue</VTooltip>
            </VBtn>
            <VBtn v-else-if="['approval_progress', 'approved'].includes(rowCategory(item))" color="grey-darken-1" @click="openClaimDetail(item)">
              <VIcon icon="ri-eye-line" />
              <VTooltip activator="parent" location="top">View Claim</VTooltip>
            </VBtn>
            <VBtn v-if="item.ter_id && rowCategory(item) !== 'draft'" color="grey-darken-1" @click="openHistory(item)">
              <VIcon icon="ri-history-line" />
              <VTooltip activator="parent" location="top">History Log</VTooltip>
            </VBtn>
          </VBtnGroup>
          <VIcon v-if="!item.ter_id && rowCategory(item) === 'not_eligible'" icon="ri-forbid-line" color="grey-darken-1">
            <VTooltip activator="parent" location="top">Booked by Company with Same Route is not claimable</VTooltip>
          </VIcon>
        </template>
        <template #item.status="{ item }">
          <VChip :color="statusMeta[rowCategory(item)]?.color ?? 'primary'" variant="tonal" size="small">
            {{ statusMeta[rowCategory(item)]?.title ?? item.status }}
          </VChip>
        </template>
        <template #item.trip_type="{ item }">
          <VChip :color="tripTypeColor(item.trip_type)" variant="tonal" size="small">{{ tripTypeLabel(item.trip_type) }}</VChip>
        </template>
        <template #item.purpose="{ item }">
          <div class="py-1">
            <div class="font-weight-medium">{{ item.purpose || '-' }}</div>
            <div class="text-caption text-medium-emphasis mt-1">{{ item.to_number }}</div>
          </div>
        </template>
        <template #item.travel_region="{ item }">
          <span>{{ item.travel_region || '-' }}</span>
        </template>
        <template #item.period="{ item }">
          <span class="text-no-wrap">{{ formatPeriod(item) }}</span>
        </template>
        <template #item.total_claim="{ item }">
          <span v-if="item.total_claim != null" class="font-weight-semibold text-no-wrap">
            {{ item.currency ?? 'IDR' }} {{ new Intl.NumberFormat('id-ID').format(item.total_claim) }}{{ item.is_excess ? ' Excess' : '' }}
          </span>
          <span v-else>-</span>
        </template>
      </VDataTable>
  </BrandCard>
    <TravelExpenseReportModal v-model="showDetail" :item="selectedItem" />
    <AnnualTicketClaimModal v-model="showTicketClaim" :ticket="ticketClaimRaw" :initial="claimInitial" :processing="claimProcessing" @submit="onClaimSubmit" @draft="onClaimDraft" />
    <TerClaimDetailModal v-model="showClaimDetail" :report="claimDetail" />
    <HistoryLogModal v-model="historyDialog" :loading="historyLoading" :rounds="historyRounds" />
</template>