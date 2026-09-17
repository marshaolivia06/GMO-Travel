<template>
  <div
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/45 p-5"
    @click.self="handleClose"
  >
    <div
      class="w-full max-w-[480px] max-h-[90vh] overflow-hidden rounded-xl bg-white font-sans shadow-[0_20px_50px_rgba(15,23,42,0.20)]"
    >
      <!-- Header -->
      <div
        class="flex items-start justify-between gap-5 border-b border-slate-200 px-6 py-[22px]"
      >
        <div>
          <p
            class="mb-1 text-[10px] font-bold uppercase tracking-[0.14em] text-[#1E4F8A]"
          >
            User
          </p>

          <h2 class="m-0 text-xl font-semibold text-[#172033]">
            Edit User
          </h2>
        </div>

        <button
          type="button"
          aria-label="Close"
          :disabled="loading"
          class="flex h-8 w-8 items-center justify-center rounded-md border-0 bg-slate-100 text-[22px] leading-none text-slate-500 transition hover:bg-slate-200 hover:text-[#172033] disabled:cursor-not-allowed disabled:opacity-60"
          @click="handleClose"
        >
          ×
        </button>
      </div>

      <!-- Scrollable Form -->
      <form
        class="max-h-[calc(90vh-90px)] overflow-y-auto p-6"
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
            class="box-border w-full rounded-[7px] border border-slate-300 bg-white px-[13px] py-[11px] text-[13px] text-[#172033] outline-none transition placeholder:text-slate-400 focus:border-[#1E4F8A] focus:ring-[3px] focus:ring-[#1E4F8A]/10 disabled:cursor-not-allowed disabled:bg-slate-50"
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
            class="box-border w-full rounded-[7px] border border-slate-300 bg-white px-[13px] py-[11px] text-[13px] text-[#172033] outline-none transition placeholder:text-slate-400 focus:border-[#1E4F8A] focus:ring-[3px] focus:ring-[#1E4F8A]/10 disabled:cursor-not-allowed disabled:bg-slate-50"
          />
        </div>

        <!-- New Password -->
        <div class="mb-5 flex flex-col gap-2">
          <label
            for="user-password"
            class="text-[13px] font-semibold text-slate-700"
          >
            New Password
          </label>

          <input
            id="user-password"
            v-model="form.password"
            type="password"
            placeholder="Leave blank to keep current password"
            autocomplete="new-password"
            minlength="8"
            :disabled="loading"
            class="box-border w-full rounded-[7px] border border-slate-300 bg-white px-[13px] py-[11px] text-[13px] text-[#172033] outline-none transition placeholder:text-slate-400 focus:border-[#1E4F8A] focus:ring-[3px] focus:ring-[#1E4F8A]/10 disabled:cursor-not-allowed disabled:bg-slate-50"
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
            placeholder="Re-enter new password"
            autocomplete="new-password"
            minlength="8"
            :required="!!form.password"
            :disabled="loading"
            class="box-border w-full rounded-[7px] border border-slate-300 bg-white px-[13px] py-[11px] text-[13px] text-[#172033] outline-none transition placeholder:text-slate-400 focus:border-[#1E4F8A] focus:ring-[3px] focus:ring-[#1E4F8A]/10 disabled:cursor-not-allowed disabled:bg-slate-50"
          />

          <p
            v-if="
              form.password &&
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
            for="user-role"
            class="text-[13px] font-semibold text-slate-700"
          >
            Role
          </label>

          <SelectTo
            id="user-role"
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
            v-if="error && !form.role"
            class="m-0 text-xs text-red-600"
          >
            {{ error }}
          </p>
        </div>

        <!-- General Error -->
        <div
          v-if="error && form.role"
          class="mb-[18px] rounded-[7px] bg-red-50 px-3 py-[10px] text-xs text-[#b42318]"
        >
          {{ error }}
        </div>

        <!-- Actions -->
        <div
          class="flex justify-end gap-2 pt-1 max-[600px]:flex-col-reverse"
        >
          <button
            type="button"
            :disabled="loading"
            class="rounded-[7px] border-0 bg-slate-100 px-4 py-[9px] text-xs font-semibold text-slate-600 transition hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-60 max-[600px]:w-full"
            @click="handleClose"
          >
            Cancel
          </button>

          <button
            type="submit"
            :disabled="
              loading ||
              rolesLoading ||
              !form.name.trim() ||
              !form.email.trim() ||
              !form.role ||
              (
                form.password &&
                form.password !== form.confirmPassword
              )
            "
            class="rounded-[7px] border-0 bg-green-600 px-4 py-[9px] text-xs font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-60 max-[600px]:w-full"
          >
            {{ loading ? 'Saving...' : 'Update' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'

import {
  updateUser,
  getRoles,
} from '../../../services/userService'

import SelectTo from '../../../../../components/SelectTo.vue'
import swal from '../../../../../plugins/swal'

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits([
  'close',
  'updated',
])

const form = ref({
  name: props.user?.name || '',
  email: props.user?.email || '',
  password: '',
  confirmPassword: '',
  role: props.user?.roles?.[0]?.name || '',
})

const roles = ref([])

const loading = ref(false)
const rolesLoading = ref(false)
const error = ref('')

const fetchRoles = async () => {
  rolesLoading.value = true
  error.value = ''

  try {
    const response = await getRoles()

    roles.value =
      response?.data ??
      response ??
      []
  } catch (err) {
    error.value =
      err?.message ||
      'Failed to load roles.'

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

  if (!form.value.name.trim()) {
    error.value = 'Name is required.'
    return
  }

  if (!form.value.email.trim()) {
    error.value = 'Email is required.'
    return
  }

  if (!form.value.role) {
    error.value = 'Please select a role.'

    await swal.error(
      'Invalid Role',
      'Please select a role.'
    )

    return
  }

  if (
    form.value.password &&
    form.value.password !== form.value.confirmPassword
  ) {
    error.value =
      'Password and confirmation do not match.'

    await swal.error(
      'Password Mismatch',
      'Password and confirmation do not match.'
    )

    return
  }

  if (
    form.value.password &&
    form.value.password.length < 8
  ) {
    error.value =
      'Password must be at least 8 characters.'

    await swal.error(
      'Invalid Password',
      'Password must be at least 8 characters.'
    )

    return
  }

  const result = await swal.confirm(
    'Are you sure you want to update this user?'
  )

  if (!result.isConfirmed) {
    return
  }

  loading.value = true

  try {
    const data = {
      name: form.value.name.trim(),
      email: form.value.email.trim(),
      role: form.value.role,
    }

    if (form.value.password) {
      data.password = form.value.password
      data.password_confirmation =
        form.value.confirmPassword
    }

    await updateUser(
      props.user.id,
      data
    )

    emit('updated')
    emit('close')

    await swal.success(
      'User Updated',
      `User ${form.value.name} has been updated successfully.`
    )
  } catch (err) {
    error.value =
      err?.message ||
      'Failed to update user.'

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
