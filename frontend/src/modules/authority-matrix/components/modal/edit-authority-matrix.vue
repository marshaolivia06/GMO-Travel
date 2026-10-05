<script setup>
import { onMounted, reactive, ref } from 'vue'
import { getRoles } from '../../../user-management/services/roleService'
import { getDepartmentOptions } from '../../../master-management/services/departmentService'
import { getSectionOptions } from '../../../master-management/services/sectionService'
import { useAuthorityMatrixStore } from '../../stores/authorityMatrixStore'
import { useToastStore } from '../../../../stores/toast'
import { useConfirm } from '../../../../composables/useConfirm'

const props = defineProps({ authorityMatrix: { type: Object, required: true } })
const emit = defineEmits(['close'])
const store = useAuthorityMatrixStore()
const toast = useToastStore()
const confirm = useConfirm()

const roles = ref([])
const departments = ref([])
const sections = ref([])
const loading = ref(false)

const form = reactive({
  document_type: '',
  status: true,
  steps: [],
})

const errors = reactive({ document_type: '', steps: '' })

async function fetchOptions() {
  loading.value = true
  try {
    const [roleRes, departmentRes, sectionRes] = await Promise.all([
      getRoles(),
      getDepartmentOptions(),
      getSectionOptions(),
    ])
    roles.value = roleRes.data ?? roleRes
    departments.value = departmentRes.data ?? departmentRes
    sections.value = sectionRes.data ?? sectionRes
  } catch (err) {
    toast.error(err.response?.data?.message || err.message || 'Failed to load options.')
  } finally {
    loading.value = false
  }
}

function loadData() {
  form.document_type = props.authorityMatrix.document_type
  form.status = !!props.authorityMatrix.status
  form.steps = (props.authorityMatrix.steps || []).map(item => ({
    id: item.id,
    step: item.step,
    role_id: item.role_id,
    label: item.label,
    role_level: item.role_level,
    is_specific_section: !!item.is_specific_section,
    section_id: item.section_id,
    is_specific_department: !!item.is_specific_department,
    department_id: item.department_id,
  }))
}

function addStep() {
  form.steps.push({
    step: form.steps.length + 1,
    role_id: null,
    label: '',
    role_level: null,
    is_specific_section: false,
    section_id: null,
    is_specific_department: false,
    department_id: null,
  })
}

function removeStep(index) {
  if (form.steps.length === 1) return
  form.steps.splice(index, 1)
  form.steps.forEach((item, i) => item.step = i + 1)
}

function validate() {
  errors.document_type = form.document_type ? '' : 'Document type is required.'
  errors.steps = form.steps.length && form.steps.every(item =>
    item.role_id && item.label &&
    (!item.is_specific_section || item.section_id) &&
    (!item.is_specific_department || item.department_id)
  ) ? '' : 'Please complete all step fields.'
  return !errors.document_type && !errors.steps
}

async function submit() {
  if (!validate()) return

  const confirmed = await confirm({
    title: 'Update Authority Matrix',
    text: 'Are you sure you want to update this authority matrix?',
    confirmButtonText: 'Update',
  })
  if (!confirmed) return

  try {
    await store.editAuthorityMatrix(props.authorityMatrix.id, {
      document_type: form.document_type,
      status: form.status,
      steps: form.steps,
    })
    toast.success('Authority matrix updated successfully.')
    emit('close')
  } catch (err) {
    toast.error(err.response?.data?.message || err.message || 'Failed to update authority matrix.')
  }
}

onMounted(async () => {
  await fetchOptions()
  loadData()
})
</script>

<template>
  <VDialog max-width="1000" persistent>
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between">
        Edit Authority Matrix
        <VBtn icon="ri-close-line" variant="text" @click="emit('close')" />
      </VCardTitle>

      <VCardText>
        <VForm @submit.prevent="submit">
          <VSelect v-model="form.document_type" :items="['Travel Order']" label="Document Type" :error-messages="errors.document_type" class="mb-4" />

          <div v-for="(item, index) in form.steps" :key="item.id || index" class="mb-5">
            <div class="d-flex align-center justify-space-between mb-2">
              <div class="text-subtitle-1 font-weight-medium">Step {{ item.step }}</div>
              <VBtn v-if="form.steps.length > 1" icon="ri-delete-bin-line" size="small" variant="text" color="error" @click="removeStep(index)" />
            </div>

            <VRow>
              <VCol cols="12" md="4">
                <VAutocomplete v-model="item.role_id" :items="roles" item-title="name" item-value="id" label="Role" :loading="loading" />
              </VCol>

              <VCol cols="12" md="4">
                <VTextField v-model="item.label" label="Label" />
              </VCol>

              <VCol cols="12" md="4">
                <VTextField v-model="item.role_level" label="Role Level" type="number" min="1" />
              </VCol>

              <VCol cols="12" md="6">
                <VSwitch v-model="item.is_specific_section" label="Specific Section" color="success" hide-details />
                <VAutocomplete v-if="item.is_specific_section" v-model="item.section_id" :items="sections" item-title="name" item-value="id" label="Section" class="mt-2" />
              </VCol>

              <VCol cols="12" md="6">
                <VSwitch v-model="item.is_specific_department" label="Specific Department" color="success" hide-details />
                <VAutocomplete v-if="item.is_specific_department" v-model="item.department_id" :items="departments" item-title="name" item-value="id" label="Department" class="mt-2" />
              </VCol>
            </VRow>

            <VDivider v-if="index < form.steps.length - 1" class="mt-4" />
          </div>

          <div v-if="errors.steps" class="text-error text-caption mb-3">{{ errors.steps }}</div>

          <VBtn variant="outlined" color="success" prepend-icon="ri-add-line" @click="addStep">
            Add Step
          </VBtn>
        </VForm>
      </VCardText>

      <VCardActions class="justify-end">
        <VBtn variant="text" @click="emit('close')">Cancel</VBtn>
        <VBtn color="success" :loading="loading" @click="submit">Update</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>