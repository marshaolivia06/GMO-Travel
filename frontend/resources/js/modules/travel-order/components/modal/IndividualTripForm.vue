<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useAuthStore } from '../../../../stores/auth'
import { getDepartmentLock, getMasterRateLimits, getMasterRegions } from '../../services/travelOrderService'
import { getDepartmentOptions } from '../../../master-management/services/departmentService'
import { useConfirm } from '../../../../composables/useConfirm'
import { getMySection } from '../../../master-management/services/sectionService'

const emit = defineEmits(['cancel', 'submit', 'summary-change'])
const props = defineProps({ loading: { type: Boolean, default: false }, initialData: { type: Object, default: null } })
const authStore = useAuthStore()
const { confirm } = useConfirm()
const sectionName = ref('')
const generalError = ref('')
const reviewDialog = ref(false)
const confirmInfo = ref(false)
const acknowledgeTer = ref(false)
const departmentLocked = ref(false)
const departmentOptions = ref([])
const regionOptions = ref([])
const rateLimits = ref(null)
const loadingDepartmentInfo = ref(true)
const loadingTravelAdvanceLimit = ref(false)
let limitTimer = null

const ferryTicketTypes = ['Ferry', 'Airplane']
const ferryArrangements = ['Direct Payment', 'Booked by Company']
const accommodationArrangements = ['Direct Payment', 'Booked by Company', 'Not Required']

const errors = reactive({
  employee: '', department: '', travelFrom: '', travelTo: '', departureDate: '', departureTime: '', returnDate: '', returnTime: '',
  purpose: '', remarks: '', regionId: '', country: '', pocketMoney: '', mealAllowance: '',
  ferryTicketType: '', ferryArrangement: '', accommodationArrangement: '',
})

const form = reactive({
  employee: authStore.user?.name || '', department: '', departmentId: null, travelFrom: '', travelTo: '',
  departureDate: '', departureTime: '', returnDate: '', returnTime: '', purpose: '', remarks: '',
  regionId: null, country: '', pocketMoney: '', mealAllowance: '',
  ferryTicketType: '', ferryArrangement: '', accommodationArrangement: '',
})

const selectedRegion = computed(() => regionOptions.value.find(item => item.id === form.regionId) || null)
const regionGroup = computed(() => !selectedRegion.value ? '' : selectedRegion.value.code === 'DN' ? 'Domestic' : selectedRegion.value.code === 'LN-SG' ? 'Singapore' : 'Overseas')
const showAdvance = computed(() => ['Singapore', 'Overseas'].includes(regionGroup.value))
const showCountry = computed(() => !!form.regionId)
const lockCountry = computed(() => ['Domestic', 'Singapore'].includes(regionGroup.value))
const advanceCurrency = computed(() => selectedRegion.value?.currency || '')
const advanceCurrencyItems = computed(() => advanceCurrency.value ? [advanceCurrency.value] : [])
const selectedRegionLabel = computed(() => selectedRegion.value?.label || '')

const summaryCountry = computed(() => {
  if (regionGroup.value === 'Domestic') return 'Indonesia'
  if (regionGroup.value === 'Singapore') return 'Singapore'
  return form.country || '-'
})

function normalizeDate(value) {
  if (!value) return ''
  const str = String(value)
  if (/^\d{4}-\d{2}-\d{2}$/.test(str)) return str
  const date = new Date(str)
  if (isNaN(date.getTime())) return ''
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
}

function normalizeTime(value) {
  if (!value) return ''
  const str = String(value)
  if (/^\d{2}:\d{2}$/.test(str)) return str
  const match = str.match(/^(\d{2}):(\d{2})(?::\d{2})?$/)
  return match ? `${match[1]}:${match[2]}` : ''
}

function fillInitialData(order) {
  if (!order) return
  const ticket = order.ticket || {}, accommodation = order.accommodation || {}, advance = order.advance || {}

  Object.assign(form, {
    employee: order.user?.name || authStore.user?.name || '',
    department: order.department?.name || order.department_name || '',
    departmentId: order.department_id || order.department?.id || null,
    travelFrom: order.travel_from || '', travelTo: order.travel_to || '',
    departureDate: normalizeDate(order.departure_date), departureTime: normalizeTime(order.departure_time),
    returnDate: normalizeDate(order.return_date), returnTime: normalizeTime(order.return_time),
    purpose: order.purpose || '', remarks: order.remarks || '', regionId: order.region_id || null,
    country: order.country || '', pocketMoney: advance.pocket_money ?? order.pocket_money ?? '',
    mealAllowance: advance.meal_allowance ?? order.meal_allowance ?? '',
    ferryTicketType: ticket.ticket_type || order.ticket_type || '',
    ferryArrangement: ticket.arrangement || order.ticket_arrangement || '',
    accommodationArrangement: accommodation.arrangement || order.accommodation_arrangement || '',
  })
}

