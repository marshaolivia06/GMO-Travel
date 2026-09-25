<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useAuthStore } from '../../../../stores/auth'
import { getDepartmentLock, getTravelAdvanceLimit } from '../../services/travelOrderService'
import { getDepartmentOptions } from '../../../master-management/services/departmentService'
import { useConfirm } from '../../../../composables/useConfirm'

const emit = defineEmits(['cancel', 'submit'])
const authStore = useAuthStore()
const { confirm } = useConfirm()

const generalError = ref('')
const departmentLocked = ref(false)
const departmentOptions = ref([])
const loadingDepartmentInfo = ref(true)
const loadingTravelAdvanceLimit = ref(false)
const travelAdvanceLimits = reactive({})

const props = defineProps({
  loading: {
    type: Boolean,
    default: false,
  },
})

const errors = reactive({
  employee: '', department: '', travelFrom: '', travelTo: '', departureDate: '',
  departureTime: '', returnDate: '', returnTime: '', purpose: '', remarks: '',
  travelRegion: '', pocketMoney: '', mealAllowance: '', ferryTicketType: '',
  ferryArrangement: '', accommodationArrangement: '',
})

const form = reactive({
  employee: authStore.user?.name || '', department: '', departmentId: null,
  travelFrom: '', travelTo: '', departureDate: '', departureTime: '',
  returnDate: '', returnTime: '', purpose: '', remarks: '', travelRegion: '',
  pocketMoney: '', mealAllowance: '', ferryTicketType: '', ferryArrangement: '',
  accommodationArrangement: '',
})

const travelRegions = ['Indonesia', 'Singapore', 'Non Singapore']
const ferryTicketTypes = ['Ferry', 'Airplane']
const ferryArrangements = ['Direct Payment', 'Booked by Company']
const accommodationArrangements = ['Direct Payment', 'Booked by Company', 'Not Required']
const nonSingaporeCurrencies = ['USD', 'EUR', 'MYR', 'JPY']

const mealCurrency = ref('')
const pocketCurrency = ref('')

const isOverseas = computed(() => ['Singapore', 'Non Singapore'].includes(form.travelRegion))
const isFerryReimbursable = computed(() => form.ferryArrangement === 'Direct Payment')
const isAccommodationReimbursable = computed(() => form.accommodationArrangement === 'Direct Payment')

const mealLimit = computed(() => {
  if (!isOverseas.value || !mealCurrency.value) return null
  return travelAdvanceLimits[`${form.travelRegion}_${mealCurrency.value}`]?.meal_allowance_limit ?? null
})

const pocketLimit = computed(() => {
  if (!isOverseas.value || !pocketCurrency.value) return null
  return travelAdvanceLimits[`${form.travelRegion}_${pocketCurrency.value}`]?.pocket_money_limit ?? null
})

const mealExceeded = computed(() => {
  if (mealLimit.value === null || form.mealAllowance === '') return false
  return Number(form.mealAllowance) > Number(mealLimit.value)
})

const pocketExceeded = computed(() => {
  if (pocketLimit.value === null || form.pocketMoney === '') return false
  return Number(form.pocketMoney) > Number(pocketLimit.value)
})

function onDepartmentSelect(departmentId) {
  form.departmentId = departmentId
  const selected = departmentOptions.value.find(d => d.id === departmentId)
  form.department = selected ? selected.name : ''
}

function formatNumberByCurrency(rawValue, currency) {
  if (rawValue === '' || rawValue === null || rawValue === undefined) return ''

  const num = Number(rawValue)
  if (isNaN(num)) return ''

  if (currency === 'EUR') {
    return new Intl.NumberFormat('de-DE', { maximumFractionDigits: 2 }).format(num)
  }

  if (currency === 'JPY') {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(num)
  }

  return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(num)
}

function parseNumberInput(displayValue, currency) {
  if (!displayValue) return ''

  let cleaned = String(displayValue)

  if (currency === 'EUR') {
    cleaned = cleaned.replace(/\./g, '').replace(',', '.')
  } else {
    cleaned = cleaned.replace(/,/g, '')
  }

  return cleaned.replace(/[^0-9.]/g, '')
}

const mealAllowanceDisplay = computed({
  get: () => formatNumberByCurrency(form.mealAllowance, mealCurrency.value),
  set: val => { form.mealAllowance = parseNumberInput(val, mealCurrency.value) },
})

