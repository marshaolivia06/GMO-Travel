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

const form = reactive({
  purpose: '',
  vendor_customer: '',
  check_in_date: '',
  check_out_date: '',
  guests: [{ name: '' }],
  remark: '',
})

const errors = reactive({})

const initialForm = {
  purpose: '',
  vendor_customer: '',
  check_in_date: '',
  check_out_date: '',
  guests: [{ name: '' }],
  remark: '',
}

watch(() => props.modelValue, value => {
  if (value) {
    Object.assign(form, JSON.parse(JSON.stringify(initialForm)))

    const edit = props.editItem

    Object.assign(form, {
      purpose: edit?.purpose ?? '',
      vendor_customer: edit?.vendor_customer ?? '',
      check_in_date: edit?.check_in_date ?? '',
      check_out_date: edit?.check_out_date ?? '',
      guests: edit?.travelers?.length ? edit.travelers.map(traveler => ({ name: traveler.name })) : [{ name: '' }],
      remark: edit?.remark ?? '',
    })
    Object.keys(errors).forEach(key => delete errors[key])
  }
})

const isValid = computed(() =>
  !!form.purpose.trim()
  && !!form.vendor_customer.trim()
  && !!form.check_in_date
  && !!form.check_out_date
  && form.guests.length > 0
  && form.guests.every(guest => !!guest.name.trim()))

function validate() {
  Object.keys(errors).forEach(key => delete errors[key])

  if (!form.purpose.trim()) errors.purpose = 'Purpose is required.'
  if (!form.vendor_customer.trim()) errors.vendor_customer = 'Vendor / customer is required.'
  if (!form.check_in_date) errors.check_in_date = 'Check-in date is required.'
  if (!form.check_out_date) errors.check_out_date = 'Check-out date is required.'
  else if (form.check_in_date && form.check_out_date < form.check_in_date) errors.check_out_date = 'Check-out date must be after the check-in date.'

  const firstUnnamed = form.guests.find(guest => !guest.name.trim())

  if (firstUnnamed) errors.guests = 'All guest names are required.'

  return Object.keys(errors).length === 0
}

function addGuest() {
  form.guests.push({ name: '' })
}

function removeGuest(index) {
  if (form.guests.length > 1) form.guests.splice(index, 1)
}

function close() {
  emit('update:modelValue', false)
}

function applyBackendErrors(err) {
  Object.keys(errors).forEach(key => delete errors[key])

  const bag = err.response?.status === 422 ? err.response?.data?.errors : null

  if (!bag) return

  Object.entries(bag).forEach(([key, messages]) => {
    if (key.startsWith('travelers')) errors.guests = messages[0]
    else errors[key] = messages[0]
  })
}

async function send(action, successMessage) {
  if (submitting.value) return

  submitting.value = true

  try {
    if (props.editItem)
      await updateExternalFormRequest(props.editItem.id, 'hotel_reservation', form, action)
    else
      await createExternalFormRequest('hotel_reservation', form, action)
    toast.success(successMessage)
    emit('submitted')
    close()
  } catch (err) {
    applyBackendErrors(err)
    toast.error(err.response?.data?.message || 'Failed to save hotel reservation request.')
  } finally {
    submitting.value = false
  }
}

async function saveDraft() {
  if (submitting.value) return

  const ok = await confirm({ title: 'Save Draft', text: 'Are you sure you want to save this request as a draft?', confirmText: 'Save Draft', color: 'secondary' })

  if (!ok) return

  send('draft', 'Hotel reservation request saved as draft.')
}

async function submit() {
  if (submitting.value) return

  if (!validate()) {
    toast.error('Please complete all required fields.')

    return
  }

  const ok = await confirm({ title: 'Submit Request', text: 'Are you sure you want to submit this hotel reservation request?', confirmText: 'Submit', color: 'primary' })

  if (!ok) return

  send('submit', 'Hotel reservation request submitted successfully.')
}
</script>

