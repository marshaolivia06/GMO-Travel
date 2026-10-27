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
      <VCard rounded="lg" class="d-flex flex-column" style="max-height: 90vh">
      <div class="d-flex align-center justify-space-between px-5 bg-primary rounded-t-lg flex-shrink-0" style="height: 60px">
        <span class="text-title-large font-weight-bold text-white">Edit Master Region</span>
        <VBtn icon="ri-close-line" variant="text" color="white" density="comfortable" @click="close" />
      </div>

      <VForm class="d-flex flex-column" style="min-height: 0; flex: 1 1 auto" @submit.prevent="submitMasterRegion">
          <VCardText class="pa-5" style="overflow-y: auto; flex: 1 1 auto">
            <VAlert v-if="generalError" type="error" class="mb-4">{{ generalError }}</VAlert>

          <VRow dense>
            <VCol cols="12">
              <label class="text-body-medium font-weight-600 d-block mb-1">Code</label>
              <VTextField v-model="form.code" hide-details="auto" disabled />
            </VCol>
            <VCol cols="12">
              <label class="text-body-medium font-weight-600 d-block mb-1">Region</label>
              <VTextField v-model="form.name" placeholder="Area I" hide-details="auto" :disabled="loading" :error-messages="errors.name ? [errors.name] : []" />
            </VCol>
            <VCol cols="12">
              <label class="text-body-medium font-weight-600 d-block mb-1">Coverage</label>
              <VTextField v-model="form.coverage" placeholder="Asia, Australia, New Zealand" hide-details="auto" :disabled="loading" :error-messages="errors.coverage ? [errors.coverage] : []" />
            </VCol>
            <VCol cols="12">
              <label class="text-body-medium font-weight-600 d-block mb-1">Currency</label>
              <VSelect v-model="form.currency" :items="currencyOptions" placeholder="Select currency" hide-details="auto" :disabled="loading" :error-messages="errors.currency ? [errors.currency] : []" />
            </VCol>
            <VCol cols="12">
              <label class="text-body-medium font-weight-600 d-block mb-1">Travel Area (optional)</label>
              <VTextField v-model="form.travel_area_label" placeholder="Asia, Australia, New Zealand" hide-details="auto" :disabled="loading" :error-messages="errors.travel_area_label ? [errors.travel_area_label] : []" />
            </VCol>
            <VCol cols="12">
              <label class="text-body-medium font-weight-600 d-block mb-1">No Daily Allowance Destinations (optional)</label>
              <VTextField v-model="form.destinations" placeholder="Batam, Medan, Jakarta" hint="Separate with commas" persistent-hint hide-details="auto" :disabled="loading" :error-messages="errors.destinations ? [errors.destinations] : []" />
            </VCol>
          </VRow>
        </VCardText>
  
          <VDivider class="flex-shrink-0" />
        <VCardActions class="pa-5 flex-shrink-0">
          <VSpacer />
            <VBtn variant="tonal" :disabled="loading" @click="close">Close</VBtn>
            <VBtn type="submit" color="primary" variant="flat" :loading="loading">Update</VBtn>
          </VCardActions>
        </VForm>
      </VCard>
    </VDialog>
  </template>