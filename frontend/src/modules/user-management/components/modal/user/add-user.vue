<template>
  <div
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/50 p-5 backdrop-blur-[2px]"
    @click.self="handleClose"
  >
  <div
  class="w-full max-w-[480px] max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-[0_24px_70px_rgba(15,23,42,0.20)]"
>
      <!-- Header -->
      <div
        class="flex items-start justify-between gap-5 border-b border-slate-200 px-6 py-[22px]"
      >
        <div>
          <p
            class="mb-1.5 text-[10px] font-bold uppercase tracking-[0.14em] text-[#1E4F8A]"
          >
            User
          </p>

          <h2 class="m-0 text-xl font-semibold text-[#172033]">
            Add User
          </h2>
        </div>

        <button
          type="button"
          aria-label="Close"
          class="flex h-8 w-8 items-center justify-center rounded-lg border-0 bg-slate-100 text-[22px] leading-none text-slate-500 transition hover:bg-slate-200 hover:text-[#172033] disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="loading"
          @click="handleClose"
        >
          ×
        </button>
      </div>

      <!-- Form -->
      <form
        class="p-6"
        @submit.prevent="handleSubmit"
      >
        <!-- Name -->
        <div class="mb-5 flex flex-col gap-2">
          <label
            for="user-name"
            class="text-[13px] font-semibold text-slate-700"
          >
            Name
          </label>

          <input
            id="user-name"
            v-model="form.name"
            type="text"
            placeholder="Enter name"
            autocomplete="off"
            required
            :disabled="loading"
            class="box-border w-full rounded-lg border border-slate-300 bg-white px-[13px] py-[11px] text-[13px] text-[#172033] outline-none transition placeholder:text-slate-400 focus:border-[#1E4F8A] focus:ring-[3px] focus:ring-[#1E4F8A]/10 disabled:cursor-not-allowed disabled:bg-slate-50"
          />
        </div>

        <!-- Email -->
        <div class="mb-5 flex flex-col gap-2">
          <label
            for="user-email"
            class="text-[13px] font-semibold text-slate-700"
          >
            Email
          </label>

          <input
            id="user-email"
            v-model="form.email"
            type="email"
            placeholder="Enter email"
            autocomplete="off"
            required
            :disabled="loading"
            class="box-border w-full rounded-lg border border-slate-300 bg-white px-[13px] py-[11px] text-[13px] text-[#172033] outline-none transition placeholder:text-slate-400 focus:border-[#1E4F8A] focus:ring-[3px] focus:ring-[#1E4F8A]/10 disabled:cursor-not-allowed disabled:bg-slate-50"
          />
        </div>

        <!-- Password -->
        <div class="mb-5 flex flex-col gap-2">
          <label
            for="user-password"
            class="text-[13px] font-semibold text-slate-700"
          >
            Password
          </label>

          <input
            id="user-password"
            v-model="form.password"
            type="password"
            placeholder="Minimum 8 characters"
            autocomplete="new-password"
            minlength="8"
            required
            :disabled="loading"
            class="box-border w-full rounded-lg border border-slate-300 bg-white px-[13px] py-[11px] text-[13px] text-[#172033] outline-none transition placeholder:text-slate-400 focus:border-[#1E4F8A] focus:ring-[3px] focus:ring-[#1E4F8A]/10 disabled:cursor-not-allowed disabled:bg-slate-50"
          />
        </div>

        <!-- Confirm Password -->
        <div class="mb-5 flex flex-col gap-2">
          <label
            for="user-confirm-password"
            class="text-[13px] font-semibold text-slate-700"
          >
            Confirm Password
          </label>

          <input
            id="user-confirm-password"
            v-model="form.confirmPassword"
            type="password"
            placeholder="Re-enter password"
            autocomplete="new-password"
            minlength="8"
            required
            :disabled="loading"
            class="box-border w-full rounded-lg border border-slate-300 bg-white px-[13px] py-[11px] text-[13px] text-[#172033] outline-none transition placeholder:text-slate-400 focus:border-[#1E4F8A] focus:ring-[3px] focus:ring-[#1E4F8A]/10 disabled:cursor-not-allowed disabled:bg-slate-50"
          />

          <p
            v-if="
              form.confirmPassword &&
              form.password !== form.confirmPassword
            "
            class="m-0 text-xs text-red-600"
          >
            Password confirmation does not match.
          </p>
        </div>

        <!-- Role -->
        <div class="mb-5 flex flex-col gap-2">
          <label
            class="text-[13px] font-semibold text-slate-700"
          >
            Role
          </label>

          <SelectTo
            v-model="form.role"
            :options="roles"
            label-key="name"
            value-key="name"
            placeholder="Select role"
            :loading="rolesLoading"
            :disabled="loading || rolesLoading"
            clearable
          />

          <p
            v-if="roleError"
            class="m-0 text-xs text-red-600"
          >
            {{ roleError }}
          </p>
        </div>

        <!-- General Error -->
        <div
          v-if="error"
          class="mb-[18px] rounded-lg bg-red-50 px-3 py-2.5 text-xs text-[#b42318]"
        >
          {{ error }}
        </div>

        <!-- Actions -->
        <div
          class="flex justify-end gap-2.5 pt-1 max-[600px]:flex-col-reverse"
        >
          <button
            type="button"
            class="rounded-lg border-0 bg-slate-100 px-4 py-[9px] text-xs font-semibold text-slate-600 transition hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-60 max-[600px]:w-full"
            :disabled="loading"
            @click="handleClose"
          >
            Cancel
          </button>

          <button
            type="submit"
            class="rounded-lg border-0 bg-green-600 px-4 py-[9px] text-xs font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-60 max-[600px]:w-full"
            :disabled="
              loading ||
              rolesLoading ||
              !form.confirmPassword ||
              form.password !== form.confirmPassword
            "
          >
            {{ loading ? 'Saving...' : 'Add' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { createUser, getRoles } from '../../../services/userService'
import SelectTo from '../../../../../components/SelectTo.vue'
import swal from '../../../../../plugins/swal'

const emit = defineEmits(['close', 'created'])

const form = ref({
  name: '',
  email: '',
  password: '',
  confirmPassword: '',
  role: '',
})

const roles = ref([])

const loading = ref(false)
const rolesLoading = ref(false)
const error = ref('')
const roleError = ref('')

const fetchRoles = async () => {
  rolesLoading.value = true
  error.value = ''

  try {
    roles.value = await getRoles()
  } catch (err) {
    error.value =
      err?.response?.data?.message ||
      err?.message ||
      'Failed to fetch roles.'

    await swal.error(
      'Action Failed',
      error.value
    )
  } finally {
    rolesLoading.value = false
  }
}

const handleClose = () => {
  if (loading.value) {
    return
  }

  emit('close')
}

const handleSubmit = async () => {
  if (loading.value) {
    return
  }

  error.value = ''
  roleError.value = ''

  if (!form.value.name.trim()) {
    error.value = 'Name is required.'
    return
  }

  if (!form.value.email.trim()) {
    error.value = 'Email is required.'
    return
  }

  if (!form.value.password) {
    error.value = 'Password is required.'
    return
  }

  if (form.value.password.length < 8) {
    error.value =
      'Password must be at least 8 characters.'
    return
  }

  if (!form.value.confirmPassword) {
    error.value =
      'Please confirm your password.'
    return
  }

  if (
    form.value.password !==
    form.value.confirmPassword
  ) {
    error.value =
      'Password and confirmation password do not match.'
    return
  }

  if (!form.value.role) {
    roleError.value = 'Please select a role.'
    return
  }

  const result = await swal.confirm(
    'Are you sure you want to add this user?'
  )

  if (!result.isConfirmed) {
    return
  }

  loading.value = true

  try {
    await createUser({
      name: form.value.name.trim(),
      email: form.value.email.trim(),
      password: form.value.password,
      role: form.value.role,
    })

    emit('created')
    emit('close')

    await swal.success(
      'User Added',
      `User ${form.value.name} has been added successfully.`
    )
  } catch (err) {
    const validationErrors =
      err?.response?.data?.errors

    if (validationErrors) {
      error.value =
        validationErrors.name?.[0] ||
        validationErrors.email?.[0] ||
        validationErrors.password?.[0] ||
        validationErrors.role?.[0] ||
        ''
    }

    if (!error.value) {
      error.value =
        err?.response?.data?.message ||
        err?.message ||
        'Failed to create user.'
    }

    await swal.error(
      'Action Failed',
      error.value
    )
  } finally {
    loading.value = false
  }
}

onMounted(fetchRoles)
</script>
