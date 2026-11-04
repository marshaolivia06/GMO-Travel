<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useToastStore } from '../../../../stores/toast'
import { createExternalFormRequest, updateExternalFormRequest } from '../../services/externalFormRequestService'
import { useConfirm } from '../../../../composables/useConfirm'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  editItem: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue', 'submitted', 'back'])

const toast = useToastStore()
const { confirm } = useConfirm()
const submitting = ref(false)
const isEdit = computed(() => !!props.editItem)

const tripTypes = [
  { title: 'One-Way', value: 'one_way' },
  { title: 'Round-Trip', value: 'round_trip' },
]

const form = reactive({
  transport_mode: null,
  vendor_customer: '',
  purpose: '',
  trip_type: 'one_way',
  travel_from: '',
  departure_date: '',
  travel_to: '',
  return_date: '',
  remark: '',
  travelers: [{ name: '' }],
})

const errors = reactive({})

watch(() => form.trip_type, type => {
  if (type === 'one_way') form.return_date = ''
})

watch(() => props.modelValue, value => {
  if (value) {
    const edit = props.editItem

    Object.assign(form, {
      transport_mode: edit?.transport_mode ?? null,
      vendor_customer: edit?.vendor_customer ?? '',
      purpose: edit?.purpose ?? '',
      trip_type: edit?.trip_type ?? 'one_way',
      travel_from: edit?.travel_from ?? '',
      departure_date: edit?.departure_date ?? '',
      travel_to: edit?.travel_to ?? '',
      return_date: edit?.return_date ?? '',
      remark: edit?.remark ?? '',
      travelers: edit?.travelers?.length ? edit.travelers.map(traveler => ({ name: traveler.name })) : [{ name: '' }],
    })
    Object.keys(errors).forEach(key => delete errors[key])
  }
})

const isValid = computed(() =>
  !!form.transport_mode
  && !!form.vendor_customer.trim()
  && !!form.purpose.trim()
  && !!form.travel_from.trim()
  && !!form.departure_date
  && !!form.travel_to.trim()
  && (form.trip_type === 'one_way' || !!form.return_date)
  && form.travelers.length > 0
  && form.travelers.every(traveler => !!traveler.name.trim()))

function validate() {
  Object.keys(errors).forEach(key => delete errors[key])

  if (!form.transport_mode) errors.transport_mode = 'Transport mode is required.'
  if (!form.vendor_customer.trim()) errors.vendor_customer = 'Vendor / customer is required.'
  if (!form.purpose.trim()) errors.purpose = 'Purpose is required.'
  if (!form.travel_from.trim()) errors.travel_from = 'Travel from is required.'
  if (!form.departure_date) errors.departure_date = 'Departure date is required.'
  if (!form.travel_to.trim()) errors.travel_to = 'Travel to is required.'
  if (form.trip_type === 'round_trip' && !form.return_date) errors.return_date = 'Return date is required.'

  const firstUnnamed = form.travelers.find(traveler => !traveler.name.trim())

  if (firstUnnamed) errors.travelers = 'All traveler names are required.'

  return Object.keys(errors).length === 0
}

function addTraveler() {
  form.travelers.push({ name: '' })
}

function removeTraveler(index) {
  if (form.travelers.length > 1) form.travelers.splice(index, 1)
}

function close() {
  emit('update:modelValue', false)
}

function applyBackendErrors(err) {
  Object.keys(errors).forEach(key => delete errors[key])

  const bag = err.response?.status === 422 ? err.response?.data?.errors : null

  if (!bag) return

  Object.entries(bag).forEach(([key, messages]) => {
    if (key.startsWith('travelers')) errors.travelers = messages[0]
    else errors[key] = messages[0]
  })
}

async function send(action, successMessage) {
  if (submitting.value) return

  submitting.value = true

  try {
    if (props.editItem)
      await updateExternalFormRequest(props.editItem.id, 'airplane_ferry', form, action)
    else
      await createExternalFormRequest('airplane_ferry', form, action)
    toast.success(successMessage)
    emit('submitted')
    close()
  } catch (err) {
    applyBackendErrors(err)
    toast.error(err.response?.data?.message || 'Failed to save external form request.')
  } finally {
    submitting.value = false
  }
}