const isFerryReimbursable = computed(() => form.ferryArrangement === 'Direct Payment')
const isAccommodationReimbursable = computed(() => form.accommodationArrangement === 'Direct Payment')

const travelDays = computed(() => {
  if (!form.departureDate || !form.returnDate) return 0
  const start = new Date(form.departureDate), end = new Date(form.returnDate), diff = end - start
  return isNaN(diff) || diff < 0 ? 0 : Math.floor(diff / 86400000) + 1
})

const mealMax = computed(() => rateLimits.value?.meal_allowance_limit ?? null)
const pocketMax = computed(() => rateLimits.value?.pocket_money_limit ?? null)
const mealExceeded = computed(() => mealMax.value !== null && form.mealAllowance !== '' && Number(form.mealAllowance) > mealMax.value)
const pocketExceeded = computed(() => pocketMax.value !== null && form.pocketMoney !== '' && Number(form.pocketMoney) > pocketMax.value)
const mealHint = computed(() => rateLimits.value?.meal_basis === 'at_cost' ? 'At cost: no maximum, the actual amount is claimed through TER.' : mealMax.value !== null && travelDays.value ? `Maximum ${formatNumberByCurrency(mealMax.value, advanceCurrency.value)} ${advanceCurrency.value} for ${travelDays.value} day(s)` : 'Maximum travel advance based on travel duration.')
const pocketHint = computed(() => pocketMax.value === 0 ? 'No daily allowance for this destination.' : pocketMax.value !== null && travelDays.value ? `Maximum ${formatNumberByCurrency(pocketMax.value, advanceCurrency.value)} ${advanceCurrency.value} for ${travelDays.value} day(s)` : 'Maximum travel advance based on travel duration.')
const canSubmit = computed(() => confirmInfo.value && acknowledgeTer.value && !props.loading)

const mealAllowanceDisplay = computed({
  get: () => formatNumberByCurrency(form.mealAllowance, advanceCurrency.value),
  set: value => { form.mealAllowance = parseNumberInput(value, advanceCurrency.value) },
})

const pocketMoneyDisplay = computed({
  get: () => formatNumberByCurrency(form.pocketMoney, advanceCurrency.value),
  set: value => { form.pocketMoney = parseNumberInput(value, advanceCurrency.value) },
})

function formatNumberByCurrency(value, currency) {
  if (value === '' || value == null) return ''
  const number = Number(value)
  if (isNaN(number)) return ''
  if (currency === 'IDR') return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(number)
  if (currency === 'EUR') return new Intl.NumberFormat('de-DE', { maximumFractionDigits: 2 }).format(number)
  if (currency === 'JPY') return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(number)
  return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(number)
}

function parseNumberInput(value, currency) {
  if (!value) return ''
  let result = String(value)
  if (currency === 'IDR') return result.replace(/[^0-9]/g, '')
  result = currency === 'EUR' ? result.replace(/\./g, '').replace(',', '.') : result.replace(/,/g, '')
  return result.replace(/[^0-9.]/g, '')
}

function blockOverMax(event, max, currency) {
  if (max === null || event.inputType?.startsWith('delete')) return
  const text = event.data ?? event.dataTransfer?.getData('text') ?? ''
  if (!text) return
  const el = event.target
  const next = el.value.slice(0, el.selectionStart) + text + el.value.slice(el.selectionEnd)
  if (Number(parseNumberInput(next, currency) || 0) > max) event.preventDefault()
}

