<template>
  <div
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/50 p-5 backdrop-blur-[2px]"
    @click.self="handleClose"
    @keydown.esc="handleClose"
  >
    <div
      v-if="!showConfirmation"
      class="w-full max-w-[480px] overflow-hidden rounded-2xl bg-white shadow-[0_24px_70px_rgba(15,23,42,0.20)]"
    >
      <div
        class="flex items-start justify-between gap-5 border-b border-slate-200 px-6 py-[22px]"
      >
        <div>
          <p
            class="mb-1.5 text-[10px] font-bold uppercase tracking-[0.14em] text-[#1E4F8A]"
          >
            Permission
          </p>

          <h2 class="m-0 text-xl font-semibold text-[#172033]">
            Edit Permission
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

      <form
        class="p-6"
        @submit.prevent="openConfirmation"
      >
        <div class="mb-6 flex flex-col gap-2">
          <label
            for="edit-permission-module"
            class="text-[13px] font-semibold text-slate-700"
          >
            Module
          </label>

          <input
            id="edit-permission-module"
            v-model="form.module"
            type="text"
            placeholder="Contoh: User Management"
            required
            class="box-border w-full rounded-lg border border-slate-300 px-[13px] py-[11px] text-[13px] text-[#172033] outline-none transition placeholder:text-slate-400 focus:border-[#1E4F8A] focus:ring-[3px] focus:ring-[#1E4F8A]/10"
          />
        </div>

        <div class="mb-6">
          <label
            class="mb-1 block text-[13px] font-semibold text-slate-700"
          >
            Action
          </label>

          <p class="mb-3 text-[11px] text-slate-400">
            Select Action
          </p>

          <div class="grid grid-cols-2 gap-3">
            <label
              v-for="action in ACTIONS"
              :key="action.value"
              class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-[#1E4F8A]/40 hover:bg-[#1E4F8A]/5"
              :class="{
                'border-[#1E4F8A] bg-[#1E4F8A]/5':
                  form.actions.includes(action.value)
              }"
            >
              <input
                v-model="form.actions"
                type="checkbox"
                :value="action.value"
                class="h-4 w-4 cursor-pointer accent-[#1E4F8A]"
              />

              <span class="text-[13px] font-medium text-slate-700">
                {{ action.label }}
              </span>
            </label>
          </div>
        </div>

        <div
          v-if="error"
          class="mb-[18px] rounded-lg bg-red-50 px-3 py-2.5 text-xs text-[#b42318]"
        >
          {{ error }}
        </div>

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
            :disabled="loading"
          >
            Update
          </button>
        </div>
      </form>
    </div>

    <div
      v-else
      class="w-full max-w-[440px] overflow-hidden rounded-2xl bg-white shadow-[0_28px_80px_rgba(15,23,42,0.25)]"
    >
      <div
        class="h-1.5 w-full bg-gradient-to-r from-[#1E4F8A] via-[#2E6FB4] to-[#1E4F8A]"
      />

      <div class="px-6 pb-6 pt-7">
        <div class="flex justify-center">
          <div
            class="flex h-[68px] w-[68px] items-center justify-center rounded-2xl bg-[#EAF2F9] text-[#1E4F8A] shadow-sm"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-8 w-8"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 3l7 3v5c0 4.5-2.9 8.5-7 10-4.1-1.5-7-5.5-7-10V6l7-3z"
              />

              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M9 12l2 2 4-4"
              />
            </svg>
          </div>
        </div>

        <div class="mt-5 text-center">
          <h2
            class="m-0 text-lg font-semibold text-[#172033]"
          >
            Confirm Permission Update
          </h2>

          <p
            class="mx-auto mt-2 max-w-[330px] text-[13px] leading-5 text-slate-500"
          >
            Please review the permission details below before updating it.
          </p>
        </div>

        <div
          class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-slate-50"
        >
          <div class="border-b border-slate-200 px-4 py-3.5">
            <p
              class="mb-1.5 text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400"
            >
              Module
            </p>

            <p
              class="m-0 break-words text-[15px] font-semibold text-[#172033]"
            >
              {{ form.module.trim() }}
            </p>
          </div>

          <div class="px-4 py-3.5">
            <div class="mb-2.5 flex items-center justify-between">
              <p
                class="m-0 text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400"
              >
                Actions
              </p>

              <span
                class="rounded-full bg-white px-2 py-0.5 text-[10px] font-semibold text-slate-500 shadow-sm"
              >
                {{ form.actions.length }} selected
              </span>
            </div>

            <div class="flex flex-wrap gap-2">
              <span
                v-for="action in form.actions"
                :key="action"
                class="inline-flex items-center gap-1.5 rounded-lg border border-[#1E4F8A]/10 bg-white px-2.5 py-1.5 text-[11px] font-semibold capitalize text-[#1E4F8A] shadow-sm"
              >
                <span
                  class="h-1.5 w-1.5 rounded-full bg-[#1E4F8A]"
                />

                {{ action }}
              </span>
            </div>
          </div>
        </div>

        <div
          class="mt-6 flex items-center justify-end gap-2.5 max-[600px]:flex-col-reverse"
        >
          <button
            type="button"
            class="min-w-[100px] rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-60 max-[600px]:w-full"
            :disabled="loading"
            @click="cancelConfirmation"
          >
            Cancel
          </button>

          <button
            type="button"
            class="inline-flex min-w-[140px] items-center justify-center gap-2 rounded-lg border-0 bg-green-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-green-700 hover:shadow disabled:cursor-not-allowed disabled:opacity-60 max-[600px]:w-full"
            :disabled="loading"
            @click="handleSubmit"
          >
            <svg
              v-if="!loading"
              xmlns="http://www.w3.org/2000/svg"
              class="h-4 w-4"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M5 12l4 4L19 6"
              />
            </svg>

            <svg
              v-else
              xmlns="http://www.w3.org/2000/svg"
              class="h-4 w-4 animate-spin"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <circle
                cx="12"
                cy="12"
                r="9"
                class="opacity-25"
              />

              <path
                d="M21 12a9 9 0 00-9-9"
                class="opacity-75"
              />
            </svg>

            {{ loading ? 'Menyimpan...' : 'Yes, Update Permission' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

import {
  updatePermissionsByModule,
} from '../../../services/permissionService'

import { useToastStore } from '../../../../../stores/toast'

const props = defineProps({
  permission: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits([
  'close',
  'updated',
])

const toast = useToastStore()

const ACTIONS = [
  {
    label: 'View',
    value: 'view',
  },
  {
    label: 'Create',
    value: 'create',
  },
  {
    label: 'Update',
    value: 'update',
  },
  {
    label: 'Delete',
    value: 'delete',
  },
]

const form = ref({
  module: '',
  actions: [],
})

const loading = ref(false)
const error = ref('')
const showConfirmation = ref(false)

const loadPermission = permission => {
  if (!permission) {
    return
  }

  form.value = {
    module: permission.module || '',
    actions: Array.isArray(permission.actions)
      ? [...permission.actions]
      : [],
  }

  if (
    form.value.actions.length === 0 &&
    Array.isArray(permission.items)
  ) {
    form.value.actions = permission.items
      .map(item => {
        if (!item?.name) {
          return null
        }

        return item.name
          .split('.')
          .pop()
          .toLowerCase()
      })
      .filter(action =>
        ACTIONS.some(item => item.value === action)
      )
  }
}

watch(
  () => props.permission,
  permission => {
    loadPermission(permission)
  },
  {
    immediate: true,
    deep: true,
  }
)

const openConfirmation = () => {
  error.value = ''

  form.value.module =
    form.value.module.trim()

  if (!form.value.module) {
    error.value = 'Module wajib diisi.'
    return
  }

  if (form.value.actions.length === 0) {
    error.value = 'Pilih minimal satu action.'
    return
  }

  showConfirmation.value = true
}

const cancelConfirmation = () => {
  if (loading.value) {
    return
  }

  showConfirmation.value = false
}

const handleClose = () => {
  if (loading.value) {
    return
  }

  emit('close')
}

const handleSubmit = async () => {
  if (
    loading.value ||
    !props.permission?.module
  ) {
    return
  }

  loading.value = true
  error.value = ''

  const oldModule =
    props.permission.module

  const newModule =
    form.value.module.trim()

  const actions = [
    ...new Set(form.value.actions),
  ]

  try {
    await updatePermissionsByModule(
      oldModule,
      newModule,
      actions
    )

    toast.success(
      'Permission Updated',
      'Permission berhasil diperbarui.'
    )

    emit('updated')
    emit('close')
  } catch (err) {
    error.value =
      err.message ||
      'Gagal mengubah permission.'

    toast.error(
      'Action Failed',
      error.value
    )

    showConfirmation.value = false
  } finally {
    loading.value = false
  }
}
</script>