async function saveDraft() {
  if (submitting.value) return

  const ok = await confirm({ title: 'Save Draft', text: 'Are you sure you want to save this request as a draft?', confirmText: 'Save Draft', color: 'secondary' })

  if (!ok) return

  send('draft', 'External form request saved as draft.')
}

async function submit() {
  if (submitting.value) return

  if (!validate()) {
    toast.error('Please complete all required fields.')

    return
  }

  const ok = await confirm({ title: 'Submit Request', text: 'Are you sure you want to submit this airplane / ferry request?', confirmText: 'Submit', color: 'primary' })

  if (!ok) return

  send('submit', 'External form request submitted successfully.')
}
</script>

<template>
  <VDialog :model-value="modelValue" max-width="1000" scrollable @update:model-value="!$event && close()">
    <VCard rounded="lg">
      <VCardTitle class="d-flex align-center justify-space-between pa-5">
        <div class="d-flex align-center ga-3">
          <VBtn icon="ri-arrow-left-line" variant="tonal" color="primary" size="small" @click="emit('back')" />
          <div class="text-h6 font-weight-bold">{{ isEdit ? 'Edit Airplane / Ferry Request' : 'New Airplane / Ferry Request' }}</div>
        </div>
        <VBtn icon="ri-close-line" variant="text" @click="close" />
      </VCardTitle>
      <VDivider />

      <VCardText class="pa-6" style="max-height: calc(100vh - 220px)">
        <VRow dense>
          <!-- REQUEST INFORMATION -->
          <VCol cols="12">
            <VCard rounded="lg" elevation="2" color="grey-lighten-5" class="h-100">
              <VCardItem class="pb-2 pt-3">
                <VCardTitle class="text-subtitle-2 font-weight-bold pa-0">Request Information</VCardTitle>
              </VCardItem>
              <VCardText class="pt-0 pb-4 px-4">
                <VRow dense>
                  <VCol cols="12" md="6">
                    <div class="text-caption mb-1">1. Transport Mode</div>
                    <VSelect v-model="form.transport_mode" :items="['Airplane', 'Ferry']" variant="outlined" bg-color="white" density="comfortable" hide-details="auto" :error-messages="errors.transport_mode ? [errors.transport_mode] : []">
                      <template #selection="{ item }"><span v-if="form.transport_mode">{{ item.title }}</span><span v-else class="text-medium-emphasis" style="font-size: 13px">Select transport mode</span></template>
                    </VSelect>
                  </VCol>
                  <VCol cols="12" md="6">
                    <div class="text-caption mb-1">2. Vendor / Customer</div>
                    <VTextField v-model="form.vendor_customer" placeholder="Enter vendor or customer name" variant="outlined" bg-color="white" density="comfortable" hide-details="auto" :error-messages="errors.vendor_customer ? [errors.vendor_customer] : []" />
                  </VCol>
                  <VCol cols="12">
                    <div class="text-caption mb-1">3. Purpose</div>
                    <VTextarea v-model="form.purpose" placeholder="Enter the purpose of this request" variant="outlined" bg-color="white" density="comfortable" rows="3" hide-details="auto" :error-messages="errors.purpose ? [errors.purpose] : []" />
                  </VCol>
                </VRow>
              </VCardText>
            </VCard>
          </VCol>

          <!-- TRANSPORT INFORMATION -->
          <VCol cols="12">
            <VCard rounded="lg" elevation="2" color="grey-lighten-5" class="h-100">
              <VCardItem class="pb-2 pt-3">
                <VCardTitle class="text-subtitle-2 font-weight-bold pa-0">Transport Information</VCardTitle>
              </VCardItem>
              <VCardText class="pt-0 pb-4 px-4">
                <VRow dense>
                  <VCol cols="12" md="4">
                    <div class="text-caption mb-1">1. Trip Type</div>
                    <VSelect v-model="form.trip_type" :items="tripTypes" item-title="title" item-value="value" variant="outlined" bg-color="white" density="comfortable" hide-details="auto" :error-messages="errors.trip_type ? [errors.trip_type] : []">
                      <template #selection="{ item }"><span v-if="form.trip_type">{{ item.title }}</span><span v-else class="text-medium-emphasis" style="font-size: 13px">Select trip type</span></template>
                    </VSelect>
                  </VCol>
                  <VCol cols="12" md="4">
                    <div class="text-caption mb-1">2. Travel From</div>
                    <VTextField v-model="form.travel_from" placeholder="e.g. Batam" variant="outlined" bg-color="white" density="comfortable" hide-details="auto" :error-messages="errors.travel_from ? [errors.travel_from] : []" />
                  </VCol>
                  <VCol cols="12" md="4">
                    <div class="text-caption mb-1">3. Departure Date</div>
                    <VTextField v-model="form.departure_date" type="date" variant="outlined" bg-color="white" density="comfortable" hide-details="auto" :error-messages="errors.departure_date ? [errors.departure_date] : []" />
                  </VCol>
                  <VCol cols="12" md="4">
                    <div class="text-caption mb-1">4. Travel To</div>
                    <VTextField v-model="form.travel_to" placeholder="e.g. Jakarta" variant="outlined" bg-color="white" density="comfortable" hide-details="auto" :error-messages="errors.travel_to ? [errors.travel_to] : []" />
                  </VCol>
                  <VCol v-if="form.trip_type === 'round_trip'" cols="12" md="4">
                    <div class="text-caption mb-1">5. Return Date</div>
                    <VTextField v-model="form.return_date" type="date" :min="form.departure_date || undefined" variant="outlined" bg-color="white" density="comfortable" hide-details="auto" :error-messages="errors.return_date ? [errors.return_date] : []" />
                  </VCol>
                </VRow>
              </VCardText>
            </VCard>
          </VCol>

          <!-- REMARK -->
          <VCol cols="12">
            <VCard rounded="lg" elevation="2" color="grey-lighten-5" class="h-100">
              <VCardItem class="pb-2 pt-3">
                <VCardTitle class="text-subtitle-2 font-weight-bold pa-0">Remark</VCardTitle>
              </VCardItem>
              <VCardText class="pt-0 pb-4 px-4">
                <VTextarea v-model="form.remark" placeholder="Enter remark (optional)" variant="outlined" bg-color="white" density="comfortable" rows="3" hide-details="auto" />
              </VCardText>
            </VCard>
          </VCol>

          <!-- TRAVEL INFORMATION -->
          <VCol cols="12">
            <VCard rounded="lg" elevation="2" color="grey-lighten-5" class="h-100">
              <VCardItem class="pb-2 pt-3">
                <VCardTitle class="text-subtitle-2 font-weight-bold pa-0">Traveler Information</VCardTitle>
                <template #append>
                  <VBtn variant="tonal" color="primary" size="small" prepend-icon="ri-add-line" @click="addTraveler">Add Traveler</VBtn>
                </template>
              </VCardItem>
              <VCardText class="pt-0 pb-4 px-4">
                <div v-for="(traveler, index) in form.travelers" :key="index" class="d-flex align-center ga-2 mb-2">
                  <VAvatar size="30" color="primary" variant="tonal" class="text-body-2 font-weight-bold">{{ index + 1 }}</VAvatar>
                  <VTextField v-model="traveler.name" placeholder="Enter traveler / guest name" variant="outlined" bg-color="white" density="comfortable" hide-details="auto" class="flex-grow-1" />
                  <VBtn icon="ri-delete-bin-line" variant="text" color="error" :disabled="form.travelers.length <= 1" @click="removeTraveler(index)" />
                </div>

                <VAlert v-if="errors.travelers" type="error" variant="tonal" density="compact">{{ errors.travelers }}</VAlert>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />
      <VCardActions class="pa-5 ga-2 justify-end">
        <VBtn variant="outlined" color="secondary" @click="close">Cancel</VBtn>
        <VBtn variant="tonal" color="secondary" :disabled="submitting" :loading="submitting" @click="saveDraft">Draft</VBtn>
        <VBtn variant="flat" color="primary" :disabled="submitting" @click="submit">Submit</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
