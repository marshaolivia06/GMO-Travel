<script setup>
import { reactive, ref, watch } from 'vue'
import { useMasterRateStore } from '../../stores/masterRateStore'
import { useMasterRegionStore } from '../../../master-region/stores/masterRegionStore'
import { useConfirm } from '../../../../composables/useConfirm'
import { useToastStore } from '../../../../stores/toast'

const props = defineProps({
  masterRate: { type: Object, required: true },
  regionFilter: { type: Number, default: null },
})

const emit = defineEmits(['close'])
const { confirm } = useConfirm()
const toast = useToastStore()
const masterRateStore = useMasterRateStore()
const masterRegionStore = useMasterRegionStore()

const loading = ref(false)
const generalError = ref('')

const starOptions = [1, 2, 3, 4, 5]
const currencyOptions = ['IDR', 'USD', 'SGD', 'EUR']
const mealBasisOptions = [{ title: 'Limit', value: 'limit' }, { title: 'At Cost', value: 'at_cost' }]
const flightClassOptions = [{ title: 'Economy', value: 'economy' }, { title: 'Economy / Business', value: 'economy_business' }]

const errors = reactive({
  region_id: '', grade_min: '', grade_max: '', hotel_star: '', hotel_rate: '', hotel_rate_currency: '',
  meal_basis: '', meal_limit_per_meal: '', daily_allowance: '', daily_allowance_max_days: '',
  flight_class: '', effective_from: '', effective_to: '',
})

const form = reactive({
  region_id: null, grade_min: '', grade_max: '', hotel_star: null, hotel_rate: '', hotel_rate_currency: '',
  meal_basis: null, meal_limit_per_meal: '', daily_allowance: '', daily_allowance_max_days: '',
  flight_class: null, effective_from: '', effective_to: '',
})

// API bisa mengirim angka sebagai "400000.00"; input hanya menerima angka bulat
function toDigits(value) {
  if (value === null || value === undefined || value === '') return ''
  const number = Number(value)
  return isNaN(number) ? '' : String(Math.round(number))
}

// "2026-01-01" atau "2026-01-01T00:00:00.000000Z" -> "2026-01-01"
function toDateInput(value) {
  return value ? String(value).slice(0, 10) : ''
}

function fillForm(rate) {
  if (!rate) return

  Object.assign(form, {
    region_id: rate.region_id ?? rate.region?.id ?? null,
    grade_min: rate.grade_min ?? '',
    grade_max: rate.grade_max ?? '',
    hotel_star: rate.hotel_star ?? null,
    hotel_rate: toDigits(rate.hotel_rate),
    hotel_rate_currency: rate.hotel_rate_currency || '',
    meal_basis: rate.meal_basis ?? null,
    meal_limit_per_meal: toDigits(rate.meal_limit_per_meal),
    daily_allowance: toDigits(rate.daily_allowance),
    daily_allowance_max_days: rate.daily_allowance_max_days ?? '',
    flight_class: rate.flight_class ?? null,
    effective_from: toDateInput(rate.effective_from),
    effective_to: toDateInput(rate.effective_to),
  })
}

watch(() => props.masterRate, fillForm, { immediate: true })

function clearErrors() {
  Object.keys(errors).forEach(k => { errors[k] = '' })
  generalError.value = ''
}

function handleValidationError(err) {
  const validationErrors = err?.response?.data?.errors
  if (!validationErrors) return

  Object.keys(errors).forEach(k => { errors[k] = validationErrors[k]?.[0] || '' })
}

function formatNumber(value) {
  if (value === '' || value === null || value === undefined) return ''

  const number = String(value).replace(/\D/g, '')
  if (!number) return ''

  return Number(number).toLocaleString('en-US')
}

function handleNumberInput(field, event) {
  form[field] = event.target.value.replace(/\D/g, '')
}

