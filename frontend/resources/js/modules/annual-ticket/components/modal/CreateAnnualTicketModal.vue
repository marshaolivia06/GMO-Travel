<script setup>
import { computed, nextTick, reactive, ref, watch } from 'vue'
import TravelLegInformationDialog from './TravelLegInformationDialog.vue'
import ReviewSubmit from './ReviewSubmit.vue'
import { useToastStore } from '../../../../stores/toast'
import { useConfirm } from '../../../../composables/useConfirm'
import { createAnnualTicket } from '../../services/annualTicketService'

const props = defineProps({
  travelers: { type: Object, required: true },
})

const emit = defineEmits(['close', 'submitted'])

const step = ref(1)
const steps = [{ value: 1, title: 'Select Travelers' }, { value: 2, title: 'Travel Leg Information' }, { value: 3, title: 'Review & Submit' }]
const subtitles = { 1: 'Select who will travel and which direction each traveler joins.', 2: 'Fill in the date and route for each traveler.', 3: 'Review the request before submitting.' }
const employee = reactive({ departure: true, return: true })
const dependents = reactive(props.travelers.dependents.map(item => ({ ...item, selected: false, departure: false, return: false })))
const selectedTravelers = ref([])
const committedSignature = ref('')
const legsDraft = ref(null)
const legsValid = ref(false)
const legsKey = ref(0)

const employeeValid = computed(() => employee.departure || employee.return)
const defaultRoute = computed(() => ({ origin: 'Batam', destination: props.travelers.eligible_destination || '' }))
const isValid = computed(() => employeeValid.value && dependents.every(item => !item.selected || item.departure || item.return))
const selectedCount = computed(() => 1 + dependents.filter(item => item.selected).length)
const signature = computed(() => JSON.stringify([employee.departure, employee.return, dependents.filter(item => item.selected).map(item => [item.id, item.departure, item.return])]))
const step1Done = computed(() => committedSignature.value !== '')

function toggle(item, value) {
  item.selected = value
  item.departure = value
  item.return = value
}

function commitStep1() {
  if (signature.value === committedSignature.value)
    return

  const selected = dependents.filter(item => item.selected).map(item => ({ type: 'dependent', id: item.id, name: item.name, relationship: item.relationship, departure: item.departure, return: item.return }))

  selectedTravelers.value = [{ type: 'employee', id: props.travelers.employee.id, name: props.travelers.employee.name, departure: employee.departure, return: employee.return }, ...selected]
  committedSignature.value = signature.value
  legsDraft.value = null
  legsValid.value = false
  legsKey.value += 1
}

function canOpen(value) {
  if (value === 1)
    return true

  if (!step1Done.value || !isValid.value)
    return false

  return value === 2 || legsValid.value
}

function isDone(value) {
  if (value === 1)
    return step1Done.value

  return value === 2 && legsValid.value
}

function goTo(value) {
  if (value === step.value || !canOpen(value))
    return

  if (step.value === 1 && value > 1)
    commitStep1()

  step.value = canOpen(value) ? value : 2
}

function next() {
  commitStep1()
  step.value = 2
}

function onLegsUpdate(payload) {
  legsDraft.value = payload.legs
  legsValid.value = payload.valid
}

const toast = useToastStore()
const { confirm } = useConfirm()
const submitting = ref(false)

async function submit(payload) {
  if (submitting.value)
    return

  const confirmed = await confirm({ title: 'Submit Annual Ticket?', text: 'Are you sure you want to submit this Annual Ticket request?', color: 'primary' })

  if (!confirmed)
    return

  submitting.value = true

  const travelers = selectedTravelers.value.map(traveler => {
    const dep = (legsDraft.value?.departure || []).find(leg => leg.type === traveler.type && leg.id === traveler.id)
    const ret = (legsDraft.value?.return || []).find(leg => leg.type === traveler.type && leg.id === traveler.id)

    return {
      type: traveler.type,
      traveler_id: traveler.type === 'dependent' ? traveler.id : null,
      name: traveler.name,
      relationship: traveler.type === 'employee' ? 'Employee' : traveler.relationship,
      departure_date: dep?.date || null,
      departure_from: dep?.from || null,
      departure_to: dep?.to || null,
      return_date: ret?.date || null,
      return_from: ret?.from || null,
      return_to: ret?.to || null,
    }
  })

  try {
    await createAnnualTicket({
      quota_year: new Date().getFullYear(),
      route_condition: payload.routeCondition,
      ticket_arrangement: payload.arrangement === 'booked_by_company' ? 'Booked by Company' : 'Direct Payment',
      remarks: payload.remarks,
      travelers,
    }, payload.evidenceFile)

    toast.success('Annual ticket request submitted successfully.')
    emit('submitted')
    emit('close')
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to submit annual ticket request.')
  } finally {
    submitting.value = false
  }
}

const cardRef = ref(null)

watch(step, async () => {
  await nextTick()

  const el = cardRef.value?.$el

  if (!el)
    return

  el.scrollTop = 0
  el.querySelectorAll('.v-card-text').forEach(text => { text.scrollTop = 0 })
})
</script>

