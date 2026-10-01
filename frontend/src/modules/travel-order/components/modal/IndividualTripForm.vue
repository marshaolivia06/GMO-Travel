<script setup>
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue'
import { useAuthStore } from '../../../../stores/auth'
import { getDepartmentLock, getTravelAdvanceCountries, getTravelAdvanceLimit, getTravelAdvanceLimitByGrade } from '../../services/travelOrderService'
import { getDepartmentOptions } from '../../../master-management/services/departmentService'
import { useConfirm } from '../../../../composables/useConfirm'

const emit = defineEmits(['cancel', 'submit'])
const props = defineProps({
  loading: { type: Boolean, default: false },
  initialData: { type: Object, default: null },
})
const authStore = useAuthStore()
const { confirm } = useConfirm()

const userGrade = computed(() => Number(authStore.user?.grade) || 0)

const generalError = ref('')
const reviewDialog = ref(false)
const confirmInfo = ref(false)
const acknowledgeTer = ref(false)
const departmentLocked = ref(false)
const departmentOptions = ref([])
const countryOptions = ref([])
const loadingDepartmentInfo = ref(true)
const loadingTravelAdvanceLimit = ref(false)
const travelAdvanceLimits = reactive({})
const mealCurrency = ref('')
const pocketCurrency = ref('')
const customCountryInput = ref(null)
const manualCountry = ref(false)
const selectedCountryCurrency = ref('')
const manualCurrency = ref('')

const travelRegions = ['Indonesia', 'Singapore', 'Non Singapore']
const ferryTicketTypes = ['Ferry', 'Airplane']
const ferryArrangements = ['Direct Payment', 'Booked by Company']
const accommodationArrangements = ['Direct Payment', 'Booked by Company', 'Not Required']
const nonSingaporeCurrencies = ['USD', 'EUR', 'MYR', 'JPY']

const errors = reactive({
  employee: '', department: '', travelFrom: '', travelTo: '', departureDate: '', departureTime: '',
  returnDate: '', returnTime: '', purpose: '', remarks: '', travelRegion: '', country: '',
  customCountry: '', pocketMoney: '', mealAllowance: '', ferryTicketType: '', ferryArrangement: '',
  accommodationArrangement: ''
})

const form = reactive({
  employee: authStore.user?.name || '', department: '', departmentId: null, travelFrom: '', travelTo: '',
  departureDate: '', departureTime: '', returnDate: '', returnTime: '', purpose: '', remarks: '',
  travelRegion: '', country: '', customCountry: '', pocketMoney: '', mealAllowance: '',
  ferryTicketType: '', ferryArrangement: '', accommodationArrangement: ''
})
function normalizeDate(value) {
  if (!value) return ''

  const str = String(value)

  if (/^\d{4}-\d{2}-\d{2}$/.test(str)) {
    return str
  }

  const date = new Date(str)

  if (isNaN(date.getTime())) return ''

  const yyyy = date.getFullYear()
  const mm = String(date.getMonth() + 1).padStart(2, '0')
  const dd = String(date.getDate()).padStart(2, '0')

  return `${yyyy}-${mm}-${dd}`
}
function normalizeTime(value) {
  if (!value) return ''

  const str = String(value)

  // Sudah HH:mm
  if (/^\d{2}:\d{2}$/.test(str)) {
    return str
  }

  // HH:mm:ss → HH:mm
  const match = str.match(/^(\d{2}):(\d{2})(?::\d{2})?$/)

  if (!match) return ''

  return `${match[1]}:${match[2]}`
}

