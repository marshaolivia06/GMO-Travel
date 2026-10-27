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
  <VDialog :model-value="true" max-width="520" persistent>
    <VCard rounded="lg" class="d-flex flex-column" style="max-height: 90vh">
      <div class="d-flex align-center justify-space-between px-5 bg-primary rounded-t-lg flex-shrink-0" style="height: 60px">
        <span class="text-title-large font-weight-bold text-white">Edit Section</span>
        <VBtn icon="ri-close-line" variant="text" color="white" density="comfortable" @click="close" />
      </div>

      <VForm class="d-flex flex-column" style="min-height: 0; flex: 1 1 auto" @submit.prevent="submitSection">
        <VCardText class="pa-5" style="overflow-y: auto; flex: 1 1 auto">
          <VAlert v-if="generalError" type="error" class="mb-4">{{ generalError }}</VAlert>

          <VRow dense>
            <VCol cols="12">
              <label class="text-body-medium font-weight-600 d-block mb-1">Section Name</label>
              <VTextField v-model="form.name" :disabled="loading" :error-messages="errors.name ? [errors.name] : []" placeholder="Enter section name" hide-details="auto"/>
            </VCol>
            <VCol cols="12">
              <label class="text-body-medium font-weight-600 d-block mb-1">Sect Head</label>
              <VAutocomplete v-model="form.section_head_id" :items="sectionStore.options.section_heads" item-title="name" item-value="id" clearable :loading="sectionStore.optionsLoading" :disabled="loading || sectionStore.optionsLoading" :error-messages="errors.section_head_id ? [errors.section_head_id] : []" placeholder="Select sect head" hide-details="auto"/>
            </VCol>
            <VCol cols="12">
              <label class="text-body-medium font-weight-600 d-block mb-1">Department</label>
              <VAutocomplete v-model="form.department_id" :items="sectionStore.options.departments" item-title="name" item-value="id" clearable :loading="sectionStore.optionsLoading" :disabled="loading || sectionStore.optionsLoading" :error-messages="errors.department_id ? [errors.department_id] : []" placeholder="Select department" hide-details="auto"/>
            </VCol>
          </VRow>

</VCardText>

        <VDivider class="flex-shrink-0" />
        <VCardActions class="pa-5 flex-shrink-0">
          <VSpacer />
  <VBtn variant="tonal" :disabled="loading" @click="close">Close</VBtn>
  <VBtn type="submit" color="primary" variant="flat" :loading="loading" :disabled="sectionStore.optionsLoading">Update</VBtn>
</VCardActions>
      </VForm>
    </VCard>
  </VDialog>
</template>
