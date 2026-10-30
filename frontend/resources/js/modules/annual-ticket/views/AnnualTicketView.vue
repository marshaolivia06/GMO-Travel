<script setup>
import { computed, onMounted, ref } from 'vue'
import { getMyDependents, getAnnualTickets, getAnnualTicketHistory, getAnnualTicketPdf, checkAnnualTicket, approveAnnualTicket, revisionAnnualTicket, processAnnualTicketByGmo, processAnnualTicketByGmoWithFare, updateAnnualTicketSchedule, requestAnnualTicketCancellation } from '../services/annualTicketService'
import { useToastStore } from '../../../stores/toast'
import { useConfirm } from '../../../composables/useConfirm'
import CreateAnnualTicketModal from '../components/modal/CreateAnnualTicketModal.vue'
import HistoryLogModal from '../components/modal/HistoryLogModal.vue'
import AttachmentsModal from '../components/modal/AttachmentsModal.vue'
import FareReferenceModal from '../components/modal/FareReferenceModal.vue'
import AnnualTicketSummaryModal from '../components/modal/AnnualTicketSummaryModal.vue'
import PdfViewerModal from '../../travel-order/components/modal/PdfViewerModal.vue'
import NoteModal from '../../travel-order/components/modal/NoteModal.vue'

const toast = useToastStore()
const { confirm } = useConfirm()
const requests = ref([])
const loading = ref(false)

const search = ref('')
const statusTab = ref('all')
const showCreateModal = ref(false)
const resubmitTicket = ref(null)
const travelers = ref(null)
const selectedTravelers = ref([])
const step = ref(1)
const legs = ref(null)

async function openCreate() {
  travelers.value = await getMyDependents()
  step.value = 1
  resubmitTicket.value = null
  showCreateModal.value = true
}

async function openResubmit(item) {
  travelers.value = await getMyDependents()
  step.value = 1
  resubmitTicket.value = item.raw
  showCreateModal.value = true
}

const formatDate = date => new Intl.DateTimeFormat('en-GB', {
  timeZone: 'Asia/Jakarta',
  day: '2-digit',
  month: 'short',
  year: 'numeric',
}).format(new Date(date))

function formatPeriod(rowLegs = []) {
  const dates = [...new Set(rowLegs.map(leg => leg.reschedule_date ?? leg.travel_date).filter(Boolean).map(formatDate))].sort()

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
      raw: item,
      status: item.status,
      travel_period: formatPeriod(item.legs ?? []),
      route_condition: item.route_condition,
      ticket_arrangement: item.ticket_arrangement,
      can_process: item.can_process === true,
      can_gmo_process: item.can_gmo_process === true,
      can_gmo_summary: item.can_gmo_summary === true,
      stage_action: item.stage_action,
      stage_role: item.stage_role,
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
  awaiting_approval_hr_supervisor: { title: 'Awaiting Approval - HR Supervisor', color: 'warning', icon: 'ri-user-star-line' },
  awaiting_cancellation_check_hr_admin: { title: 'Awaiting Cancellation Check - HR Admin', color: 'warning', icon: 'ri-time-line' },
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
  col('ATTACHMENT', 'attachment', { align: 'center', width: 130 }),
  col('ACTION', 'action', { align: 'center', width: 260 }),
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
  checked: { text: 'Checked', color: 'success' },
  approved: { text: 'Approved', color: 'success' },
  revision: { text: 'Revision', color: 'warning' },
  processed: { text: 'Processed by GMO', color: 'success' },
}

const processing = ref(false)
const noteDialog = ref(false)
const noteTarget = ref(null)
const revisionMeta = { action: 'revision', title: 'Return for Revision', label: 'Remark', button: 'Revision', color: 'warning', requireEvidence: false }
const cancellationMeta = { action: 'cancel-request', title: 'Cancellation Request', label: 'Reason', button: 'Submit', color: 'error', requireEvidence: false }
const noteMeta = ref(revisionMeta)

function handleReviewRevision() {
  if (!reviewItem.value)
    return

  noteTarget.value = reviewItem.value
  noteMeta.value = revisionMeta
  noteDialog.value = true
}

