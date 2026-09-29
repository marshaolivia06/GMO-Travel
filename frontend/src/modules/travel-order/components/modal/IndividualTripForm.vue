<script setup>
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue'
import { useAuthStore } from '../../../../stores/auth'
import { getDepartmentLock, getTravelAdvanceCountries, getTravelAdvanceLimit, getTravelAdvanceLimitByGrade } from '../../services/travelOrderService'
import { getDepartmentOptions } from '../../../master-management/services/departmentService'
import { useConfirm } from '../../../../composables/useConfirm'

const emit = defineEmits(['cancel', 'submit'])
const authStore = useAuthStore()
const userGrade = computed(() => Number(authStore.user?.grade) || 0) // TODO: sesuaikan nama field grade di authStore.user
const props = defineProps({ loading: { type: Boolean, default: false } })

const generalError = ref('')
const reviewDialog = ref(false)
const confirmInfo = ref(false)
const acknowledgeTer = ref(false)
const departmentLocked = ref(false)
const departmentOptions = ref([])
const loadingDepartmentInfo = ref(true)
const loadingTravelAdvanceLimit = ref(false)
const travelAdvanceLimits = reactive({})
const mealCurrency = ref('')
const pocketCurrency = ref('')
const customCountryInput = ref(null)
const { confirm } = useConfirm()

const travelRegions = ['Indonesia', 'Singapore', 'Non Singapore']
const ferryTicketTypes = ['Ferry', 'Airplane']
const ferryArrangements = ['Direct Payment', 'Booked by Company']
const accommodationArrangements = ['Direct Payment', 'Booked by Company', 'Not Required']
const nonSingaporeCurrencies = ['USD', 'EUR', 'MYR', 'JPY']

const errors = reactive({ employee: '', department: '', travelFrom: '', travelTo: '', departureDate: '', departureTime: '', returnDate: '', returnTime: '', purpose: '', remarks: '', travelRegion: '', country: '', customCountry: '', pocketMoney: '', mealAllowance: '', ferryTicketType: '', ferryArrangement: '', accommodationArrangement: '' })

const form = reactive({ employee: authStore.user?.name || '', department: '', departmentId: null, travelFrom: '', travelTo: '', departureDate: '', departureTime: '', returnDate: '', returnTime: '', purpose: '', remarks: '', travelRegion: '', country: '', customCountry: '', pocketMoney: '', mealAllowance: '', ferryTicketType: '', ferryArrangement: '', accommodationArrangement: '' })

const hasAdvance = computed(() => ['Indonesia', 'Singapore', 'Non Singapore'].includes(form.travelRegion))
const advanceCurrencyItems = computed(() => form.travelRegion === 'Indonesia' ? ['IDR'] : form.travelRegion === 'Singapore' ? ['SGD'] : [selectedCountryCurrency.value || manualCurrency.value || 'USD'])
const showCountry = computed(() => form.travelRegion === 'Non Singapore')
const isFerryReimbursable = computed(() => form.ferryArrangement === 'Direct Payment')
const isAccommodationReimbursable = computed(() => form.accommodationArrangement === 'Direct Payment')

// Batas per hari dari master advance
const mealLimit = computed(() => (!hasAdvance.value || !mealCurrency.value) ? null : travelAdvanceLimits[`${form.travelRegion}_${mealCurrency.value}`]?.meal_allowance_limit ?? null)
const pocketLimit = computed(() => (!hasAdvance.value || !pocketCurrency.value) ? null : travelAdvanceLimits[`${form.travelRegion}_${pocketCurrency.value}`]?.pocket_money_limit ?? null)

// Jumlah hari perjalanan (tanggal berangkat & pulang dihitung, jadi 1 Okt - 2 Okt = 2 hari)
const travelDays = computed(() => {
  if (!form.departureDate || !form.returnDate) return 0
  const diff = new Date(form.returnDate) - new Date(form.departureDate)
  return isNaN(diff) || diff < 0 ? 0 : Math.floor(diff / 86400000) + 1
})

// Batas total = limit per hari x jumlah hari
const mealMax = computed(() => mealLimit.value === null ? null : Number(mealLimit.value) * travelDays.value)
const pocketMax = computed(() => pocketLimit.value === null ? null : Number(pocketLimit.value) * travelDays.value)