function fillInitialData(order) {
  if (!order) return

  const ticket = order.ticket || {}
  const accommodation = order.accommodation || {}
  const advance = order.advance || {}

  Object.assign(form, {
  employee: order.user?.name || authStore.user?.name || '',
  department: order.department?.name || order.department_name || '',
  departmentId: order.department_id || order.department?.id || null,

  travelFrom: order.travel_from || '',
  travelTo: order.travel_to || '',

  departureDate: normalizeDate(order.departure_date),
  departureTime: normalizeTime(order.departure_time),

  returnDate: normalizeDate(order.return_date),
  returnTime: normalizeTime(order.return_time),

  purpose: order.purpose || '',
  remarks: order.remarks || '',

  travelRegion: order.travel_region || '',
  country: order.country || '',
  customCountry: '',

  pocketMoney: advance.pocket_money ?? order.pocket_money ?? '',
  mealAllowance: advance.meal_allowance ?? order.meal_allowance ?? '',

  ferryTicketType:
    ticket.ticket_type ||
    order.ferry_ticket_type ||
    '',

  ferryArrangement:
    ticket.arrangement ||
    order.ferry_arrangement ||
    '',

  accommodationArrangement:
    accommodation.arrangement ||
    order.accommodation_arrangement ||
    '',
})

  if (form.travelRegion === 'Indonesia') {
    mealCurrency.value = 'IDR'
    pocketCurrency.value = 'IDR'
  } else if (form.travelRegion === 'Singapore') {
    mealCurrency.value = 'SGD'
    pocketCurrency.value = 'SGD'
  } else {
    const currency =
      advance.currency ||
      order.currency ||
      ''

    mealCurrency.value = currency
    pocketCurrency.value = currency
    manualCurrency.value = currency
  }

  if (form.travelRegion) {
    loadCountries(form.travelRegion)
  }
}

const hasAdvance = computed(() => travelRegions.includes(form.travelRegion))
const showCountry = computed(() => form.travelRegion === 'Non Singapore')
const isFerryReimbursable = computed(() => form.ferryArrangement === 'Direct Payment')
const isAccommodationReimbursable = computed(() => form.accommodationArrangement === 'Direct Payment')

const advanceCurrencyItems = computed(() => {
  if (form.travelRegion === 'Indonesia') return ['IDR']
  if (form.travelRegion === 'Singapore') return ['SGD']
  return [selectedCountryCurrency.value || manualCurrency.value || 'USD']
})

const travelDays = computed(() => {
  if (!form.departureDate || !form.returnDate) return 0
  const start = new Date(form.departureDate)
  const end = new Date(form.returnDate)
  const diff = end - start
  return isNaN(diff) || diff < 0 ? 0 : Math.floor(diff / 86400000) + 1
})

const mealLimit = computed(() => {
  if (!hasAdvance.value || !mealCurrency.value) return null
  return travelAdvanceLimits[`${form.travelRegion}_${mealCurrency.value}`]?.meal_allowance_limit ?? null
})

const pocketLimit = computed(() => {
  if (!hasAdvance.value || !pocketCurrency.value) return null
  return travelAdvanceLimits[`${form.travelRegion}_${pocketCurrency.value}`]?.pocket_money_limit ?? null
})

const mealMax = computed(() => mealLimit.value === null ? null : Number(mealLimit.value) * travelDays.value)
const pocketMax = computed(() => pocketLimit.value === null ? null : Number(pocketLimit.value) * travelDays.value)

const mealExceeded = computed(() => mealMax.value !== null && form.mealAllowance !== '' && Number(form.mealAllowance) > mealMax.value)
const pocketExceeded = computed(() => pocketMax.value !== null && form.pocketMoney !== '' && Number(form.pocketMoney) > pocketMax.value)

const mealHint = computed(() => mealMax.value !== null && travelDays.value ? `Maximum ${formatNumberByCurrency(mealMax.value, mealCurrency.value)} ${mealCurrency.value} for ${travelDays.value} day(s)` : 'Maximum travel advance based on travel duration.')
const pocketHint = computed(() => pocketMax.value !== null && travelDays.value ? `Maximum ${formatNumberByCurrency(pocketMax.value, pocketCurrency.value)} ${pocketCurrency.value} for ${travelDays.value} day(s)` : 'Maximum travel advance based on travel duration.')

const canSubmit = computed(() => confirmInfo.value && acknowledgeTer.value && !props.loading)

const mealAllowanceDisplay = computed({
  get: () => formatNumberByCurrency(form.mealAllowance, mealCurrency.value),
  set: value => { form.mealAllowance = parseNumberInput(value, mealCurrency.value) },
})

const pocketMoneyDisplay = computed({
  get: () => formatNumberByCurrency(form.pocketMoney, pocketCurrency.value),
  set: value => { form.pocketMoney = parseNumberInput(value, pocketCurrency.value) },
})

const departureDisplay = computed(() => `${formatReviewDate(form.departureDate)} ${form.departureTime || ''}`.trim())
const returnDisplay = computed(() => `${formatReviewDate(form.returnDate)} ${form.returnTime || ''}`.trim())

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

  result = currency === 'EUR'
    ? result.replace(/\./g, '').replace(',', '.')
    : result.replace(/,/g, '')

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
  return isNaN(value.getTime())
    ? '-'
    : value.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

