<script setup>
import { computed, reactive, ref, watch } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  ticket: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  processing: { type: Boolean, default: false },
  canEditSchedule: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'update-schedule', 'view-pdf', 'cancel-request'])

const legHeaders = [
  { title: 'TRAVELER', key: 'name', sortable: false },
  { title: 'DATE', key: 'date', sortable: false },
  { title: 'ROUTE', key: 'route', sortable: false },
  { title: 'ROUTE STATUS', key: 'routeStatus', sortable: false },
]

const formatDate = date => date
  ? new Intl.DateTimeFormat('en-GB', { timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(date))
  : '-'

const formatDateNumeric = date => date
  ? new Intl.DateTimeFormat('id-ID', { timeZone: 'Asia/Jakarta', day: '2-digit', month: '2-digit', year: 'numeric' }).format(new Date(date))
  : '-'

function mapLegs(direction) {
  const legs = props.ticket?.legs?.filter(leg => leg.direction === direction) ?? []

  return legs.map(leg => ({
    id: leg.id,
    name: leg.name,
    role: leg.relationship || 'Employee',
    date: formatDate(leg.reschedule_date ?? leg.travel_date),
    route: `${leg.travel_from} - ${leg.travel_to}`,
    routeStatus: props.ticket?.route_condition ?? 'Same Route',
  }))
}

const departureLegs = computed(() => mapLegs('departure'))
const returnLegs = computed(() => mapLegs('return'))

const departureFare = computed(() => props.ticket?.fare_references?.find(reference => reference.direction === 'departure'))
const returnFare = computed(() => props.ticket?.fare_references?.find(reference => reference.direction === 'return'))

const departureMax = computed(() => Number(departureFare.value?.fare_amount || 0) * departureLegs.value.length)
const returnMax = computed(() => Number(returnFare.value?.fare_amount || 0) * returnLegs.value.length)
const totalMax = computed(() => departureMax.value + returnMax.value)

const formatMoney = row => `${row?.currency ?? 'IDR'} ${new Intl.NumberFormat('id-ID').format(Number(row?.fare_amount || 0))}`
const formatIdr = value => 'IDR ' + new Intl.NumberFormat('id-ID').format(Number(value || 0))

const travelPeriod = computed(() => {
  const dates = [...new Set((props.ticket?.legs ?? [])
    .map(leg => leg.reschedule_date ?? leg.travel_date)
    .filter(Boolean)
    .map(formatDate))]
    .sort()

  if (!dates.length)
    return '-'

  return dates.length > 1 ? `${dates[0]} → ${dates[dates.length - 1]}` : dates[0]
})

const arrangementLabel = computed(() => {
  if (!props.ticket?.ticket_arrangement)
    return '-'

  return props.ticket.ticket_arrangement === 'Direct Payment'
    ? 'Direct Payment - Reimbursable'
    : `Booked by Company - ${props.ticket.route_condition === 'Different Route' ? 'Excess Potential' : 'Not Reimbursable'}`
})

const arrangementNotice = computed(() => {
  if (props.ticket?.ticket_arrangement === 'Direct Payment')
    return { color: 'success', title: 'Direct Payment by Employee', text: 'The employee may only "REIMBURSE" up to the maximum covered budget.' }

  if (!props.ticket)
    return null

  return props.ticket.route_condition === 'Different Route'
    ? { color: 'warning', title: 'Booked by Company', text: 'If the actual company booking exceeds the maximum covered budget, the employee must pay "EXCESS".' }
    : { color: 'grey', title: 'Booked by Company', text: 'The employee cannot claim "REIMBURSE" for this ticket.' }
})

const summaryInformation = computed(() => [
  { label: 'Employee', value: props.ticket?.employee?.name },
  { label: 'Department', value: props.ticket?.department_name },
  { label: 'Office Location', value: 'Batam' },
  { label: 'Travel Period', value: travelPeriod.value },
  { label: 'Route Condition', value: props.ticket?.route_condition },
  { label: 'Eligible Travel Destination', value: props.ticket?.employee?.eligible_destination?.city_name },
  { label: 'Ticket Arrangement', value: arrangementLabel.value, tooltip: arrangementNotice.value ? `${arrangementNotice.value.title} — ${arrangementNotice.value.text}` : '' },
])

const editingSchedule = ref(false)
const scheduleForm = reactive({})

const scheduleHeaders = [
  { title: 'TRAVELER', key: 'name', sortable: false },
  { title: 'ORIGINAL DATE', key: 'originalDate', sortable: false },
  { title: 'RESCHEDULE DATE', key: 'rescheduleDate', sortable: false },
]

function rawDate(value) {
  if (!value)
    return ''

  return new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Jakarta', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date(value))
}

function scheduleRows(direction) {
  return (props.ticket?.legs ?? [])
    .filter(leg => leg.direction === direction)
    .map(leg => ({
      id: leg.id,
      name: leg.name,
      role: leg.relationship || 'Employee',
      originalDate: formatDateNumeric(leg.travel_date),
      rawOriginal: rawDate(leg.travel_date),
      rescheduleDate: formatDateNumeric(leg.reschedule_date ?? leg.travel_date),
      isRescheduled: !!leg.reschedule_date,
      rawReschedule: rawDate(leg.reschedule_date ?? leg.travel_date),
    }))
}

const departureScheduleRows = computed(() => scheduleRows('departure'))
const returnScheduleRows = computed(() => scheduleRows('return'))

const terLastClaimDate = computed(() => {
  const latest = (props.ticket?.legs ?? [])
    .map(leg => rawDate(leg.reschedule_date ?? leg.travel_date))
    .filter(Boolean)
    .sort()
    .at(-1)

  if (!latest)
    return '-'

  const date = new Date(latest)
  date.setDate(date.getDate() + 7)

  return date.toISOString().slice(0, 10)
})

function syncScheduleForm() {
  for (const key of Object.keys(scheduleForm))
    delete scheduleForm[key]

  for (const leg of props.ticket?.legs ?? [])
    scheduleForm[leg.id] = rawDate(leg.reschedule_date ?? leg.travel_date)
}

watch(() => [props.modelValue, props.ticket], ([open]) => {
  if (open) {
    editingSchedule.value = false
    syncScheduleForm()
  }
}, { immediate: true })

function editSchedule() {
  syncScheduleForm()
  editingSchedule.value = true
}

function cancelSchedule() {
  syncScheduleForm()
  editingSchedule.value = false
}

function saveSchedule() {
  if (props.processing)
    return

  const reschedules = (props.ticket?.legs ?? []).map(leg => ({
    leg_id: leg.id,
    reschedule_date: scheduleForm[leg.id] || null,
  }))

  emit('update-schedule', { ticketId: props.ticket?.id, reschedules })
}

watch(() => props.processing, value => {
  if (value)
    editingSchedule.value = false
})

const CANCELLATION_BLOCKED_STATUSES = [
  'cancelled',
  'rejected',
  'awaiting_cancellation_check_hr_admin',
  'awaiting_cancellation_approval_hr_supervisor',
  'awaiting_cancellation_finalization_gmo',
]

const canRequestCancellation = computed(() => !!props.ticket?.is_owner && !CANCELLATION_BLOCKED_STATUSES.includes(props.ticket?.status))

const close = () => emit('update:modelValue', false)
</script>

<template>
  <VDialog :model-value="modelValue" max-width="1100" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <VCardItem class="px-6 py-4">
        <div class="d-flex align-center flex-wrap ga-2">
          <VCardTitle class="pa-0 text-subtitle-1 font-weight-bold">Annual Ticket Summary</VCardTitle>
        </div>
        <template #append>
          <VBtn icon="ri-close-line" size="small" variant="text" @click="close" />
        </template>
      </VCardItem>

      <VDivider />
      <VProgressLinear v-if="loading" indeterminate />

      <VCardText class="px-6 py-5">
        <VCard rounded="lg" elevation="0" border class="mb-5">
          <div class="bg-grey-lighten-4 px-4 py-3 d-flex align-center justify-space-between">
            <div class="text-subtitle-2 font-weight-bold">Annual Ticket Information</div>
            <VBtn color="primary" variant="flat" size="small" :disabled="!ticket?.id" @click="emit('view-pdf', ticket?.id)">PDF</VBtn>
          </div>
          <VDivider />
          <VCardText class="pa-4">
            <VRow dense>
              <VCol v-for="field in summaryInformation" :key="field.label" cols="12" sm="6" md="4" lg="3">
                <div class="rounded-lg border pa-3 h-100">
                  <div class="text-caption text-medium-emphasis mb-1">{{ field.label }}</div>
                  <div class="text-body-2 font-weight-bold text-high-emphasis" style="white-space: pre-wrap; overflow-wrap: anywhere">
                    <VTooltip v-if="field.tooltip" location="top" max-width="320">
                      <template #activator="{ props: tooltipProps }">
                        <span v-bind="tooltipProps" class="cursor-pointer">{{ field.value || '-' }}</span>
                      </template>
                      <span>{{ field.tooltip }}</span>
                    </VTooltip>
                    <template v-else>{{ field.value || '-' }}</template>
                  </div>
                </div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <div class="text-subtitle-1 font-weight-bold mb-2">Travel Legs</div>
        <VSheet color="grey-lighten-5" border rounded="lg" class="pa-4 mb-5">
          <VRow>
            <VCol cols="12" md="6">
              <div class="text-subtitle-2 font-weight-bold mb-2">Departure Legs</div>
              <VDataTable
                :headers="legHeaders"
                :items="departureLegs"
                hide-default-footer
                density="comfortable"
                no-data-text="No departure legs"
                class="bg-white rounded-lg"
              >
                <template #item.name="{ item }">
                  <div class="text-body-2 font-weight-bold">{{ item.name }}</div>
                  <div class="text-caption text-medium-emphasis">{{ item.role }}</div>
                </template>
                <template #item.date="{ item }">
                  <span class="text-caption text-medium-emphasis text-no-wrap">{{ item.date }}</span>
                </template>
                <template #item.route="{ item }">
                  <div class="text-body-2 font-weight-medium text-no-wrap">{{ item.route }}</div>
                </template>
                <template #item.routeStatus="{ item }">
                  <VChip :color="item.routeStatus === 'Same Route' ? 'success' : 'warning'" variant="tonal" size="small" class="font-weight-bold">{{ item.routeStatus }}</VChip>
                </template>
              </VDataTable>
            </VCol>
            <VCol cols="12" md="6">
              <div class="text-subtitle-2 font-weight-bold mb-2">Return Legs</div>
              <VDataTable
                :headers="legHeaders"
                :items="returnLegs"
                hide-default-footer
                density="comfortable"
                no-data-text="No return legs"
                class="bg-white rounded-lg"
              >
                <template #item.name="{ item }">
                  <div class="text-body-2 font-weight-bold">{{ item.name }}</div>
                  <div class="text-caption text-medium-emphasis">{{ item.role }}</div>
                </template>
                <template #item.date="{ item }">
                  <span class="text-caption text-medium-emphasis text-no-wrap">{{ item.date }}</span>
                </template>
                <template #item.route="{ item }">
                  <div class="text-body-2 font-weight-medium text-no-wrap">{{ item.route }}</div>
                </template>
                <template #item.routeStatus="{ item }">
                  <VChip :color="item.routeStatus === 'Same Route' ? 'success' : 'warning'" variant="tonal" size="small" class="font-weight-bold">{{ item.routeStatus }}</VChip>
                </template>
              </VDataTable>
            </VCol>
          </VRow>
        </VSheet>

        <div class="text-subtitle-1 font-weight-bold mb-2">Fare Reference</div>
        <VRow class="mb-2">
          <VCol cols="12" md="6">
            <VSheet color="teal-lighten-5" border rounded="lg" class="pa-4 h-100">
              <div class="text-subtitle-2 font-weight-bold mb-3">Departure</div>
              <div class="d-flex justify-space-between py-2 text-body-2"><span class="text-medium-emphasis">Fare / Traveler</span><span class="font-weight-bold">{{ formatMoney(departureFare) }}</span></div>
              <VDivider />
              <div class="d-flex justify-space-between py-2 text-body-2"><span class="text-medium-emphasis">Operator</span><span class="font-weight-bold">{{ departureFare?.operator_name || '-' }}</span></div>
              <VDivider />
              <div class="d-flex justify-space-between py-2 text-body-2"><span class="text-medium-emphasis">Departure Legs</span><span class="font-weight-bold">{{ departureLegs.length }}</span></div>
              <VDivider />
              <div class="d-flex justify-space-between py-2 text-body-2"><span class="text-medium-emphasis">Departure Maximum</span><span class="font-weight-bold">{{ formatIdr(departureMax) }}</span></div>
            </VSheet>
          </VCol>
          <VCol cols="12" md="6">
            <VSheet color="teal-lighten-5" border rounded="lg" class="pa-4 h-100">
              <div class="text-subtitle-2 font-weight-bold mb-3">Return</div>
              <div class="d-flex justify-space-between py-2 text-body-2"><span class="text-medium-emphasis">Fare / Traveler</span><span class="font-weight-bold">{{ formatMoney(returnFare) }}</span></div>
              <VDivider />
              <div class="d-flex justify-space-between py-2 text-body-2"><span class="text-medium-emphasis">Operator</span><span class="font-weight-bold">{{ returnFare?.operator_name || '-' }}</span></div>
              <VDivider />
              <div class="d-flex justify-space-between py-2 text-body-2"><span class="text-medium-emphasis">Return Legs</span><span class="font-weight-bold">{{ returnLegs.length }}</span></div>
              <VDivider />
              <div class="d-flex justify-space-between py-2 text-body-2"><span class="text-medium-emphasis">Return Maximum</span><span class="font-weight-bold">{{ formatIdr(returnMax) }}</span></div>
            </VSheet>
          </VCol>
        </VRow>
        <VSheet color="green-lighten-5" border rounded="lg" class="d-flex align-center justify-space-between pa-4 mb-5">
          <span class="text-subtitle-1 font-weight-medium">Total Maximum Covered Budget</span>
          <span class="text-h6 font-weight-bold text-success">{{ formatIdr(totalMax) }}</span>
        </VSheet>

        <VCard v-if="canEditSchedule" rounded="lg" elevation="0" border>
          <div class="bg-grey-lighten-4 px-4 py-3 d-flex align-center justify-space-between">
            <div class="text-subtitle-2 font-weight-bold">Travel Schedule Update</div>
            <div class="d-flex ga-2">
              <template v-if="editingSchedule">
                <VBtn size="small" variant="text" :disabled="processing" @click="cancelSchedule">Cancel</VBtn>
                <VBtn size="small" color="primary" variant="flat" :loading="processing" :disabled="processing" @click="saveSchedule">Save Changes</VBtn>
              </template>
              <VBtn v-else size="small" color="primary" variant="tonal" prepend-icon="ri-edit-line" :disabled="processing" @click="editSchedule">Edit</VBtn>
            </div>
          </div>
          <VDivider />
          <VCardText class="pa-4">
            <VRow>
              <VCol cols="12" md="6">
                <div class="text-subtitle-2 font-weight-bold mb-2">Departure Schedule</div>
                <VDataTable
                  :headers="scheduleHeaders"
                  :items="departureScheduleRows"
                  hide-default-footer
                  density="comfortable"
                  no-data-text="No departure schedule"
                  class="bg-grey-lighten-4 rounded-lg"
                >
                  <template #item.name="{ item }">
                    <div class="text-body-2 font-weight-bold">{{ item.name }}</div>
                    <div class="text-caption text-medium-emphasis">{{ item.role }}</div>
                  </template>
                  <template #item.originalDate="{ item }">
                    <span class="text-body-2 text-no-wrap">{{ item.originalDate }}</span>
                  </template>
                  <template #item.rescheduleDate="{ item }">
                    <VTextField
                      v-if="editingSchedule"
                      v-model="scheduleForm[item.id]"
                      type="date"
                      variant="outlined"
                      density="compact"
                      hide-details
                      :disabled="processing"
                    />
                    <span v-else class="text-body-2 text-no-wrap">{{ item.rescheduleDate || '-' }}</span>
                  </template>
                </VDataTable>
              </VCol>
              <VCol cols="12" md="6">
                <div class="text-subtitle-2 font-weight-bold mb-2">Return Schedule</div>
                <VDataTable
                  :headers="scheduleHeaders"
                  :items="returnScheduleRows"
                  hide-default-footer
                  density="comfortable"
                  no-data-text="No return schedule"
                  class="bg-grey-lighten-4 rounded-lg"
                >
                  <template #item.name="{ item }">
                    <div class="text-body-2 font-weight-bold">{{ item.name }}</div>
                    <div class="text-caption text-medium-emphasis">{{ item.role }}</div>
                  </template>
                  <template #item.originalDate="{ item }">
                    <span class="text-body-2 text-no-wrap">{{ item.originalDate }}</span>
                  </template>
                  <template #item.rescheduleDate="{ item }">
                    <VTextField
                      v-if="editingSchedule"
                      v-model="scheduleForm[item.id]"
                      type="date"
                      variant="outlined"
                      density="compact"
                      hide-details
                      :disabled="processing"
                    />
                    <span v-else class="text-body-2 text-no-wrap">{{ item.rescheduleDate || '-' }}</span>
                  </template>
                </VDataTable>
              </VCol>
            </VRow>

            <VAlert type="warning" variant="tonal" density="compact" :icon="false" class="mt-4 text-black">
              <div class="text-body-2">
                <span class="font-weight-bold">TER Last Claim Date: {{ terLastClaimDate }}</span>
                <span> · 7 calendar days after the latest Departure or Return Date</span>
              </div>
            </VAlert>
          </VCardText>
        </VCard>
      </VCardText>

      <VDivider />

      <VCardActions v-if="canRequestCancellation" class="px-6 py-4 justify-end">
        <VBtn
          color="error"
          variant="flat"
          :disabled="processing"
          @click="emit('cancel-request', ticket?.id)"
        >
          Cancellation Request
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