function formatReviewDate(date) {
  if (!date) return '-'
  const value = new Date(date)
  return isNaN(value.getTime()) ? '-' : value.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

function onDepartmentSelect(id) {
  form.departmentId = id
  form.department = departmentOptions.value.find(item => item.id === id)?.name || ''
}

async function loadMySection() {
  try {
    const data = await getMySection()
    sectionName.value = data?.name || ''
  } catch (error) {
    sectionName.value = ''
    console.error('Failed to load section:', error)
  }
}

async function loadDepartmentInfo() {
  loadingDepartmentInfo.value = true
  try {
    const lockInfo = await getDepartmentLock()
    if (lockInfo.locked) {
      departmentLocked.value = true
      form.departmentId = lockInfo.department.id
      form.department = lockInfo.department.name
    } else {
      departmentLocked.value = false
      departmentOptions.value = await getDepartmentOptions()
    }
  } catch (error) {
    generalError.value = error?.response?.data?.message || 'Failed to load departments.'
  } finally {
    loadingDepartmentInfo.value = false
  }
}

async function loadRegions() {
  try {
    const response = await getMasterRegions()
    regionOptions.value = (response.data || response || []).map(item => ({ ...item, label: item.travel_area_label || item.name }))
  } catch (error) {
    generalError.value = error?.response?.data?.message || 'Failed to load travel regions.'
  }
}

async function loadRateLimits() {
  rateLimits.value = null
  if (!showAdvance.value || !form.regionId || !travelDays.value) return
  loadingTravelAdvanceLimit.value = true
  try {
    const response = await getMasterRateLimits({ region_id: form.regionId, destination: form.travelTo, departure_date: form.departureDate, return_date: form.returnDate })
    rateLimits.value = response.data || response
  } catch (error) {
    generalError.value = error?.response?.data?.message || 'Failed to load Master Rate limits.'
  } finally {
    loadingTravelAdvanceLimit.value = false
  }
}

function selectRegion(id) {
  form.regionId = id
  form.country = regionGroup.value === 'Domestic' ? 'Indonesia' : regionGroup.value === 'Singapore' ? 'Singapore' : ''
  form.pocketMoney = form.mealAllowance = ''
  errors.pocketMoney = errors.mealAllowance = errors.country = ''
}

function clearErrors() {
  Object.keys(errors).forEach(key => { errors[key] = '' })
  generalError.value = ''
}

function validate(isSubmit = true) {
  clearErrors()
  let valid = true
  if (!isSubmit) return true

  const isDivHead = authStore.user?.roles?.includes('Div Head')
  const required = ['employee', 'travelFrom', 'travelTo', 'departureDate', 'departureTime', 'returnDate', 'returnTime', 'purpose', 'regionId', 'ferryTicketType', 'ferryArrangement', 'accommodationArrangement']

  if (!isDivHead) required.push('department')

  required.forEach(key => {
    if (!String(form[key] ?? '').trim()) {
      errors[key] = 'This field is required.'
      valid = false
    }
  })

  if (regionGroup.value === 'Overseas' && !String(form.country || '').trim()) {
    errors.country = 'This field is required.'
    valid = false
  }

  if (showAdvance.value) {
    if (!String(form.pocketMoney || '').trim()) {
      errors.pocketMoney = 'This field is required.'
      valid = false
    }

    if (!String(form.mealAllowance || '').trim()) {
      errors.mealAllowance = 'This field is required.'
      valid = false
    }

    if (mealExceeded.value) {
      errors.mealAllowance = `Maximum allowed is ${mealMax.value} ${advanceCurrency.value}.`
      valid = false
    }

    if (pocketExceeded.value) {
      errors.pocketMoney = `Maximum allowed is ${pocketMax.value} ${advanceCurrency.value}.`
      valid = false
    }
  }

  return valid
}

function getCurrency() {
  return advanceCurrency.value || null
}

function saveDraft() {
  if (props.loading) return
  emit('submit', { ...form, request_type: 'business_trip', trip_type: 'individual', status: 'draft', currency: getCurrency() })
}

function submitForm() {
  if (props.loading || !validate(true)) return
  reviewDialog.value = true
  emit('summary-change', true)
}

function backToEdit() {
  if (props.loading) return
  reviewDialog.value = false
  emit('summary-change', false)
}

function handleSummaryBack() {
  backToEdit()
}

defineExpose({ handleSummaryBack })

async function confirmSubmit() {
  if (!canSubmit.value) return
  const confirmed = await confirm({ title: 'Submit Travel Order?', text: 'Are you sure you want to submit this Travel Order?', color: 'primary' })
  if (!confirmed) return
  emit('submit', { ...form, request_type: 'business_trip', trip_type: 'individual', status: 'submitted', currency: getCurrency() })
}

function cancel() {
  if (!props.loading) emit('cancel')
}

watch(() => props.initialData, data => {
  if (data) fillInitialData(data)
}, { immediate: true })

watch([() => form.regionId, () => form.travelTo, () => form.departureDate, () => form.returnDate, showAdvance], () => {
  clearTimeout(limitTimer)
  limitTimer = setTimeout(loadRateLimits, 400)
})

watch(mealMax, max => {
  if (max !== null && form.mealAllowance !== '' && Number(form.mealAllowance) > max) form.mealAllowance = String(max)
})

watch(pocketMax, max => {
  if (max === 0) form.pocketMoney = '0'
  else if (max !== null && form.pocketMoney !== '' && Number(form.pocketMoney) > max) form.pocketMoney = String(max)
})

onMounted(() => {
  loadDepartmentInfo()
  loadMySection()
  loadRegions()
})
</script>

<template>
  <div>
    <div v-if="!reviewDialog" class="text-primary text-body-2 font-weight-bold mb-4">BUSINESS TRIP - INDIVIDUAL</div>

    <VCard v-if="!reviewDialog" rounded="lg" elevation="1">
      <VForm @submit.prevent="submitForm">
        <VCardText class="pa-6">
  <VAlert v-if="generalError" type="error" variant="tonal" class="mb-6">{{ generalError }}</VAlert>

  <div class="text-subtitle-2 font-weight-bold mb-4">Trip Information</div>
  <VRow>
  <VCol cols="12" md="4"><div class="text-caption mb-1">1. Travel From</div><VTextField v-model="form.travelFrom" placeholder="Enter Travel From" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.travelFrom ? [errors.travelFrom] : []" /></VCol>
  <VCol cols="12" md="4"><div class="text-caption mb-1">2. Travel To</div><VTextField v-model="form.travelTo" placeholder="Enter Travel To" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.travelTo ? [errors.travelTo] : []" /></VCol>
  <VCol cols="12" md="4"><div class="text-caption mb-1">3. Departure Date</div><VTextField v-model="form.departureDate" type="date" variant="outlined" density="comfortable" prepend-inner-icon="mdi-calendar" hide-details="auto" :disabled="loading" :error-messages="errors.departureDate ? [errors.departureDate] : []" /></VCol>
  <VCol cols="12" md="4"><div class="text-caption mb-1">4. Departure Time</div><VTextField v-model="form.departureTime" type="time" variant="outlined" density="comfortable" prepend-inner-icon="mdi-clock-outline" hide-details="auto" :disabled="loading" :error-messages="errors.departureTime ? [errors.departureTime] : []" /></VCol>
  <VCol cols="12" md="4"><div class="text-caption mb-1">5. Return Date</div><VTextField v-model="form.returnDate" type="date" variant="outlined" density="comfortable" prepend-inner-icon="mdi-calendar" hide-details="auto" :disabled="loading" :error-messages="errors.returnDate ? [errors.returnDate] : []" /></VCol>
  <VCol cols="12" md="4"><div class="text-caption mb-1">6. Return Time</div><VTextField v-model="form.returnTime" type="time" variant="outlined" density="comfortable" prepend-inner-icon="mdi-clock-outline" hide-details="auto" :disabled="loading" :error-messages="errors.returnTime ? [errors.returnTime] : []" /></VCol>
  <VCol cols="12" md="6"><div class="text-caption mb-1">7. Travelling Purpose</div><VTextarea v-model="form.purpose" placeholder="Enter Travelling Purpose" variant="outlined" density="comfortable" rows="3" hide-details="auto" :disabled="loading" :error-messages="errors.purpose ? [errors.purpose] : []" /></VCol>
  <VCol cols="12" md="6"><div class="text-caption mb-1">8. Remarks </div><VTextarea v-model="form.remarks" placeholder="Enter Remarks (Optional)" variant="outlined" density="comfortable" rows="3" hide-details="auto" :disabled="loading" /></VCol>
</VRow>

  <VRow>
    <VCol cols="12" md="3">
      <div class="text-caption mb-1">9. Travel Region</div>
      <VSelect :model-value="form.regionId" :items="regionOptions" item-title="label" item-value="id" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.regionId ? [errors.regionId] : []" @update:model-value="selectRegion">
        <template #selection="{ item }"><span v-if="form.regionId">{{ item.title }}</span><span v-else class="text-medium-emphasis" style="font-size:13px">Select travel region</span></template>
      </VSelect>
    </VCol>

    <VCol v-if="showCountry" cols="12" md="3">
      <div class="text-caption mb-1">Country</div>
      <VTextField v-model="form.country" placeholder="Input country" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading || lockCountry" :error-messages="errors.country ? [errors.country] : []" />
    </VCol>
  </VRow>

  <VRow class="mt-2">
  <VCol cols="12">
    <div class="text-caption mb-1">10. Ticket, Accommodation & Travel Advance</div>
  </VCol>
</VRow>
<VRow>
  <VCol cols="12" md="4">
    <VCard rounded="lg" elevation="2" color="grey-lighten-5" class="h-100">
      <VCardItem>
        <template #append><VChip variant="tonal" :color="isFerryReimbursable ? 'success' : 'error'" size="small">{{ isFerryReimbursable ? 'Reimbursable' : 'Not Reimbursable' }}</VChip></template>
        <VCardTitle class="text-subtitle-2 font-weight-bold pa-0">Ferry Ticket <VTooltip text="Employee pays first and claims eligible expense through TER."><template #activator="{ props }"><VIcon v-bind="props" icon="ri-information-line" size="16" class="ms-1 text-medium-emphasis" /></template></VTooltip></VCardTitle>
        <div class="text-caption text-medium-emphasis mt-1">Select transport type, arranger, and payer.</div>
      </VCardItem>
      <VCardText>
        <VRow>
          <VCol cols="6"><div class="text-caption mb-1">Ticket Type</div><VSelect v-model="form.ferryTicketType" :items="ferryTicketTypes" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.ferryTicketType ? [errors.ferryTicketType] : []"><template #selection="{ item }"><span v-if="form.ferryTicketType">{{ item.title }}</span><span v-else class="text-medium-emphasis" style="font-size:13px">Select ticket type</span></template></VSelect></VCol>
          <VCol cols="6"><div class="text-caption mb-1">Arrangement</div><VSelect v-model="form.ferryArrangement" :items="ferryArrangements" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.ferryArrangement ? [errors.ferryArrangement] : []"><template #selection="{ item }"><span v-if="form.ferryArrangement">{{ item.title }}</span><span v-else class="text-medium-emphasis" style="font-size:13px">Select arrangement</span></template></VSelect></VCol>
        </VRow>
      </VCardText>
    </VCard>
  </VCol>

  <VCol cols="12" md="4">
    <VCard rounded="lg" elevation="2" color="grey-lighten-5" class="h-100">
      <VCardItem>
        <template #append><VChip variant="tonal" :color="isAccommodationReimbursable ? 'success' : 'error'" size="small">{{ isAccommodationReimbursable ? 'Reimbursable' : 'Not Reimbursable' }}</VChip></template>
        <VCardTitle class="text-subtitle-2 font-weight-bold pa-0">Accommodation <VTooltip text="Employee pays first and claims eligible expense through TER."><template #activator="{ props }"><VIcon v-bind="props" icon="ri-information-line" size="16" class="ms-1 text-medium-emphasis" /></template></VTooltip></VCardTitle>
        <div class="text-caption text-medium-emphasis mt-1">Select accommodation arrangement.</div>
      </VCardItem>
      <VCardText>
        <div class="text-caption mb-1">Arrangement</div>
        <VSelect v-model="form.accommodationArrangement" :items="accommodationArrangements" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.accommodationArrangement ? [errors.accommodationArrangement] : []"><template #selection="{ item }"><span v-if="form.accommodationArrangement">{{ item.title }}</span><span v-else class="text-medium-emphasis" style="font-size:13px">Select arrangement</span></template></VSelect>
      </VCardText>
    </VCard>
  </VCol>

  <VCol v-if="showAdvance" cols="12" md="4">
    <VCard rounded="lg" elevation="2" color="grey-lighten-5" class="h-100">
      <VCardItem>
        <VCardTitle class="text-subtitle-2 font-weight-bold pa-0">Travel Advance <VTooltip text="Travel Advance limit is calculated based on travel duration and applicable policy."><template #activator="{ props }"><VIcon v-bind="props" icon="ri-information-line" size="16" class="ms-1 text-medium-emphasis" /></template></VTooltip></VCardTitle>
        <div class="text-caption text-medium-emphasis mt-1">Optional advance based on travel region.</div>
      </VCardItem>
      <VCardText>
        <VAlert type="info" variant="tonal" density="compact" class="mb-4"><strong>{{ selectedRegionLabel }} policy:</strong> Travel Advance is requested in {{ advanceCurrency }} and follows your grade.</VAlert>

        <VProgressLinear v-if="loadingTravelAdvanceLimit" indeterminate color="primary" class="mb-4" />

        <div class="d-flex align-center text-caption mb-1">
          <span>Meal Allowance</span>
          <VTooltip location="top"><template #activator="{ props }"><VIcon v-bind="props" icon="ri-information-line" size="15" class="ms-1 text-medium-emphasis" /></template><span>{{ mealHint }}</span></VTooltip>
        </div>
        <VRow class="align-center">
          <VCol cols="4" class="pa-1"><VSelect :model-value="advanceCurrency" :items="advanceCurrencyItems" variant="outlined" density="comfortable" hide-details disabled /></VCol>
          <VCol cols="8" class="pa-1"><VTextField v-model="mealAllowanceDisplay" type="text" inputmode="decimal" :placeholder="travelDays ? '0' : 'Fill travel dates first'" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading || !travelDays" :error-messages="errors.mealAllowance ? [errors.mealAllowance] : []" class="text-right-input" @beforeinput="blockOverMax($event, mealMax, advanceCurrency)" /></VCol>
        </VRow>

        <div class="d-flex align-center text-caption mb-1 mt-4">
          <span>Pocket Money</span>
          <VTooltip location="top"><template #activator="{ props }"><VIcon v-bind="props" icon="ri-information-line" size="15" class="ms-1 text-medium-emphasis" /></template><span>{{ pocketHint }}</span></VTooltip>
        </div>
        <VRow class="align-center">
          <VCol cols="4" class="pa-1"><VSelect :model-value="advanceCurrency" :items="advanceCurrencyItems" variant="outlined" density="comfortable" hide-details disabled /></VCol>
          <VCol cols="8" class="pa-1"><VTextField v-model="pocketMoneyDisplay" type="text" inputmode="decimal" :placeholder="travelDays ? '0' : 'Fill travel dates first'" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading || !travelDays || pocketMax === 0" :error-messages="errors.pocketMoney ? [errors.pocketMoney] : []" class="text-right-input" @beforeinput="blockOverMax($event, pocketMax, advanceCurrency)" /></VCol>
        </VRow>
      </VCardText>
    </VCard>
  </VCol>
</VRow>
</VCardText>

        <VDivider />
        <VCardActions class="pa-4 justify-end">
          <VBtn variant="text" :disabled="loading" @click="cancel">Cancel</VBtn>
          <VBtn type="button" variant="flat" color="primary" :loading="props.loading" :disabled="props.loading" @click="saveDraft">Draft</VBtn>
          <VBtn type="submit" color="primary" variant="flat" :disabled="props.loading || mealExceeded || pocketExceeded">Continue</VBtn>
        </VCardActions>
      </VForm>
    </VCard>

<div v-if="reviewDialog">
  <VCard rounded="lg" elevation="1" class="mb-4">
    <VCardItem class="bg-grey-lighten-4 py-3"><VCardTitle class="text-subtitle-1 font-weight-bold">Trip Information</VCardTitle></VCardItem>
    <VDivider />
  <VCardText class="pa-4">
    <VRow dense>
      <VCol v-for="i in [
  { l: 'Request Type', chip: 'Business Trip - Individual' }, { l: 'Section', v: sectionName }, { l: 'Department', v: form.department }, { l: 'Travel Region', v: selectedRegionLabel },
  { l: 'Travel From', v: form.travelFrom }, { l: 'Travel To', v: form.travelTo }, { l: 'Country', v: summaryCountry }, { l: 'Departure Date', v: formatReviewDate(form.departureDate) },
  { l: 'Departure Time', v: form.departureTime }, { l: 'Return Date', v: formatReviewDate(form.returnDate) }, { l: 'Return Time', v: form.returnTime }, { l: 'Travelling Purpose', v: form.purpose },
  { l: 'Remarks', v: form.remarks, cols: 12 }
]" :key="i.l" cols="12" sm="6" :md="i.cols || 3">
        <VCard variant="outlined" border="thin" color="grey-lighten-2" rounded="lg" class="h-100">
          <VCardText class="pa-3 text-high-emphasis">
            <div class="text-caption text-medium-emphasis">{{ i.l }}</div>
            <VChip v-if="i.chip" variant="outlined" color="primary" size="small" class="mt-1">{{ i.chip }}</VChip>
            <div v-else class="text-body-2 font-weight-bold mt-1">{{ i.v || '-' }}</div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </VCardText>
