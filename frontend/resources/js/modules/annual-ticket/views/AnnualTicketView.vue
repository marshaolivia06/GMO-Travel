<script setup>
import { computed, onMounted, ref } from 'vue'
import { getMyDependents, getAnnualTickets, getAnnualTicketHistory, getAnnualTicketPdf } from '../services/annualTicketService'
import { useToastStore } from '../../../stores/toast'
import CreateAnnualTicketModal from '../components/modal/CreateAnnualTicketModal.vue'
import HistoryLogModal from '../components/modal/HistoryLogModal.vue'
import PdfViewerModal from '../../travel-order/components/modal/PdfViewerModal.vue'

const toast = useToastStore()
const requests = ref([])
const loading = ref(false)

const search = ref('')
const statusTab = ref('all')
const showCreateModal = ref(false)
const travelers = ref(null)
const selectedTravelers = ref([])
const step = ref(1)
const legs = ref(null)

async function openCreate() {
  travelers.value = await getMyDependents()
  step.value = 1
  showCreateModal.value = true
}

const formatDate = date => new Intl.DateTimeFormat('en-GB', {
  timeZone: 'Asia/Jakarta',
  day: '2-digit',
  month: 'short',
  year: 'numeric',
}).format(new Date(date))

function formatPeriod(rowLegs = []) {
  const dates = [...new Set(rowLegs.map(leg => leg.travel_date).filter(Boolean).map(formatDate))].sort()

  if (!dates.length)
    return '-'

  return dates.length > 1 ? `${dates[0]} → ${dates[dates.length - 1]}` : dates[0]
}

async function fetchRequests() {
  loading.value = true

  try {
    const data = await getAnnualTickets()
    const rows = Array.isArray(data) ? data : data?.data ?? []

    requests.value = rows.map(item => ({
      id: item.id,
      status: item.status,
      travel_period: formatPeriod(item.legs ?? []),
      route_condition: item.route_condition,
      ticket_arrangement: item.ticket_arrangement,
    }))
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to load annual tickets.')
  } finally {
    loading.value = false
  }
}

onMounted(fetchRequests)

const statusMeta = {
  draft: { title: 'Draft', color: 'secondary', icon: 'ri-draft-line' },
  awaiting_check_hr_admin: { title: 'Awaiting Check - HR Admin', color: 'warning', icon: 'ri-time-line' },
  awaiting_cancellation_check_hr_admin: { title: 'Awaiting Cancellation Check - HR Admin', color: 'warning', icon: 'ri-time-line' },
  awaiting_approval_hr_supervisor: { title: 'Awaiting Approval - HR Supervisor', color: 'warning', icon: 'ri-user-star-line' },
  awaiting_cancellation_approval_hr_supervisor: { title: 'Awaiting Cancellation Approval - HR Supervisor', color: 'warning', icon: 'ri-user-star-line' },
  awaiting_fare_reference_gmo: { title: 'Awaiting Fare Reference - GMO', color: 'info', icon: 'ri-money-dollar-circle-line' },
  awaiting_cancellation_finalization_gmo: { title: 'Awaiting Cancellation Finalization - GMO', color: 'info', icon: 'ri-close-circle-line' },
  awaiting_ticket_processing: { title: 'Awaiting Ticket Processing', color: 'info', icon: 'ri-ticket-2-line' },
  revision_required: { title: 'Revision Required', color: 'info', icon: 'ri-edit-2-line' },
  rejected: { title: 'Rejected', color: 'error', icon: 'ri-close-circle-line' },
  cancelled: { title: 'Cancelled', color: 'error', icon: 'ri-forbid-line' },
  processed_by_gmo: { title: 'Processed by GMO', color: 'success', icon: 'ri-checkbox-circle-line' },
}

const routeColor = { 'Same Route': 'success', 'Different Route': 'warning' }

const headerClass = 'px-3 py-3 text-caption font-weight-bold text-medium-emphasis bg-grey-lighten-4 text-no-wrap'
const cellClass = 'px-3 py-3'
const col = (title, key, extra = {}) => ({ title, key, sortable: false, headerProps: { class: headerClass }, cellProps: { class: cellClass }, ...extra })

const headers = [
  col('ACTION', 'action', { align: 'center', width: 150 }),
  col('STATUS', 'status', { align: 'center' }),
  col('TRAVEL PERIOD', 'travel_period', { align: 'center' }),
  col('ROUTE CONDITION', 'route_condition', { align: 'center' }),
  col('TICKET ARRANGEMENT', 'ticket_arrangement', { align: 'center' }),
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

  return requests.value.filter(row =>
    (statusTab.value === 'all' || row.status === statusTab.value)
    && (!query || `${row.id} ${row.travel_period}`.toLowerCase().includes(query)))
})

