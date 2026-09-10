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

      <form
        class="p-6 max-[500px]:px-[18px]"
        @submit.prevent="openConfirmation"
      >
        <div class="mb-6">
          <label
            for="role-name"
            class="mb-2 block text-[13px] font-semibold text-slate-700"
          >
            Role Name
          </label>

          <input
            id="role-name"
            v-model="form.name"
            type="text"
            placeholder="Example: Admin"
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
                Select permissions for this role.
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
  class="grid grid-cols-[1fr_repeat(5,60px)] border-b border-slate-200 bg-slate-100"
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
  class="grid grid-cols-[1fr_repeat(5,60px)] border-b border-slate-200 last:border-b-0"
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
            No permissions have been registered yet.
          </div>
        </div>

        <div
          v-if="error"
          class="mb-4 rounded-lg bg-red-50 px-3 py-2.5 text-xs text-red-700"
        >
          {{ error }}
        </div>

        <div class="flex justify-end gap-2.5 pt-1 max-[500px]:flex-col-reverse">
          <button
            type="button"
            :disabled="saving"
            class="rounded-lg bg-slate-100 px-4 py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-60 max-[500px]:w-full"
            @click="handleClose"
          >
            Cancel
          </button>

          <button
            type="submit"
            :disabled="saving"
            class="rounded-lg bg-green-600 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-60 max-[500px]:w-full"
          >
            Add
          </button>
        </div>
      </form>
    </div>

    <div
      v-else
      class="w-full max-w-[440px] overflow-hidden rounded-2xl bg-white shadow-[0_28px_80px_rgba(15,23,42,0.25)]"
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
          <p class="mx-auto max-w-[330px] text-[15px] font-medium leading-6 text-[#172033]">
            Are you sure you want to add this role?
          </p>
        </div>

        <div class="mt-6 flex items-center justify-center gap-2.5">
          <button
            type="button"
            class="min-w-[90px] rounded-lg bg-slate-100 px-5 py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="loading"
            @click="cancelConfirmation"
          >
            Cancel
          </button>

          <button
            type="button"
            :disabled="saving"
            class="min-w-[100px] rounded-lg bg-green-600 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-60"
            @click="submitRole"
          >
            {{ saving ? 'Saving...' : 'Yes' }}
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

const headerActions = ['view', 'create', 'update', 'delete', 'approve',]

const getAction = (permission) => {
  if (!permission?.name) return ''

  const parts = permission.name.split('.')
  return parts[parts.length - 1].toLowerCase()
}

const permissionGroups = computed(() => {
  const groups = {}

  permissions.value.forEach(permission => {
    const module = permission.module

    if (!module) {
      return
    }

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
    error.value = 'Role name is required.'

    toast.warning(
      'Invalid Role',
      'Role name is required.'
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
      err.message || 'Failed to load permissions.'
  } finally {
    loadingPermissions.value = false
  }
}

const submitRole = async () => {
  if (saving.value) return

  saving.value = true

  try {
    await createRole({
      name: form.value.name.trim(),
      permissions: form.value.permissions,
    })

    toast.success(
      'Role Added',
      'Role has been added successfully.'
    )

    emit('created')
    emit('close')
  } catch (err) {
    toast.error(
      'Action Failed',
      err.message || 'Role already exists or failed to create role.'
    )

    showConfirmation.value = false
  } finally {
    saving.value = false
  }
}

onMounted(fetchPermissions)
</script>