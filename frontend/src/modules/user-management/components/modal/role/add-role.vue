<script setup>
import { computed, onMounted, ref } from 'vue'
import { createRole } from '../../../services/roleService'
import { getPermissions } from '../../../services/permissionService'
import swal from '../../../../../plugins/swal'

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
const nameError = ref('')

const headerActions = [
  'view',
  'create',
  'update',
  'delete',
  'approve',
]

const getAction = permission => {
  if (!permission?.name) return ''

  const parts = permission.name.split('.')

  return parts[parts.length - 1].toLowerCase()
}

const permissionGroups = computed(() => {
  const groups = {}

  permissions.value.forEach(permission => {
    const module = permission.module

    if (!module) return

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

function clearErrors() {
  error.value = ''
  nameError.value = ''
}

async function fetchPermissions() {
  loadingPermissions.value = true
  permissionError.value = ''

  try {
    const response = await getPermissions()

    permissions.value = response.data || response
  } catch (err) {
    permissionError.value =
      err?.response?.data?.message ||
      err?.message ||
      'Failed to load permissions.'
  } finally {
    loadingPermissions.value = false
  }
}

async function openConfirmation() {
  if (saving.value) return

  clearErrors()

  form.value.name = form.value.name.trim()

  if (!form.value.name) {
    nameError.value = 'Role name is required.'
    return
  }

  const confirmed = await swal.confirm(
    'Are you sure you want to add this role?'
  )

  if (!confirmed.isConfirmed) return

  await submitRole()
}

async function submitRole() {
  if (saving.value) return

  saving.value = true
  error.value = ''

  try {
    await createRole({
      name: form.value.name,
      permissions: form.value.permissions,
    })

    await swal.success(
      'Role Added',
      'Role has been added successfully.'
    )

    emit('created')
    emit('close')
  } catch (err) {
    error.value =
      err?.response?.data?.message ||
      err?.message ||
      'Role already exists or failed to create role.'

    await swal.error(
      'Action Failed',
      error.value
    )
  } finally {
    saving.value = false
  }
}

function handleClose() {
  if (!saving.value) {
    emit('close')
  }
}

onMounted(fetchPermissions)
</script>

<template>
  <VDialog
    :model-value="true"
    max-width="700"
    persistent
  >
    <VCard>
      <VCardTitle class="pa-4">
        Add Role
      </VCardTitle>

      <VCardSubtitle class="px-4">
        Create a new role and assign permissions.
      </VCardSubtitle>

      <VForm @submit.prevent="openConfirmation">
        <VCardText>
          <VAlert
            v-if="error"
            type="error"
            class="mb-4"
          >
            {{ error }}
          </VAlert>

          <VTextField
            v-model="form.name"
            label="Role Name"
            placeholder="Example: Admin"
            class="mb-5"
            :disabled="saving"
            :error-messages="
              nameError ? [nameError] : []
            "
          />

          <div class="d-flex align-center justify-space-between mb-3">
            <div>
              <div class="text-subtitle-2 font-weight-semibold">
                Assign Permissions
              </div>

              <div class="text-caption text-medium-emphasis">
                Select permissions for this role.
              </div>
            </div>

            <VBtn
              size="small"
              color="primary"
              variant="flat"
              :disabled="
                loadingPermissions ||
                !permissions.length ||
                saving
              "
              @click="toggleAll"
            >
              {{ isAllSelected ? 'Uncheck All' : 'Check All' }}
            </VBtn>
          </div>

          <VProgressLinear
            v-if="loadingPermissions"
            indeterminate
            color="primary"
            class="mb-3"
          />

          <VAlert
            v-else-if="permissionError"
            type="error"
            variant="tonal"
            class="mb-3"
          >
            {{ permissionError }}
          </VAlert>

          <VTable
            v-else-if="permissionGroups.length"
            density="compact"
            class="border rounded"
          >
            <thead>
              <tr>
                <th class="text-left">
                  Module
                </th>

                <th
                  v-for="action in headerActions"
                  :key="action"
                  class="text-center"
                >
                  <div
                    class="d-flex flex-column align-center justify-center py-2"
                  >
                    <input
                      type="checkbox"
                      :checked="isActionSelected(action)"
                      :disabled="
                        !getActionPermissions(action).length ||
                        saving
                      "
                      @change="toggleAction(action)"
                    />

                    <span
                      class="text-caption text-uppercase font-weight-bold mt-1"
                    >
                      {{ action }}
                    </span>
                  </div>
                </th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="group in permissionGroups"
                :key="group.module"
              >
                <td>
                  <span class="text-body-2 font-weight-medium">
                    {{ group.module }}
                  </span>
                </td>

                <td
                  v-for="action in headerActions"
                  :key="`${group.module}-${action}`"
                  class="text-center"
                >
                  <div
                    class="d-flex align-center justify-center"
                  >
                    <input
                      v-if="getPermissionByAction(group, action)"
                      v-model="form.permissions"
                      type="checkbox"
                      :value="
                        getPermissionByAction(group, action).id
                      "
                      :disabled="saving"
                    />

                    <span
                      v-else
                      class="text-medium-emphasis"
                    >
                      —
                    </span>
                  </div>
                </td>
              </tr>
            </tbody>
          </VTable>

          <VAlert
            v-else
            type="info"
            variant="tonal"
            class="mt-3"
          >
            No permissions have been registered yet.
          </VAlert>
        </VCardText>

        <VCardActions class="justify-end gap-2 pa-4">
          <VBtn
            variant="tonal"
            :disabled="saving"
            @click="handleClose"
          >
            Cancel
          </VBtn>

          <VBtn
            type="submit"
            color="success"
            variant="flat"
            :loading="saving"
          >
            Add
          </VBtn>
        </VCardActions>
      </VForm>
    </VCard>
  </VDialog>
</template>