const mealExceeded = computed(() => mealMax.value !== null && form.mealAllowance !== '' && Number(form.mealAllowance) > mealMax.value)
const pocketExceeded = computed(() => pocketMax.value !== null && form.pocketMoney !== '' && Number(form.pocketMoney) > pocketMax.value)
const mealHint = computed(() => mealMax.value !== null && travelDays.value ? `Max ${formatNumberByCurrency(mealMax.value, mealCurrency.value)} ${mealCurrency.value} (${travelDays.value} days)` : '')
const pocketHint = computed(() => pocketMax.value !== null && travelDays.value ? `Max ${formatNumberByCurrency(pocketMax.value, pocketCurrency.value)} ${pocketCurrency.value} (${travelDays.value} days)` : '')
const canSubmit = computed(() => confirmInfo.value && acknowledgeTer.value && !props.loading)

const mealAllowanceDisplay = computed({
  get: () => formatNumberByCurrency(form.mealAllowance, mealCurrency.value),
  set: v => { form.mealAllowance = parseNumberInput(v, mealCurrency.value) },
})

const pocketMoneyDisplay = computed({
  get: () => formatNumberByCurrency(form.pocketMoney, pocketCurrency.value),
  set: v => { form.pocketMoney = parseNumberInput(v, pocketCurrency.value) },
})

const travelAdvanceCurrencyOptions = computed(() => {
  if (manualCountry.value) {
    return [
      { title: 'USD', value: 'USD', disabled: false },
      { title: 'EUR', value: 'EUR', disabled: false },
      { title: 'MYR', value: 'MYR', disabled: true },
      { title: 'JPY', value: 'JPY', disabled: true },
    ]
  }

  return nonSingaporeCurrencies
})

const departureDisplay = computed(() => `${formatReviewDate(form.departureDate)} ${form.departureTime || ''}`.trim())
const returnDisplay = computed(() => `${formatReviewDate(form.returnDate)} ${form.returnTime || ''}`.trim())

function onDepartmentSelect(id) {
  form.departmentId = id
  form.department = departmentOptions.value.find(d => d.id === id)?.name || ''
}

function formatNumberByCurrency(rawValue, currency) {
  if (rawValue === '' || rawValue == null) return ''
  const num = Number(rawValue)
  if (isNaN(num)) return ''
  if (currency === 'IDR') return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(num)
  if (currency === 'EUR') return new Intl.NumberFormat('de-DE', { maximumFractionDigits: 2 }).format(num)
  if (currency === 'JPY') return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(num)
  return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(num)
}

function parseNumberInput(val, currency) {
  if (!val) return ''
  if (currency === 'IDR') return String(val).replace(/[^0-9]/g, '')
  let c = String(val)
  c = currency === 'EUR' ? c.replace(/\./g, '').replace(',', '.') : c.replace(/,/g, '')
  return c.replace(/[^0-9.]/g, '')
}

// Blok ketikan/paste sebelum masuk ke input kalau hasilnya melebihi batas (tanpa pesan warning)
function blockOverMax(e, max, currency) {
  if (max === null || e.inputType?.startsWith('delete')) return
  const text = e.data ?? e.dataTransfer?.getData('text') ?? ''
  if (!text) return
  const el = e.target
  const next = el.value.slice(0, el.selectionStart) + text + el.value.slice(el.selectionEnd)
  if (Number(parseNumberInput(next, currency) || 0) > max) e.preventDefault()
}

function formatReviewDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return isNaN(d.getTime()) ? '-' : d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
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
  } catch (err) {
    generalError.value = err?.response?.data?.message || 'Gagal memuat data department.'
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
    const res = region === 'Indonesia'
      ? await getTravelAdvanceLimitByGrade(masterRegionMap[region], userGrade.value)
      : await getTravelAdvanceLimit(region, currency)
    travelAdvanceLimits[key] = res.data || res
  } catch (err) {
    generalError.value = err?.response?.data?.message || 'Gagal memuat batas Travel Advance.'
  } finally {
    loadingTravelAdvanceLimit.value = false
  }
}

function selectRegion(region) {
  form.travelRegion = region
  loadCountries(region)
  form.country = ''
  form.customCountry = ''
  manualCountry.value = false
  manualCurrency.value = ''
  selectedCountryCurrency.value = ''

  form.pocketMoney = ''
  form.mealAllowance = ''
  errors.pocketMoney = ''
  errors.mealAllowance = ''

  if (region === 'Indonesia') {
    mealCurrency.value = 'IDR'
    pocketCurrency.value = 'IDR'
  }

  if (region === 'Singapore') {
    mealCurrency.value = 'SGD'
    pocketCurrency.value = 'SGD'
  }

  if (region === 'Non Singapore') {
    mealCurrency.value = 'USD'
    pocketCurrency.value = 'USD'
  }
}