const pocketMoneyDisplay = computed({
  get: () => formatNumberByCurrency(form.pocketMoney, pocketCurrency.value),
  set: val => { form.pocketMoney = parseNumberInput(val, pocketCurrency.value) },
})

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

async function loadTravelAdvanceLimit(region, currency) {
  if (!region || !currency || region === 'Indonesia') return

  const key = `${region}_${currency}`

  if (travelAdvanceLimits[key]) return

  loadingTravelAdvanceLimit.value = true

  try {
    const response = await getTravelAdvanceLimit(region, currency)
    travelAdvanceLimits[key] = response.data || response
  } catch (err) {
    generalError.value = err?.response?.data?.message || 'Gagal memuat batas Travel Advance.'
  } finally {
    loadingTravelAdvanceLimit.value = false
  }
}

function selectRegion(region) {
  form.travelRegion = region

  if (region === 'Indonesia') {
    form.pocketMoney = ''
    form.mealAllowance = ''
    mealCurrency.value = ''
    pocketCurrency.value = ''
    errors.pocketMoney = ''
    errors.mealAllowance = ''
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

function clearErrors() {
  Object.keys(errors).forEach(key => { errors[key] = '' })
  generalError.value = ''
}

function validate() {
  clearErrors()
  let valid = true

  const required = [
  'employee', 'department', 'travelFrom', 'travelTo', 'departureDate',
  'departureTime', 'returnDate', 'returnTime', 'purpose',
  'travelRegion', 'ferryTicketType', 'ferryArrangement', 'accommodationArrangement',
]

  for (const field of required) {
    if (!String(form[field] || '').trim()) {
      errors[field] = 'This field is required.'
      valid = false
    }
  }

  if (isOverseas.value) {
    if (!String(form.pocketMoney || '').trim()) {
      errors.pocketMoney = 'This field is required.'
      valid = false
    }

    if (!String(form.mealAllowance || '').trim()) {
      errors.mealAllowance = 'This field is required.'
      valid = false
    }

    if (mealExceeded.value) {
      errors.mealAllowance = `Maximum allowed is ${mealLimit.value} ${mealCurrency.value}.`
      valid = false
    }

    if (pocketExceeded.value) {
      errors.pocketMoney = `Maximum allowed is ${pocketLimit.value} ${pocketCurrency.value}.`
      valid = false
    }
  }

  return valid
}

async function submitForm() {
  if (props.loading || !validate()) return

  const confirmed = await confirm({
    title: 'Submit Travel Order?',
    text: 'Please make sure all the information you entered is correct before submitting.',
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

watch([() => form.travelRegion, mealCurrency], ([region, currency]) => {
  loadTravelAdvanceLimit(region, currency)
}, { immediate: true })

watch([() => form.travelRegion, pocketCurrency], ([region, currency]) => {
  loadTravelAdvanceLimit(region, currency)
}, { immediate: true })

onMounted(loadDepartmentInfo)
</script>

<template>
  <div>
    <div class="d-flex align-start justify-space-between mb-6">
      <div>
        <div class="text-primary text-body-2 font-weight-bold mb-1">BUSINESS TRIP - INDIVIDUAL</div>
        <div class="text-h5 font-weight-bold mb-1">Travel Order Form</div>
        <div class="text-body-2 text-medium-emphasis">Complete a standalone employee Travel Order.</div>
      </div>
      <VChip variant="tonal" color="primary" size="small">Standalone Request</VChip>
    </div>

    <VCard rounded="lg" elevation="2" color="primary" class="mb-6">
      <VCardText class="d-flex align-center ga-4">
        <VAvatar color="white" size="42"><span class="text-primary font-weight-bold">01</span></VAvatar>
        <div>
          <div class="text-caption text-white font-weight-bold">SELECTED REQUEST PATH</div>
          <div class="text-subtitle-1 text-white font-weight-bold">Individual Trip</div>
          <div class="text-body-2 text-white">Standalone employee Travel Order.</div>
        </div>
      </VCardText>
    </VCard>

    <VCard rounded="lg" elevation="1">
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
            <VCol cols="12" md="6">
              <VTextField v-model="form.employee" label="Employee  *" variant="outlined" density="comfortable" hide-details="auto" disabled />
            </VCol>

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
            <VCol cols="12" md="6"><VTextarea v-model="form.remarks" label="Remarks (Optional)" placeholder="Add remarks if needed" variant="outlined" density="comfortable" rows="3" hide-details="auto" :disabled="loading" :error-messages="errors.remarks ? [errors.remarks] : []" /></VCol>
          </VRow>
          <div class="text-subtitle-2 font-weight-bold mb-4 mt-4">Travel Region</div>

          <VRow>
            <VCol v-for="region in travelRegions" :key="region" cols="12" md="4">
              <VCard :variant="form.travelRegion === region ? 'tonal' : 'flat'" :color="form.travelRegion === region ? 'primary' : undefined" rounded="lg" hover class="h-100" @click="selectRegion(region)">
                <VCardText>
                  <div class="text-subtitle-2 font-weight-bold">{{ region }}</div>
                  <div class="text-body-2 text-medium-emphasis mt-1">{{ region === 'Indonesia' ? 'Domestic travel' : 'International travel' }}</div>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>

          <div class="text-subtitle-2 font-weight-bold mb-4 mt-6">Ticket, Accommodation & Travel Advance</div>

          <VRow>
            <VCol cols="12" md="4">
              <VCard rounded="lg" elevation="2" color="grey-lighten-5" class="h-100">
                <VCardItem>
                  <template #append>
                    <VChip variant="tonal" :color="isFerryReimbursable ? 'success' : 'error'" size="small">{{ isFerryReimbursable ? 'Reimbursable' : 'Not Reimbursable' }}</VChip>
                  </template>
                  <div class="d-flex align-center ga-1">
  <VCardTitle class="text-subtitle-2 font-weight-bold pa-0">
    Ferry Ticket
  </VCardTitle>

  <VTooltip location="top">
    <template #activator="{ props }">
      <VIcon
        v-bind="props"
        icon="ri-information-line"
        size="15"
        class="text-medium-emphasis"
      />
    </template>

    Employee pays first and claims eligible expense through TER.
  </VTooltip>
</div>

<div class="text-caption text-medium-emphasis mt-1">
  Select transport type, arranger, and payer.
</div>
                </VCardItem>

                <VCardText>
                  <VRow>
                    <VCol cols="6">
                      <VSelect v-model="form.ferryTicketType" :items="ferryTicketTypes" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.ferryTicketType ? [errors.ferryTicketType] : []">
                        <template #selection="{ item }"><span v-if="form.ferryTicketType">{{ item.title }}</span><span v-else class="text-medium-emphasis" style="font-size: 12px;">Select ticket type</span></template>
                      </VSelect>
                    </VCol>
                    <VCol cols="6">
                      <VSelect v-model="form.ferryArrangement" :items="ferryArrangements" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.ferryArrangement ? [errors.ferryArrangement] : []">
                        <template #selection="{ item }"><span v-if="form.ferryArrangement">{{ item.title }}</span><span v-else class="text-medium-emphasis" style="font-size: 12px;">Select arrangement</span></template>
                      </VSelect>
                    </VCol>
                  </VRow>
                </VCardText>
              </VCard>
            </VCol>

            <VCol cols="12" md="4">
              <VCard rounded="lg" elevation="2" color="grey-lighten-5" class="h-100">
                <VCardItem>
                  <template #append>
                    <VChip variant="tonal" :color="isAccommodationReimbursable ? 'success' : 'error'" size="small">{{ isAccommodationReimbursable ? 'Reimbursable' : 'Not Reimbursable' }}</VChip>
                  </template>
                  <div class="d-flex align-center ga-1">
  <VCardTitle class="text-subtitle-2 font-weight-bold pa-0">
    Accommodation
  </VCardTitle>

  <VTooltip location="top">
    <template #activator="{ props }">
      <VIcon
        v-bind="props"
        icon="ri-information-line"
        size="15"
        class="text-medium-emphasis"
      />
    </template>

    Employee pays first and claims eligible expense through TER.
  </VTooltip>
</div>

<div class="text-caption text-medium-emphasis mt-1">
  Select accommodation arrangement.
</div>
                </VCardItem>

                <VCardText>
                  <VSelect v-model="form.accommodationArrangement" :items="accommodationArrangements" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.accommodationArrangement ? [errors.accommodationArrangement] : []">
                    <template #selection="{ item }"><span v-if="form.accommodationArrangement">{{ item.title }}</span><span v-else class="text-medium-emphasis" style="font-size: 12px;">Select arrangement</span></template>
                  </VSelect>
                </VCardText>
              </VCard>
            </VCol>

            <VCol cols="12" md="4">
              <VCard rounded="lg" elevation="2" color="grey-lighten-5" class="h-100">
                <VCardItem>
                  <div class="d-flex align-center ga-1">
  <VCardTitle class="text-subtitle-2 font-weight-bold pa-0">
    Travel Advance
  </VCardTitle>

  <VTooltip location="top">
    <template #activator="{ props }">
      <VIcon
        v-bind="props"
        icon="ri-information-line"
        size="15"
        class="text-medium-emphasis"
      />
    </template>

    Travel Advance is optional and determined by Travel Region.
  </VTooltip>
</div>

<div class="text-caption text-medium-emphasis mt-1">
  Optional advance based on travel region.
</div>
                </VCardItem>

                <VCardText>
                  <VAlert v-if="form.travelRegion === 'Indonesia'" type="info" variant="tonal" density="compact">Travel Advance is not applicable for Indonesia domestic travel.</VAlert>

                  <template v-else>
                    <VAlert type="info" variant="tonal" density="compact" class="mb-4">
                      <template v-if="form.travelRegion === 'Singapore'">
                        <strong>Singapore policy:</strong> Travel Advance is requested in SGD.
                      </template>
                      <template v-else>
                        <strong>Non-Singapore policy:</strong> Select USD, EUR, MYR, or JPY for each advance component.
                      </template>
                    </VAlert>

                    <VProgressLinear v-if="loadingTravelAdvanceLimit" indeterminate color="primary" class="mb-4" />

                    <div class="text-body-2 font-weight-medium mb-2">Meal Allowance</div>

                    <VRow class="align-center">
                      <VCol cols="4" class="pa-1">
                        <VSelect v-model="mealCurrency" :items="form.travelRegion === 'Singapore' ? ['SGD'] : nonSingaporeCurrencies" variant="outlined" density="comfortable" hide-details :disabled="form.travelRegion === 'Singapore' || loading" />
                      </VCol>
                      <VCol cols="8" class="pa-1">
                        <VTextField v-model="mealAllowanceDisplay" type="text" inputmode="decimal" placeholder="0" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.mealAllowance ? [errors.mealAllowance] : []" class="text-right-input" />
                      </VCol>
                    </VRow>

                    <VAlert v-if="mealExceeded" type="error" variant="tonal" density="compact" class="mt-4 w-auto">
                      Meal allowance exceeds the maximum limit of <strong>{{ mealLimit }} {{ mealCurrency }}</strong>.
                    </VAlert>

                    <div class="text-body-2 font-weight-medium mb-2 mt-4">Pocket Money</div>

                    <VRow class="align-center">
                      <VCol cols="4" class="pa-1">
                        <VSelect v-model="pocketCurrency" :items="form.travelRegion === 'Singapore' ? ['SGD'] : nonSingaporeCurrencies" variant="outlined" density="comfortable" hide-details :disabled="form.travelRegion === 'Singapore' || loading" />
                      </VCol>
                      <VCol cols="8" class="pa-1">
                        <VTextField v-model="pocketMoneyDisplay" type="text" inputmode="decimal" placeholder="0" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.pocketMoney ? [errors.pocketMoney] : []" class="text-right-input" />
                      </VCol>
                    </VRow>

                    <VAlert v-if="pocketExceeded" type="error" variant="tonal" density="compact" class="mt-4 w-auto">
                      Pocket money exceeds the maximum limit of <strong>{{ pocketLimit }} {{ pocketCurrency }}</strong>.
                    </VAlert>
                  </template>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>
        </VCardText>

        <VDivider />

        <VCardActions class="pa-4 justify-end">
          <VBtn variant="text" :disabled="loading" @click="cancel">Cancel</VBtn>
          <VBtn type="submit" color="primary" variant="flat" :loading="props.loading" :disabled="props.loading || mealExceeded || pocketExceeded">Submit</VBtn>
        </VCardActions>
      </VForm>
    </VCard>
  </div>
</template>

<style scoped>
.text-right-input :deep(input) {
  text-align: right;
}
</style>