function onDepartmentSelect(id) {
  form.departmentId = id
  form.department = departmentOptions.value.find(item => item.id === id)?.name || ''
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
    generalError.value = error?.response?.data?.message || 'Gagal memuat data department.'
  } finally {
    loadingDepartmentInfo.value = false
  }
}

const masterRegionMap = { Indonesia: 'Domestic', Singapore: 'Singapore', 'Non Singapore': 'Overseas' }

async function loadTravelAdvanceLimit(region, currency) {
  if (!region || !currency) return

  const key = `${region}_${currency}`
  if (travelAdvanceLimits[key]) return

  if (region === 'Indonesia' && !userGrade.value) {
    generalError.value = 'Grade user tidak ditemukan.'
    return
  }

  loadingTravelAdvanceLimit.value = true

  try {
    const response = region === 'Indonesia'
      ? await getTravelAdvanceLimitByGrade(masterRegionMap[region], userGrade.value)
      : await getTravelAdvanceLimit(region, currency)

    travelAdvanceLimits[key] = response.data || response
  } catch (error) {
    generalError.value = error?.response?.data?.message || 'Gagal memuat batas Travel Advance.'
  } finally {
    loadingTravelAdvanceLimit.value = false
  }
}

async function loadCountries(region) {
  countryOptions.value = []
  if (!region) return

  const masterRegion = masterRegionMap[region]
  if (!masterRegion) return

  try {
    const response = await getTravelAdvanceCountries(masterRegion)
    countryOptions.value = [...(response.data || response || []), { country: 'Other', currency: null }]
  } catch (error) {
    console.error('Country API error:', error)
    countryOptions.value = []
  }
}

function selectRegion(region) {
  form.travelRegion = region
  form.country = ''
  form.customCountry = ''
  form.pocketMoney = ''
  form.mealAllowance = ''
  manualCountry.value = false
  manualCurrency.value = ''
  selectedCountryCurrency.value = ''
  mealCurrency.value = region === 'Indonesia' ? 'IDR' : region === 'Singapore' ? 'SGD' : 'USD'
  pocketCurrency.value = mealCurrency.value
  errors.pocketMoney = ''
  errors.mealAllowance = ''
  loadCountries(region)
}

function selectCountry(country) {
  const selected = countryOptions.value.find(item => item.country === country)
  if (!selected) return

  if (country === 'Other') {
    manualCountry.value = true
    form.country = ''
    selectedCountryCurrency.value = ''
    manualCurrency.value = ''
    mealCurrency.value = ''
    pocketCurrency.value = ''
    nextTick(() => customCountryInput.value?.focus())
    return
  }

  manualCountry.value = false
  form.country = selected.country
  selectedCountryCurrency.value = selected.currency || ''
  mealCurrency.value = selected.currency || ''
  pocketCurrency.value = selected.currency || ''
}

function selectManualCurrency(currency) {
  manualCurrency.value = currency
  mealCurrency.value = currency
  pocketCurrency.value = currency
}

function backToCountryDropdown() {
  manualCountry.value = false
  form.country = ''
  manualCurrency.value = ''
  mealCurrency.value = ''
  pocketCurrency.value = ''
}

function clearErrors() {
  Object.keys(errors).forEach(key => { errors[key] = '' })
  generalError.value = ''
}

