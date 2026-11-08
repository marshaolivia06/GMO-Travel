<script setup>
import { reactive, ref } from 'vue'
import { updateMasterEmployee, getEmployeeDestinationOptions } from '../../services/masterEmployeeService'
import { useConfirm } from '../../../../composables/useConfirm'
import { useToastStore } from '../../../../stores/toast'

const props = defineProps({
  item: { type: Object, default: null },
})

const emit = defineEmits(['close'])
const { confirm } = useConfirm()
const toast = useToastStore()

const loading = ref(false)
const generalError = ref('')
const destinationOptions = ref([])

const errors = reactive({ grade: '', dateOfJoin: '' })

const form = reactive({
  grade: props.item?.grade ?? null,
  eligibleDestinationId: props.item?.eligible_destination_id ?? null,
  dateOfJoin: '',
  officeLocation: props.item?.office_location || '',
})

const officeLocations = ['Batam', 'Jakarta', 'Singapore']

function normalizeDate(value) {
  if (!value) return ''
  const text = String(value)
  if (/^\d{4}-\d{2}-\d{2}$/.test(text)) return text
  const date = new Date(text)
  return isNaN(date.getTime()) ? '' : `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
}

form.dateOfJoin = normalizeDate(props.item?.date_of_join)

function clearErrors() {
  Object.keys(errors).forEach(key => { errors[key] = '' })
  generalError.value = ''
}

function validate() {
  clearErrors()
  let valid = true
  if (form.grade !== null && form.grade !== '' && (Number(form.grade) < 1 || Number(form.grade) > 255 || isNaN(Number(form.grade)))) { errors.grade = 'Grade must be between 1 and 255.'; valid = false }
  if (form.dateOfJoin && !normalizeDate(form.dateOfJoin)) { errors.dateOfJoin = 'Invalid date.'; valid = false }
  return valid
}

async function submitEditEmployee() {
  if (loading.value || !props.item?.id || !validate()) return

  const confirmed = await confirm({ title: 'Edit Employee', text: 'Are you sure you want to update this employee?' })
  if (!confirmed) return

  loading.value = true

  try {
    await updateMasterEmployee(props.item.id, {
      grade: form.grade === '' ? null : form.grade,
      eligible_destination_id: form.eligibleDestinationId || null,
      date_of_join: form.dateOfJoin || null,
      office_location: form.officeLocation || null,
    })

    toast.success('Employee has been updated successfully.')
    emit('close')
  } catch (err) {
    generalError.value = err.response?.data?.message || err.message || 'Failed to update employee.'
  } finally {
    loading.value = false
  }
}

function close() {
  if (!loading.value) emit('close')
}

getEmployeeDestinationOptions()
  .then(response => {
    const data = response?.data ?? response
    destinationOptions.value = Array.isArray(data) ? data : data?.data ?? []
  })
  .catch(() => { destinationOptions.value = [] })
</script>

<template>
  <VDialog :model-value="true" max-width="520" persistent>
    <VCard rounded="lg" class="d-flex flex-column" style="max-height: 90vh">
      <div class="d-flex align-center justify-space-between px-5 bg-primary rounded-t-lg flex-shrink-0" style="height: 60px">
        <span class="text-title-large font-weight-bold text-white">Edit Employee</span>
        <VBtn icon="ri-close-line" variant="text" color="white" density="comfortable" @click="close" />
      </div>

      <VForm class="d-flex flex-column" style="min-height: 0; flex: 1 1 auto" @submit.prevent="submitEditEmployee">
        <VCardText class="pa-5" style="overflow-y: auto; flex: 1 1 auto">
          <VAlert v-if="generalError" type="error" class="mb-4">{{ generalError }}</VAlert>

          <VRow dense>
            <VCol cols="12">
              <label class="text-body-medium font-weight-600 d-block mb-1">Employee</label>
              <VTextField :model-value="item?.name" hide-details="auto" disabled />
            </VCol>
            <VCol cols="12">
              <label class="text-body-medium font-weight-600 d-block mb-1">Grade</label>
              <VTextField v-model.number="form.grade" type="number" placeholder="Enter grade" hide-details="auto" :disabled="loading" :error-messages="errors.grade ? [errors.grade] : []" />
            </VCol>
            <VCol cols="12">
              <label class="text-body-medium font-weight-600 d-block mb-1">Eligible Destination</label>
              <VAutocomplete
                v-model="form.eligibleDestinationId"
                :items="destinationOptions"
                item-title="city_name"
                item-value="id"
                placeholder="Select destination"
                hide-details="auto"
                clearable
                :disabled="loading"
              />
            </VCol>
            <VCol cols="12">
              <label class="text-body-medium font-weight-600 d-block mb-1">Date of Join</label>
              <VTextField v-model="form.dateOfJoin" type="date" hide-details="auto" :disabled="loading" :error-messages="errors.dateOfJoin ? [errors.dateOfJoin] : []" />
            </VCol>
            <VCol cols="12">
              <label class="text-body-medium font-weight-600 d-block mb-1">Office Location</label>
              <VSelect v-model="form.officeLocation" :items="officeLocations" placeholder="Select office location" hide-details="auto" clearable :disabled="loading" />
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