<template>
  <VDialog :model-value="true" max-width="1200" persistent scrollable>
    <VCard ref="cardRef">
      <VCardTitle class="d-flex align-center justify-space-between px-6 py-5">
        <div>
          <div class="text-h6 font-weight-bold">Create Annual Ticket</div>
          <div class="text-body-2 text-medium-emphasis">{{ subtitles[step] }}</div>
        </div>
        <DialogCloseBtn @click="emit('close')" />
      </VCardTitle>

      <VDivider />

      <VCardText class="px-6 pt-6 pb-0 flex-grow-0 flex-shrink-0 position-relative" style="z-index: 1">
        <VRow>
          <VCol v-for="item in steps" :key="item.value" cols="12" md="4">
            <VCard :variant="step === item.value ? 'tonal' : 'outlined'" :color="step === item.value ? 'primary' : undefined" class="px-4 py-3 d-flex align-center ga-3" :class="{ 'cursor-pointer': canOpen(item.value), 'opacity-50': !canOpen(item.value) }" @click="goTo(item.value)">
              <VAvatar size="28" :color="step === item.value ? 'primary' : isDone(item.value) ? 'success' : 'secondary'" :variant="step === item.value ? 'flat' : 'tonal'" class="text-body-2 font-weight-bold">
                <VIcon v-if="isDone(item.value) && step !== item.value" icon="ri-check-line" size="16" />
                <span v-else>{{ item.value }}</span>
              </VAvatar>
              <span class="text-body-1 font-weight-bold">{{ item.title }}</span>
            </VCard>
          </VCol>
        </VRow>
      </VCardText>

      <template v-if="step === 1">
        <VCardText class="px-6 py-6">
          <div class="d-flex align-center justify-space-between mb-4">
            <span class="text-h6 font-weight-bold">HR Dependent Master</span>
            <span class="text-body-2 text-medium-emphasis">Employee must participate in at least one direction.</span>
          </div>

          <VAlert v-if="!employeeValid" type="error" variant="tonal" density="compact" class="mb-4">
            Employee must participate in at least one direction.
          </VAlert>

          <VCard variant="tonal" color="primary" class="px-5 py-4 mb-4 d-flex align-center ga-4">
            <VCheckbox :model-value="true" disabled hide-details density="compact" class="flex-grow-0" />
            <div>
              <div class="text-subtitle-1 font-weight-bold">{{ props.travelers.employee.name }}</div>
              <div class="d-flex align-center ga-2 mt-1">
                <VChip size="small" variant="tonal">Employee</VChip>
                <VChip size="small" variant="outlined">Primary Traveler</VChip>
              </div>
            </div>
            <VSpacer />
            <div class="d-flex align-center ga-6">
              <VCheckbox v-model="employee.departure" label="Departure" hide-details density="comfortable" />
              <VCheckbox v-model="employee.return" label="Return" hide-details density="comfortable" />
            </div>
          </VCard>

          <VCard v-for="item in dependents" :key="item.id" :variant="item.selected ? 'tonal' : 'outlined'" :color="item.selected ? 'primary' : undefined" class="px-5 py-4 mb-4 d-flex align-center ga-4" :class="{ 'opacity-60': !item.eligible }">
            <VCheckbox :model-value="item.selected" :disabled="!item.eligible" hide-details density="compact" class="flex-grow-0" @update:model-value="toggle(item, $event)" />
            <div>
              <div class="text-subtitle-1 font-weight-bold">{{ item.name }}</div>
              <div class="d-flex align-center ga-2 mt-1">
                <VChip size="small" variant="tonal">{{ item.relationship }}</VChip>
                <span v-if="!item.eligible" class="text-caption text-medium-emphasis">Age {{ item.age }} - not eligible for Annual Ticket dependent selection.</span>
              </div>
            </div>
            <VSpacer />
            <div class="d-flex align-center ga-6">
              <VCheckbox v-model="item.departure" label="Departure" :disabled="!item.selected" hide-details density="comfortable" />
              <VCheckbox v-model="item.return" label="Return" :disabled="!item.selected" hide-details density="comfortable" />
            </div>
          </VCard>
        </VCardText>

        <VDivider />

        <VCardActions class="px-6 py-4 ga-2">
          <span class="text-body-2 text-medium-emphasis">{{ selectedCount }} traveler(s) selected</span>
          <VSpacer />
          <VBtn variant="outlined" color="secondary" @click="emit('close')">Cancel</VBtn>
          <VBtn variant="flat" color="primary" :disabled="!isValid" @click="next">Continue to Travel Information</VBtn>
        </VCardActions>
      </template>

      <TravelLegInformationDialog v-else-if="step === 2" :key="legsKey" :travelers="selectedTravelers" :route="defaultRoute" :draft="legsDraft" @update="onLegsUpdate" @next="goTo(3)" />

      <ReviewSubmit v-else :travelers="selectedTravelers" :legs="legsDraft" :route="defaultRoute" :submitting="submitting" @back="step = 2" @submit="submit" />
    </VCard>
  </VDialog>
</template>