<template>
  <div
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/50 p-5 backdrop-blur-[2px]"
    @click.self="handleClose"
    @keydown.esc="handleClose"
  >
    <div
      class="w-full max-w-[600px] max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-[0_24px_70px_rgba(15,23,42,0.20)]"
    >
      <div
        class="flex items-start justify-between gap-5 border-b border-slate-200 px-6 py-[22px]"
      >
        <div>
          <p
            class="mb-1.5 text-[10px] font-bold uppercase tracking-[0.14em] text-[#1E4F8A]"
          >
            Role Management
          </p>

          <h2 class="m-0 text-xl font-semibold text-[#172033]">
            Add Role
          </h2>
        </div>

        <button
          type="button"
          aria-label="Close"
          :disabled="saving"
          class="flex h-8 w-8 items-center justify-center rounded-lg border-0 bg-slate-100 text-[22px] leading-none text-slate-500 transition hover:bg-slate-200 hover:text-[#172033] disabled:cursor-not-allowed disabled:opacity-60"
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
            for="role-name"
            class="text-[13px] font-semibold text-slate-700"
          >
            Role Name
          </label>

          <input
            id="role-name"
            v-model="form.name"
            type="text"
            placeholder="Example: Admin"
            required
            class="box-border w-full rounded-lg border border-slate-300 px-[13px] py-[11px] text-[13px] text-[#172033] outline-none transition placeholder:text-slate-400 focus:border-[#1E4F8A] focus:ring-[3px] focus:ring-[#1E4F8A]/10"
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
              class="rounded-md bg-[#1E4F8A] px-3 py-2 text-[11px] font-semibold text-white transition hover:bg-[#173E6D] disabled:cursor-not-allowed disabled:opacity-50"
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
          class="mb-[18px] rounded-lg bg-red-50 px-3 py-2.5 text-xs text-[#b42318]"
        >
          {{ error }}
        </div>

        <div
          class="flex justify-end gap-2.5 pt-1 max-[500px]:flex-col-reverse"
        >
          <button
            type="button"
            :disabled="saving"
            class="rounded-lg border-0 bg-slate-100 px-4 py-[9px] text-xs font-semibold text-slate-600 transition hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-60 max-[500px]:w-full"
            @click="handleClose"
          >
            Cancel
          </button>

          <button
            type="submit"
            :disabled="saving"
            class="rounded-lg border-0 bg-green-600 px-4 py-[9px] text-xs font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-60 max-[500px]:w-full"
          >
            Add
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'

import { createRole } from '../../../services/roleService'
import { getPermissions } from '../../../services/permissionService'
import swal from '../../../../../plugins/swal'

const emit = defineEmits([
  'close',
  'created',
])

const form = ref({
  name: '',
  permissions: [],
})

const permissions = ref([])
const loadingPermissions = ref(false)
const permissionError = ref('')
const saving = ref(false)
const error = ref('')

const headerActions = [
  'view',
  'create',
  'update',
  'delete',
  'approve',
]

const getAction = permission => {
  if (!permission?.name) {
    return ''
  }

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

const getActionPermissions = action => {
  return permissions.value.filter(
    permission => getAction(permission) === action
  )
}

const isActionSelected = action => {
  const list = getActionPermissions(action)

  return (
    list.length > 0 &&
    list.every(permission =>
      form.value.permissions.includes(permission.id)
    )
  )
}

const toggleAction = action => {
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
  return (
    permissions.value.length > 0 &&
    permissions.value.every(permission =>
      form.value.permissions.includes(permission.id)
    )
  )
})

const toggleAll = () => {
  form.value.permissions = isAllSelected.value
    ? []
    : permissions.value.map(permission => permission.id)
}

const openConfirmation = async () => {
  error.value = ''

  form.value.name = form.value.name.trim()

  if (!form.value.name) {
    error.value = 'Role name is required.'
    return
  }

  const result = await swal.confirm(
    'Are you sure you want to add this role?'
  )

  if (!result.isConfirmed) {
    return
  }

  await submitRole()
}

const handleClose = () => {
  if (saving.value) {
    return
  }

  emit('close')
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
  if (saving.value) {
    return
  }

  saving.value = true
  error.value = ''

  try {
    await createRole({
      name: form.value.name,
      permissions: form.value.permissions,
    })

    emit('created')
    emit('close')

    await swal.success(
      'Role Added',
      'Role has been added successfully.'
    )
  } catch (err) {
    await swal.error(
      'Action Failed',
      err.message ||
        'Role already exists or failed to create role.'
    )
  } finally {
    saving.value = false
  }
}

onMounted(fetchPermissions)
</script>