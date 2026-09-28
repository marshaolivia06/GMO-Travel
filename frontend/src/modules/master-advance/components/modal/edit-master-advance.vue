<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useMasterAdvanceStore } from '../../stores/masterAdvanceStore'
import { useConfirm } from '../../../../composables/useConfirm'
import { useToastStore } from '../../../../stores/toast'
import { getTravelAdvanceRegions } from '../../services/masterAdvanceService'

const props = defineProps({
  item: { type: Object, required: true },
})

const emit = defineEmits(['close'])
const { confirm } = useConfirm()
const toast = useToastStore()
const masterAdvanceStore = useMasterAdvanceStore()

const loading = ref(false)
const loadingRegions = ref(false)
const generalError = ref('')
const travelRegions = ref([])

const errors = reactive({
  travel_region: '',
  grade_min: '',
  grade_max: '',
  country: '',
  currency: '',
  pocket_money_limit: '',
  meal_allowance_limit: '',
})

function toInputValue(value) {
  if (value === null || value === undefined || value === '') return ''
  return String(Math.trunc(Number(value)))
}

const form = reactive({
  travel_region: props.item.travel_region ?? '',
  grade_min: toInputValue(props.item.grade_min),
  grade_max: toInputValue(props.item.grade_max),
  country: props.item.country ?? '',
  currency: props.item.currency ?? '',
  pocket_money_limit: toInputValue(props.item.pocket_money_limit),
  meal_allowance_limit: toInputValue(props.item.meal_allowance_limit),
})

function clearErrors() {
  Object.keys(errors).forEach(key => { errors[key] = '' })
  generalError.value = ''
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

function handleValidationError(err) {
  const validationErrors = err?.response?.data?.errors
  if (!validationErrors) return
  Object.keys(errors).forEach(key => { errors[key] = validationErrors[key]?.[0] || '' })
}

async function loadRegions() {
  loadingRegions.value = true
  try {
    travelRegions.value = await getTravelAdvanceRegions()
  } catch (err) {
    generalError.value = err?.response?.data?.message || 'Gagal memuat data travel region.'
  } finally {
    loadingRegions.value = false
  }
}

async function submitMasterAdvance() {
  if (loading.value) return
  clearErrors()

  if (!form.travel_region.trim()) {
    errors.travel_region = 'Travel region is required.'
    return
  }

  if (form.currency.trim().length !== 3) {
    errors.currency = 'Currency must contain exactly 3 characters.'
    return
  }

  if (!/^[a-zA-Z]{3}$/.test(form.currency.trim())) {
    errors.currency = 'Currency must contain letters only.'
    return
  }

  if (form.pocket_money_limit === '') {
    errors.pocket_money_limit = 'Pocket money limit is required.'
    return
  }

  if (form.meal_allowance_limit === '') {
    errors.meal_allowance_limit = 'Meal allowance limit is required.'
    return
  }

  const confirmed = await confirm({
    title: 'Edit Master Advance',
    text: 'Are you sure you want to save these changes?',
  })

  if (!confirmed) return

  loading.value = true

  try {
    await masterAdvanceStore.editTravelAdvanceMaster(props.item.id_travel_advance_master, {
      travel_region: form.travel_region.trim(),
      grade_min: form.grade_min === '' ? null : Number(form.grade_min),
      grade_max: form.grade_max === '' ? null : Number(form.grade_max),
      country: form.country.trim() || null,
      currency: form.currency.trim().toUpperCase(),
      pocket_money_limit: Number(form.pocket_money_limit),
      meal_allowance_limit: Number(form.meal_allowance_limit),
    })

    toast.success('Travel Advance master has been updated successfully.')
    emit('close')
  } catch (err) {
    handleValidationError(err)
    generalError.value = err.response?.data?.message || err.message || 'Failed to update travel advance master.'
  } finally {
    loading.value = false
  }
}

function close() {
  if (!loading.value) emit('close')
}

onMounted(loadRegions)
</script>

<template>
  <VDialog :model-value="true" max-width="520" persistent>
    <VCard>
      <VCardTitle class="pa-4">Edit Master Advance</VCardTitle>
      <VCardSubtitle class="px-4">Update travel advance master information.</VCardSubtitle>

      <VForm @submit.prevent="submitMasterAdvance">
        <VCardText>
          <VAlert v-if="generalError" type="error" class="mb-4">{{ generalError }}</VAlert>

          <VSelect v-model="form.travel_region" :items="travelRegions" label="Travel Region" class="mb-3" :loading="loadingRegions" :disabled="loading" :error-messages="errors.travel_region ? [errors.travel_region] : []" />

          <VRow dense class="mb-3">
            <VCol cols="6"><VTextField v-model.number="form.grade_min" label="Grade Min" type="number" :disabled="loading" :error-messages="errors.grade_min ? [errors.grade_min] : []" /></VCol>
            <VCol cols="6"><VTextField v-model.number="form.grade_max" label="Grade Max" type="number" :disabled="loading" :error-messages="errors.grade_max ? [errors.grade_max] : []" /></VCol>
          </VRow>

          <VTextField v-model="form.country" label="Country" class="mb-3" :disabled="loading" :error-messages="errors.country ? [errors.country] : []" />

          <VTextField v-model="form.currency" label="Currency" placeholder="USD" maxlength="3" class="mb-3" :disabled="loading" :error-messages="errors.currency ? [errors.currency] : []" />

          <VTextField :model-value="formatNumber(form.pocket_money_limit)" label="Pocket Money Limit" placeholder="0" type="text" inputmode="numeric" class="mb-3 text-right-input" :disabled="loading" :error-messages="errors.pocket_money_limit ? [errors.pocket_money_limit] : []" @input="handleNumberInput('pocket_money_limit', $event)" />

          <VTextField :model-value="formatNumber(form.meal_allowance_limit)" label="Meal Allowance Limit" placeholder="0" type="text" inputmode="numeric" class="mb-3 text-right-input" :disabled="loading" :error-messages="errors.meal_allowance_limit ? [errors.meal_allowance_limit] : []" @input="handleNumberInput('meal_allowance_limit', $event)" />
        </VCardText>

        <VCardActions class="justify-end gap-2 pa-4">
          <VBtn variant="tonal" :disabled="loading" @click="close">Cancel</VBtn>
          <VBtn type="submit" color="success" variant="flat" :loading="loading">Update</VBtn>
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