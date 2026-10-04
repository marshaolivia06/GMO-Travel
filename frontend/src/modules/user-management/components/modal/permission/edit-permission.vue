<script setup>
import { ref, watch } from 'vue'
import { updatePermissionsByModule } from '../../../services/permissionService'
import { useConfirm } from '../../../../../composables/useConfirm'
import { useToastStore } from '../../../../../stores/toast'

const props = defineProps({
  permission: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['close', 'updated'])

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
  {
    label: 'Approve',
    value: 'approve',
  },
]

const form = ref({
  module: '',
  actions: [],
})

const loading = ref(false)
const moduleError = ref('')
const actionsError = ref('')

const { confirm } = useConfirm()
const toast = useToastStore()

function loadPermission(permission) {
  if (!permission) return

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
        if (!item?.name) return null

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

function handleClose() {
  if (!loading.value) {
    emit('close')
  }
}

async function openConfirmation() {
  if (loading.value) return

  moduleError.value = ''
  actionsError.value = ''

  form.value.module = form.value.module.trim()

  if (!form.value.module) {
    moduleError.value = 'Module is required.'
    return
  }

  if (!form.value.actions.length) {
    actionsError.value = 'Please select at least one action.'
    return
  }

  const confirmed = await confirm({
    title: 'Update Permission',
    text: 'Are you sure you want to update this permission?',
  })

  if (!confirmed) return

  await handleSubmit()
}

async function handleSubmit() {
  if (
    loading.value ||
    !props.permission?.module
  ) {
    return
  }

  loading.value = true
  moduleError.value = ''
  actionsError.value = ''

  const oldModule = props.permission.module
  const newModule = form.value.module.trim()

  const actions = [
    ...new Set(form.value.actions),
  ]

  try {
    await updatePermissionsByModule(
      oldModule,
      newModule,
      actions
    )

    toast.success('Permission has been updated successfully.')

    emit('updated')
    emit('close')
  } catch (err) {
    const message =
      err?.response?.data?.message ||
      err?.message ||
      'Failed to update permission.'

    const validationErrors = err?.response?.data?.errors

    moduleError.value =
      validationErrors?.module?.[0] ||
      ''

    actionsError.value =
      validationErrors?.actions?.[0] ||
      ''

    if (!moduleError.value && !actionsError.value) {
      moduleError.value = message
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <VDialog
    :model-value="true"
    max-width="480"
    persistent
  >
    <VCard>
      <VCardTitle class="pa-4">
        Edit Permission
      </VCardTitle>

      <VCardSubtitle class="px-4">
        Update permissions for a module.
      </VCardSubtitle>

      <VForm @submit.prevent="openConfirmation">
        <VCardText class="pt-3 pb-2">
          <VTextField
  v-model="form.module"
  label="Module"
  placeholder="Example: User Management"
  density="compact"
  :disabled="loading"
  :error-messages="moduleError ? [moduleError] : []"
/>

          <div class="mb-2">
            <div class="text-subtitle-2 font-weight-semibold">
              Actions
            </div>

            <div class="text-caption text-medium-emphasis">
              Select actions for this module.
            </div>
          </div>

          <VRow
  class="mt-0"
  no-gutters
>
  <VCol
    v-for="action in ACTIONS"
    :key="action.value"
    cols="4"
    class="py-1"
  >
    <div class="d-flex align-center ga-2">
      <input
        v-model="form.actions"
        type="checkbox"
        :value="action.value"
        :disabled="loading"
        class="flex-shrink-0"
      />

      <span class="text-body-2">
        {{ action.label }}
      </span>
    </div>
  </VCol>
</VRow>

<div
  v-if="actionsError"
  class="text-error text-caption mt-1"
>
  {{ actionsError }}
</div>
        </VCardText>

        <VCardActions class="justify-end gap-2 px-4 pt-2 pb-4">
          <VBtn
            variant="tonal"
            :disabled="loading"
            @click="handleClose"
          >
            Cancel
          </VBtn>

          <VBtn
            type="submit"
            color="success"
            variant="flat"
            :loading="loading"
          >
            Update
          </VBtn>
        </VCardActions>
      </VForm>
    </VCard>
  </VDialog>
</template>