async function submitMasterRate() {
  if (loading.value) return

  clearErrors()

  if (!form.region_id) { errors.region_id = 'Region is required.'; return }
  if (form.grade_min === '' || form.grade_min === null) { errors.grade_min = 'Grade min is required.'; return }
  if (form.grade_max === '' || form.grade_max === null) { errors.grade_max = 'Grade max is required.'; return }
  if (Number(form.grade_max) < Number(form.grade_min)) { errors.grade_max = 'Grade max must be greater than or equal to grade min.'; return }
  if (!form.hotel_star) { errors.hotel_star = 'Hotel star is required.'; return }
  if (form.hotel_rate === '') { errors.hotel_rate = 'Hotel rate is required.'; return }
  if (!form.hotel_rate_currency) { errors.hotel_rate_currency = 'Hotel rate currency is required.'; return }
  if (!form.meal_basis) { errors.meal_basis = 'Meal basis is required.'; return }
  if (form.meal_basis === 'limit' && form.meal_limit_per_meal === '') { errors.meal_limit_per_meal = 'Meal limit per meal is required.'; return }
  if (form.daily_allowance !== '' && form.daily_allowance_max_days === '') { errors.daily_allowance_max_days = 'Max days is required when daily allowance is filled.'; return }
  if (!form.flight_class) { errors.flight_class = 'Flight class is required.'; return }
  if (!form.effective_from) { errors.effective_from = 'Effective from is required.'; return }
  if (form.effective_to && form.effective_to < form.effective_from) { errors.effective_to = 'Effective to must be on or after effective from.'; return }

  const confirmed = await confirm({ title: 'Update Master Rate', text: 'Are you sure you want to save changes to this master rate?' })
  if (!confirmed) return

  loading.value = true

  try {
    await masterRateStore.editMasterRate(props.masterRate.id, {
      region_id: form.region_id,
      grade_min: Number(form.grade_min),
      grade_max: Number(form.grade_max),
      hotel_star: form.hotel_star,
      hotel_rate: Number(form.hotel_rate),
      hotel_rate_currency: form.hotel_rate_currency,
      meal_basis: form.meal_basis,
      meal_limit_per_meal: form.meal_basis === 'limit' ? Number(form.meal_limit_per_meal) : null,
      daily_allowance: form.daily_allowance === '' ? null : Number(form.daily_allowance),
      daily_allowance_max_days: form.daily_allowance === '' ? null : Number(form.daily_allowance_max_days),
      flight_class: form.flight_class,
      effective_from: form.effective_from,
      effective_to: form.effective_to || null,
    }, props.regionFilter)

    toast.success('Master rate has been updated successfully.')
    emit('close')
  } catch (err) {
    handleValidationError(err)
    generalError.value = err.response?.data?.message || err.message || 'Failed to update master rate.'
  } finally {
    loading.value = false
  }
}

function close() {
  if (!loading.value) emit('close')
}
</script>