function openCancelRequest(ticketId) {
  if (!ticketId)
    return

  noteTarget.value = { id: ticketId }
  noteMeta.value = cancellationMeta
  noteDialog.value = true
}

async function handleNoteSubmit({ text }) {
  const target = noteTarget.value

  if (!target || processing.value)
    return

  const isCancellation = noteMeta.value.action === 'cancel-request'

  const confirmed = await confirm(isCancellation
    ? { title: 'Cancellation Request', text: 'Are you sure you want to request the cancellation of this Annual Ticket?', color: 'error' }
    : { title: 'Return for Revision', text: 'Are you sure you want to return this Annual Ticket for revision?', color: 'warning' },
  )

  if (!confirmed)
    return

  processing.value = true

  try {
    if (isCancellation) {
      await requestAnnualTicketCancellation(target.id, text)
      toast.success('Cancellation request has been submitted successfully.')
      noteDialog.value = false
      summaryDialog.value = false
      await fetchRequests()
    } else {
      await revisionAnnualTicket(target.id, text)
      toast.success('Annual Ticket has been returned for revision.')
      noteDialog.value = false
      pdfDialog.value = false
      await fetchRequests()
    }
  } catch (err) {
    toast.error(err.response?.data?.message || (isCancellation ? 'Failed to submit the cancellation request.' : 'Failed to return the Annual Ticket for revision.'))
  } finally {
    processing.value = false
  }
}

async function handleCheck(item) {
  const confirmed = await confirm({
    title: 'Check Annual Ticket',
    text: `Are you sure you want to mark this Annual Ticket as checked?`,
    color: 'primary',
  })

  if (!confirmed || processing.value)
    return

  processing.value = true

  try {
    await checkAnnualTicket(item.id)
    toast.success('Annual Ticket has been checked successfully.')
    pdfDialog.value = false
    await fetchRequests()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to check the Annual Ticket.')
  } finally {
    processing.value = false
  }
}

