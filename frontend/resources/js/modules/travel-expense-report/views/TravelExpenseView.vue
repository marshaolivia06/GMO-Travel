<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../../../stores/auth.js'
import { getTravelOrders } from '../../travel-order/services/travelOrderService.js'
import { getAnnualTickets } from '../../annual-ticket/services/annualTicketService.js'
import TravelExpenseReportModal from '../components/modal/TravelExpenseReportModal.vue'
import AnnualTicketClaimModal from '../components/modal/AnnualTicketClaimModal.vue'

const authStore = useAuthStore()
const search = ref('')
const items = ref([])
const loading = ref(false)
const showDetail = ref(false)
const selectedItem = ref(null)
const showTicketClaim = ref(false)
const ticketClaimRaw = ref(null)

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
const statusLabel = status => normalize(status) === 'draft' ? 'Draft' : 'New'
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
    const [ordersRes, ticketsRes] = await Promise.all([getTravelOrders(), getAnnualTickets()])

    const orders = Array.isArray(ordersRes.data) ? ordersRes.data : Array.isArray(ordersRes.data?.data) ? ordersRes.data.data : Array.isArray(ordersRes) ? ordersRes : []
    const tickets = Array.isArray(ticketsRes) ? ticketsRes : Array.isArray(ticketsRes?.data) ? ticketsRes.data : []

    const orderItems = orders.filter(to => String(to.user_id) === String(currentUserId.value) && normalize(to.status) === PROCESSED_BY_GMO).map(to => ({
      id: to.id,
      status: 'new',
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

    const ticketItems = tickets
      .filter(ticket => String(ticket.employee_id) === String(currentUserId.value))
      .filter(ticket => CLAIMABLE_TICKET_STATUSES.includes(normalize(ticket.status)))
      .filter(ticket => ticket.ticket_arrangement === 'Direct Payment' || ticket.route_condition === 'Different Route')
      .map(ticket => {
        const dates = (ticket.legs ?? []).map(leg => leg.reschedule_date ?? leg.travel_date).filter(Boolean).sort()
        const country = String(ticket.employee?.eligible_destination?.country ?? '').trim().toLowerCase()
        const region = country === 'indonesia' ? 'Domestic' : country === 'singapore' ? 'Singapore' : country ? 'Overseas' : null
        return {
          id: `annual-ticket-${ticket.id}`,
          status: 'new',
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

    items.value = [...orderItems, ...ticketItems]
  } catch (error) {
    console.error('Failed to load Travel Expense Reports:', error)
    items.value = []
  } finally {
    loading.value = false
  }
}

function onContinue(item) {
  if (item.trip_type === 'annual_ticket') {
    ticketClaimRaw.value = item.raw ?? null
    showTicketClaim.value = true
    return
  }
  selectedItem.value = item
  showDetail.value = true
}

onMounted(load)
</script>

<template>
  <BrandCard>
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

      <VDataTable :headers="headers" :items="items" :search="search" :loading="loading" item-value="id" hover class="ter-table" :items-per-page="10" :items-per-page-options="[10, 25, 50]" no-data-text="No Travel Expense Report found" loading-text="Loading travel reports...">
        <template #item.action="{ item }">
          <VBtnGroup density="compact" variant="tonal">
            <VBtn color="grey-darken-1" @click="onContinue(item)">
              <VIcon icon="ri-edit-line" />
              <VTooltip activator="parent" location="top">Continue</VTooltip>
            </VBtn>
          </VBtnGroup>
        </template>
        <template #item.status="{ item }">
  <VChip :color="item.status === 'draft' ? 'secondary' : 'warning'" variant="tonal" size="small">
    {{ statusLabel(item.status) }}
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
          <span class="font-weight-semibold text-no-wrap">{{ item.total_claim ?? '-' }}</span>
        </template>
      </VDataTable>
  </BrandCard>
    <TravelExpenseReportModal v-model="showDetail" :item="selectedItem" />
    <AnnualTicketClaimModal v-model="showTicketClaim" :ticket="ticketClaimRaw" @submit="payload => console.log('Submit annual ticket claim:', payload)" @draft="payload => console.log('Draft annual ticket claim:', payload)" />
</template>