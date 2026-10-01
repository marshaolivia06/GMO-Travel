<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import GroupTripForm from '../components/modal/GroupTripForm.vue'
import IndividualTripForm from '../components/modal/IndividualTripForm.vue'
import { getTravelOrders, getTravelOrder, createTravelOrder, updateTravelOrder, approveTravelOrder } from '../services/travelOrderService'
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

const filters = reactive({ search: '', status: null })

const statusOptions = [
  'Awaiting Approval Manager',
  'Awaiting Approval Director',
  'Awaiting Approval President Director',
  'Awaiting Approval GMO',
  'Awaiting GMO Processing',
  'Awaiting GMO Booking Preparation',
  'Awaiting GMO Document Issuance',
  'Processed by GMO',
  'Approved',
]

const statusColor = {
  'Processed by GMO': 'success',
  'Approved': 'success',
  'Awaiting Approval Manager': 'warning',
  'Awaiting Approval Director': 'warning',
  'Awaiting Approval President Director': 'warning',
  'Awaiting Approval GMO': 'warning',
  'Revision Required': 'warning',
  'Cancelled': 'error',
}

const statusLabel = {
  awaiting_approval_manager: 'Awaiting Approval Manager',
  awaiting_approval_director: 'Awaiting Approval Director',
  awaiting_approval_predir: 'Awaiting Approval President Director',
  awaiting_approval_gmo: 'Awaiting Approval GMO',
  approved: 'Approved',
  revision_required: 'Revision Required',
  cancelled: 'Cancelled',
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

const approvalColor = {
  submitted: 'success',
  approved: 'success',
  pending: 'warning',
  rejected: 'error',
  revision: 'warning',
}

const approvalLabel = {
  submitted: 'Submitted',
  approved: 'Approved',
  pending: 'Awaiting Approval',
  rejected: 'Rejected',
  revision: 'Revision Required',
}

const headerClass = 'px-3 py-3 text-caption font-weight-bold text-medium-emphasis bg-grey-lighten-4 text-no-wrap'
const cellClass = 'px-3 py-3'
const col = (title, key, extra = {}) => ({ title, key, sortable: false, headerProps: { class: headerClass }, cellProps: { class: cellClass }, ...extra })

const headers = [
  col('ACTION', 'action', { align: 'center', width: 120 }),
  col('ID', 'id'),
  col('REQUEST TYPE', 'type'),
  col('SUBJECT', 'subject'),
  col('PERIOD', 'period'),
  col('ARRANGEMENT', 'arrangement'),
  col('STATUS', 'status'),
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

function showMessage(text, type = 'success') {
  toast[type]?.(text)
}

function toStatusLabel(status) {
  return status ? statusLabel[status] || status : 'Awaiting Approval Manager'
}

// Tombol Approve/Reject/Revision hanya muncul kalau backend bilang user ini berhak
const canDecide = computed(() => selected.value?.can_approve === true)

function formatDate(value) {
  if (!value) return '-'
  const date = new Date(value)
  return isNaN(date.getTime()) ? '-' : date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

function formatDateTime(value) {
  if (!value) return ''
  const date = new Date(value)
  if (isNaN(date.getTime())) return ''

  return date
    .toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false })
    .replace(',', '')
}

function money(amount, currency) {
  const n = Number(amount)
  if (!n) return 'Not Applicable'
  return `${n.toLocaleString('en-US', { maximumFractionDigits: 2 })} ${currency || ''}`.trim()
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
    id: order.id,
    type: typeLabel[order.trip_type] || 'Business Trip - Individual',
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

function backToBusiness() {
  dialog.value = false
  dialogType.value = null
  businessDialog.value = true
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

// Ambil detail lengkap dari backend (daftar tidak membawa approvals dan can_approve)
async function refreshDetail(id) {
  try {
    const response = await getTravelOrder(id)
    selected.value = response?.data?.data ?? response?.data ?? response
  } catch (error) {
    console.error('Load detail error:', error)
  }
}

async function openDetail(row) {
  selected.value = row.raw
  detailDialog.value = true
  await refreshDetail(row.id)
}

// Dipakai Reject dan Revision (masih lokal, belum terhubung ke backend)
function updateStatus(status, message, type = 'success') {
  const orderNumber = selected.value?.order_number
  const index = orders.value.findIndex(order => order.order_number === orderNumber)

  if (index !== -1) orders.value[index] = { ...orders.value[index], status }

  detailDialog.value = false
  showMessage(message, type)
}

async function handleApprove() {
  if (approving.value) return

  const ok = await confirm({
    title: 'Approve Travel Order',
    message: `Are you sure you want to approve ${selected.value?.order_number}?`,
  })

  if (!ok) return

  approving.value = true

  try {
    await approveTravelOrder(selected.value.id)
    await loadOrders()
    await refreshDetail(selected.value.id)
    showMessage('Travel order approved.')
  } catch (error) {
    showMessage(error?.response?.data?.message || 'Failed to approve travel order.', 'error')
  } finally {
    approving.value = false
  }
}

// Reject: Yes -> Cancelled | Cancel -> tidak ada perubahan (belum ke backend)
async function handleReject() {
  const ok = await confirm({
    title: 'Reject Travel Order',
    message: `Are you sure you want to reject ${selected.value?.order_number}? This order will be cancelled.`,
  })

  if (!ok) return

  updateStatus('cancelled', 'Travel order has been cancelled.')
}

// Revision: Yes -> Revision Required | Cancel -> tidak ada perubahan (belum ke backend)
async function handleRevision() {
  const ok = await confirm({
    title: 'Return for Revision',
    message: `Are you sure you want to return ${selected.value?.order_number} for revision?`,
  })

  if (!ok) return

  updateStatus('revision_required', 'Travel order has been returned for revision.', 'warning')
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
        { label: 'Request Type', value: typeLabel[order.trip_type] || 'Business Trip - Individual' },
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
        { label: 'Meal Allowance', value: money(advance.meal_allowance, advance.currency) },
        { label: 'Pocket Money', value: money(advance.pocket_money, advance.currency) },
      ],
    },
  ]
})

