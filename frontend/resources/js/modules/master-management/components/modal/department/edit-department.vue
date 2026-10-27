<script setup>
import { reactive, ref } from 'vue'
import { useDepartmentStore } from '../../../stores/departmentStore'
import { useConfirm } from '../../../../../composables/useConfirm'
import { useToastStore } from '../../../../../stores/toast'

const props = defineProps({
  department: { type: Object, required: true },
})

const emit = defineEmits(['close'])
const { confirm } = useConfirm()
const toast = useToastStore()
const departmentStore = useDepartmentStore()

const loading = ref(false)
const generalError = ref('')

const errors = reactive({
  name: '',
  category_id: '',
  dept_head_id: '',
  dept_admin_id: '',
  division_head_id: '',
})

const form = reactive({
  name: props.department?.name || '',
  category_id: props.department?.category_id ?? props.department?.category?.id ?? '',
  dept_head_id: props.department?.dept_head_id ?? props.department?.dept_head?.id ?? '',
  dept_admin_id: props.department?.dept_admin_id ?? props.department?.dept_admin?.id ?? '',
  division_head_id: props.department?.division_head_id ?? props.department?.division_head?.id ?? '',
})

function clearErrors() {
  errors.name = ''
  errors.category_id = ''
  errors.dept_head_id = ''
  errors.dept_admin_id = ''
  errors.division_head_id = ''
  generalError.value = ''
}

function handleValidationError(err) {
  const validationErrors = err?.response?.data?.errors
  if (!validationErrors) return

  errors.name = validationErrors.name?.[0] || ''
  errors.category_id = validationErrors.category_id?.[0] || ''
  errors.dept_head_id = validationErrors.dept_head_id?.[0] || ''
  errors.dept_admin_id = validationErrors.dept_admin_id?.[0] || ''
  errors.division_head_id = validationErrors.division_head_id?.[0] || ''
}

async function submitDepartment() {
  if (loading.value) return

  clearErrors()

  if (!form.name.trim()) {
    errors.name = 'Department name is required.'
    return
  }
  if (!form.category_id) {
    errors.category_id = 'Category is required.'
    return
  }
  if (!form.dept_head_id) {
    errors.dept_head_id = 'Department head is required.'
    return
  }
  if (!form.dept_admin_id) {
    errors.dept_admin_id = 'Department admin is required.'
    return
  }
  if (!form.division_head_id) {
    errors.division_head_id = 'Division head is required.'
    return
  }

  const confirmed = await confirm({
    title: 'Are you sure',
    text: 'Are you sure you want to update this department?',
  })

  if (!confirmed) return

  loading.value = true

  try {
    await departmentStore.editDepartment(props.department.id, {
      name: form.name.trim(),
      category_id: form.category_id,
      dept_head_id: form.dept_head_id,
      dept_admin_id: form.dept_admin_id,
      division_head_id: form.division_head_id,
    })

    toast.success('Department has been updated successfully.')
    emit('close')
  } catch (err) {
    handleValidationError(err)
    generalError.value = err.response?.data?.message || err.message || 'Failed to update department.'
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
        <span class="text-title-large font-weight-bold text-white">Edit Department</span>
        <VBtn icon="ri-close-line" variant="text" color="white" density="comfortable" @click="close" />
      </div>

      <VForm class="d-flex flex-column" style="min-height: 0; flex: 1 1 auto" @submit.prevent="submitDepartment">
        <VCardText class="pa-5" style="overflow-y: auto; flex: 1 1 auto">
          <VAlert v-if="generalError" type="error" class="mb-4">{{ generalError }}</VAlert>

          <VRow dense>
            <VCol cols="12">
              <label class="text-body-medium font-weight-600 d-block mb-1">Department Name</label>
              <VTextField v-model="form.name" :disabled="loading" :error-messages="errors.name ? [errors.name] : []" placeholder="Enter department name" hide-details="auto"/>
            </VCol>
            <VCol cols="12">
              <label class="text-body-medium font-weight-600 d-block mb-1">Category</label>
              <VAutocomplete v-model="form.category_id" :items="departmentStore.options.categories" item-title="name" item-value="id" clearable :loading="departmentStore.optionsLoading" :disabled="loading || departmentStore.optionsLoading" :error-messages="errors.category_id ? [errors.category_id] : []" placeholder="Select category" hide-details="auto"/>
            </VCol>
            <VCol cols="12">
              <label class="text-body-medium font-weight-600 d-block mb-1">Department Head</label>
              <VAutocomplete v-model="form.dept_head_id" :items="departmentStore.options.dept_heads" item-title="name" item-value="id" clearable :loading="departmentStore.optionsLoading" :disabled="loading || departmentStore.optionsLoading" :error-messages="errors.dept_head_id ? [errors.dept_head_id] : []" placeholder="Select department head" hide-details="auto"/>
            </VCol>
            <VCol cols="12">
              <label class="text-body-medium font-weight-600 d-block mb-1">Department Admin</label>
              <VAutocomplete v-model="form.dept_admin_id" :items="departmentStore.options.dept_admins" item-title="name" item-value="id" clearable :loading="departmentStore.optionsLoading" :disabled="loading || departmentStore.optionsLoading" :error-messages="errors.dept_admin_id ? [errors.dept_admin_id] : []" placeholder="Select department admin" hide-details="auto"/>
            </VCol>
            <VCol cols="12">
              <label class="text-body-medium font-weight-600 d-block mb-1">Division Head</label>
              <VAutocomplete v-model="form.division_head_id" :items="departmentStore.options.division_heads" item-title="division_name" item-value="id" clearable :loading="departmentStore.optionsLoading" :disabled="loading || departmentStore.optionsLoading" :error-messages="errors.division_head_id ? [errors.division_head_id] : []" placeholder="Select division head" hide-details="auto"/>
            </VCol>
          </VRow>

</VCardText>

        <VDivider class="flex-shrink-0" />
        <VCardActions class="pa-5 flex-shrink-0">
          <VSpacer />
  <VBtn variant="tonal" :disabled="loading" @click="close">Close</VBtn>
  <VBtn type="submit" color="primary" variant="flat" :loading="loading" :disabled="departmentStore.optionsLoading">Update</VBtn>
</VCardActions>

      </VForm>
    </VCard>
  </VDialog>
</template>
