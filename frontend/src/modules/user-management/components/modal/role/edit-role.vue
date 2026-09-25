<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { updateRole } from '../../../services/roleService'
import { getPermissions } from '../../../services/permissionService'
import swal from '../../../../../plugins/swal'

const props = defineProps({
  role: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['close', 'updated'])

const loading = ref(false)
const permissionsLoading = ref(false)
const generalError = ref('')
const permissionError = ref('')
const permissions = ref([])

const errors = reactive({
  name: '',
})

const form = reactive({
  name: props.role?.name || '',
  permissions: Array.isArray(props.role?.permissions)
    ? props.role.permissions.map(permission => permission.id)
    : [],
})

const headerActions = [
  'view',
  'create',
  'update',
  'delete',
  'approve',
]

function clearErrors() {
  errors.name = ''
  generalError.value = ''
  permissionError.value = ''
}

function handleValidationError(err) {
  const validationErrors = err?.response?.data?.errors

  if (!validationErrors) return

  errors.name = validationErrors.name?.[0] || ''
}

function getAction(permission) {
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

function getPermissionByAction(group, action) {
  return group.permissions.find(
    permission => getAction(permission) === action
  )
}

function getActionPermissions(action) {
  return permissions.value.filter(
    permission => getAction(permission) === action
  )
}

function isActionSelected(action) {
  const list = getActionPermissions(action)

  return (
    list.length > 0 &&
    list.every(permission =>
      form.permissions.includes(permission.id)
    )
  )
}

function toggleAction(action) {
  const list = getActionPermissions(action)
  const ids = list.map(permission => permission.id)

  if (isActionSelected(action)) {
    form.permissions = form.permissions.filter(
      id => !ids.includes(id)
    )

    return
  }

  form.permissions = [
    ...new Set([
      ...form.permissions,
      ...ids,
    ]),
  ]
}

const isAllSelected = computed(() => {
  return (
    permissions.value.length > 0 &&
    permissions.value.every(permission =>
      form.permissions.includes(permission.id)
    )
  )
})

function toggleAll() {
  form.permissions = isAllSelected.value
    ? []
    : permissions.value.map(permission => permission.id)
}

async function fetchPermissions() {
  permissionsLoading.value = true
  permissionError.value = ''

  try {
    const response = await getPermissions()

    permissions.value = response.data ?? response
  } catch (err) {
    permissionError.value =
      err?.response?.data?.message ||
      err?.message ||
      'Failed to load permissions.'
  } finally {
    permissionsLoading.value = false
  }
}

async function submitRole() {
  if (loading.value) return

  clearErrors()

  form.name = form.name.trim()

  if (!form.name) {
    errors.name = 'Role name is required.'
    return
  }

  const confirmed = await swal.confirm(
    'Are you sure you want to update this role?'
  )

  if (!confirmed.isConfirmed) return

  loading.value = true

  try {
    await updateRole(props.role.id, {
      name: form.name,
      permissions: form.permissions,
    })

    await swal.success(
      'Role Updated',
      'Role has been updated successfully.'
    )

    emit('updated')
    emit('close')
  } catch (err) {
    handleValidationError(err)

    generalError.value =
      err?.response?.data?.message ||
      err?.message ||
      'Failed to update role.'

    await swal.error(
      'Action Failed',
      generalError.value
    )
  } finally {
    loading.value = false
  }
}

function close() {
  if (!loading.value) {
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
        Edit Role
      </VCardTitle>

      <VCardSubtitle class="px-4">
        Update the role and its assigned permissions.
      </VCardSubtitle>

      <VForm @submit.prevent="submitRole">
        <VCardText>
          <VAlert
            v-if="generalError"
            type="error"
            class="mb-4"
          >
            {{ generalError }}
          </VAlert>

          <VTextField
            v-model="form.name"
            label="Role Name"
            placeholder="Example: Admin"
            class="mb-3"
            :disabled="loading"
            :error-messages="
              errors.name ? [errors.name] : []
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
                permissionsLoading ||
                !permissions.length ||
                loading
              "
              @click="toggleAll"
            >
              {{ isAllSelected ? 'Uncheck All' : 'Check All' }}
            </VBtn>
          </div>

          <VProgressLinear
            v-if="permissionsLoading"
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
                        loading
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
                  <div class="d-flex align-center justify-center">
                    <input
                      v-if="getPermissionByAction(group, action)"
                      v-model="form.permissions"
                      type="checkbox"
                      :value="
                        getPermissionByAction(group, action).id
                      "
                      :disabled="loading"
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
            :disabled="loading"
            @click="close"
          >
            Cancel
          </VBtn>

          <VBtn
            type="submit"
            color="success"
            variant="flat"
            :loading="loading"
            :disabled="permissionsLoading"
          >
            Update
          </VBtn>
        </VCardActions>
      </VForm>
    </VCard>
  </VDialog>
</template>