function validate(isSubmit = true) {
  clearErrors()
  let valid = true

  // Draft boleh disimpan walaupun belum lengkap
  if (!isSubmit) return true

  const required = [
    'employee', 'department', 'travelFrom', 'travelTo', 'departureDate', 'departureTime', 'returnDate', 'returnTime', 'purpose', 'travelRegion', 'ferryTicketType', 'ferryArrangement', 'accommodationArrangement' ]
  required.forEach(key => {
    if (!String(form[key] ?? '').trim()) {
      errors[key] = 'This field is required.'
      valid = false
    }
  })

  if (form.travelRegion === 'Non Singapore' && !String(form.country || '').trim()) {
    errors.country = 'This field is required.'
    valid = false
  }

  if (hasAdvance.value) {
    if (!String(form.pocketMoney || '').trim()) {
      errors.pocketMoney = 'This field is required.'
      valid = false
    }

    if (!String(form.mealAllowance || '').trim()) {
      errors.mealAllowance = 'This field is required.'
      valid = false
    }

    if (mealExceeded.value) {
      errors.mealAllowance = `Maximum allowed is ${mealMax.value} ${mealCurrency.value}.`
      valid = false
    }

    if (pocketExceeded.value) {
      errors.pocketMoney = `Maximum allowed is ${pocketMax.value} ${pocketCurrency.value}.`
      valid = false
    }
  }

  return valid
}
function saveDraft() {
  if (props.loading) return

  let currency = null

  if (form.travelRegion === 'Indonesia') {
    currency = 'IDR'
  } else if (form.travelRegion === 'Singapore') {
    currency = 'SGD'
  } else {
    currency =
      manualCurrency.value ||
      selectedCountryCurrency.value ||
      mealCurrency.value ||
      pocketCurrency.value ||
      null
  }

  emit('submit', {
    ...form,
    status: 'draft',
    currency,
  })
}

function submitForm() {
  if (props.loading || !validate(true)) return

  reviewDialog.value = true
}

function backToEdit() {
  if (!props.loading) reviewDialog.value = false
}

async function confirmSubmit() {
  if (!canSubmit.value) return

  const confirmed = await confirm({
    title: 'Submit Travel Order?',
    text: 'Are you sure you want to submit this Travel Order?',
    color: 'primary',
  })

  if (!confirmed) return

  let currency = null

  if (form.travelRegion === 'Indonesia') currency = 'IDR'
  else if (form.travelRegion === 'Singapore') currency = 'SGD'
  else currency = manualCurrency.value || selectedCountryCurrency.value || mealCurrency.value || pocketCurrency.value || null

  emit('submit', { ...form,   status: 'submitted', currency })
}

function cancel() {
  if (!props.loading) emit('cancel')
}
watch(() => props.initialData, data => {
  if (data) fillInitialData(data)
}, { immediate: true })

watch([() => form.travelRegion, mealCurrency], ([region, currency]) => loadTravelAdvanceLimit(region, currency), { immediate: true })
watch([() => form.travelRegion, pocketCurrency], ([region, currency]) => loadTravelAdvanceLimit(region, currency), { immediate: true })

watch(mealMax, max => {
  if (max !== null && form.mealAllowance !== '' && Number(form.mealAllowance) > max) form.mealAllowance = String(max)
})

watch(pocketMax, max => {
  if (max !== null && form.pocketMoney !== '' && Number(form.pocketMoney) > max) form.pocketMoney = String(max)
})

onMounted(loadDepartmentInfo)
</script>

