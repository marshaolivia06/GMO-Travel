<script setup>
import { onMounted, reactive, ref } from 'vue'
import { createUser, getRoles } from '../../../services/userService'
import swal from '../../../../../plugins/swal'

const emit = defineEmits(['close', 'created'])

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
  name: '',
  email: '',
  password: '',
  confirmPassword: '',
  role: '',
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

  const confirmed = await swal.confirm(
    'Are you sure you want to add this user?'
  )

  if (!confirmed.isConfirmed) return

  loading.value = true

  try {
    await createUser({
      name: form.name.trim(),
      email: form.email.trim(),
      password: form.password,
      role: form.role,
    })

    await swal.success(
      'User Added',
      `User ${form.name} has been added successfully.`
    )

    emit('created')
    emit('close')
  } catch (err) {
    handleValidationError(err)

    generalError.value =
      err?.response?.data?.message ||
      err?.message ||
      'Failed to create user.'
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
        Add User
      </VCardTitle>

      <VCardSubtitle class="px-4">
        Create a new user.
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
            :error-messages="errors.name ? [errors.name] : []"
          />

          <VTextField
            v-model="form.email"
            label="Email"
            type="email"
            class="mb-3"
            :disabled="loading"
            :error-messages="errors.email ? [errors.email] : []"
          />

          <VTextField
            v-model="form.password"
            label="Password"
            type="password"
            class="mb-3"
            :disabled="loading"
            :error-messages="errors.password ? [errors.password] : []"
          />

          <VTextField
            v-model="form.confirmPassword"
            label="Confirm Password"
            type="password"
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
            :error-messages="errors.role ? [errors.role] : []"
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
            Add
          </VBtn>
        </VCardActions>
      </VForm>
    </VCard>
  </VDialog>
</template>
