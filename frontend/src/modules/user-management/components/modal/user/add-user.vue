<template>
  <div
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/45 p-5"
    @click.self="handleClose"
  >
    <div
      v-if="!showConfirmation"
      class="w-full max-w-[480px] overflow-hidden rounded-xl bg-white shadow-[0_20px_50px_rgba(15,23,42,0.20)]"
    >
      <div
        class="flex items-start justify-between gap-5 border-b border-slate-200 px-6 py-[22px]"
      >
        <div>
          <p
            class="mb-[5px] text-[10px] font-bold uppercase tracking-[0.14em] text-[#1E4F8A]"
          >
            User
          </p>

          <h2 class="m-0 text-xl font-semibold text-[#172033]">
            Add User
          </h2>
        </div>

        <button
          type="button"
          class="flex h-8 w-8 items-center justify-center rounded-md border-0 bg-slate-100 text-[22px] leading-none text-slate-500 transition hover:bg-slate-200 hover:text-[#172033] disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="loading"
          @click="handleClose"
        >
          ×
        </button>
      </div>

      <form
        class="p-6"
        @submit.prevent="openConfirmation"
      >
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
            class="box-border w-full rounded-[7px] border border-slate-300 bg-white px-[13px] py-[11px] text-[13px] text-[#172033] outline-none transition placeholder:text-slate-400 focus:border-[#1E4F8A] focus:ring-[3px] focus:ring-[#1E4F8A]/10"
          />
        </div>

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
            class="box-border w-full rounded-[7px] border border-slate-300 bg-white px-[13px] py-[11px] text-[13px] text-[#172033] outline-none transition placeholder:text-slate-400 focus:border-[#1E4F8A] focus:ring-[3px] focus:ring-[#1E4F8A]/10"
          />
        </div>

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
            class="box-border w-full rounded-[7px] border border-slate-300 bg-white px-[13px] py-[11px] text-[13px] text-[#172033] outline-none transition placeholder:text-slate-400 focus:border-[#1E4F8A] focus:ring-[3px] focus:ring-[#1E4F8A]/10"
          />
        </div>

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
            class="box-border w-full rounded-[7px] border border-slate-300 bg-white px-[13px] py-[11px] text-[13px] text-[#172033] outline-none transition placeholder:text-slate-400 focus:border-[#1E4F8A] focus:ring-[3px] focus:ring-[#1E4F8A]/10"
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

        <div class="mb-5 flex flex-col gap-2">
          <label
            for="user-role"
            class="text-[13px] font-semibold text-slate-700"
          >
            Role
          </label>

          <select
            id="user-role"
            v-model="form.role"
            required
            class="box-border w-full rounded-[7px] border border-slate-300 bg-white px-[13px] py-[11px] text-[13px] text-[#172033] outline-none transition focus:border-[#1E4F8A] focus:ring-[3px] focus:ring-[#1E4F8A]/10"
          >
            <option
              value=""
              disabled
            >
              Select role
            </option>

            <option
              v-for="role in roles"
              :key="role.id"
              :value="role.name"
            >
              {{ role.name }}
            </option>
          </select>
        </div>

        <div
          v-if="error"
          class="mb-[18px] rounded-[7px] bg-red-50 px-3 py-2.5 text-xs text-[#b42318]"
        >
          {{ error }}
        </div>

        <div class="flex justify-end gap-2.5 pt-1 max-[600px]:flex-col-reverse">
          <button
            type="button"
            class="rounded-[7px] border-0 bg-slate-100 px-4 py-[9px] text-xs font-semibold text-slate-600 transition hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-60 max-[600px]:w-full"
            :disabled="loading"
            @click="handleClose"
          >
            Cancel
          </button>

          <button
            type="submit"
            class="rounded-[7px] border-0 bg-green-600 px-4 py-[9px] text-xs font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-60 max-[600px]:w-full"
            :disabled="
              loading ||
              !form.confirmPassword ||
              form.password !== form.confirmPassword
            "
          >
            Add
          </button>
        </div>
      </form>
    </div>

    <div
      v-else
      class="box-border w-full max-w-[420px] rounded-xl bg-white px-[25px] py-[30px] text-center shadow-[0_20px_50px_rgba(15,23,42,0.20)] max-[600px]:max-w-[calc(100%-30px)]"
    >
      <div
        class="mx-auto mb-[18px] flex h-[54px] w-[54px] items-center justify-center rounded-full bg-[#eaf2f9] text-[27px] font-bold text-[#1E4F8A]"
      >
        ?
      </div>

      <div>
        <h2 class="m-0 text-[15px] font-semibold leading-[1.5] text-[#172033]">
          Are you sure you want to add this data?
        </h2>
      </div>

      <div
        class="mt-[25px] flex items-center justify-center gap-2.5 max-[600px]:flex-col-reverse"
      >
        <button
          type="button"
          class="min-w-[90px] rounded-[7px] border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60 max-[600px]:w-full"
          :disabled="loading"
          @click="cancelConfirmation"
        >
          Cancel
        </button>

        <button
          type="button"
          class="min-w-[90px] rounded-[7px] border-0 bg-green-600 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-60 max-[600px]:w-full"
          :disabled="loading"
          @click="handleSubmit"
        >
          {{ loading ? 'Menyimpan...' : 'Yes' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'

import {
  createUser,
  getRoles,
} from '../../../services/userService'

import { useToastStore } from '../../../../../stores/toast'

const emit = defineEmits([
  'close',
  'created',
])

const toast = useToastStore()

const form = ref({
  name: '',
  email: '',
  password: '',
  confirmPassword: '',
  role: '',
})

const roles = ref([])

const loading = ref(false)

const error = ref('')

const showConfirmation = ref(false)

const fetchRoles = async () => {
  try {
    roles.value = await getRoles()
  } catch (err) {
    error.value =
      err.message ||
      'Gagal mengambil role.'

    toast.error(
      'Action Failed',
      error.value
    )
  }
}

const openConfirmation = () => {
  error.value = ''

  if (
    form.value.password !==
    form.value.confirmPassword
  ) {
    error.value =
      'Password dan konfirmasi password tidak sama.'

    toast.warning(
      'Password Mismatch',
      'Password dan konfirmasi password tidak sama.'
    )

    return
  }

  showConfirmation.value = true
}

const cancelConfirmation = () => {
  if (loading.value) return

  showConfirmation.value = false
}

const handleClose = () => {
  if (loading.value) return

  emit('close')
}

const handleSubmit = async () => {
  if (loading.value) return

  loading.value = true
  error.value = ''

  try {
    await createUser({
      name: form.value.name,
      email: form.value.email,
      password: form.value.password,
      role: form.value.role,
    })

    toast.success(
      'User Added',
      `User ${form.value.name} berhasil ditambahkan.`
    )

    emit('created')
    emit('close')
  } catch (err) {
    error.value =
      err.message ||
      'Gagal membuat user.'

    toast.error(
      'Action Failed',
      error.value
    )

    showConfirmation.value = false
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchRoles()
})
</script>