// Susun item jadi baris: item biasa berpasangan 2 per baris, item "full" sebaris sendiri
function toRows(items) {
  const result = []
  let pending = null

  for (const item of items) {
    if (item.full) {
      if (pending) { result.push([pending]); pending = null }
      result.push([item])
    } else if (pending) {
      result.push([pending, item])
      pending = null
    } else {
      pending = item
    }
  }

  if (pending) result.push([pending])
  return result
}

// Data timeline asli dari backend (order.approvals)
const approvalSteps = computed(() =>
  (selected.value?.approvals || []).map(step => ({
    key: step.id,
    title: `${step.role_name || '-'} - ${step.name || '-'}`,
    status: step.status,
    time: formatDateTime(step.acted_at),
    note: step.note || '',
  })),
)

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
    const requestPayload = {
      ...payload,
      status: payload.status || 'submitted',
    }

    if (editingDraft.value && selected.value?.id) {
      await updateTravelOrder(selected.value.id, requestPayload)
    } else {
      await createTravelOrder(requestPayload)
    }

    closeDialog()
    editingDraft.value = false
    selected.value = null
    await loadOrders()

    if (requestPayload.status === 'draft') {
      showMessage('Travel order saved as draft.')
    } else {
      showMessage('Travel order submitted successfully.')
    }
  } catch (error) {
    console.error('Submit error:', error?.response?.data || error)

    const errors = error?.response?.data?.errors
    const firstError = errors ? Object.values(errors).flat()[0] : null

    showMessage(
      firstError || error?.response?.data?.message || 'Failed to save travel order.',
      'error',
    )
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

        <template #item.type="{ item }">
          <VChip size="small" variant="tonal" :color="typeColor[item.type] || 'primary'" class="text-no-wrap">{{ item.type }}</VChip>
        </template>

        <template #item.subject="{ item }">
          <div class="font-weight-bold text-body-2">{{ item.subject }}</div>
          <div class="text-caption text-medium-emphasis">{{ item.department || '-' }}</div>
        </template>

        <template #item.period="{ item }"><span class="text-body-2 text-no-wrap">{{ item.period }}</span></template>
        <template #item.arrangement="{ item }"><span class="text-body-2 text-no-wrap">{{ item.arrangement }}</span></template>

        <template #item.status="{ item }">
          <VChip size="small" variant="tonal" :color="statusColor[item.status] || 'primary'" class="text-no-wrap">
            <VIcon icon="ri-checkbox-blank-circle-fill" size="8" start />
            {{ item.status }}
          </VChip>
        </template>

        <template #item.action="{ item }">
          <div class="d-flex align-center justify-center ga-1">
            <VBtn icon="ri-eye-line" size="small" variant="text" color="primary" @click="openDetail(item)" />
            <VBtn v-if="item.raw.status === 'draft'" icon="ri-edit-line" size="small" variant="text" color="success" :loading="draftLoading" @click="continueDraft(item)" />
          </div>
        </template>
      </VDataTable>
    </VCard>

    <VDialog v-model="requestDialog" max-width="720">
      <VCard rounded="xl">
        <VCardItem class="px-6 py-5">
          <VCardTitle class="text-h6 font-weight-bold">New Travel Request</VCardTitle>
          <VCardSubtitle>Select the type of request you want to submit.</VCardSubtitle>
          <template #append><VBtn icon="ri-close-line" variant="text" size="small" @click="requestDialog = false" /></template>
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
                  <ul class="text-body-2 text-medium-emphasis ps-5 mt-4"><li v-for="point in item.points" :key="point">{{ point }}</li></ul>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>
        </VCardText>
      </VCard>
    </VDialog>

    <VDialog v-model="businessDialog" max-width="720">
      <VCard rounded="xl">
        <VCardItem class="px-6 py-5">
          <template #prepend><VBtn icon="ri-arrow-left-line" variant="tonal" color="primary" size="small" @click="backToRequest" /></template>
          <VCardTitle class="text-h6 font-weight-bold">Business Trip</VCardTitle>
          <VCardSubtitle>How many people are traveling?</VCardSubtitle>
          <template #append><VBtn icon="ri-close-line" variant="text" size="small" @click="businessDialog = false" /></template>
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
                  <ul class="text-body-2 text-medium-emphasis ps-5 mt-4"><li v-for="point in item.points" :key="point">{{ point }}</li></ul>
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
            <VBtn icon="ri-arrow-left-line" variant="tonal" color="primary" size="small" :disabled="submitting" @click="backToBusiness" />
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
          <IndividualTripForm v-if="dialogType === 'individual'" :loading="submitting" :initial-data="editingDraft ? selected : null" @cancel="closeDialog" @submit="submitOrder" />
          <GroupTripForm v-else-if="dialogType === 'group'" :loading="submitting" :initial-data="editingDraft ? selected : null" @cancel="closeDialog" @review="submitOrder" />
        </VCardText>
      </VCard>
    </VDialog>

    <!-- Detail dialog -->
    <VDialog v-model="detailDialog" max-width="900" scrollable>
      <VCard rounded="lg">
        <VCardItem class="px-6 py-3">
          <VCardTitle class="pa-0 text-subtitle-1 font-weight-bold">Travel Order Detail</VCardTitle>
          <template #append>
            <VBtn icon="ri-close-line" variant="text" @click="detailDialog = false" />
          </template>
        </VCardItem>

        <VDivider />

        <!-- Latar abu-abu seperti viewer PDF -->
        <VCardText class="bg-grey-lighten-3 pa-6">

          <!-- Halaman 1: dokumen -->
          <VSheet elevation="3" max-width="780" class="mx-auto pa-10">

            <!-- Kop dokumen -->
            <div class="d-flex align-start justify-space-between pb-4 mb-6 border-b-lg border-opacity-100 border-primary">
              <div>
                <div class="text-h5 font-weight-bold">TRAVEL ORDER</div>
                <div class="text-caption text-medium-emphasis">Business Trip Request Form</div>
              </div>

              <div class="text-end">
                <div class="text-caption text-medium-emphasis">Order Number</div>
                <div class="text-subtitle-1 font-weight-bold mb-1">{{ selected?.order_number || '-' }}</div>
                <VChip size="small" variant="tonal" :color="statusColor[toStatusLabel(selected?.status)] || 'primary'">
                  {{ toStatusLabel(selected?.status) }}
                </VChip>
              </div>
            </div>

            <!-- Section -->
            <div v-for="section in detailSections" :key="section.title" class="mb-6">
              <div class="bg-grey-lighten-4 border px-3 py-2 text-caption font-weight-bold text-uppercase">
                {{ section.title }}
              </div>

              <VTable density="compact" class="border-s border-e border-b">
                <tbody>
                  <tr v-for="(row, i) in toRows(section.items)" :key="i">
                    <template v-for="item in row" :key="item.label">
                      <td width="18%" class="text-caption text-medium-emphasis bg-grey-lighten-5 align-top py-2">{{ item.label }}</td>
                      <td :width="row.length === 1 ? undefined : '32%'" :colspan="row.length === 1 ? 3 : 1" class="text-body-2 font-weight-medium align-top py-2">{{ item.value }}</td>
                    </template>
                  </tr>
                </tbody>
              </VTable>
            </div>
          </VSheet>

          <!-- Halaman 2: Approval Progress -->
          <VSheet elevation="3" max-width="780" class="mx-auto mt-6">
            <div class="px-6 py-3 text-subtitle-2 font-weight-bold">Approval Progress</div>
            <VDivider />

            <VTimeline v-if="approvalSteps.length" side="end" align="start" density="compact" truncate-line="both" line-thickness="1" class="px-6 py-4">
              <VTimelineItem
                v-for="step in approvalSteps"
                :key="step.key"
                :dot-color="approvalColor[step.status] || 'grey'"
                size="x-small"
                class="pb-1"
              >
                <div class="d-flex align-center ga-2 flex-wrap">
                  <span class="text-body-2 font-weight-bold">{{ step.title }}</span>
                  <VChip size="x-small" variant="tonal" border label :color="approvalColor[step.status] || 'grey'" class="font-weight-bold">
                    {{ approvalLabel[step.status] || step.status }}
                  </VChip>
                </div>

                <div class="text-caption text-medium-emphasis">
                  <template v-if="step.time">{{ step.time }}</template>
                  <template v-else-if="step.status === 'pending'">Current stage</template>
                </div>

                <div v-if="step.note" class="text-caption">Note: {{ step.note }}</div>
              </VTimelineItem>
            </VTimeline>

            <div v-else class="px-6 py-4 text-caption text-medium-emphasis">No approval data yet.</div>
          </VSheet>
        </VCardText>

        <template v-if="canDecide">
          <VDivider />
          <VCardActions class="px-6 py-3 justify-end ga-2">
            <VBtn variant="tonal" color="error" :disabled="approving" @click="handleReject">Reject</VBtn>
            <VBtn variant="tonal" color="warning" :disabled="approving" @click="handleRevision">Revision</VBtn>
            <VBtn variant="flat" color="success" :loading="approving" @click="handleApprove">Approve</VBtn>
          </VCardActions>
        </template>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped>
.pick-tile { border-color: rgba(var(--v-border-color), var(--v-border-opacity)); transition: transform .2s, border-color .2s; }
.pick-tile:hover { transform: translateY(-4px); border-color: rgb(var(--v-theme-primary)); }
</style>