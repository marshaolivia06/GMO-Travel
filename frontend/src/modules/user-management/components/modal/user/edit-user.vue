<script setup>
import { onMounted, reactive, ref } from 'vue'
import { updateUser, getRoles } from '../../../services/userService'
import swal from '../../../../../plugins/swal'

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['close', 'updated'])

const loading = ref(false)
const rolesLoading = ref(false)
const generalError = ref('')
const roles = ref([])

const errors = reactive({
  name: '',
  email: '',
  password: '',
  confirmPassword: '',
  role: '',
})

const form = reactive({
  name: props.user?.name || '',
  email: props.user?.email || '',
  password: '',
  confirmPassword: '',
  role: props.user?.roles?.[0]?.name || '',
})

function clearErrors() {
  errors.name = ''
  errors.email = ''
  errors.password = ''
  errors.confirmPassword = ''
  errors.role = ''
  generalError.value = ''
}

function handleValidationError(err) {
  const validationErrors = err?.response?.data?.errors

  if (!validationErrors) return

  errors.name = validationErrors.name?.[0] || ''
  errors.email = validationErrors.email?.[0] || ''
  errors.password = validationErrors.password?.[0] || ''
  errors.confirmPassword =
    validationErrors.confirmPassword?.[0] || ''
  errors.role = validationErrors.role?.[0] || ''
}

async function fetchRoles() {
  rolesLoading.value = true
  generalError.value = ''

  try {
    roles.value = await getRoles()
  } catch (err) {
    generalError.value =
      err?.response?.data?.message ||
      err?.message ||
      'Failed to load roles.'
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

  if (form.password) {
    if (form.password.length < 8) {
      errors.password =
        'Password must be at least 8 characters.'
      return
    }

    if (!form.confirmPassword) {
      errors.confirmPassword =
        'Please confirm your new password.'
      return
    }

    if (form.password !== form.confirmPassword) {
      errors.confirmPassword =
        'Password and confirmation do not match.'
      return
    }
  }

  const confirmed = await swal.confirm(
    'Are you sure you want to update this user?'
  )

  if (!confirmed.isConfirmed) return

  loading.value = true

  try {
    const data = {
      name: form.name.trim(),
      email: form.email.trim(),
    }

    if (form.role) {
      data.role = form.role
    }

    if (form.password) {
      data.password = form.password
      data.password_confirmation = form.confirmPassword
    }

    await updateUser(props.user.id, data)

    await swal.success(
      'User Updated',
      `User ${form.name} has been updated successfully.`
    )

    emit('updated')
    emit('close')
  } catch (err) {
    handleValidationError(err)

    generalError.value =
      err?.response?.data?.message ||
      err?.message ||
      'Failed to update user.'

    await swal.error(
      'Action Failed',
      generalError.value
    )
  } finally {
    loading.value = false
  }
}

function close() {
  if (!loading.value) {
    emit('close')
  }
}

onMounted(fetchRoles)
</script>

<template>
  <VDialog
    :model-value="true"
    max-width="480"
    persistent
  >
    <VCard>
      <VCardTitle class="pa-4">
        Edit User
      </VCardTitle>

      <VCardSubtitle class="px-4">
        Update user information.
      </VCardSubtitle>

      <VForm @submit.prevent="submitUser">
        <VCardText>
          <VAlert
            v-if="generalError"
            type="error"
            class="mb-4"
          >
            {{ generalError }}
          </VAlert>

          <VTextField
            v-model="form.name"
            label="Name"
            class="mb-3"
            :disabled="loading"
            :error-messages="
              errors.name ? [errors.name] : []
            "
          />

          <VTextField
            v-model="form.email"
            label="Email"
            type="email"
            class="mb-3"
            :disabled="loading"
            :error-messages="
              errors.email ? [errors.email] : []
            "
          />

          <VTextField
            v-model="form.password"
            label="New Password"
            type="password"
            placeholder="Leave blank to keep current password"
            class="mb-3"
            :disabled="loading"
            :error-messages="
              errors.password ? [errors.password] : []
            "
          />

          <VTextField
            v-model="form.confirmPassword"
            label="Confirm Password"
            type="password"
            placeholder="Re-enter new password"
            class="mb-3"
            :disabled="loading"
            :error-messages="
              errors.confirmPassword
                ? [errors.confirmPassword]
                : []
            "
          />

          <VAutocomplete
            v-model="form.role"
            :items="roles"
            item-title="name"
            item-value="name"
            label="Select Role"
            class="mb-2"
            :loading="rolesLoading"
            :disabled="loading || rolesLoading"
            :error-messages="
              errors.role ? [errors.role] : []
            "
            clearable
          />
        </VCardText>

        <VCardActions class="justify-end gap-2 pa-4">
          <VBtn
            variant="tonal"
            :disabled="loading"
            @click="close"
          >
            Cancel
          </VBtn>

          <VBtn
            type="submit"
            color="success"
            variant="flat"
            :loading="loading"
            :disabled="rolesLoading"
          >
            Update
          </VBtn>
        </VCardActions>
      </VForm>
    </VCard>
  </VDialog>
</template>