const historyChip = {
  submitted: { text: 'Requested', color: 'primary' },
}

const historyDialog = ref(false)
const historyLoading = ref(false)
const historyLogs = ref([])

function formatDateTime(value) {
  if (!value)
    return ''

  const date = new Date(value)

  return isNaN(date.getTime()) ? '' : date.toLocaleString('en-GB', { timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const historyRounds = computed(() => {
  const groups = new Map()

  for (const log of historyLogs.value) {
    const round = log.round ?? 1

    if (!groups.has(round))
      groups.set(round, { round, items: [] })

    const meta = historyChip[log.event] || { text: log.description || log.event, color: 'grey' }

    groups.get(round).items.push({
      key: log.id,
      chipText: meta.text,
      chipColor: meta.color,
      time: formatDateTime(log.created_at),
      role: log.role || '-',
      name: log.causer || '',
      note: log.note || '',
      remark: log.remark || '',
      evidence: log.evidence || '',
    })
  }

  return [...groups.values()].sort((a, b) => a.round - b.round)
})

async function openHistory(item) {
  historyDialog.value = true
  historyLoading.value = true
  historyLogs.value = []

  try {
    const response = await getAnnualTicketHistory(item.id)
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

async function openPdf(item) {
  pdfDialog.value = true
  pdfLoading.value = true

  try {
    const blob = await getAnnualTicketPdf(item.id)
    pdfUrl.value = URL.createObjectURL(blob)
  } catch (err) {
    pdfDialog.value = false
    toast.error(err.response?.data?.message || 'Failed to load the Annual Ticket document.')
  } finally {
    pdfLoading.value = false
  }
}
</script>

<template>
  <div>
    <BrandCard>
      <template #stats>
        <VRow dense>
          <VCol v-for="tab in statusTabs" :key="tab.value" cols="6" sm="4" lg="3">
            <VCard
              :variant="statusTab === tab.value ? 'tonal' : 'outlined'"
              :color="statusTab === tab.value ? tab.color : undefined"
              class="pa-3 d-flex align-center cursor-pointer stat-tile h-100"
              style="gap: 12px"
              @click="statusTab = tab.value"
            >
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
        <VBtn variant="flat" color="primary" prepend-icon="ri-add-line" @click="openCreate">
          Create Annual Ticket
        </VBtn>
      </template>

      <template #search>
        <VTextField
          v-model="search"
          prepend-inner-icon="ri-search-line"
          placeholder="Search annual ticket..."
          variant="outlined"
          density="comfortable"
          hide-details
          clearable
          style="min-width: 260px; max-width: 320px"
        />
      </template>

      <VDataTable
        class="orders-table"
        :headers="headers"
        :items="filteredRows"
        :loading="loading"
        show-current-page
        item-value="id"
        hover
        no-data-text="No annual tickets found."
      >
        <template #item.action="{ item }">
          <VBtnGroup density="compact" variant="tonal">
            <VBtn color="blue-darken-1" @click="openPdf(item)">
              <VIcon icon="ri-eye-line" />
              <VTooltip activator="parent" location="top">View PDF</VTooltip>
            </VBtn>
            <VBtn color="blue-grey-darken-1" @click="openHistory(item)">
              <VIcon icon="ri-history-line" />
              <VTooltip activator="parent" location="top">History Log</VTooltip>
            </VBtn>
          </VBtnGroup>
        </template>

        <template #item.travel_period="{ item }">
          <span class="text-body-2 text-no-wrap">{{ item.travel_period }}</span>
        </template>

        <template #item.route_condition="{ item }">
          <VChip size="small" variant="tonal" :color="routeColor[item.route_condition] || 'secondary'" class="text-no-wrap">
            {{ item.route_condition }}
          </VChip>
        </template>

        <template #item.status="{ item }">
          <VChip size="small" variant="tonal" :color="statusMeta[item.status]?.color || 'primary'" class="text-no-wrap">
            <VIcon icon="ri-checkbox-blank-circle-fill" size="8" start />
            {{ statusMeta[item.status]?.title || item.status }}
          </VChip>
        </template>
      </VDataTable>
    </BrandCard>

    <CreateAnnualTicketModal v-if="showCreateModal" :travelers="travelers" @close="showCreateModal = false" @submitted="fetchRequests" />

    <HistoryLogModal v-model="historyDialog" :loading="historyLoading" :rounds="historyRounds" />

    <PdfViewerModal v-model="pdfDialog" title="Annual Ticket Detail" :pdf-url="pdfUrl" :loading="pdfLoading" />
</div>
</template>