async function handleApprove(item) {
  const confirmed = await confirm({
    title: 'Approve Annual Ticket',
    text: `Are you sure you want to mark this Annual Ticket as approved?`,
    color: 'primary',
  })

  if (!confirmed || processing.value)
    return

  processing.value = true

  try {
    await approveAnnualTicket(item.id)
    toast.success('Annual Ticket has been approved successfully.')
    pdfDialog.value = false
    await fetchRequests()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to approve the Annual Ticket.')
  } finally {
    processing.value = false
  }
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

function buildHistoryRounds(logs) {
  const groups = new Map()

  for (const log of logs) {
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
}

const historyRounds = computed(() => buildHistoryRounds(historyLogs.value))

const summaryDialog = ref(false)
const summaryTicket = ref(null)

function openSummary(item) {
  summaryTicket.value = item.raw
  summaryDialog.value = true
}

function openSummaryPdf(ticketId) {
  if (!ticketId)
    return

  summaryDialog.value = false
  pdfReviewMode.value = 'view'
  pdfBackToSummary.value = true
  reviewItem.value = null
  loadPdf({ id: ticketId })
}

async function handleScheduleUpdate({ ticketId, reschedules }) {
  if (processing.value)
    return

  const confirmed = await confirm({
    title: 'Update Travel Schedule',
    text: 'Are you sure you want to update the travel schedule for this Annual Ticket?',
    color: 'primary',
  })

  if (!confirmed)
    return

  processing.value = true

  try {
    await updateAnnualTicketSchedule(ticketId, { reschedules })
    toast.success('Travel schedule has been updated successfully.')
    await fetchRequests()

    const fresh = requests.value.find(row => row.id === ticketId)
    if (fresh)
      summaryTicket.value = fresh.raw
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to update the travel schedule.')
  } finally {
    processing.value = false
  }
}

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
const pdfReviewMode = ref('view')
const pdfBackToSummary = ref(false)
const reviewItem = ref(null)
const pdfCanDecide = computed(() => ['decide', 'gmo'].includes(pdfReviewMode.value))

const fareDialog = ref(false)
const fareItem = ref(null)

function mapFareLegs(direction) {
  return (fareItem.value?.raw?.legs ?? [])
    .filter(leg => leg.direction === direction)
    .map((leg, index) => ({
      id: leg.id ?? index + 1,
      name: leg.name,
      role: leg.relationship || 'Employee',
      date: formatDate(leg.travel_date),
      route: `${leg.travel_from} - ${leg.travel_to}`,
      routeStatus: fareItem.value?.raw?.route_condition ?? 'Same Route',
    }))
}

const fareDepartureLegs = computed(() => mapFareLegs('departure'))
const fareReturnLegs = computed(() => mapFareLegs('return'))

function openFareReference(item) {
  fareItem.value = item
  pdfDialog.value = false
  fareDialog.value = true
}

function handleFareBack() {
  fareDialog.value = false
  fareItem.value = null
  pdfDialog.value = true
}

const attachmentsDialog = ref(false)
const attachmentsTicket = ref(null)

function openAttachments(item) {
  attachmentsTicket.value = item.raw
  attachmentsDialog.value = true
}

async function loadPdf(item) {
  pdfDialog.value = true
  pdfLoading.value = true
  pdfUrl.value = null

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

function handlePdfBack() {
  pdfDialog.value = false

  if (pdfBackToSummary.value) {
    pdfBackToSummary.value = false
    summaryDialog.value = true
  }
}

function openPdf(item) {
  pdfReviewMode.value = 'view'
  pdfBackToSummary.value = false
  reviewItem.value = null
  loadPdf(item)
}

function openReview(item) {
  pdfReviewMode.value = 'decide'
  pdfBackToSummary.value = false
  reviewItem.value = item
  loadPdf(item)
}

function openProcess(item) {
  pdfReviewMode.value = 'gmo'
  pdfBackToSummary.value = false
  reviewItem.value = item
  loadPdf(item)
}

async function handleGmoProcess(item) {
  const confirmed = await confirm({
    title: 'Process Annual Ticket',
    text: `Are you sure you want to mark Annual Ticket #${item.id} as processed?`,
    color: 'primary',
  })

  if (!confirmed || processing.value)
    return

  processing.value = true

  try {
    await processAnnualTicketByGmo(item.id)
    toast.success('Annual Ticket has been processed successfully.')
    pdfDialog.value = false
    fareDialog.value = false
    fareItem.value = null
    await fetchRequests()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to process the Annual Ticket.')
  } finally {
    processing.value = false
  }
}

function handleReviewProcess() {
  if (!reviewItem.value)
    return

  if (reviewItem.value.can_gmo_process) {
    if (reviewItem.value.raw?.ticket_arrangement === 'Direct Payment') {
      openFareReference(reviewItem.value)
      return
    }

    handleGmoProcess(reviewItem.value)
  }
  else if (reviewItem.value.stage_action === 'check')
    handleCheck(reviewItem.value)
  else
    handleApprove(reviewItem.value)
}

async function handleFareProcess(payload) {
  if (!fareItem.value || processing.value)
    return

  const confirmed = await confirm({
    title: 'Process Annual Ticket',
    text: `Are you sure you want to mark Annual Ticket #${fareItem.value.id} as processed?`,
    color: 'primary',
  })

  if (!confirmed)
    return

  processing.value = true

  try {
    await processAnnualTicketByGmoWithFare(fareItem.value.id, {
      fareReferences: [
        { direction: 'departure', fare_amount: payload.departureFare, operator_name: payload.departureOperator, currency: 'IDR' },
        { direction: 'return', fare_amount: payload.returnFare, operator_name: payload.returnOperator, currency: 'IDR' },
      ],
      attachments: payload.attachments,
    })
    toast.success('Annual Ticket has been processed successfully.')
    pdfDialog.value = false
    fareDialog.value = false
    fareItem.value = null
    await fetchRequests()
  } catch (err) {
    const errors = err.response?.data?.errors
    const firstError = errors ? Object.values(errors).flat()[0] : null
    toast.error(firstError || err.response?.data?.message || 'Failed to process the Annual Ticket.')
  } finally {
    processing.value = false
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
        <template #item.attachment="{ item }">
          <VBtn v-if="item.raw?.evidence_url" color="deep-purple-darken-1" variant="tonal" @click="openAttachments(item)">
            <VIcon icon="ri-attachment-2" />
            <VTooltip activator="parent" location="top">View Attachments</VTooltip>
          </VBtn>
          <span v-else>-</span>
        </template>

        <template #item.action="{ item }">
          <VBtnGroup density="compact" variant="tonal">
            <template v-if="item.can_process">
              <VBtn :color="item.stage_action === 'check' ? 'success' : 'green-darken-1'" :disabled="processing" @click="openReview(item)">
                <VIcon :icon="item.stage_action === 'check' ? 'ri-checkbox-multiple-line' : 'ri-check-line'" />
                <VTooltip activator="parent" location="top">{{ item.stage_action === 'check' ? 'Check' : 'Approval' }}</VTooltip>
              </VBtn>
            </template>
            <VBtn v-else-if="item.can_gmo_process" color="teal-darken-1" :disabled="processing" @click="openProcess(item)">
              <VIcon icon="ri-task-line" />
              <VTooltip activator="parent" location="top">Process</VTooltip>
            </VBtn>
            <VBtn v-else-if="item.status === 'revision_required' && item.raw?.is_owner" color="primary" @click="openResubmit(item)">
              <VIcon icon="ri-edit-line" />
              <VTooltip activator="parent" location="top">Edit</VTooltip>
            </VBtn>
            <VBtn v-else-if="item.status === 'processed_by_gmo' && (item.can_gmo_summary || item.raw?.is_owner)" color="grey-darken-1" :disabled="processing" @click="openSummary(item)">
              <VIcon icon="ri-eye-line" />
              <VTooltip activator="parent" location="top">View</VTooltip>
            </VBtn>
            <VBtn v-else color="blue-darken-1" @click="openPdf(item)">
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

    <CreateAnnualTicketModal v-if="showCreateModal" :travelers="travelers" :ticket="resubmitTicket" @close="showCreateModal = false" @submitted="fetchRequests" />

    <HistoryLogModal v-model="historyDialog" :loading="historyLoading" :rounds="historyRounds" />

    <AttachmentsModal v-model="attachmentsDialog" :ticket="attachmentsTicket" />

    <NoteModal v-model="noteDialog" :meta="noteMeta" @submit="handleNoteSubmit" />

    <PdfViewerModal
      v-model="pdfDialog"
      :title="!pdfCanDecide ? 'Annual Ticket Detail' : reviewItem?.can_gmo_process ? 'Process Annual Ticket' : 'Review Annual Ticket'"
      :pdf-url="pdfUrl"
      :loading="pdfLoading"
      :can-decide="pdfCanDecide"
      :show-back="pdfBackToSummary"
      :approving="processing"
      confirm-text="I confirm that I have reviewed the Annual Ticket document and agree that it is ready to proceed."
      @back="handlePdfBack"
      @update:model-value="value => !value && (pdfReviewMode = 'view')"
    >
      <template #decide="{ agreed }">
        <VCardActions v-if="agreed" class="px-8 pb-5 pt-0 justify-end ga-3">
          <template v-if="reviewItem?.can_gmo_process">
            <VBtn variant="flat" color="primary" :loading="processing" @click="handleReviewProcess">
              {{ reviewItem?.raw?.ticket_arrangement === 'Direct Payment' ? 'Continue' : 'Complete Processing' }}
            </VBtn>
          </template>
          <template v-else>
            <VBtn variant="flat" color="warning" :disabled="processing" @click="handleReviewRevision">Revision</VBtn>
            <VBtn variant="flat" color="primary" :loading="processing" @click="handleReviewProcess">
              {{ reviewItem?.stage_action === 'check' ? 'Checked' : 'Approved' }}
            </VBtn>
          </template>
        </VCardActions>
      </template>
    </PdfViewerModal>

    <FareReferenceModal
      v-model="fareDialog"
      :departure-legs="fareDepartureLegs"
      :return-legs="fareReturnLegs"
      :processing="processing"
      @back="handleFareBack"
      @process="handleFareProcess"
    />

    <AnnualTicketSummaryModal
      v-model="summaryDialog"
      :ticket="summaryTicket"
      :processing="processing"
      :can-edit-schedule="summaryTicket?.can_gmo_summary === true"
      @update-schedule="handleScheduleUpdate"
      @view-pdf="openSummaryPdf"
      @cancel-request="openCancelRequest"
    />
</div>
</template>