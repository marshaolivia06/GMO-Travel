<script setup>
import { ref } from 'vue'
import { useConfirm } from '../../../../../composables/useConfirm'
import { useToastStore } from '../../../../../stores/toast'
import { createPermission } from '../../../services/permissionService'

const emit = defineEmits(['close', 'created'])
const { confirm } = useConfirm()
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
const moduleError = ref('')
const actionsError = ref('')

function handleClose() {
  if (!loading.value) {
    emit('close')
  }
}

async function handleSubmit() {
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
    title: 'Add Permission',
    text: 'Are you sure you want to add this permission?',
  })

  if (!confirmed) return

  loading.value = true

  try {
    const moduleName = form.value.module
      .toLowerCase()
      .replace(/\s+/g, '-')

    for (const action of form.value.actions) {
      await createPermission({
        module: form.value.module,
        name: `${moduleName}.${action}`,
      })
    }

    toast.success('Permission has been added successfully.')

    emit('created')
    emit('close')
  } catch (err) {
    moduleError.value =
      err?.response?.data?.message ||
      err?.message ||
      'Failed to add permission.'
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
        Add Permission
      </VCardTitle>

      <VCardSubtitle class="px-4">
        Create permissions for a module.
      </VCardSubtitle>

      <VForm @submit.prevent="handleSubmit">
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
    v-for="action in availableActions"
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
            Add
          </VBtn>
        </VCardActions>
      </VForm>
    </VCard>
  </VDialog>
</template>
