<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import GroupTripForm from '../components/modal/GroupTripForm.vue'
import IndividualTripForm from '../components/modal/IndividualTripForm.vue'
import { getTravelOrders, createTravelOrder } from '../services/travelOrderService'

const route = useRoute()
const router = useRouter()

const orders = ref([])
const loading = ref(false)
const errorMessage = ref('')
const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')
const detailDialog = ref(false)
const requestDialog = ref(false)
const businessDialog = ref(false)
const dialog = ref(false)
const selected = ref(null)
const dialogType = ref(null)
const submitting = ref(false)

const filters = reactive({ search: '', status: null })

const statusOptions = ['Awaiting Approval Manager', 'Awaiting GMO Processing', 'Awaiting GMO Booking Preparation', 'Awaiting GMO Document Issuance', 'Processed by GMO']

const statusColor = {
  'Processed by GMO': 'success',
  'Awaiting Approval Manager': 'warning',
}

const statusLabel = {
  awaiting_approval_manager: 'Awaiting Approval Manager',
  awaiting_gmo_processing: 'Awaiting GMO Processing',
  awaiting_gmo_booking_preparation: 'Awaiting GMO Booking Preparation',
  awaiting_gmo_document_issuance: 'Awaiting GMO Document Issuance',
  processed_by_gmo: 'Processed by GMO',
}

const typeColor = {
  'Business Trip - Individual': 'primary',
  'Business Trip - Group Trip': 'info',
  'Annual Trip': 'success',
}

const typeLabel = {
  individual: 'Business Trip - Individual',
  group: 'Business Trip - Group Trip',
  annual: 'Annual Trip',
}

const headerClass = 'px-3 py-3 text-caption font-weight-bold text-medium-emphasis bg-grey-lighten-4 text-no-wrap'
const cellClass = 'px-3 py-3'

const col = (title, key, extra = {}) => ({ title, key, sortable: false, headerProps: { class: headerClass }, cellProps: { class: cellClass }, ...extra })

const headers = [
  col('ID', 'id'),
  col('REQUEST TYPE', 'type'),
  col('SUBJECT', 'subject'),
  col('PERIOD', 'period'),
  col('ARRANGEMENT', 'arrangement'),
  col('STATUS', 'status'),
  col('ACTION', 'action', { align: 'center', width: 100 }),
]

const requestTypes = [
  { key: 'annual', badge: 'AL', title: 'Annual Leave', desc: 'Submit your annual leave request.', points: ['Leave period selection', 'Leave balance check'] },
  { key: 'business', badge: 'BT', title: 'Business Trip', desc: 'Create an individual trip or a group trip request.', points: ['Individual Trip', 'Group Trip', 'Travel region and travel advance'] },
]

const businessTypes = [
  { key: 'individual', badge: 'IT', title: 'Individual Trip', desc: 'Business trip for one person.', points: ['Route and travel dates', 'Ticket and accommodation', 'Travel advance'] },
  { key: 'group', badge: 'GT', title: 'Group Trip', desc: 'Business trip for multiple people.', points: ['Traveler list', 'Route and travel dates', 'Ticket and accommodation'] },
]

function showMessage(text, color = 'success') {
  snackbarText.value = text
  snackbarColor.value = color
  snackbar.value = true
}

function toStatusLabel(status) {
  return status ? statusLabel[status] || status : 'Awaiting Approval Manager'
}

function formatDate(value) {
  if (!value) return '-'
  const date = new Date(value)
  return isNaN(date.getTime()) ? '-' : date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
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
    raw: order,
    id: order.order_number,
    type: typeLabel[order.request_type] || 'Business Trip - Individual',
    subject: order.subject || order.user?.name || '-',
    department: order.department?.name || order.department_name || '',
    period: `${formatDate(order.departure_date)} – ${formatDate(order.return_date)}`,
    arrangement: resolveArrangement(order),
    status: toStatusLabel(order.status),
  }
}

const rows = computed(() => orders.value.map(normalize))

const filteredRows = computed(() => {
  const query = filters.search.trim().toLowerCase()

  return rows.value.filter(row =>
    (!query || `${row.id} ${row.subject}`.toLowerCase().includes(query)) &&
    (!filters.status || row.status === filters.status),
  )
})

function clearFilters() {
  filters.search = ''
  filters.status = null
}

function openNewRequest() {
  requestDialog.value = true
}

function selectRequest(type) {
  if (type === 'annual') {
    requestDialog.value = false
    showMessage('Annual Leave form is not available yet.', 'info')
    return
  }

  requestDialog.value = false
  businessDialog.value = true
}

function backToRequest() {
  businessDialog.value = false
  requestDialog.value = true
}

function selectBusinessType(type) {
  businessDialog.value = false
  dialogType.value = type
  dialog.value = true
}

function closeDialog() {
  dialog.value = false
  dialogType.value = null
}

function openDetail(row) {
  selected.value = row.raw
  detailDialog.value = true
}

