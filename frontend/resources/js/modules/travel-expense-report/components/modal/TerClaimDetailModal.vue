<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { legRouteStatus } from '../../../annual-ticket/utils/routeStatus.js'
import { getTravelExpenseReportPdf } from '../../services/travelExpenseReportService.js'
import { useToastStore } from '../../../../stores/toast'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  report: { type: Object, default: null },
})
const emit = defineEmits(['update:modelValue'])

const toast = useToastStore()
const r = computed(() => props.report ?? {})
const ticket = computed(() => r.value.annual_ticket ?? {})
const legs = computed(() => ticket.value.legs ?? [])
const isReimbursement = computed(() => r.value.claim_type === 'ticket_reimbursement')
const isAnnualTicket = computed(() => (r.value.source_type ?? 'annual_ticket') === 'annual_ticket')
const tripTypeLabel = computed(() => isAnnualTicket.value ? 'Annual Ticket' : 'Travel Order')
const sourceLabel = computed(() => isAnnualTicket.value ? 'ANNUAL TICKET' : 'TRAVEL ORDER')
const purpose = computed(() => `${ticket.value.route_condition ?? '-'} - ${ticket.value.ticket_arrangement ?? '-'} ${isReimbursement.value ? 'Reimbursable' : 'Excess Settlement'}`)

const close = () => emit('update:modelValue', false)

