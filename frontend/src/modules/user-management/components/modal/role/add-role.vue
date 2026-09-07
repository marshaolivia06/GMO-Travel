<template>
  <div
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/45 p-4"
    @click.self="handleClose"
  >
    <div
      v-if="!showConfirmation"
      class="w-full max-w-[600px] max-h-[90vh] overflow-y-auto rounded-xl bg-white shadow-xl"
    >
      <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
        <div>
          <p class="mb-1 text-[10px] font-bold uppercase tracking-wider text-[#1E4F8A]">
            Role Management
          </p>
          <h2 class="text-xl font-semibold text-[#172033]">
            Add Role
          </h2>
        </div>

        <button
          type="button"
          :disabled="saving"
          class="flex h-8 w-8 items-center justify-center rounded-md bg-slate-100 text-xl text-slate-500 hover:bg-slate-200 disabled:opacity-50"
          @click="handleClose"
        >
          ×
        </button>
      </div>

      <form class="p-6 max-[500px]:px-[18px]" @submit.prevent="openConfirmation">
        <div class="mb-6">
          <label for="role-name" class="mb-2 block text-[13px] font-semibold text-slate-700">
            Role Name
          </label>

          <input
            id="role-name"
            v-model="form.name"
            type="text"
            placeholder="Contoh: Admin"
            required
            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-[13px] outline-none focus:border-[#1E4F8A] focus:ring-4 focus:ring-[#1E4F8A]/10"
          />
        </div>

        <div class="mb-6">
          <div class="mb-3 flex items-center justify-between">
            <div>
              <label class="block text-[13px] font-semibold text-slate-700">
                Assign Permissions
              </label>
              <p class="mt-1 text-[11px] text-slate-400">
                Pilih permission untuk role ini.
              </p>
            </div>

            <button
              type="button"
              :disabled="loadingPermissions || !permissions.length"
              class="rounded-md bg-[#1E4F8A] px-3 py-2 text-[11px] font-semibold text-white hover:bg-[#173E6D] disabled:opacity-50"
              @click="toggleAll"
            >
              {{ isAllSelected ? 'Uncheck All' : 'Check All' }}
            </button>
          </div>

          <div
            v-if="loadingPermissions"
            class="rounded-lg border border-slate-200 bg-slate-50 p-6 text-center text-xs text-slate-500"
          >
            Loading permissions...
          </div>

          <div
            v-else-if="permissionError"
            class="rounded-lg bg-red-50 px-3 py-2.5 text-xs text-red-700"
          >
            {{ permissionError }}
          </div>

          <div
  v-else-if="permissionGroups.length"
  class="overflow-hidden rounded-lg border border-slate-200"
>
  <div
    class="grid grid-cols-[1fr_repeat(4,60px)] border-b border-slate-200 bg-slate-100"
  >
    <div
      class="flex items-center px-4 py-3 text-[10px] font-bold uppercase tracking-wide text-slate-400"
    >
      Module
    </div>

    <label
      v-for="action in headerActions"
      :key="action"
      class="flex cursor-pointer flex-col items-center justify-center gap-1 border-l border-slate-200 py-2.5 transition hover:bg-slate-200"
    >
      <input
        type="checkbox"
        :checked="isActionSelected(action)"
        :disabled="!getActionPermissions(action).length"
        class="h-3.5 w-3.5 accent-[#1E4F8A]"
        @change="toggleAction(action)"
      />

      <span
        class="text-[9px] font-bold uppercase text-[#1E4F8A]"
      >
        {{ action }}
      </span>
    </label>
  </div>

  <div class="max-h-[300px] overflow-y-auto">
    <div
      v-for="group in permissionGroups"
      :key="group.module"
      class="grid grid-cols-[1fr_repeat(4,60px)] border-b border-slate-200 last:border-b-0"
    >
      <div class="flex items-center px-4 py-3">
        <span class="text-[12px] font-semibold text-slate-700">
          {{ group.module }}
        </span>
      </div>

      <div
        v-for="action in headerActions"
        :key="`${group.module}-${action}`"
        class="flex items-center justify-center border-l border-slate-200"
      >
        <template v-if="getPermissionByAction(group, action)">
          <input
            v-model="form.permissions"
            type="checkbox"
            :value="getPermissionByAction(group, action).id"
            class="h-4 w-4 accent-[#1E4F8A]"
          />
        </template>

        <span
          v-else
          class="text-[11px] text-slate-200"
        >
          —
        </span>
      </div>
    </div>
  </div>
          </div>

          <div
            v-else
            class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-5 text-center text-xs text-slate-500"
          >
            Belum ada permission yang terdaftar.
          </div>
        </div>

        <div
          v-if="error"
          class="mb-4 rounded-lg bg-red-50 px-3 py-2.5 text-xs text-red-700"
        >
          {{ error }}
        </div>

        <div class="flex justify-end gap-2.5 max-[500px]:flex-col-reverse">
          <button
            type="button"
            :disabled="saving"
            class="rounded-lg bg-slate-100 px-4 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-200 disabled:opacity-50 max-[500px]:w-full"
            @click="handleClose"
          >
            Cancel
          </button>

          <button
            type="submit"
            :disabled="saving"
            class="rounded-lg bg-green-600 px-4 py-2.5 text-xs font-semibold text-white hover:bg-green-700 disabled:opacity-50 max-[500px]:w-full"
          >
            Save Role
          </button>
        </div>
      </form>
    </div>

    <div
  v-else
  class="w-full max-w-[460px] overflow-hidden rounded-2xl bg-white shadow-[0_30px_90px_rgba(15,23,42,0.25)]"
>
  <div class="h-1.5 bg-[#1E4F8A]" />

  <div class="px-6 pb-6 pt-6">
    <div class="flex items-start justify-between">
      <div>
        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#1E4F8A]">
          Role Management
        </p>

        <h2 class="mt-1 text-xl font-semibold text-[#172033]">
          Confirm New Role
        </h2>
      </div>

      <div
        class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#EAF2F9] text-[#1E4F8A]"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-5 w-5"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="1.8"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M9 12l2 2 4-4"
          />

          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M12 3l7 3v5c0 4.5-2.9 8.5-7 10-4.1-1.5-7-5.5-7-10V6l7-3z"
          />
        </svg>
      </div>
    </div>

    <p class="mt-2 text-xs leading-5 text-slate-500">
      Review the role information and assigned permissions before saving.
    </p>

    <div
      class="mt-5 overflow-hidden rounded-xl border border-slate-200"
    >
      <div class="bg-gradient-to-r from-[#F5F8FB] to-white px-4 py-4">
        <p class="text-[9px] font-bold uppercase tracking-[0.12em] text-slate-400">
          Role Name
        </p>

        <div class="mt-2 flex items-center gap-3">
          <div
            class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#1E4F8A] text-xs font-bold text-white"
          >
            {{ form.name.trim().charAt(0).toUpperCase() }}
          </div>

          <p class="text-base font-semibold text-[#172033]">
            {{ form.name.trim() }}
          </p>
        </div>
      </div>

      <div class="border-t border-slate-200 bg-slate-50 px-4 py-4">
        <div class="mb-3 flex items-center justify-between">
          <div>
            <p class="text-[9px] font-bold uppercase tracking-[0.12em] text-slate-400">
              Assigned Permissions
            </p>

            <p class="mt-1 text-[11px] text-slate-500">
              Permissions that will be assigned to this role.
            </p>
          </div>

          <span
            class="rounded-full bg-[#1E4F8A]/10 px-2.5 py-1 text-[10px] font-bold text-[#1E4F8A]"
          >
            {{ form.permissions.length }}
          </span>
        </div>

        <div
          v-if="selectedPermissionGroups.length"
          class="max-h-[220px] space-y-2 overflow-y-auto pr-1"
        >
          <div
            v-for="group in selectedPermissionGroups"
            :key="group.module"
            class="rounded-lg border border-slate-200 bg-white p-3"
          >
            <div class="mb-2 flex items-center gap-2">
              <span
                class="h-1.5 w-1.5 rounded-full bg-[#1E4F8A]"
              />

              <span class="text-[11px] font-semibold text-[#172033]">
                {{ group.module }}
              </span>

              <span class="text-[9px] text-slate-400">
                {{ group.permissions.length }}
              </span>
            </div>

            <div class="flex flex-wrap gap-1.5">
              <span
                v-for="permission in group.permissions"
                :key="permission.id"
                class="rounded-md bg-[#EAF2F9] px-2 py-1 text-[9px] font-semibold capitalize text-[#1E4F8A]"
              >
                {{ getAction(permission) }}
              </span>
            </div>
          </div>
        </div>

        <div
          v-else
          class="rounded-lg border border-dashed border-slate-200 bg-white p-4 text-center text-[11px] text-slate-400"
        >
          No permissions selected.
        </div>
      </div>
    </div>

    <div class="mt-5 flex gap-2.5 max-[500px]:flex-col-reverse">
  <button
    type="button"
    :disabled="saving"
    class="flex-1 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
    @click="cancelConfirmation"
  >
    Go Back
  </button>

  <button
    type="button"
    :disabled="saving"
    class="flex-1 rounded-lg bg-green-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-green-700 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50"
    @click="submitRole"
  >
    <span class="inline-flex items-center justify-center gap-2">
      <svg
        v-if="!saving"
        xmlns="http://www.w3.org/2000/svg"
        class="h-4 w-4"
        fill="none"
        viewBox="0 0 24 24"
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
        fill="none"
        viewBox="0 0 24 24"
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

      {{ saving ? 'Menyimpan...' : 'Confirm & Add Role' }}
    </span>
  </button>
</div>

  </div>
</div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'

import { createRole } from '../../../services/roleService'
import { getPermissions } from '../../../services/permissionService'
import { useToastStore } from '../../../../../stores/toast'

const toast = useToastStore()

const emit = defineEmits(['close', 'created'])

const form = ref({
  name: '',
  permissions: [],
})

const permissions = ref([])
const loadingPermissions = ref(false)
const permissionError = ref('')
const saving = ref(false)
const error = ref('')
const showConfirmation = ref(false)

const headerActions = ['view', 'create', 'update', 'delete']

const getAction = (permission) => {
  if (!permission?.name) return ''

  const parts = permission.name.split('.')
  return parts[parts.length - 1].toLowerCase()
}

const permissionGroups = computed(() => {
  const groups = {}

  permissions.value.forEach(permission => {
    const module = permission.module || 'Other'

    if (!groups[module]) {
      groups[module] = []
    }

    groups[module].push(permission)
  })

  return Object.keys(groups).map(module => ({
    module,
    permissions: groups[module],
  }))
})

const getPermissionByAction = (group, action) => {
  return group.permissions.find(
    permission => getAction(permission) === action
  )
}

const getActionPermissions = (action) => {
  return permissions.value.filter(
    permission => getAction(permission) === action
  )
}

const isActionSelected = (action) => {
  const list = getActionPermissions(action)

  return list.length > 0 &&
    list.every(permission =>
      form.value.permissions.includes(permission.id)
    )
}

const toggleAction = (action) => {
  const list = getActionPermissions(action)
  const ids = list.map(permission => permission.id)

  if (isActionSelected(action)) {
    form.value.permissions =
      form.value.permissions.filter(id => !ids.includes(id))
    return
  }

  form.value.permissions = [
    ...new Set([
      ...form.value.permissions,
      ...ids,
    ]),
  ]
}

const isAllSelected = computed(() => {
  return permissions.value.length > 0 &&
    permissions.value.every(permission =>
      form.value.permissions.includes(permission.id)
    )
})

const toggleAll = () => {
  form.value.permissions = isAllSelected.value
    ? []
    : permissions.value.map(permission => permission.id)
}

const selectedPermissionGroups = computed(() => {
  return permissionGroups.value
    .map(group => ({
      module: group.module,
      permissions: group.permissions.filter(permission =>
        form.value.permissions.includes(permission.id)
      ),
    }))
    .filter(group => group.permissions.length)
})

const openConfirmation = () => {
  error.value = ''

  if (!form.value.name.trim()) {
    error.value = 'Role name wajib diisi.'

    toast.warning(
      'Invalid Role',
      'Role name wajib diisi.'
    )

    return
  }

  showConfirmation.value = true
}

const cancelConfirmation = () => {
  if (!saving.value) {
    showConfirmation.value = false
  }
}

const handleClose = () => {
  if (!saving.value) {
    emit('close')
  }
}

const fetchPermissions = async () => {
  loadingPermissions.value = true
  permissionError.value = ''

  try {
    const response = await getPermissions()
    permissions.value = response.data || response
  } catch (err) {
    permissionError.value =
      err.message || 'Gagal mengambil permissions.'
  } finally {
    loadingPermissions.value = false
  }
}

const submitRole = async () => {
  if (saving.value) return

  saving.value = true
  error.value = ''

  try {
    await createRole({
      name: form.value.name.trim(),
      permissions: form.value.permissions,
    })

    toast.success(
      'Role Added',
      'Role berhasil ditambahkan.'
    )

    emit('created')
    emit('close')
  } catch (err) {
    error.value =
      err.message || 'Gagal membuat role.'

    toast.error(
      'Action Failed',
      error.value
    )

    showConfirmation.value = false
  } finally {
    saving.value = false
  }
}

onMounted(fetchPermissions)
</script>