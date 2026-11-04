<script setup>
import { computed, onMounted, ref } from 'vue'
import { getExternalFormRequests, getExternalFormRequestHistory } from '../services/externalFormRequestService'
import { useToastStore } from '../../../stores/toast'
import { useAuthStore } from '../../../stores/auth'
import PickTypeModal from '../../travel-order/components/modal/PickTypeModal.vue'
import AirplaneFerryFormModal from '../components/modal/AirplaneFerryFormModal.vue'
import HotelReservationFormModal from '../components/modal/HotelReservationFormModal.vue'
import HistoryLogModal from '../components/modal/HistoryLogModal.vue'

const toast = useToastStore()
const auth = useAuthStore()
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
      <template #item.action="{ item }">
        <VBtnGroup density="compact" variant="tonal">
          <VBtn v-if="item.raw.status === 'draft'" color="green-darken-1" @click="editDraft(item)"><VIcon icon="ri-edit-line" /><VTooltip activator="parent" location="top">Edit Draft</VTooltip></VBtn>
          <VBtn v-else color="grey-darken-1" @click="() => {}"><VIcon icon="ri-eye-line" /><VTooltip activator="parent" location="top">View</VTooltip></VBtn>
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

  <HistoryLogModal v-model="historyDialog" :loading="historyLoading" :logs="historyLogs" />
</template>