const detailSections = computed(() => {
  const order = selected.value
  if (!order) return []

  const ticket = order.ticket || {}
  const accommodation = order.accommodation || {}
  const advance = order.advance || {}
  const val = value => value === null || value === undefined || value === '' ? '-' : value

  return [
    {
      title: 'Request Summary',
      items: [
        { label: 'Request Type', value: typeLabel[order.request_type] || 'Business Trip - Individual' },
        { label: 'Employee', value: val(order.user?.name) },
        { label: 'Department', value: val(order.department?.name) },
        { label: 'Travel Region', value: val(order.travel_region) },
        { label: 'Country', value: val(order.country) },
        { label: 'Route', value: `${val(order.travel_from)} → ${val(order.travel_to)}` },
        { label: 'Departure', value: `${formatDate(order.departure_date)} ${order.departure_time || ''}`.trim() },
        { label: 'Return', value: `${formatDate(order.return_date)} ${order.return_time || ''}`.trim() },
        { label: 'Purpose', value: val(order.purpose), full: true },
        { label: 'Remarks', value: val(order.remarks), full: true },
      ],
    },
    {
      title: 'Ticket, Accommodation & Travel Advance',
      items: [
        { label: 'Ticket Type', value: val(ticket.ticket_type ?? order.ferry_ticket_type) },
        { label: 'Ticket Arrangement', value: val(ticket.arrangement ?? order.ferry_arrangement) },
        { label: 'Accommodation', value: val(accommodation.arrangement ?? order.accommodation_arrangement) },
        { label: 'Meal Allowance', value: advance.meal_allowance ? `${advance.meal_allowance} ${advance.meal_currency || ''}` : 'Not Applicable' },
        { label: 'Pocket Money', value: advance.pocket_money ? `${advance.pocket_money} ${advance.pocket_currency || ''}` : 'Not Applicable' },
      ],
    },
  ]
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
    await createTravelOrder(payload)
    closeDialog()
    router.push({ name: 'my-travel-order', query: { submitted: '1' } })
  } catch (error) {
    console.error('Submit error:', error?.response?.data || error)

    const errors = error?.response?.data?.errors
    const firstError = errors ? Object.values(errors)[0][0] : null

    showMessage(firstError || error?.response?.data?.message || 'Failed to submit travel order.', 'error')
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  loadOrders()

  if (route.query.submitted) {
    snackbar.value = true
    snackbarText.value = 'Travel order submitted successfully.'
    snackbarColor.value = 'success'
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

        <template #append>
          <VChip size="small" variant="tonal" color="primary">{{ filteredRows.length }} requests</VChip>
        </template>
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
        <template #item.id="{ item }">
          <span class="font-weight-bold text-body-2 text-no-wrap">{{ item.id }}</span>
        </template>

        <template #item.type="{ item }">
          <VChip size="small" variant="tonal" :color="typeColor[item.type] || 'primary'" class="text-no-wrap">{{ item.type }}</VChip>
        </template>

        <template #item.subject="{ item }">
          <div class="font-weight-bold text-body-2">{{ item.subject }}</div>
          <div class="text-caption text-medium-emphasis">{{ item.department || '-' }}</div>
        </template>

        <template #item.period="{ item }">
          <span class="text-body-2 text-no-wrap">{{ item.period }}</span>
        </template>

        <template #item.arrangement="{ item }">
          <span class="text-body-2 text-no-wrap">{{ item.arrangement }}</span>
        </template>

        <template #item.status="{ item }">
          <VChip size="small" variant="tonal" :color="statusColor[item.status] || 'primary'" class="text-no-wrap">
            <VIcon icon="ri-checkbox-blank-circle-fill" size="8" start />
            {{ item.status }}
          </VChip>
        </template>

        <template #item.action="{ item }">
          <VBtn size="small" variant="tonal" color="primary" prepend-icon="ri-eye-line" @click="openDetail(item)">View</VBtn>
        </template>
      </VDataTable>
    </VCard>

    <!-- Modal 1: pilih Annual Leave / Business Trip -->
    <VDialog v-model="requestDialog" max-width="720">
      <VCard rounded="xl">
        <VCardItem class="px-6 py-5">
          <VCardTitle class="text-h6 font-weight-bold">New Travel Request</VCardTitle>
          <VCardSubtitle>Select the type of request you want to submit.</VCardSubtitle>

          <template #append>
            <VBtn icon="ri-close-line" variant="text" size="small" @click="requestDialog = false" />
          </template>
        </VCardItem>

        <VDivider />

        <VCardText class="pa-6">
          <VRow>
            <VCol v-for="item in requestTypes" :key="item.key" cols="6">
              <VCard variant="outlined" rounded="lg" hover class="pick-tile h-100" @click="selectRequest(item.key)">
                <VCardText class="pa-6">
                  <VAvatar color="primary" rounded="lg" size="56" class="mb-4"><span class="text-h6 font-weight-bold">{{ item.badge }}</span></VAvatar>
                  <div class="text-h6 font-weight-bold mb-1">{{ item.title }}</div>
                  <div class="text-body-2 text-medium-emphasis">{{ item.desc }}</div>
                  <ul class="text-body-2 text-medium-emphasis ps-5 mt-4">
                    <li v-for="point in item.points" :key="point">{{ point }}</li>
                  </ul>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- Modal 2: pilih Individual / Group -->
    <VDialog v-model="businessDialog" max-width="720">
      <VCard rounded="xl">
        <VCardItem class="px-6 py-5">
          <template #prepend>
            <VBtn icon="ri-arrow-left-line" variant="tonal" color="primary" size="small" @click="backToRequest" />
          </template>

          <VCardTitle class="text-h6 font-weight-bold">Business Trip</VCardTitle>
          <VCardSubtitle>How many people are traveling?</VCardSubtitle>

          <template #append>
            <VBtn icon="ri-close-line" variant="text" size="small" @click="businessDialog = false" />
          </template>
        </VCardItem>

        <VDivider />

        <VCardText class="pa-6">
          <VRow>
            <VCol v-for="item in businessTypes" :key="item.key" cols="6">
              <VCard variant="outlined" rounded="lg" hover class="pick-tile h-100" @click="selectBusinessType(item.key)">
                <VCardText class="pa-6">
                  <VAvatar color="primary" rounded="lg" size="56" class="mb-4"><span class="text-h6 font-weight-bold">{{ item.badge }}</span></VAvatar>
                  <div class="text-h6 font-weight-bold mb-1">{{ item.title }}</div>
                  <div class="text-body-2 text-medium-emphasis">{{ item.desc }}</div>
                  <ul class="text-body-2 text-medium-emphasis ps-5 mt-4">
                    <li v-for="point in item.points" :key="point">{{ point }}</li>
                  </ul>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>
        </VCardText>
      </VCard>
    </VDialog>

    <VDialog v-model="dialog" max-width="1200" scrollable>
      <VCard rounded="lg">
        <VCardTitle class="d-flex align-center justify-space-between pa-5">
          <div class="d-flex align-center ga-3">
            <VAvatar color="primary" variant="tonal" size="42"><VIcon :icon="dialogType === 'individual' ? 'ri-user-line' : 'ri-group-line'" size="22" /></VAvatar>

            <div>
              <div class="text-subtitle-1 font-weight-bold">{{ dialogType === 'individual' ? 'Individual Trip' : 'Group Trip' }}</div>
              <div class="text-caption text-medium-emphasis">{{ dialogType === 'individual' ? 'Create an individual business trip request.' : 'Create a group business trip request.' }}</div>
            </div>
          </div>

          <VBtn icon="ri-close-line" variant="text" @click="closeDialog" />
        </VCardTitle>

        <VDivider />

        <VCardText class="pa-6">
          <IndividualTripForm v-if="dialogType === 'individual'" :loading="submitting" @cancel="closeDialog" @submit="submitOrder" />
          <GroupTripForm v-else-if="dialogType === 'group'" :loading="submitting" @cancel="closeDialog" @review="submitOrder" />
        </VCardText>
      </VCard>
    </VDialog>

    <VDialog v-model="detailDialog" max-width="800" scrollable>
      <VCard rounded="lg">
        <VCardItem class="px-6 py-4">
          <div class="text-caption text-primary font-weight-bold">TRAVEL ORDER</div>
          <VCardTitle class="pa-0 text-h6 font-weight-bold">{{ selected?.order_number || '-' }}</VCardTitle>

          <template #append>
            <VChip size="small" variant="tonal" :color="statusColor[toStatusLabel(selected?.status)] || 'primary'" class="me-2">{{ toStatusLabel(selected?.status) }}</VChip>
            <VBtn icon="ri-close-line" variant="text" @click="detailDialog = false" />
          </template>
        </VCardItem>

        <VDivider />

        <VCardText class="px-6 py-2">
          <template v-for="(section, index) in detailSections" :key="section.title">
            <VDivider v-if="index > 0" class="my-4" />
            <div class="text-subtitle-2 font-weight-bold text-primary mt-4 mb-4">{{ section.title }}</div>

            <VRow dense>
              <VCol v-for="item in section.items" :key="item.label" cols="12" :md="item.full ? 12 : 4" class="pb-4">
                <div class="text-caption text-medium-emphasis">{{ item.label }}</div>
                <div class="text-body-2 font-weight-medium">{{ item.value }}</div>
              </VCol>
            </VRow>
          </template>
        </VCardText>

        <VDivider />

        <VCardActions class="px-6 py-3 justify-end">
          <VBtn variant="tonal" color="primary" @click="detailDialog = false">Close</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VSnackbar v-model="snackbar" :color="snackbarColor" :timeout="4000" location="top end">{{ snackbarText }}</VSnackbar>
  </div>
</template>

<style scoped>
.pick-tile { border-color: rgba(var(--v-border-color), var(--v-border-opacity)); transition: transform .2s, border-color .2s; }
.pick-tile:hover { transform: translateY(-4px); border-color: rgb(var(--v-theme-primary)); }
</style>