<template>
  <div>
    <div v-if="!reviewDialog" class="d-flex align-start justify-space-between mb-6">
      <div>
        <div class="text-primary text-body-2 font-weight-bold mb-1">BUSINESS TRIP - INDIVIDUAL</div>
        <div class="text-h5 font-weight-bold mb-1">Travel Order Form</div>
        <div class="text-body-2 text-medium-emphasis">Complete a standalone employee Travel Order.</div>
      </div>
      <VChip variant="tonal" color="primary" size="small">Standalone Request</VChip>
    </div>

    <VCard v-if="!reviewDialog" rounded="lg" elevation="1">
      <VCardItem>
        <VCardTitle class="text-subtitle-1 font-weight-bold">Travel Order Information</VCardTitle>
        <VCardSubtitle>Fields inherited from Group Trip or Annual Leave Request are read-only.</VCardSubtitle>
      </VCardItem>
      <VDivider />

      <VForm @submit.prevent="submitForm">
        <VCardText class="pa-6">
          <VAlert v-if="generalError" type="error" variant="tonal" class="mb-6">{{ generalError }}</VAlert>

          <div class="text-subtitle-2 font-weight-bold mb-4">Employee Information</div>

          <VRow>
            <VCol cols="12" md="6"><VTextField v-model="form.employee" label="Employee *" variant="outlined" density="comfortable" hide-details="auto" disabled /></VCol>
            <VCol cols="12" md="6">
              <VTextField v-if="departmentLocked" v-model="form.department" label="Department *" variant="outlined" density="comfortable" hide-details="auto" disabled :loading="loadingDepartmentInfo" />
              <VSelect v-else :model-value="form.departmentId" label="Department *" :items="departmentOptions" item-title="name" item-value="id" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :loading="loadingDepartmentInfo" :error-messages="errors.department ? [errors.department] : []" @update:model-value="onDepartmentSelect" />
            </VCol>
          </VRow>

          <div class="text-subtitle-2 font-weight-bold mb-4 mt-4">Trip Information</div>

          <VRow>
            <VCol cols="12" md="6"><VTextField v-model="form.travelFrom" label="Travel From *" placeholder="Contoh: Batam" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.travelFrom ? [errors.travelFrom] : []" /></VCol>
            <VCol cols="12" md="6"><VTextField v-model="form.travelTo" label="Travel To *" placeholder="Contoh: Jakarta" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.travelTo ? [errors.travelTo] : []" /></VCol>
            <VCol cols="12" md="6"><VTextField v-model="form.departureDate" label="Departure Date *" type="date" variant="outlined" density="comfortable" prepend-inner-icon="mdi-calendar" hide-details="auto" :disabled="loading" :error-messages="errors.departureDate ? [errors.departureDate] : []" /></VCol>
            <VCol cols="12" md="6"><VTextField v-model="form.departureTime" label="Departure Time *" type="time" variant="outlined" density="comfortable" prepend-inner-icon="mdi-clock-outline" hide-details="auto" :disabled="loading" :error-messages="errors.departureTime ? [errors.departureTime] : []" /></VCol>
            <VCol cols="12" md="6"><VTextField v-model="form.returnDate" label="Return Date *" type="date" variant="outlined" density="comfortable" prepend-inner-icon="mdi-calendar" hide-details="auto" :disabled="loading" :error-messages="errors.returnDate ? [errors.returnDate] : []" /></VCol>
            <VCol cols="12" md="6"><VTextField v-model="form.returnTime" label="Return Time *" type="time" variant="outlined" density="comfortable" prepend-inner-icon="mdi-clock-outline" hide-details="auto" :disabled="loading" :error-messages="errors.returnTime ? [errors.returnTime] : []" /></VCol>
            <VCol cols="12" md="6"><VTextarea v-model="form.purpose" label="Travelling Purpose *" placeholder="Jelaskan tujuan perjalanan dinas ini" variant="outlined" density="comfortable" rows="3" hide-details="auto" :disabled="loading" :error-messages="errors.purpose ? [errors.purpose] : []" /></VCol>
            <VCol cols="12" md="6"><VTextarea v-model="form.remarks" label="Remarks (Optional)" placeholder="Add remarks if needed" variant="outlined" density="comfortable" rows="3" hide-details="auto" :disabled="loading" /></VCol>
          </VRow>

          <div class="text-subtitle-2 font-weight-bold mb-4 mt-4">Travel Region</div>

          <VRow>
            <VCol cols="12" md="3">
              <VSelect v-model="form.travelRegion" :items="travelRegions" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.travelRegion ? [errors.travelRegion] : []" @update:model-value="selectRegion">
                <template #selection="{ item }"><span v-if="form.travelRegion">{{ item.title }}</span><span v-else class="text-medium-emphasis" style="font-size:13px">Select travel region</span></template>
              </VSelect>
            </VCol>

            <VCol v-if="showCountry" cols="12" md="3">
              <VSelect v-if="!manualCountry" v-model="form.country" :items="countryOptions" item-title="country" item-value="country" label="Country" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.country ? [errors.country] : []" @update:model-value="selectCountry">
                <template #selection="{ item }"><span v-if="form.country">{{ item.title }}</span><span v-else class="text-medium-emphasis" style="font-size:13px">Select country</span></template>
              </VSelect>

              <VTextField v-else ref="customCountryInput" v-model="form.country" label="Country" placeholder="Input country" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.country ? [errors.country] : []" />
            </VCol>

            <VCol v-if="manualCountry" cols="12" md="3">
              <VSelect v-model="manualCurrency" :items="['USD', 'EUR']" label="Currency" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" @update:model-value="selectManualCurrency">
                <template #selection="{ item }"><span v-if="manualCurrency">{{ item.title }}</span><span v-else class="text-medium-emphasis" style="font-size:13px">Select currency</span></template>
              </VSelect>
            </VCol>
          </VRow>

          <div class="text-subtitle-2 font-weight-bold mb-4 mt-6">Ticket, Accommodation & Travel Advance</div>

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
                    <VCol cols="6"><VSelect v-model="form.ferryTicketType" :items="ferryTicketTypes" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.ferryTicketType ? [errors.ferryTicketType] : []"><template #selection="{ item }"><span v-if="form.ferryTicketType">{{ item.title }}</span><span v-else class="text-medium-emphasis" style="font-size:13px">Select ticket type</span></template></VSelect></VCol>
                    <VCol cols="6"><VSelect v-model="form.ferryArrangement" :items="ferryArrangements" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.ferryArrangement ? [errors.ferryArrangement] : []"><template #selection="{ item }"><span v-if="form.ferryArrangement">{{ item.title }}</span><span v-else class="text-medium-emphasis" style="font-size:13px">Select arrangement</span></template></VSelect></VCol>
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

                <VCardText><VSelect v-model="form.accommodationArrangement" :items="accommodationArrangements" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.accommodationArrangement ? [errors.accommodationArrangement] : []"><template #selection="{ item }"><span v-if="form.accommodationArrangement">{{ item.title }}</span><span v-else class="text-medium-emphasis" style="font-size:13px">Select arrangement</span></template></VSelect></VCardText>
              </VCard>
            </VCol>

            <VCol cols="12" md="4">
              <VCard rounded="lg" elevation="2" color="grey-lighten-5" class="h-100">
                <VCardItem>
                  <VCardTitle class="text-subtitle-2 font-weight-bold pa-0">Travel Advance <VTooltip text="Travel Advance limit is calculated based on travel duration and applicable policy."><template #activator="{ props }"><VIcon v-bind="props" icon="ri-information-line" size="16" class="ms-1 text-medium-emphasis" /></template></VTooltip></VCardTitle>
                  <div class="text-caption text-medium-emphasis mt-1">Optional advance based on travel region.</div>
                </VCardItem>

                <VCardText>
                  <VAlert v-if="!hasAdvance" type="info" variant="tonal" density="compact">Select a travel region first.</VAlert>

                  <template v-else>
                    <VAlert type="info" variant="tonal" density="compact" class="mb-4">
                      <template v-if="form.travelRegion === 'Indonesia'"><strong>Indonesia policy:</strong> Travel Advance is requested in IDR and follows your grade.</template>
                      <template v-else-if="form.travelRegion === 'Singapore'"><strong>Singapore policy:</strong> Travel Advance is requested in SGD.</template>
                      <template v-else><strong>Non-Singapore policy:</strong> Select USD, EUR, MYR, or JPY for each advance component.</template>
                    </VAlert>

                    <VProgressLinear v-if="loadingTravelAdvanceLimit" indeterminate color="primary" class="mb-4" />

                    <div class="d-flex align-center text-body-2 font-weight-medium mb-2">
                      <span>Meal Allowance</span>
                      <VTooltip location="top">
                        <template #activator="{ props }"><VIcon v-bind="props" icon="ri-information-line" size="15" class="ms-1 text-medium-emphasis" /></template>
                        <span>{{ mealHint }}</span>
                      </VTooltip>
                    </div>

                    <VRow class="align-center">
                      <VCol cols="4" class="pa-1"><VSelect v-model="mealCurrency" :items="advanceCurrencyItems" variant="outlined" density="comfortable" hide-details disabled /></VCol>
                      <VCol cols="8" class="pa-1"><VTextField v-model="mealAllowanceDisplay" type="text" inputmode="decimal" :placeholder="travelDays ? '0' : 'Fill travel dates first'" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading || !travelDays" :error-messages="errors.mealAllowance ? [errors.mealAllowance] : []" class="text-right-input" @beforeinput="blockOverMax($event, mealMax, mealCurrency)" /></VCol>
                    </VRow>

                    <div class="d-flex align-center text-body-2 font-weight-medium mb-2 mt-4">
                      <span>Pocket Money</span>
                      <VTooltip location="top">
                        <template #activator="{ props }"><VIcon v-bind="props" icon="ri-information-line" size="15" class="ms-1 text-medium-emphasis" /></template>
                        <span>{{ pocketHint }}</span>
                      </VTooltip>
                    </div>

                    <VRow class="align-center">
                      <VCol cols="4" class="pa-1"><VSelect v-model="pocketCurrency" :items="advanceCurrencyItems" variant="outlined" density="comfortable" hide-details disabled /></VCol>
                      <VCol cols="8" class="pa-1"><VTextField v-model="pocketMoneyDisplay" type="text" inputmode="decimal" :placeholder="travelDays ? '0' : 'Fill travel dates first'" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading || !travelDays" :error-messages="errors.pocketMoney ? [errors.pocketMoney] : []" class="text-right-input" @beforeinput="blockOverMax($event, pocketMax, pocketCurrency)" /></VCol>
                    </VRow>
                  </template>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>
        </VCardText>

        <VDivider />

        <VCardActions class="pa-4 justify-end">
  <VBtn variant="text" :disabled="loading" @click="cancel">Cancel</VBtn>
  <VBtn type="button" variant="flat" color="success" :loading="props.loading" :disabled="props.loading" @click="saveDraft"> Draft </VBtn>

  <VBtn type="submit" color="primary" variant="flat" :disabled="props.loading || mealExceeded || pocketExceeded">Continue</VBtn>
