<template>
  <div
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/45 p-5"
    @click.self="handleClose"
  >
    <div
      v-if="!showConfirmation"
      class="w-full max-w-[560px] max-h-[90vh] overflow-y-auto rounded-xl bg-white shadow-[0_20px_50px_rgba(15,23,42,0.20)]"
    >
      <div
        class="flex items-start justify-between gap-5 border-b border-slate-200 px-[26px] py-6 max-[500px]:px-[18px]"
      >
        <div>
          <p
            class="mb-[5px] text-[10px] font-bold uppercase tracking-[0.14em] text-[#1E4F8A]"
          >
            Role Management
          </p>

          <h2 class="m-0 mb-[5px] text-[22px] font-semibold text-[#172033]">
            Edit Role
          </h2>

          <p class="m-0 text-[13px] text-slate-500">
            Update the role and its assigned permissions.
          </p>
        </div>

        <button
          type="button"
          class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md border-0 bg-slate-100 text-[22px] leading-none text-slate-600 transition hover:bg-slate-200 hover:text-[#172033] disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="loading"
          @click="handleClose"
        >
          ×
        </button>
      </div>

      <form
        class="px-[26px] py-6 max-[500px]:px-[18px]"
        @submit.prevent="openConfirmation"
      >
        <div class="mb-[22px] flex flex-col gap-[7px]">
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
            class="box-border w-full rounded-[7px] border border-slate-300 px-3 py-2.5 text-[13px] text-[#172033] outline-none transition placeholder:text-slate-400 focus:border-[#1E4F8A] focus:ring-[3px] focus:ring-[#1E4F8A]/10"
          />
        </div>

        <div class="mb-[22px] flex flex-col gap-[7px]">
          <label class="text-[13px] font-semibold text-slate-700">
            Permissions
          </label>

          <p class="-mt-0.5 mb-[5px] text-[11px] text-slate-400">
            Select the permissions assigned to this role.
          </p>

          <div
            v-if="loadingPermissions"
            class="rounded-lg bg-slate-50 p-[18px] text-center text-xs text-slate-500"
          >
            Loading permissions...
          </div>

          <div
            v-else-if="permissionError"
            class="rounded-[7px] bg-red-50 px-3 py-2.5 text-xs text-red-700"
          >
            {{ permissionError }}
          </div>

          <div
            v-else
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

                <span class="text-[9px] font-bold uppercase text-[#1E4F8A]">
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
                <div class="flex items-center bg-white px-4 py-3">
                  <span class="text-[12px] font-semibold text-slate-700">
                    {{ group.module }}
                  </span>
                </div>

                <div
                  v-for="action in headerActions"
                  :key="`${group.module}-${action}`"
                  class="flex items-center justify-center border-l border-slate-200 bg-white"
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
        </div>

        <div
          v-if="error"
          class="mb-[18px] rounded-[7px] bg-red-50 px-3 py-2.5 text-xs text-red-700"
        >
          {{ error }}
        </div>

        <div class="flex justify-end gap-2.5 pt-1 max-[500px]:flex-col-reverse">
          <button
            type="button"
            class="rounded-[7px] border-0 bg-slate-100 px-4 py-[9px] text-xs font-semibold text-slate-600 transition hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-60 max-[500px]:w-full"
            :disabled="loading"
            @click="handleClose"
          >
            Cancel
          </button>

          <button
            type="submit"
            class="rounded-[7px] border-0 bg-green-600 px-4 py-[9px] text-xs font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-60 max-[500px]:w-full"
            :disabled="loading"
          >
            Save Changes
          </button>
        </div>
      </form>
    </div>

    <div
      v-else
      class="w-full max-w-[440px] overflow-hidden rounded-2xl bg-white shadow-[0_28px_80px_rgba(15,23,42,0.25)]"
    >
      <div class="h-1.5 w-full bg-gradient-to-r from-[#1E4F8A] via-[#2E6FB4] to-[#1E4F8A]" />

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
            Are you sure you want to update this role?
          </p>
        </div>

        <div
          class="mt-6 flex items-center justify-center gap-2.5 max-[600px]:flex-col-reverse"
        >
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
            class="min-w-[100px] rounded-lg bg-green-600 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-60 max-[600px]:w-full"
            :disabled="loading"
            @click="handleSubmit"
          >
            {{ loading ? 'Saving...' : 'Yes' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'

import { updateRole } from '../../../services/roleService'
import { getPermissions } from '../../../services/permissionService'
import { useToastStore } from '../../../../../stores/toast'

const toast = useToastStore()

const props = defineProps({
  role: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits([
  'close',
  'updated',
])

const form = ref({
  name: props.role.name || '',
  permissions: props.role.permissions?.map(
    permission => permission.id
  ) || [],
})

const permissions = ref([])
const loadingPermissions = ref(false)
const permissionError = ref('')
const loading = ref(false)
const error = ref('')
const showConfirmation = ref(false)

const headerActions = [
  'view',
  'create',
  'update',
  'delete',
  'approve',
]

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
      form.value.permissions.filter(
        id => !ids.includes(id)
      )

    return
  }

  form.value.permissions = [
    ...new Set([
      ...form.value.permissions,
      ...ids,
    ]),
  ]
}

const selectedPermissionGroups = computed(() => {
  return permissionGroups.value
    .map(group => ({
      module: group.module,
      permissions: group.permissions.filter(
        permission =>
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
  if (loading.value) return

  showConfirmation.value = false
}

const handleClose = () => {
  if (loading.value) return

  emit('close')
}

const fetchPermissions = async () => {
  loadingPermissions.value = true
  permissionError.value = ''

  try {
    const response = await getPermissions()

    permissions.value = response.data ?? response
  } catch (err) {
    permissionError.value =
      err.message ||
      'Failed to load permissions.'
  } finally {
    loadingPermissions.value = false
  }
}

const handleSubmit = async () => {
  if (loading.value) return

  loading.value = true
  error.value = ''

  try {
    await updateRole(
      props.role.id,
      {
        name: form.value.name.trim(),
        permissions: form.value.permissions,
      }
    )

    toast.success(
      'Role Updated',
      'Role has been updated successfully.'
    )

    emit('updated')
    emit('close')
  } catch (err) {
  console.log('ROLE UPDATE ERROR:', err)
  console.log('ROLE UPDATE RESPONSE:', err.response?.data)
    error.value =
      err.message ||
      'Failed to update role.'

    toast.error(
      'Action Failed',
      error.value
    )

    showConfirmation.value = false
  } finally {
    loading.value = false
  }
}

onMounted(fetchPermissions)
</script>