<template>
  <div
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/50 p-5 backdrop-blur-[2px]"
    @click.self="handleClose"
    @keydown.esc="handleClose"
  >
    <!-- =========================
         ADD PERMISSION FORM
    ========================== -->
    <div
      v-if="!showConfirmation"
      class="w-full max-w-[480px] overflow-hidden rounded-2xl bg-white shadow-[0_24px_70px_rgba(15,23,42,0.20)]"
    >
      <!-- Header -->
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
            Add Permission
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
        @submit.prevent="openConfirmation"
      >
        <!-- Module -->
        <div class="mb-6 flex flex-col gap-2">
          <label
            for="permission-module"
            class="text-[13px] font-semibold text-slate-700"
          >
            Module
          </label>

          <input
            id="permission-module"
            v-model="form.module"
            type="text"
            placeholder="Example: User Management"
            required
            class="box-border w-full rounded-lg border border-slate-300 px-[13px] py-[11px] text-[13px] text-[#172033] outline-none transition placeholder:text-slate-400 focus:border-[#1E4F8A] focus:ring-[3px] focus:ring-[#1E4F8A]/10"
          />
        </div>

        <!-- ACTION -->
        <div class="mb-6">
          <label class="mb-1 block text-[13px] font-semibold text-slate-700">
            Action
          </label>

          <p class="mb-3 text-[11px] text-slate-400">
            Select actions
          </p>

          <div class="grid grid-cols-2 gap-3">
            <label
              v-for="action in availableActions"
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

        <!-- Error -->
        <div
          v-if="error"
          class="mb-[18px] rounded-lg bg-red-50 px-3 py-2.5 text-xs text-[#b42318]"
        >
          {{ error }}
        </div>

        <!-- Footer -->
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
            Add
          </button>
        </div>
      </form>
    </div>

    <!-- =========================
         CONFIRMATION MODAL
    ========================== -->
   <!-- =========================
     CONFIRMATION MODAL
========================== -->
<div
  v-else
  class="w-full max-w-[420px] overflow-hidden rounded-xl bg-white shadow-[0_20px_50px_rgba(15,23,42,0.20)] max-[600px]:max-w-[calc(100%-30px)]"
>
  <div class="px-6 pb-6 pt-7">
    <div class="flex justify-center">
      <div
        class="flex h-[68px] w-[68px] items-center justify-center rounded-full bg-[#EAF2F9] text-[#1E4F8A]"
      >
        <span class="text-3xl font-bold">
          ?
        </span>
      </div>
    </div>

    <div class="mt-5 text-center">
      <p
        class="mx-auto max-w-[330px] text-[15px] font-medium leading-6 text-[#172033]"
      >
        Are you sure you want to add this permission?
      </p>
    </div>

    <div
      class="mt-6 flex items-center justify-center gap-2.5 max-[600px]:flex-col-reverse"
    >
      <button
        type="button"
        class="min-w-[90px] rounded-lg bg-slate-100 px-5 py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-60 max-[600px]:w-full"
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
        {{ loading ? 'Adding...' : 'Yes' }}
      </button>
    </div>
  </div>
</div>

  </div>
</template>

<script setup>
import { ref } from 'vue'

import { createPermission } from '../../../services/permissionService'
import { useToastStore } from '../../../../../stores/toast'

const emit = defineEmits([
  'close',
  'created',
])

const toast = useToastStore()

const availableActions = [
  {
    value: 'view',
    label: 'View',
  },
  {
    value: 'create',
    label: 'Create',
  },
  {
    value: 'update',
    label: 'Update',
  },
  {
    value: 'delete',
    label: 'Delete',
  },
  {
    value: 'approve',
    label: 'Approve',
  },
]

const form = ref({
  module: '',
  actions: [
    'view',
    'create',
    'update',
    'delete',
    'approve',
  ],
})

const loading = ref(false)
const error = ref('')
const showConfirmation = ref(false)

const openConfirmation = () => {
  error.value = ''

  if (form.value.actions.length === 0) {
    error.value = 'Please select at least one action.'
    return
  }

  if (!form.value.module.trim()) {
    error.value = 'Module is required.'
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
    const moduleName = form.value.module
      .trim()
      .toLowerCase()
      .replace(/\s+/g, '-')

    const permissions = form.value.actions.map(action => ({
      module: form.value.module.trim(),
      name: `${moduleName}.${action}`,
    }))

    for (const permission of permissions) {
      await createPermission(permission)
    }

    toast.success(
      'Permission Added',
      'Permission has been added successfully.'
    )

    emit('created')
    emit('close')
  } catch (err) {
    toast.error(
      'Action Failed',
      err.message ||
        'Failed to add permission.'
    )

    showConfirmation.value = false
  } finally {
    loading.value = false
  }
}
</script>