</VCard>

<VCard rounded="lg" elevation="1" class="mb-4">
  <VCardItem class="bg-grey-lighten-4 py-3"><VCardTitle class="text-subtitle-1 font-weight-bold">Ticket, Accommodation & Travel Advance</VCardTitle></VCardItem>
  <VDivider />
  <VCardText class="pa-4">
    <VRow dense>
      <VCol v-for="i in [
        { t: form.ferryTicketType === 'Airplane' ? 'Airplane Ticket' : 'Ferry Ticket', a: form.ferryArrangement, ok: isFerryReimbursable },
        { t: 'Accommodation', a: form.accommodationArrangement, ok: isAccommodationReimbursable }
      ]" :key="i.t" cols="12" md="4">
        <VCard variant="outlined" border="thin" color="grey-lighten-2" rounded="lg" class="h-100">
          <VCardText class="pa-3 text-high-emphasis">
            <div class="d-flex align-center justify-space-between">
              <div class="text-caption text-medium-emphasis">{{ i.t }}</div>
              <VChip variant="tonal" size="small" :color="i.ok ? 'success' : 'error'">{{ i.ok ? 'Reimbursable' : 'Not Reimbursable' }}</VChip>
            </div>
            <div class="text-subtitle-1 font-weight-bold mt-1">{{ i.a || '-' }}</div>
            <div class="text-caption text-medium-emphasis">{{ i.ok ? 'Reimbursable subject to policy.' : 'Not reimbursable.' }}</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol v-if="showAdvance" cols="12" md="4">
        <VCard variant="outlined" border="thin" color="grey-lighten-2" rounded="lg" class="h-100">
          <VCardText class="pa-3 text-high-emphasis">
            <div class="text-caption text-medium-emphasis">Travel Advance</div>
            <div class="text-body-2 font-weight-bold mt-1">Meal: {{ mealAllowanceDisplay || '-' }} {{ advanceCurrency }}</div>
            <div class="text-body-2 font-weight-bold">Pocket: {{ pocketMoneyDisplay || '-' }} {{ advanceCurrency }}</div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </VCardText>
