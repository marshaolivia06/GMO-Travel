<script setup>
import { reactive, ref } from 'vue'
import { useMasterRegionStore } from '../../stores/masterRegionStore'
import { useConfirm } from '../../../../composables/useConfirm'
import { useToastStore } from '../../../../stores/toast'

const props = defineProps({
  item: { type: Object, required: true },
})

const emit = defineEmits(['close'])
const { confirm } = useConfirm()
const toast = useToastStore()
const masterRegionStore = useMasterRegionStore()

const loading = ref(false)
const generalError = ref('')

const currencyOptions = ['IDR', 'USD', 'SGD', 'EUR']

const errors = reactive({
  name: '', coverage: '', currency: '', travel_area_label: '', destinations: '',
})

const form = reactive({
  code: props.item.code,
  name: props.item.name,
  coverage: props.item.coverage,
  currency: props.item.currency,
  travel_area_label: props.item.travel_area_label || '',
  destinations: (props.item.destinations || []).join(', '),
})

function clearErrors() {
  Object.keys(errors).forEach(k => { errors[k] = '' })
  generalError.value = ''
}

function handleValidationError(err) {
  const validationErrors = err?.response?.data?.errors
  if (!validationErrors) return

  Object.keys(errors).forEach(k => { errors[k] = validationErrors[k]?.[0] || '' })

  if (!errors.destinations) {
    const key = Object.keys(validationErrors).find(k => k.startsWith('destinations.'))
    if (key) errors.destinations = validationErrors[key][0]
  }
}

function parseDestinations(text) {
  const names = (text || '').split(',').map(name => name.trim()).filter(Boolean)
  return [...new Set(names)]
}

async function submitMasterRegion() {
  if (loading.value) return

  clearErrors()

  if (!form.name.trim()) { errors.name = 'Region is required.'; return }
  if (!form.coverage.trim()) { errors.coverage = 'Coverage is required.'; return }
  if (!form.currency) { errors.currency = 'Currency is required.'; return }

  const confirmed = await confirm({ title: 'Edit Master Region', text: 'Are you sure you want to update this master region?' })
  if (!confirmed) return

  loading.value = true

  try {
    await masterRegionStore.editMasterRegion(props.item.id, {
      name: form.name.trim(),
      coverage: form.coverage.trim(),
      currency: form.currency,
      travel_area_label: form.travel_area_label.trim() || null,
      destinations: parseDestinations(form.destinations),
    })

    toast.success('Master region has been updated successfully.')
    emit('close')
  } catch (err) {
    handleValidationError(err)
    generalError.value = err.response?.data?.message || err.message || 'Failed to update master region.'
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
        <VCardTitle class="pa-4">Edit Master Region</VCardTitle>
        <VCardSubtitle class="px-4">Update this master region.</VCardSubtitle>
  
        <VForm @submit.prevent="submitMasterRegion">
          <VCardText>
            <VAlert v-if="generalError" type="error" class="mb-4">{{ generalError }}</VAlert>
  
            <div class="text-body-2 mb-1">Code</div>
            <VTextField v-model="form.code" hide-details="auto" class="mb-3" disabled />
  
            <div class="text-body-2 mb-1">Region</div>
            <VTextField v-model="form.name" placeholder="Area I" hide-details="auto" class="mb-3" :disabled="loading" :error-messages="errors.name ? [errors.name] : []" />
  
            <div class="text-body-2 mb-1">Coverage</div>
            <VTextField v-model="form.coverage" placeholder="Asia, Australia, New Zealand" hide-details="auto" class="mb-3" :disabled="loading" :error-messages="errors.coverage ? [errors.coverage] : []" />
  
            <div class="text-body-2 mb-1">Currency</div>
            <VSelect v-model="form.currency" :items="currencyOptions" placeholder="Select currency" hide-details="auto" class="mb-3" :disabled="loading" :error-messages="errors.currency ? [errors.currency] : []" />
  
            <div class="text-body-2 mb-1">Travel Area (optional)</div>
            <VTextField v-model="form.travel_area_label" placeholder="Asia, Australia, New Zealand" hide-details="auto" class="mb-3" :disabled="loading" :error-messages="errors.travel_area_label ? [errors.travel_area_label] : []" />
  
            <div class="text-body-2 mb-1">No Daily Allowance Destinations (optional)</div>
            <VTextField v-model="form.destinations" placeholder="Batam, Medan, Jakarta" hint="Pisahkan dengan koma" persistent-hint hide-details="auto" :disabled="loading" :error-messages="errors.destinations ? [errors.destinations] : []" />
          </VCardText>
  
          <VCardActions class="justify-end gap-2 pa-4">
            <VBtn variant="tonal" :disabled="loading" @click="close">Cancel</VBtn>
            <VBtn type="submit" color="success" variant="flat" :loading="loading">Update</VBtn>
          </VCardActions>
        </VForm>
      </VCard>
    </VDialog>
  </template>