const countryOptions = ref([])
const manualCountry = ref(false)
const selectedCountryCurrency = ref('')
const manualCurrency = ref('')

async function loadCountries(region) {
  countryOptions.value = []
  if (!region) return

  const masterRegion = { Indonesia: 'Domestic', Singapore: 'Singapore', 'Non Singapore': 'Overseas' }[region]
  if (!masterRegion) return

  try {
    const res = await getTravelAdvanceCountries(masterRegion)
    console.log('Region form:', region)
    console.log('Region master:', masterRegion)
    console.log('Country API response:', res)
    console.log('Country data:', res.data)
    countryOptions.value = [...(res.data || res || []), { country: 'Other', currency: null }]
    console.log('Country options:', countryOptions.value)
  } catch (error) {
    console.error('Country API error:', error)
    countryOptions.value = []
  }
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

function focusCustomCountry() {
  nextTick(() => customCountryInput.value?.focus())
}

function clearErrors() {
  Object.keys(errors).forEach(k => { errors[k] = '' })
  generalError.value = ''
}

function validate() {
  clearErrors()
  let valid = true
  const required = ['employee', 'department', 'travelFrom', 'travelTo', 'departureDate', 'departureTime', 'returnDate', 'returnTime', 'purpose', 'travelRegion', 'ferryTicketType', 'ferryArrangement', 'accommodationArrangement']

  required.forEach(k => {
    if (!String(form[k] ?? '').trim()) {
      errors[k] = 'This field is required.'
      valid = false
    }
  })

  if (form.travelRegion === 'Non Singapore') {
    if (!String(form.country || '').trim()) {
      errors.country = 'This field is required.'
      valid = false
    }
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

function backToCountryDropdown() {
  manualCountry.value = false
  form.country = ''
  manualCurrency.value = ''
  mealCurrency.value = ''
  pocketCurrency.value = ''
}

function submitForm() {
  if (props.loading || !validate()) return
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

  emit('submit', {
    ...form,
    mealCurrency: mealCurrency.value,
    pocketCurrency: pocketCurrency.value,
  })
}

function cancel() {
  if (!props.loading) emit('cancel')
}

watch([() => form.travelRegion, mealCurrency], ([r, c]) => loadTravelAdvanceLimit(r, c), { immediate: true })
watch([() => form.travelRegion, pocketCurrency], ([r, c]) => loadTravelAdvanceLimit(r, c), { immediate: true })

// Kalau tanggal diubah sampai batas turun, nilai yang sudah terisi otomatis dipotong ke batas baru
watch(mealMax, m => { if (m !== null && form.mealAllowance !== '' && Number(form.mealAllowance) > m) form.mealAllowance = String(m) })
watch(pocketMax, m => { if (m !== null && form.pocketMoney !== '' && Number(form.pocketMoney) > m) form.pocketMoney = String(m) })

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
                <template #selection="{ item }"><span v-if="form.travelRegion">{{ item.title }}</span><span v-else class="text-medium-emphasis" style="font-size: 13px">Select travel region</span></template>
              </VSelect>
            </VCol>

            <VCol v-if="showCountry" cols="12" md="3">
              <VSelect v-if="!manualCountry" v-model="form.country" :items="countryOptions" item-title="country" item-value="country" label="Country" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.country ? [errors.country] : []" @update:model-value="selectCountry">
                <template #selection="{ item }"><span v-if="form.country">{{ item.title }}</span><span v-else class="text-medium-emphasis" style="font-size: 13px">Select country</span></template>
              </VSelect>
              <VTextField v-else ref="customCountryInput" v-model="form.country" label="Country" placeholder="Input country" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.country ? [errors.country] : []" />
            </VCol>

            <VCol v-if="manualCountry" cols="12" md="3">
              <VSelect v-model="manualCurrency" :items="['USD', 'EUR']" label="Currency" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" @update:model-value="selectManualCurrency">
                <template #selection="{ item }">
                  <span v-if="manualCurrency">{{ item.title }}</span>
                  <span v-else class="text-medium-emphasis" style="font-size: 13px">Select currency</span>
                </template>
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
                    <VCol cols="6"><VSelect v-model="form.ferryTicketType" :items="ferryTicketTypes" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.ferryTicketType ? [errors.ferryTicketType] : []"><template #selection="{ item }"><span v-if="form.ferryTicketType">{{ item.title }}</span><span v-else class="text-medium-emphasis" style="font-size: 13px">Select ticket type</span></template></VSelect></VCol>
                    <VCol cols="6"><VSelect v-model="form.ferryArrangement" :items="ferryArrangements" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.ferryArrangement ? [errors.ferryArrangement] : []"><template #selection="{ item }"><span v-if="form.ferryArrangement">{{ item.title }}</span><span v-else class="text-medium-emphasis" style="font-size: 13px">Select arrangement</span></template></VSelect></VCol>
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
                <VCardText><VSelect v-model="form.accommodationArrangement" :items="accommodationArrangements" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.accommodationArrangement ? [errors.accommodationArrangement] : []"><template #selection="{ item }"><span v-if="form.accommodationArrangement">{{ item.title }}</span><span v-else class="text-medium-emphasis" style="font-size: 13px">Select arrangement</span></template></VSelect></VCardText>
              </VCard>
            </VCol>

            <VCol cols="12" md="4">
              <VCard rounded="lg" elevation="2" color="grey-lighten-5" class="h-100">
                <VCardItem>
                  <VCardTitle class="text-subtitle-2 font-weight-bold pa-0">Travel Advance <VTooltip text="Optional and determined by Travel Region."><template #activator="{ props }"><VIcon v-bind="props" icon="ri-information-line" size="16" class="ms-1 text-medium-emphasis" /></template></VTooltip></VCardTitle>
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
                    <div class="text-body-2 font-weight-medium mb-2">Meal Allowance</div>
                    <VRow class="align-center">
                      <VSelect v-model="mealCurrency" :items="advanceCurrencyItems" variant="outlined" density="comfortable" hide-details disabled />
                      <VCol cols="8" class="pa-1"><VTextField v-model="mealAllowanceDisplay" type="text" inputmode="decimal" :placeholder="travelDays ? '0' : 'Fill travel dates first'" :hint="mealHint" persistent-hint variant="outlined" density="comfortable" hide-details="auto" :disabled="loading || !travelDays" :error-messages="errors.mealAllowance ? [errors.mealAllowance] : []" @beforeinput="blockOverMax($event, mealMax, mealCurrency)" /></VCol>
                    </VRow>

                    <div class="text-body-2 font-weight-medium mb-2 mt-4">Pocket Money</div>
                    <VRow class="align-center">
                      <VSelect v-model="pocketCurrency" :items="advanceCurrencyItems" variant="outlined" density="comfortable" hide-details disabled />
                      <VCol cols="8" class="pa-1"><VTextField v-model="pocketMoneyDisplay" type="text" inputmode="decimal" :placeholder="travelDays ? '0' : 'Fill travel dates first'" :hint="pocketHint" persistent-hint variant="outlined" density="comfortable" hide-details="auto" :disabled="loading || !travelDays" :error-messages="errors.pocketMoney ? [errors.pocketMoney] : []" @beforeinput="blockOverMax($event, pocketMax, pocketCurrency)" /></VCol>
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
          <VCard color="grey-lighten-4" rounded="lg" class="mb-3"><VCardText class="d-flex align-center ga-2 pa-3"><input v-model="confirmInfo" type="checkbox" class="mr-2"><div class="text-body-2">I confirm that the Travel Order information is correct and ready to enter the approval process.</div></VCardText></VCard>
          <VCard color="amber-lighten-5" rounded="lg"><VCardText class="d-flex align-center ga-2 pa-3"><input v-model="acknowledgeTer" type="checkbox" class="mr-2"><div class="text-body-2">I acknowledge and commit to submit the <strong>TER (Travel Expense Report) no later than 7 calendar days</strong> after the Return Date.</div></VCardText></VCard>
        </VCardText>

        <VDivider />
        <VCardActions class="pa-3 justify-end">
          <VBtn variant="text" :disabled="props.loading" @click="backToEdit">Back</VBtn>
          <VBtn color="primary" variant="flat" :loading="props.loading" :disabled="!canSubmit" @click="confirmSubmit">Submit Travel Order</VBtn>
        </VCardActions>
      </VCard>
    </div>
  </div>
</template>

<style scoped>
.text-right-input :deep(input) { text-align: right; }
</style>