</VCardActions>
      </VForm>
    </VCard>

    <div v-if="reviewDialog">
      <div class="d-flex align-center justify-space-between mb-1">
        <div class="text-caption font-weight-bold text-medium-emphasis">SUMMARY</div>
        <VChip variant="tonal" color="warning" size="small">Not Submitted</VChip>
      </div>

      <div class="text-h5 font-weight-bold mb-1">Travel Order Summary</div>
      <div class="text-body-2 text-medium-emphasis mb-4">Review your Travel Order information before submission.</div>

      <VCard rounded="lg" elevation="1" class="mb-4">
        <VCardItem class="py-3"><VCardTitle class="text-subtitle-1 font-weight-bold">Request Summary</VCardTitle></VCardItem>
        <VDivider />

        <VCardText class="pa-4">
          <VRow dense>
            <VCol cols="12" md="3"><VCard color="grey-lighten-4" rounded="lg"><VCardText class="pa-3"><div class="text-caption text-medium-emphasis">Request Type</div><VChip variant="outlined" color="primary" size="small" class="mt-1">Business Trip - Individual</VChip></VCardText></VCard></VCol>
            <VCol cols="12" md="3"><VCard color="grey-lighten-4" rounded="lg"><VCardText class="pa-3"><div class="text-caption text-medium-emphasis">Employee</div><div class="text-body-2 font-weight-bold mt-1">{{ form.employee || '-' }}</div></VCardText></VCard></VCol>
            <VCol cols="12" md="3"><VCard color="grey-lighten-4" rounded="lg"><VCardText class="pa-3"><div class="text-caption text-medium-emphasis">Department</div><div class="text-body-2 font-weight-bold mt-1">{{ form.department || '-' }}</div></VCardText></VCard></VCol>
            <VCol cols="12" md="3"><VCard color="grey-lighten-4" rounded="lg"><VCardText class="pa-3"><div class="text-caption text-medium-emphasis">Travel Region</div><div class="text-body-2 font-weight-bold mt-1">{{ form.travelRegion || '-' }}</div></VCardText></VCard></VCol>
            <VCol cols="12" md="4"><VCard color="grey-lighten-4" rounded="lg"><VCardText class="pa-3"><div class="text-caption text-medium-emphasis">Departure</div><div class="text-body-2 font-weight-bold mt-1">{{ departureDisplay }}</div></VCardText></VCard></VCol>
            <VCol cols="12" md="4"><VCard color="grey-lighten-4" rounded="lg"><VCardText class="pa-3"><div class="text-caption text-medium-emphasis">Return</div><div class="text-body-2 font-weight-bold mt-1">{{ returnDisplay }}</div></VCardText></VCard></VCol>
            <VCol cols="12" md="4"><VCard color="grey-lighten-4" rounded="lg"><VCardText class="pa-3"><div class="text-caption text-medium-emphasis">Route</div><div class="text-body-2 font-weight-bold mt-1">{{ form.travelFrom || '-' }} → {{ form.travelTo || '-' }}</div></VCardText></VCard></VCol>
            <VCol cols="12"><VCard color="grey-lighten-4" rounded="lg"><VCardText class="pa-3"><div class="text-caption text-medium-emphasis">Purpose</div><div class="text-body-2 font-weight-bold mt-1">{{ form.purpose || '-' }}</div></VCardText></VCard></VCol>
            <VCol cols="12"><VCard color="grey-lighten-4" rounded="lg"><VCardText class="pa-3"><div class="text-caption text-medium-emphasis">Remarks</div><div class="text-body-2 font-weight-bold mt-1">{{ form.remarks || '—' }}</div></VCardText></VCard></VCol>
          </VRow>
        </VCardText>
      </VCard>

      <VCard rounded="lg" elevation="1" class="mb-4">
        <VCardItem class="py-3"><VCardTitle class="text-subtitle-1 font-weight-bold">Ticket, Accommodation & Travel Advance</VCardTitle></VCardItem>
        <VDivider />

        <VCardText class="pa-4">
          <VRow dense>
            <VCol cols="12" md="4"><VCard color="grey-lighten-4" rounded="lg" class="h-100"><VCardText class="pa-3"><div class="d-flex align-center justify-space-between mb-2"><div class="text-subtitle-2 font-weight-bold">{{ form.ferryTicketType === 'Airplane' ? 'Airplane Ticket' : 'Ferry Ticket' }}</div><VChip variant="tonal" size="small" :color="isFerryReimbursable ? 'success' : 'error'">{{ isFerryReimbursable ? 'Reimbursable' : 'Not Reimbursable' }}</VChip></div><div class="text-body-2 font-weight-bold">{{ form.ferryArrangement || '-' }}</div></VCardText></VCard></VCol>
            <VCol cols="12" md="4"><VCard color="grey-lighten-4" rounded="lg" class="h-100"><VCardText class="pa-3"><div class="d-flex align-center justify-space-between mb-2"><div class="text-subtitle-2 font-weight-bold">Accommodation</div><VChip variant="tonal" size="small" :color="isAccommodationReimbursable ? 'success' : 'error'">{{ isAccommodationReimbursable ? 'Reimbursable' : 'Not Reimbursable' }}</VChip></div><div class="text-body-2 font-weight-bold">{{ form.accommodationArrangement || '-' }}</div></VCardText></VCard></VCol>
            <VCol cols="12" md="4"><VCard color="grey-lighten-4" rounded="lg" class="h-100"><VCardText class="pa-3"><div class="text-subtitle-2 font-weight-bold mb-2">Travel Advance</div><div v-if="!hasAdvance" class="text-body-2 font-weight-bold">Not Applicable</div><template v-else><div class="text-caption text-medium-emphasis">Meal Allowance</div><div class="text-body-2 font-weight-bold mb-2">{{ mealAllowanceDisplay || '-' }} {{ mealCurrency }}</div><div class="text-caption text-medium-emphasis">Pocket Money</div><div class="text-body-2 font-weight-bold">{{ pocketMoneyDisplay || '-' }} {{ pocketCurrency }}</div></template></VCardText></VCard></VCol>
          </VRow>
        </VCardText>
      </VCard>

      <VCard rounded="lg" elevation="1">
        <VCardItem class="py-3"><VCardTitle class="text-subtitle-1 font-weight-bold">Submission Declaration</VCardTitle></VCardItem>
        <VDivider />

        <VCardText class="pa-4">
          <VCard color="grey-lighten-4" rounded="lg" class="mb-3">
            <VCardText class="d-flex align-center ga-2 pa-3"><input v-model="confirmInfo" type="checkbox" class="mr-2"><div class="text-body-2">I confirm that the Travel Order information is correct and ready to enter the approval process.</div></VCardText>
          </VCard>

          <VCard color="amber-lighten-5" rounded="lg">
            <VCardText class="d-flex align-center ga-2 pa-3"><input v-model="acknowledgeTer" type="checkbox" class="mr-2"><div class="text-body-2">I acknowledge and commit to submit the <strong>TER (Travel Expense Report) no later than 7 calendar days</strong> after the Return Date.</div></VCardText>
          </VCard>
        </VCardText>

        <VDivider />

        <VCardActions class="pa-3 justify-end">
          <VBtn variant="text" :disabled="props.loading" @click="backToEdit">Back</VBtn>
          <VBtn color="primary" variant="flat" :loading="props.loading" :disabled="!canSubmit" @click="confirmSubmit">Submit</VBtn>
        </VCardActions>
      </VCard>
    </div>
  </div>
</template>
<style scoped>
.text-right-input :deep(input) {
  text-align: right;
}
</style>
