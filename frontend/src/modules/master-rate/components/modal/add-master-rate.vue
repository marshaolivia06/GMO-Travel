<script setup>
import { reactive, ref } from 'vue'
import { useMasterRateStore } from '../../stores/masterRateStore'
import { useMasterRegionStore } from '../../../master-region/stores/masterRegionStore'
import { useConfirm } from '../../../../composables/useConfirm'
import { useToastStore } from '../../../../stores/toast'

const props = defineProps({
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

  const confirmed = await confirm({ title: 'Add Master Rate', text: 'Are you sure you want to add this master rate?' })
  if (!confirmed) return

  loading.value = true

  try {
    await masterRateStore.addMasterRate({
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

    toast.success('Master rate has been added successfully.')
    emit('close')
  } catch (err) {
    handleValidationError(err)
    generalError.value = err.response?.data?.message || err.message || 'Failed to create master rate.'
  } finally {
    loading.value = false
  }
}

function close() {
  if (!loading.value) emit('close')
}
</script>

<template>
  <VDialog :model-value="true" max-width="520" scrollable persistent>
    <VCard>
      <VCardTitle class="pa-4">Add Master Rate</VCardTitle>
      <VCardSubtitle class="px-4">Create a new master rate.</VCardSubtitle>

      <VForm @submit.prevent="submitMasterRate">
        <VCardText>
          <VAlert v-if="generalError" type="error" class="mb-4">{{ generalError }}</VAlert>

          <div class="text-body-2 mb-1">Region</div>
          <VSelect v-model="form.region_id" :items="masterRegionStore.masterRegions" item-title="name" item-value="id" placeholder="Select region" hide-details="auto" class="mb-3" :disabled="loading" :error-messages="errors.region_id ? [errors.region_id] : []" />

          <div class="text-body-2 mb-1">Grade Min</div>
          <VTextField v-model.number="form.grade_min" type="number" placeholder="1" hide-details="auto" class="mb-3" :disabled="loading" :error-messages="errors.grade_min ? [errors.grade_min] : []" />

          <div class="text-body-2 mb-1">Grade Max</div>
          <VTextField v-model.number="form.grade_max" type="number" placeholder="5" hide-details="auto" class="mb-3" :disabled="loading" :error-messages="errors.grade_max ? [errors.grade_max] : []" />

          <div class="text-body-2 mb-1">Hotel Star</div>
          <VSelect v-model="form.hotel_star" :items="starOptions" placeholder="Select hotel star" hide-details="auto" class="mb-3" :disabled="loading" :error-messages="errors.hotel_star ? [errors.hotel_star] : []" />

          <div class="text-body-2 mb-1">Hotel Rate Currency</div>
          <VSelect v-model="form.hotel_rate_currency" :items="currencyOptions" placeholder="Select currency" hide-details="auto" class="mb-3" :disabled="loading" :error-messages="errors.hotel_rate_currency ? [errors.hotel_rate_currency] : []" />

          <div class="text-body-2 mb-1">Hotel Rate</div>
          <VTextField :model-value="formatNumber(form.hotel_rate)" placeholder="0" type="text" inputmode="numeric" hide-details="auto" class="mb-3 text-right-input" :disabled="loading" :error-messages="errors.hotel_rate ? [errors.hotel_rate] : []" @input="handleNumberInput('hotel_rate', $event)" />

          <div class="text-body-2 mb-1">Meal Basis</div>
          <VSelect v-model="form.meal_basis" :items="mealBasisOptions" placeholder="Select meal basis" hide-details="auto" class="mb-3" :disabled="loading" :error-messages="errors.meal_basis ? [errors.meal_basis] : []" />

          <template v-if="form.meal_basis === 'limit'">
            <div class="text-body-2 mb-1">Meal Limit Per Meal</div>
            <VTextField :model-value="formatNumber(form.meal_limit_per_meal)" placeholder="0" type="text" inputmode="numeric" hide-details="auto" class="mb-3 text-right-input" :disabled="loading" :error-messages="errors.meal_limit_per_meal ? [errors.meal_limit_per_meal] : []" @input="handleNumberInput('meal_limit_per_meal', $event)" />
          </template>

          <div class="text-body-2 mb-1">Daily Allowance (optional)</div>
          <VTextField :model-value="formatNumber(form.daily_allowance)" placeholder="0" type="text" inputmode="numeric" hide-details="auto" class="mb-3 text-right-input" :disabled="loading" :error-messages="errors.daily_allowance ? [errors.daily_allowance] : []" @input="handleNumberInput('daily_allowance', $event)" />

          <div class="text-body-2 mb-1">Daily Allowance Max Days (optional)</div>
          <VTextField v-model.number="form.daily_allowance_max_days" type="number" placeholder="0" hide-details="auto" class="mb-3" :disabled="loading" :error-messages="errors.daily_allowance_max_days ? [errors.daily_allowance_max_days] : []" />

          <div class="text-body-2 mb-1">Flight Class</div>
          <VSelect v-model="form.flight_class" :items="flightClassOptions" placeholder="Select flight class" hide-details="auto" class="mb-3" :disabled="loading" :error-messages="errors.flight_class ? [errors.flight_class] : []" />

          <div class="text-body-2 mb-1">Effective From</div>
          <VTextField v-model="form.effective_from" type="date" hide-details="auto" class="mb-3" :disabled="loading" :error-messages="errors.effective_from ? [errors.effective_from] : []" />

          <div class="text-body-2 mb-1">Effective To (optional)</div>
          <VTextField v-model="form.effective_to" type="date" hide-details="auto" :disabled="loading" :error-messages="errors.effective_to ? [errors.effective_to] : []" />
        </VCardText>

        <VCardActions class="justify-end gap-2 pa-4">
          <VBtn variant="tonal" :disabled="loading" @click="close">Cancel</VBtn>
          <VBtn type="submit" color="success" variant="flat" :loading="loading">Add</VBtn>
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