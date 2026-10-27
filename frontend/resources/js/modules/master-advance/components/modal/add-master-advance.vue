<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useMasterAdvanceStore } from '../../stores/masterAdvanceStore'
import { useConfirm } from '../../../../composables/useConfirm'
import { useToastStore } from '../../../../stores/toast'
import { getTravelAdvanceRegions } from '../../services/masterAdvanceService'

const emit = defineEmits(['close'])
const { confirm } = useConfirm()
const toast = useToastStore()
const masterAdvanceStore = useMasterAdvanceStore()

const loading = ref(false)
const generalError = ref('')
const travelRegions = ref([])
const loadingRegions = ref(false)

const errors = reactive({
  travel_region: '', grade_min: '', grade_max: '', country: '', currency: '', pocket_money_limit: '', meal_allowance_limit: '',
})

const form = reactive({
  travel_region: '', grade_min: '', grade_max: '', country: '', currency: '', pocket_money_limit: '', meal_allowance_limit: '',
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

async function loadRegions() {
  loadingRegions.value = true
  try {
    travelRegions.value = await getTravelAdvanceRegions()
  } catch (err) {
    generalError.value = err?.response?.data?.message || 'Failed to load travel regions.'
  } finally {
    loadingRegions.value = false
  }
}

async function submitMasterAdvance() {
  if (loading.value) return

  clearErrors()

  if (!form.travel_region) { errors.travel_region = 'Travel region is required.'; return }
  if (!form.currency.trim()) { errors.currency = 'Currency is required.'; return }
  if (form.pocket_money_limit === '' || form.pocket_money_limit === null) { errors.pocket_money_limit = 'Pocket money limit is required.'; return }
  if (form.meal_allowance_limit === '' || form.meal_allowance_limit === null) { errors.meal_allowance_limit = 'Meal allowance limit is required.'; return }

  const confirmed = await confirm({ title: 'Add Master Advance', text: 'Are you sure you want to add this travel advance master?' })
  if (!confirmed) return

  loading.value = true

  try {
    await masterAdvanceStore.addTravelAdvanceMaster({
      travel_region: form.travel_region,
      grade_min: form.grade_min === '' ? null : Number(form.grade_min),
      grade_max: form.grade_max === '' ? null : Number(form.grade_max),
      country: form.country.trim() || null,
      currency: form.currency.trim().toUpperCase(),
      pocket_money_limit: Number(form.pocket_money_limit),
      meal_allowance_limit: Number(form.meal_allowance_limit),
    })

    toast.success('Travel Advance master has been added successfully.')
    emit('close')
  } catch (err) {
    handleValidationError(err)
    generalError.value = err.response?.data?.message || err.message || 'Failed to create travel advance master.'
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
  <VDialog :model-value="true" max-width="820" persistent>
    <VCard rounded="lg" class="d-flex flex-column" style="max-height: 90vh">
      <div class="d-flex align-center justify-space-between px-5 bg-primary rounded-t-lg flex-shrink-0" style="height: 60px">
        <span class="text-title-large font-weight-bold text-white">Add Master Advance</span>
        <VBtn icon="ri-close-line" variant="text" color="white" density="comfortable" @click="close" />
      </div>

      <VForm class="d-flex flex-column" style="min-height: 0; flex: 1 1 auto" @submit.prevent="submitMasterAdvance">
        <VCardText class="pa-5" style="overflow-y: auto; flex: 1 1 auto">
          <VAlert v-if="generalError" type="error" class="mb-4">{{ generalError }}</VAlert>

          <VRow dense>
            <VCol cols="12" sm="6">
              <label class="text-body-medium font-weight-600 d-block mb-1">Travel Region</label>
              <VSelect v-model="form.travel_region" :items="travelRegions"  placeholder="Select travel region" :loading="loadingRegions" :disabled="loading" :error-messages="errors.travel_region ? [errors.travel_region] : []"  hide-details="auto"/>
            </VCol>
            <VCol cols="12" sm="6">
              <label class="text-body-medium font-weight-600 d-block mb-1">Grade Min</label>
              <VTextField v-model.number="form.grade_min" type="number" :disabled="loading" :error-messages="errors.grade_min ? [errors.grade_min] : []"  placeholder="Enter grade min" hide-details="auto"/>
            </VCol>
            <VCol cols="12" sm="6">
              <label class="text-body-medium font-weight-600 d-block mb-1">Grade Max</label>
              <VTextField v-model.number="form.grade_max" type="number" :disabled="loading" :error-messages="errors.grade_max ? [errors.grade_max] : []"  placeholder="Enter grade max" hide-details="auto"/>
            </VCol>
            <VCol cols="12" sm="6">
              <label class="text-body-medium font-weight-600 d-block mb-1">Country</label>
              <VTextField v-model="form.country" :disabled="loading" :error-messages="errors.country ? [errors.country] : []"  placeholder="Enter country" hide-details="auto"/>
            </VCol>
            <VCol cols="12" sm="6">
              <label class="text-body-medium font-weight-600 d-block mb-1">Currency</label>
              <VTextField v-model="form.currency" placeholder="USD" maxlength="3" :disabled="loading" :error-messages="errors.currency ? [errors.currency] : []"  hide-details="auto"/>
            </VCol>
            <VCol cols="12" sm="6">
              <label class="text-body-medium font-weight-600 d-block mb-1">Pocket Money Limit</label>
              <VTextField :model-value="formatNumber(form.pocket_money_limit)" placeholder="0" type="text" inputmode="numeric" class="text-right-input" :disabled="loading" :error-messages="errors.pocket_money_limit ? [errors.pocket_money_limit] : []" @input="handleNumberInput('pocket_money_limit', $event)"  hide-details="auto"/>
            </VCol>
            <VCol cols="12" sm="6">
              <label class="text-body-medium font-weight-600 d-block mb-1">Meal Allowance Limit</label>
              <VTextField :model-value="formatNumber(form.meal_allowance_limit)" placeholder="0" type="text" inputmode="numeric" class="text-right-input" :disabled="loading" :error-messages="errors.meal_allowance_limit ? [errors.meal_allowance_limit] : []" @input="handleNumberInput('meal_allowance_limit', $event)"  hide-details="auto"/>
            </VCol>
          </VRow>

</VCardText>

        <VDivider class="flex-shrink-0" />
        <VCardActions class="pa-5 flex-shrink-0">
          <VSpacer />
          <VBtn variant="tonal" :disabled="loading" @click="close">Close</VBtn>
          <VBtn type="submit" color="primary" variant="flat" :loading="loading">Add</VBtn>
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