</VCard>
      <VCard rounded="lg" elevation="1">
        <VCardItem class="py-3"><VCardTitle class="text-subtitle-1 font-weight-bold">Submission Declaration</VCardTitle></VCardItem>
        <VDivider />
        <VCardText class="pa-4">
          <VCard color="grey-lighten-4" rounded="lg" class="mb-3"><VCardText class="d-flex align-center ga-2 pa-3"><input v-model="confirmInfo" type="checkbox" class="mr-2"><div class="text-body-2">I confirm that the Travel Order information is correct and ready to enter the approval process.</div></VCardText></VCard>
          <VCard color="amber-lighten-5" rounded="lg"><VCardText class="d-flex align-center ga-2 pa-3"><input v-model="acknowledgeTer" type="checkbox" class="mr-2"><div class="text-body-2">I acknowledge and commit to submit the <strong>TER (Travel Expense Report) no later than 7 calendar days</strong> after the Return Date.</div></VCardText></VCard>
        </VCardText>
        <VDivider />
        <VCardActions class="pa-3 justify-end">
  <VBtn variant="text" :disabled="props.loading" @click="backToEdit">Back</VBtn>
  <VBtn color="primary" variant="flat" :loading="props.loading" @click="saveDraft">Draft</VBtn>
  <VBtn color="primary" variant="flat" :loading="props.loading" :disabled="!canSubmit" @click="confirmSubmit">Submit</VBtn>
</VCardActions>
      </VCard>
    </div>
  </div>
</template>

<style scoped>
.text-right-input :deep(input) { text-align: right; }
</style>