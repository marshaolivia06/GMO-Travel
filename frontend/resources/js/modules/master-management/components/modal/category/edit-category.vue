<script setup>
import { reactive, ref } from 'vue'
import { useCategoryStore } from '../../../stores/categoryStore'
import { useConfirm } from '../../../../../composables/useConfirm'
import { useToastStore } from '../../../../../stores/toast'

const props = defineProps({
  category: { type: Object, required: true },
})

const emit = defineEmits(['close'])
const { confirm } = useConfirm()
const toast = useToastStore()
const categoryStore = useCategoryStore()

const form = reactive({ name: props.category?.name || '' })
const errors = reactive({ name: '' })
const generalError = ref('')
const loading = ref(false)

function closeModal() {
  if (loading.value) return
  emit('close')
}

async function submitCategory() {
  if (loading.value) return

  errors.name = ''
  generalError.value = ''

  if (!form.name.trim()) {
    errors.name = 'Category name is required.'
    return
  }

  const confirmed = await confirm({
    title: 'Update Category',
    text: 'Are you sure you want to update this category?',
  })

  if (!confirmed) return

  loading.value = true

  try {
    await categoryStore.editCategory(props.category.id, { name: form.name.trim() })
    toast.success('Category has been updated successfully.')
    emit('close')
  } catch (err) {
    errors.name = err.response?.data?.errors?.name?.[0] || ''
    generalError.value = err.response?.data?.message || err.message || 'Failed to update category.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <VDialog :model-value="true" max-width="440" persistent>
    <VCard rounded="lg" class="d-flex flex-column" style="max-height: 90vh">
      <div class="d-flex align-center justify-space-between px-5 bg-primary rounded-t-lg flex-shrink-0" style="height: 60px">
        <span class="text-title-large font-weight-bold text-white">Edit Category</span>
        <VBtn icon="ri-close-line" variant="text" color="white" density="comfortable" @click="close" />
      </div>

      <VForm class="d-flex flex-column" style="min-height: 0; flex: 1 1 auto" @submit.prevent="submitCategory">
        <VCardText class="pa-5" style="overflow-y: auto; flex: 1 1 auto">
          <VAlert v-if="generalError" type="error" class="mb-4">{{ generalError }}</VAlert>

          <div class="mb-4">
<label class="text-body-medium font-weight-600 d-block mb-1">Category Name</label>
<VTextField v-model="form.name" :disabled="loading" :error-messages="errors.name ? [errors.name] : []" autofocus placeholder="Enter category name" hide-details="auto"/></div>
        </VCardText>

        <VDivider class="flex-shrink-0" />
        <VCardActions class="pa-5 flex-shrink-0">
          <VSpacer />
  <VBtn variant="tonal" :disabled="loading" @click="close">Close</VBtn>
  <VBtn type="submit" color="primary" variant="flat" :loading="loading" :disabled="loading">Update</VBtn>
</VCardActions>
      </VForm>
    </VCard>
  </VDialog>
</template>
