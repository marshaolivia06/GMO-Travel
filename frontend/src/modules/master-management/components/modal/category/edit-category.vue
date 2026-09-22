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
    <VCard>
      <VCardTitle class="pa-4">Edit Category</VCardTitle>
      <VCardSubtitle class="px-4">Update category information.</VCardSubtitle>

      <VForm @submit.prevent="submitCategory">
        <VCardText>
          <VAlert v-if="generalError" type="error" class="mb-4">{{ generalError }}</VAlert>

          <VTextField
            v-model="form.name"
            label="Category Name"
            :disabled="loading"
            :error-messages="errors.name ? [errors.name] : []"
            autofocus
          />
        </VCardText>

        <VCardActions class="justify-end gap-2 pa-4">
  <VBtn variant="tonal" :disabled="loading" @click="close">Cancel</VBtn>
  <VBtn type="submit" color="success" variant="flat" :loading="loading" :disabled="loading">Update</VBtn>
</VCardActions>
      </VForm>
    </VCard>
  </VDialog>
</template>
