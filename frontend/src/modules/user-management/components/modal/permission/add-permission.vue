<script setup>
import { ref } from 'vue'
import swal from '../../../../../plugins/swal'
import { createPermission } from '../../../services/permissionService'

const emit = defineEmits(['close', 'created'])

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

function handleClose() {
  if (!loading.value) {
    emit('close')
  }
}

async function handleSubmit() {
  if (loading.value) return

  error.value = ''

  form.value.module = form.value.module.trim()

  if (!form.value.module) {
    error.value = 'Module is required.'
    return
  }

  if (!form.value.actions.length) {
    error.value = 'Please select at least one action.'
    return
  }

  const result = await swal.confirm(
    'Are you sure you want to add this permission?'
  )

  if (!result.isConfirmed) return

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

    await swal.success(
      'Permission Added',
      'Permission has been added successfully.'
    )

    emit('created')
    emit('close')
  } catch (err) {
    error.value =
      err?.response?.data?.message ||
      err?.message ||
      'Failed to add permission.'

    await swal.error(
      'Action Failed',
      error.value
    )
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
            :error-messages="error === 'Module is required.' ? [error] : []"
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
          <VAlert
            v-if="error && error !== 'Module is required.'"
            type="error"
            variant="tonal"
            class="mt-3"
          >
            {{ error }}
          </VAlert>
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
