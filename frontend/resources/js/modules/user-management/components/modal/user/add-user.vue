<script setup>
import { onMounted, reactive, ref } from 'vue'
import { createUser, getRoles } from '../../../services/userService'
import { useConfirm } from '../../../../../composables/useConfirm'
import { useToastStore } from '../../../../../stores/toast'

const emit = defineEmits(['close', 'created'])

const loading = ref(false)
const rolesLoading = ref(false)
const roles = ref([])
const { confirm } = useConfirm()
const toast = useToastStore()
const rolesError = ref('')

const errors = reactive({
  name: '',
  email: '',
  password: '',
  confirmPassword: '',
  role: '',
  grade: '',
})

const form = reactive({
  name: '',
  email: '',
  password: '',
  confirmPassword: '',
  role: '',
  grade: null,
})

function clearErrors() {
  errors.name = ''
  errors.email = ''
  errors.password = ''
  errors.confirmPassword = ''
  errors.role = ''
  errors.grade = ''
}

function handleValidationError(err) {
  const validationErrors = err?.response?.data?.errors

  if (!validationErrors) return

  errors.name = validationErrors.name?.[0] || ''
  errors.email = validationErrors.email?.[0] || ''
  errors.password = validationErrors.password?.[0] || ''
  errors.confirmPassword = validationErrors.confirmPassword?.[0] || ''
  errors.role = validationErrors.role?.[0] || ''
  errors.grade = validationErrors.grade?.[0] || ''
}

async function fetchRoles() {
  rolesLoading.value = true
  rolesError.value = ''

  try {
    roles.value = await getRoles()
  } catch (err) {
    rolesError.value =
      err?.response?.data?.message ||
      err?.message ||
      'Failed to fetch roles.'
  } finally {
    rolesLoading.value = false
  }
}

async function submitUser() {
  if (loading.value) return

  clearErrors()

  if (!form.name.trim()) {
    errors.name = 'Name is required.'
    return
  }

  if (!form.email.trim()) {
    errors.email = 'Email is required.'
    return
  }

  if (!form.password) {
    errors.password = 'Password is required.'
    return
  }

  if (form.password.length < 8) {
    errors.password = 'Password must be at least 8 characters.'
    return
  }

  if (!form.confirmPassword) {
    errors.confirmPassword = 'Please confirm your password.'
    return
  }

  if (form.password !== form.confirmPassword) {
    errors.confirmPassword = 'Password confirmation does not match.'
    return
  }

  if (!form.role) {
    errors.role = 'Role is required.'
    return
  }

  const isAdminRole = form.role.toLowerCase().startsWith('admin')

if (!isAdminRole && (form.grade === null || form.grade === '')) {
  errors.grade = 'Grade is required.'
  return
}

  const confirmed = await confirm({
    title: 'Add User',
    text: 'Are you sure you want to add this user?',
  })

  if (!confirmed) return

  loading.value = true

  try {
    await createUser({
      name: form.name.trim(),
      email: form.email.trim(),
      password: form.password,
      role: form.role,
      grade: form.grade,
    })

    toast.success(`User ${form.name} has been added successfully.`)

    emit('created')
    emit('close')
  } catch (err) {
  handleValidationError(err)

  const message =
    err?.response?.data?.message ||
    err?.message ||
    'Failed to create user.'

  if (!Object.values(errors).some(Boolean)) {
    errors.email = message
  }
} finally {
    loading.value = false
  }
}

function close() {
  if (!loading.value) emit('close')
}

onMounted(fetchRoles)
</script>

<template>
  <VDialog :model-value="true" max-width="520" persistent>
    <VCard rounded="lg" class="d-flex flex-column" style="max-height: 90vh">
      <div class="d-flex align-center justify-space-between px-5 bg-primary rounded-t-lg flex-shrink-0" style="height: 60px">
        <span class="text-title-large font-weight-bold text-white">Add User</span>
        <VBtn icon="ri-close-line" variant="text" color="white" density="comfortable" @click="close" />
      </div>

      <VForm class="d-flex flex-column" style="min-height: 0; flex: 1 1 auto" @submit.prevent="submitUser">
        <VCardText class="pa-5" style="overflow-y: auto; flex: 1 1 auto">

          <VRow dense>
            <VCol cols="12">
              <label class="text-body-medium font-weight-600 d-block mb-1">Name</label>
              <VTextField v-model="form.name" :disabled="loading" :error-messages="errors.name ? [errors.name] : []" placeholder="Enter name" hide-details="auto"/>
            </VCol>
            <VCol cols="12">
              <label class="text-body-medium font-weight-600 d-block mb-1">Email</label>
              <VTextField v-model="form.email" type="email" :disabled="loading" :error-messages="errors.email ? [errors.email] : []" placeholder="Enter email" hide-details="auto"/>
            </VCol>
            <VCol cols="12">
              <label class="text-body-medium font-weight-600 d-block mb-1">Grade</label>
              <VTextField v-model.number="form.grade" type="number" :disabled="loading" :error-messages="errors.grade ? [errors.grade] : []" placeholder="Enter grade" hide-details="auto"/>
            </VCol>
            <VCol cols="12">
              <label class="text-body-medium font-weight-600 d-block mb-1">Password</label>
              <VTextField v-model="form.password" type="password" :disabled="loading" :error-messages="errors.password ? [errors.password] : []" placeholder="Enter password" hide-details="auto"/>
            </VCol>
            <VCol cols="12">
              <label class="text-body-medium font-weight-600 d-block mb-1">Confirm Password</label>
              <VTextField v-model="form.confirmPassword" type="password" :disabled="loading" :error-messages="errors.confirmPassword ? [errors.confirmPassword] : []" placeholder="Enter confirm password" hide-details="auto"/>
            </VCol>
            <VCol cols="12">
              <label class="text-body-medium font-weight-600 d-block mb-1">Role</label>
              <VAutocomplete v-model="form.role" :items="roles" item-title="name" item-value="name" :loading="rolesLoading" :disabled="loading || rolesLoading" :error-messages="errors.role ? [errors.role] : []" clearable placeholder="Select role" hide-details="auto"/>
            </VCol>
          </VRow>

</VCardText>

        <VDivider class="flex-shrink-0" />
        <VCardActions class="pa-5 flex-shrink-0">
          <VSpacer />
          <VBtn variant="tonal" :disabled="loading" @click="close">Close</VBtn>

          <VBtn
            type="submit"
            color="primary"
            variant="flat"
            :loading="loading"
            :disabled="rolesLoading"
          >
            Add
          </VBtn>
        </VCardActions>
      </VForm>
    </VCard>
  </VDialog>
</template>