<template>
  <VDialog :model-value="true" max-width="820" scrollable persistent>
    <VCard rounded="lg" class="d-flex flex-column" style="max-height: 90vh">
      <div class="d-flex align-center justify-space-between px-5 bg-primary rounded-t-lg flex-shrink-0" style="height: 60px">
        <span class="text-title-large font-weight-bold text-white">Edit Master Rate</span>
        <VBtn icon="ri-close-line" variant="text" color="white" density="comfortable" @click="close" />
      </div>

      <VForm class="d-flex flex-column" style="min-height: 0; flex: 1 1 auto" @submit.prevent="submitMasterRate">
        <VCardText class="pa-5" style="overflow-y: auto; flex: 1 1 auto">
          <VAlert v-if="generalError" type="error" class="mb-4">{{ generalError }}</VAlert>

          <VRow dense>
            <VCol cols="12" sm="6">
              <label class="text-body-medium font-weight-600 d-block mb-1">Region</label>
              <VSelect v-model="form.region_id" :items="masterRegionStore.masterRegions" item-title="name" item-value="id" placeholder="Select region" hide-details="auto" :disabled="loading" :error-messages="errors.region_id ? [errors.region_id] : []" />
            </VCol>
            <VCol cols="12" sm="6">
              <label class="text-body-medium font-weight-600 d-block mb-1">Grade Min</label>
              <VTextField v-model.number="form.grade_min" type="number" placeholder="1" hide-details="auto" :disabled="loading" :error-messages="errors.grade_min ? [errors.grade_min] : []" />
            </VCol>
            <VCol cols="12" sm="6">
              <label class="text-body-medium font-weight-600 d-block mb-1">Grade Max</label>
              <VTextField v-model.number="form.grade_max" type="number" placeholder="5" hide-details="auto" :disabled="loading" :error-messages="errors.grade_max ? [errors.grade_max] : []" />
            </VCol>
            <VCol cols="12" sm="6">
              <label class="text-body-medium font-weight-600 d-block mb-1">Hotel Star</label>
              <VSelect v-model="form.hotel_star" :items="starOptions" placeholder="Select hotel star" hide-details="auto" :disabled="loading" :error-messages="errors.hotel_star ? [errors.hotel_star] : []" />
            </VCol>
            <VCol cols="12" sm="6">
              <label class="text-body-medium font-weight-600 d-block mb-1">Hotel Rate Currency</label>
              <VSelect v-model="form.hotel_rate_currency" :items="currencyOptions" placeholder="Select currency" hide-details="auto" :disabled="loading" :error-messages="errors.hotel_rate_currency ? [errors.hotel_rate_currency] : []" />
            </VCol>
            <VCol cols="12" sm="6">
              <label class="text-body-medium font-weight-600 d-block mb-1">Hotel Rate</label>
              <VTextField :model-value="formatNumber(form.hotel_rate)" placeholder="0" type="text" inputmode="numeric" hide-details="auto" class="text-right-input" :disabled="loading" :error-messages="errors.hotel_rate ? [errors.hotel_rate] : []" @input="handleNumberInput('hotel_rate', $event)" />
            </VCol>
            <VCol cols="12" sm="6">
              <label class="text-body-medium font-weight-600 d-block mb-1">Meal Basis</label>
              <VSelect v-model="form.meal_basis" :items="mealBasisOptions" placeholder="Select meal basis" hide-details="auto" :disabled="loading" :error-messages="errors.meal_basis ? [errors.meal_basis] : []" />
            </VCol>
            <VCol cols="12" sm="6" v-if="form.meal_basis === 'limit'">
              <label class="text-body-medium font-weight-600 d-block mb-1">Meal Limit Per Meal</label>
              <VTextField :model-value="formatNumber(form.meal_limit_per_meal)" placeholder="0" type="text" inputmode="numeric" hide-details="auto" class="text-right-input" :disabled="loading" :error-messages="errors.meal_limit_per_meal ? [errors.meal_limit_per_meal] : []" @input="handleNumberInput('meal_limit_per_meal', $event)" />
            </VCol>
            <VCol cols="12" sm="6">
              <label class="text-body-medium font-weight-600 d-block mb-1">Daily Allowance (optional)</label>
              <VTextField :model-value="formatNumber(form.daily_allowance)" placeholder="0" type="text" inputmode="numeric" hide-details="auto" class="text-right-input" :disabled="loading" :error-messages="errors.daily_allowance ? [errors.daily_allowance] : []" @input="handleNumberInput('daily_allowance', $event)" />
            </VCol>
            <VCol cols="12" sm="6">
              <label class="text-body-medium font-weight-600 d-block mb-1">Daily Allowance Max Days (optional)</label>
              <VTextField v-model.number="form.daily_allowance_max_days" type="number" placeholder="0" hide-details="auto" :disabled="loading" :error-messages="errors.daily_allowance_max_days ? [errors.daily_allowance_max_days] : []" />
            </VCol>
            <VCol cols="12" sm="6">
              <label class="text-body-medium font-weight-600 d-block mb-1">Flight Class</label>
              <VSelect v-model="form.flight_class" :items="flightClassOptions" placeholder="Select flight class" hide-details="auto" :disabled="loading" :error-messages="errors.flight_class ? [errors.flight_class] : []" />
            </VCol>
            <VCol cols="12" sm="6">
              <label class="text-body-medium font-weight-600 d-block mb-1">Effective From</label>
              <VTextField v-model="form.effective_from" type="date" hide-details="auto" :disabled="loading" :error-messages="errors.effective_from ? [errors.effective_from] : []" />
            </VCol>
            <VCol cols="12" sm="6">
              <label class="text-body-medium font-weight-600 d-block mb-1">Effective To (optional)</label>
              <VTextField v-model="form.effective_to" type="date" hide-details="auto" :disabled="loading" :error-messages="errors.effective_to ? [errors.effective_to] : []" />
            </VCol>
          </VRow>
        </VCardText>

        <VDivider class="flex-shrink-0" />
        <VCardActions class="pa-5 flex-shrink-0">
          <VSpacer />
          <VBtn variant="tonal" :disabled="loading" @click="close">Close</VBtn>
          <VBtn type="submit" color="primary" variant="flat" :loading="loading">Save</VBtn>
        </VCardActions>
      </VForm>
    </VCard>
  </VDialog>
</template>

<style scoped>
.text-right-input :deep(input) {
  text-align: right;
}
</style>