const formatMoney = value => new Intl.NumberFormat('id-ID').format(Number(value || 0))
const formatDate = date => {
  if (!date) return '-'
  const parsed = new Date(date)
  return Number.isNaN(parsed.getTime()) ? '-' : parsed.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

const legDate = leg => formatDate(leg.reschedule_date ?? leg.travel_date)
const sortedDates = computed(() => legs.value.map(leg => leg.reschedule_date ?? leg.travel_date).filter(Boolean).sort())
const period = computed(() => {
  const start = sortedDates.value[0]
  const end = sortedDates.value.at(-1)
  return start && end ? `${formatDate(start)} - ${formatDate(end)}` : '-'
})
const routeStatus = leg => legRouteStatus(ticket.value, leg)

// Fare reference - sama seperti yang diisi GMO dan ditampilkan saat claim
const directionLegs = direction => legs.value.filter(leg => leg.direction === direction)
const fareRows = computed(() => (ticket.value.fare_references ?? []).map(reference => {
  const legCount = directionLegs(reference.direction).length
  const firstLeg = directionLegs(reference.direction)[0]
  return {
    direction: reference.direction === 'departure' ? 'Departure' : 'Return',
    route: firstLeg ? `${firstLeg.travel_from} - ${firstLeg.travel_to}` : '-',
    fare: reference.fare_amount,
    currency: reference.currency ?? 'IDR',
    legs: legCount,
    subtotal: Number(reference.fare_amount || 0) * legCount,
    operator: reference.operator_name,
  }
}))
const totalMaxFare = computed(() => fareRows.value.reduce((sum, row) => sum + row.subtotal, 0))

// Tab Generate PDF - PDF di-fetch sebagai blob sekali per pembukaan modal
const tab = ref('view')
const pdfUrl = ref(null)
const pdfLoading = ref(false)
const pdfAttempted = ref(false)

const loadPdf = async () => {
  if (!r.value.id || pdfUrl.value || pdfLoading.value) return
  pdfLoading.value = true
  try {
    const blob = await getTravelExpenseReportPdf(r.value.id)
    pdfUrl.value = URL.createObjectURL(blob)
  } catch (error) {
    toast.error(error?.response?.data?.message || 'Failed to load the PDF.')
  } finally {
    pdfLoading.value = false
    pdfAttempted.value = true
  }
}

const openPdfNewTab = () => pdfUrl.value && window.open(pdfUrl.value, '_blank', 'noopener')

watch(tab, value => { if (value === 'pdf') loadPdf() })

watch(() => [props.modelValue, props.report?.id], ([open]) => {
  if (open) {
    tab.value = 'view'
    pdfLoading.value = false
    pdfAttempted.value = false
    pdfUrl.value = null
    return
  }

  if (pdfUrl.value) {
    URL.revokeObjectURL(pdfUrl.value)
    pdfUrl.value = null
  }
})

onBeforeUnmount(() => {
  if (pdfUrl.value) URL.revokeObjectURL(pdfUrl.value)
})
</script>

<template>
  <VDialog :model-value="modelValue" max-width="1400" width="95%" @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg" class="d-flex flex-column" style="height: min(90vh, 900px)">
      <div class="d-flex align-center justify-space-between px-4 py-3 flex-shrink-0">
        <span class="text-h6 font-weight-bold">Travel Expense Report Detail</span>
        <VBtn icon="ri-close-line" variant="text" size="small" @click="close" />
      </div>
      <VDivider />
      <VTabs v-model="tab" color="primary" class="flex-shrink-0">
        <VTab value="view">View</VTab>
        <VTab value="pdf">Generate PDF</VTab>
      </VTabs>
      <VDivider />

      <div v-show="tab === 'view'" class="overflow-y-auto flex-grow-1">
      <VCardText class="bg-grey-lighten-5 pa-4">
        <VCard rounded="lg" border elevation="0" class="mb-4">
          <div class="bg-grey-lighten-4 px-4 py-3">
            <span class="text-subtitle-2 font-weight-bold">Report Information</span>
          </div>
          <VDivider />
          <VCardText class="pa-4">
            <VRow no-gutters class="border rounded-lg overflow-hidden">
              <VCol cols="6" class="px-4 py-3 border-b border-e">
                <div class="text-caption text-medium-emphasis">REPORT ID</div>
                <div class="text-body-2 font-weight-bold">{{ r.document_number || '-' }}</div>
              </VCol>
              <VCol cols="6" class="px-4 py-3 border-b">
                <div class="text-caption text-medium-emphasis">EMPLOYEE</div>
                <div class="text-body-2 font-weight-bold">{{ ticket.employee?.name || '-' }}</div>
              </VCol>
              <VCol cols="6" class="px-4 py-3 border-b border-e">
                <div class="text-caption text-medium-emphasis">TRIP TYPE</div>
                <VChip color="success" variant="tonal" size="small" class="mt-1">{{ tripTypeLabel }}</VChip>
              </VCol>
              <VCol cols="6" class="px-4 py-3 border-b">
                <div class="text-caption text-medium-emphasis">PERIOD</div>
                <div class="text-body-2 font-weight-bold">{{ period }}</div>
              </VCol>
              <VCol cols="6" class="px-4 py-3 border-b border-e">
                <div class="text-caption text-medium-emphasis">{{ sourceLabel }}</div>
                <div class="text-body-2 font-weight-bold">{{ ticket.document_number || '-' }}</div>
              </VCol>
              <VCol cols="6" class="px-4 py-3 border-b">
                <div class="text-caption text-medium-emphasis">PURPOSE</div>
                <div class="text-body-2 font-weight-bold">{{ purpose }}</div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <VCard rounded="lg" border elevation="0" class="mb-4">
          <div class="bg-grey-lighten-4 px-4 py-3">
            <span class="text-subtitle-2 font-weight-bold">Claimed Expense</span>
          </div>
          <VDivider />
          <VCardText class="pa-4">
            <VCard v-for="direction in ['departure', 'return']" :key="direction" rounded="lg" border elevation="0" class="mb-4">
              <div class="bg-grey-lighten-4 px-4 py-3">
                <span class="text-subtitle-2 font-weight-bold text-capitalize">{{ direction }} Travel Legs</span>
              </div>
              <VDivider />
              <VTable density="comfortable">
                <thead>
                  <tr>
                    <th>Traveler</th>
                    <th>Relationship</th>
                    <th>Date</th>
                    <th>Travel From</th>
                    <th>Travel To</th>
                    <th>Route Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="leg in directionLegs(direction)" :key="leg.id">
                    <td class="text-body-2">{{ leg.name }}</td>
                    <td class="text-body-2">{{ leg.relationship || 'Employee' }}</td>
                    <td class="text-body-2">{{ legDate(leg) }}</td>
                    <td class="text-body-2">{{ leg.travel_from }}</td>
                    <td class="text-body-2">{{ leg.travel_to }}</td>
                    <td class="text-body-2">
                      <VChip :color="routeStatus(leg) === 'Same Route' ? 'success' : 'warning'" variant="tonal" size="x-small">{{ routeStatus(leg) }}</VChip>
                    </td>
                  </tr>
                  <tr v-if="!directionLegs(direction).length">
                    <td colspan="6" class="text-center text-medium-emphasis py-3">No {{ direction }} legs.</td>
                  </tr>
                </tbody>
              </VTable>
            </VCard>

            <VTable density="comfortable">
              <thead>
                <tr>
                  <th>Direction</th>
                  <th>Reference Route</th>
                  <th>Fare per Traveler</th>
                  <th>Travel Legs</th>
                  <th>Subtotal</th>
                  <th>Operator Reference</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in fareRows" :key="row.direction">
                  <td class="text-body-2">{{ row.direction }}</td>
                  <td class="text-body-2">{{ row.route }}</td>
                  <td class="text-body-2">{{ row.currency }} {{ formatMoney(row.fare) }}</td>
                  <td class="text-body-2">{{ row.legs }}</td>
                  <td class="text-body-2 font-weight-bold">{{ row.currency }} {{ formatMoney(row.subtotal) }}</td>
                  <td class="text-body-2">{{ row.operator }}</td>
                </tr>
                <tr v-if="!fareRows.length">
                  <td colspan="6" class="text-center text-medium-emphasis py-3">No fare reference.</td>
                </tr>
              </tbody>
              <tfoot v-if="fareRows.length">
                <tr class="bg-grey-lighten-4">
                  <td colspan="4" class="text-subtitle-2 font-weight-bold">Total Maximum Ticket Fare Reference</td>
                  <td class="text-subtitle-2 font-weight-bold">{{ fareRows[0].currency }} {{ formatMoney(totalMaxFare) }}</td>
                  <td />
                </tr>
              </tfoot>
            </VTable>
          </VCardText>
        </VCard>
      </VCardText>
      </div>

      <div v-show="tab === 'pdf'" class="bg-grey-lighten-3 overflow-y-auto" style="height: calc(min(90vh, 900px) - 130px)">
        <VProgressLinear v-if="pdfLoading" indeterminate />
        <iframe v-if="pdfUrl" :src="pdfUrl" title="Travel Expense Report PDF" height="750" class="d-block w-100 border-0" />
        <div v-else-if="!pdfLoading && pdfAttempted" class="pa-6 text-center text-medium-emphasis">PDF is not available.</div>
      </div>
    </VCard>
  </VDialog>
</template>
