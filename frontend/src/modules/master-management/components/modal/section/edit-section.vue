<script setup>
import { reactive, ref } from 'vue'
import { useSectionStore } from '../../../stores/sectionStore'
import { useConfirm } from '../../../../../composables/useConfirm'
import { useToastStore } from '../../../../../stores/toast'

const props = defineProps({
  section: { type: Object, required: true },
})

const emit = defineEmits(['close'])
const { confirm } = useConfirm()
const toast = useToastStore()
const sectionStore = useSectionStore()

const loading = ref(false)
const generalError = ref('')

const errors = reactive({ name: '', section_head_id: '', department_id: '' })

const form = reactive({
  name: props.section?.name || '',
  section_head_id: props.section?.section_head_id ?? props.section?.section_head?.id ?? props.section?.sectionHead?.id ?? '',
  department_id: props.section?.department_id ?? props.section?.department?.id ?? '',
})

function clearErrors() {
  errors.name = ''
  errors.section_head_id = ''
  errors.department_id = ''
  generalError.value = ''
}

async function submitSection() {
  if (loading.value) return

  clearErrors()

  if (!form.name.trim()) {
    errors.name = 'Section name is required.'
    return
  }
  if (!form.section_head_id) {
    errors.section_head_id = 'Sect Head is required.'
    return
  }
  if (!form.department_id) {
    errors.department_id = 'Department is required.'
    return
  }

  const confirmed = await confirm({
    title: 'Are you sure',
    text: 'Are you sure you want to update this section?',
  })

  if (!confirmed) return

  loading.value = true

  try {
    await sectionStore.editSection(props.section.id, {
      name: form.name.trim(),
      section_head_id: form.section_head_id,
      department_id: form.department_id,
    })

    toast.success('Section has been updated successfully.')
    emit('close')
  } catch (err) {
    generalError.value = err.response?.data?.message || err.message || 'Failed to update section.'
  } finally {
    loading.value = false
  }
}

function close() {
  if (!loading.value) emit('close')
}
</script>

<template>
  <VDialog :model-value="true" max-width="480" persistent>
    <VCard>
      <VCardTitle class="pa-4">Edit Section</VCardTitle>
      <VCardSubtitle class="px-4">Update the section information.</VCardSubtitle>

      <VForm @submit.prevent="submitSection">
        <VCardText>
          <VAlert v-if="generalError" type="error" class="mb-4">{{ generalError }}</VAlert>

          <VTextField
            v-model="form.name"
            label="Section Name"
            class="mb-2"
            :disabled="loading"
            :error-messages="errors.name ? [errors.name] : []"
          />

          <VAutocomplete
            v-model="form.section_head_id"
            :items="sectionStore.options.section_heads"
            item-title="name"
            item-value="id"
            label="Sect Head"
            class="mb-2"
            clearable
            :loading="sectionStore.optionsLoading"
            :disabled="loading || sectionStore.optionsLoading"
            :error-messages="errors.section_head_id ? [errors.section_head_id] : []"
          />

          <VAutocomplete
            v-model="form.department_id"
            :items="sectionStore.options.departments"
            item-title="name"
            item-value="id"
            label="Department"
            clearable
            :loading="sectionStore.optionsLoading"
            :disabled="loading || sectionStore.optionsLoading"
            :error-messages="errors.department_id ? [errors.department_id] : []"
          />
        </VCardText>

        <VCardActions class="justify-end gap-2 pa-4">
  <VBtn variant="tonal" :disabled="loading" @click="close">Cancel</VBtn>
  <VBtn type="submit" color="success" variant="flat" :loading="loading" :disabled="sectionStore.optionsLoading">Update</VBtn>
</VCardActions>
      </VForm>
    </VCard>
  </VDialog>
</template>