<template>
  <VDialog :model-value="modelValue" max-width="1000" scrollable @update:model-value="!$event && close()">
    <VCard rounded="lg">
      <VCardTitle class="d-flex align-center justify-space-between pa-5">
        <div class="d-flex align-center ga-3">
          <VBtn icon="ri-arrow-left-line" variant="tonal" color="primary" size="small" @click="emit('back')" />
          <div class="text-h6 font-weight-bold">{{ isEdit ? 'Edit Hotel Reservation Request' : 'New Hotel Reservation Request' }}</div>
        </div>
        <VBtn icon="ri-close-line" variant="text" @click="close" />
      </VCardTitle>
      <VDivider />

      <VCardText class="pa-6" style="max-height: calc(100vh - 220px)">
        <VRow dense>
          <!-- RESERVATION INFORMATION -->
          <VCol cols="12">
            <VCard rounded="lg" elevation="2" color="grey-lighten-5" class="h-100">
              <VCardItem class="pb-2 pt-3">
                <VCardTitle class="text-subtitle-2 font-weight-bold pa-0">Reservation Information</VCardTitle>
              </VCardItem>
              <VCardText class="pt-0 pb-4 px-4">
                <VRow dense>
                  <VCol cols="12" md="6">
                    <div class="text-caption mb-1">1. Purpose</div>
                    <VTextField v-model="form.purpose" placeholder="Enter the purpose of this reservation" variant="outlined" bg-color="white" density="comfortable" hide-details="auto" :error-messages="errors.purpose ? [errors.purpose] : []" />
                  </VCol>
                  <VCol cols="12" md="6">
                    <div class="text-caption mb-1">2. Vendor / Customer</div>
                    <VTextField v-model="form.vendor_customer" placeholder="Enter vendor or customer name" variant="outlined" bg-color="white" density="comfortable" hide-details="auto" :error-messages="errors.vendor_customer ? [errors.vendor_customer] : []" />
                  </VCol>
                  <VCol cols="12" md="6">
                    <div class="text-caption mb-1">3. Check In Date</div>
                    <VTextField v-model="form.check_in_date" type="date" variant="outlined" bg-color="white" density="comfortable" hide-details="auto" :error-messages="errors.check_in_date ? [errors.check_in_date] : []" />
                  </VCol>
                  <VCol cols="12" md="6">
                    <div class="text-caption mb-1">4. Check Out Date</div>
                    <VTextField v-model="form.check_out_date" type="date" :min="form.check_in_date || undefined" variant="outlined" bg-color="white" density="comfortable" hide-details="auto" :error-messages="errors.check_out_date ? [errors.check_out_date] : []" />
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

          <!-- GUEST INFORMATION -->
          <VCol cols="12">
            <VCard rounded="lg" elevation="2" color="grey-lighten-5" class="h-100">
              <VCardItem class="pb-2 pt-3">
                <VCardTitle class="text-subtitle-2 font-weight-bold pa-0">Guest Information</VCardTitle>
                <template #append>
                  <VBtn variant="tonal" color="primary" size="small" prepend-icon="ri-add-line" @click="addGuest">Add Guest</VBtn>
                </template>
              </VCardItem>
              <VCardText class="pt-0 pb-4 px-4">
                <div v-for="(guest, index) in form.guests" :key="index" class="d-flex align-center ga-2 mb-2">
                  <VAvatar size="30" color="primary" variant="tonal" class="text-body-2 font-weight-bold">{{ index + 1 }}</VAvatar>
                  <VTextField v-model="guest.name" placeholder="Enter guest name" variant="outlined" bg-color="white" density="comfortable" hide-details="auto" class="flex-grow-1" />
                  <VBtn icon="ri-delete-bin-line" variant="text" color="error" :disabled="form.guests.length <= 1" @click="removeGuest(index)" />
                </div>

                <VAlert v-if="errors.guests" type="error" variant="tonal" density="compact">{{ errors.guests }}</VAlert>
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
