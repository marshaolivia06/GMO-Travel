<script setup>
import { reactive, ref } from 'vue'
import { useMasterAdvanceStore } from '../../stores/masterAdvanceStore'
import { useConfirm } from '../../../../composables/useConfirm'
import { useToastStore } from '../../../../stores/toast'

const emit = defineEmits(['close'])
const { confirm } = useConfirm()
const toast = useToastStore()
const masterAdvanceStore = useMasterAdvanceStore()

const loading = ref(false)
const generalError = ref('')

const errors = reactive({
  travel_region: '',
  currency: '',
  pocket_money_limit: '',
  meal_allowance_limit: '',
})

const form = reactive({
  travel_region: '',
  currency: '',
  pocket_money_limit: '',
  meal_allowance_limit: '',
})

function clearErrors() {
  errors.travel_region = ''
  errors.currency = ''
  errors.pocket_money_limit = ''
  errors.meal_allowance_limit = ''
  generalError.value = ''
}

function handleValidationError(err) {
  const validationErrors = err?.response?.data?.errors
  if (!validationErrors) return

  errors.travel_region = validationErrors.travel_region?.[0] || ''
  errors.currency = validationErrors.currency?.[0] || ''
  errors.pocket_money_limit = validationErrors.pocket_money_limit?.[0] || ''
  errors.meal_allowance_limit = validationErrors.meal_allowance_limit?.[0] || ''
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

async function submitMasterAdvance() {
  if (loading.value) return

  clearErrors()

  if (!form.travel_region.trim()) {
    errors.travel_region = 'Travel region is required.'
    return
  }

  if (!form.currency.trim()) {
    errors.currency = 'Currency is required.'
    return
  }

  if (form.pocket_money_limit === '' || form.pocket_money_limit === null) {
    errors.pocket_money_limit = 'Pocket money limit is required.'
    return
  }

  if (form.meal_allowance_limit === '' || form.meal_allowance_limit === null) {
    errors.meal_allowance_limit = 'Meal allowance limit is required.'
    return
  }

  const confirmed = await confirm({
    title: 'Add Master Advance',
    text: 'Are you sure you want to add this travel advance master?',
  })

  if (!confirmed) return

  loading.value = true

  try {
    await masterAdvanceStore.addTravelAdvanceMaster({
      travel_region: form.travel_region.trim(),
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
</script>

<template>
  <VDialog :model-value="true" max-width="520" persistent>
    <VCard>
      <VCardTitle class="pa-4">Add Master Advance</VCardTitle>
      <VCardSubtitle class="px-4">Create a new travel advance master.</VCardSubtitle>

      <VForm @submit.prevent="submitMasterAdvance">
        <VCardText>
          <VAlert v-if="generalError" type="error" class="mb-4">{{ generalError }}</VAlert>

          <VTextField v-model="form.travel_region" label="Travel Region" class="mb-3" :disabled="loading" :error-messages="errors.travel_region ? [errors.travel_region] : []" />

          <VTextField v-model="form.currency" label="Currency" placeholder="USD" maxlength="3" class="mb-3" :disabled="loading" :error-messages="errors.currency ? [errors.currency] : []" />

          <VTextField :model-value="formatNumber(form.pocket_money_limit)" label="Pocket Money Limit" placeholder="0" type="text" inputmode="numeric" class="mb-3 text-right-input" :disabled="loading" :error-messages="errors.pocket_money_limit ? [errors.pocket_money_limit] : []" @input="handleNumberInput('pocket_money_limit', $event)" />

          <VTextField :model-value="formatNumber(form.meal_allowance_limit)" label="Meal Allowance Limit" placeholder="0" type="text" inputmode="numeric" class="mb-3 text-right-input" :disabled="loading" :error-messages="errors.meal_allowance_limit ? [errors.meal_allowance_limit] : []" @input="handleNumberInput('meal_allowance_limit', $event)" />
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