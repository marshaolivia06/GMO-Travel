<script setup>
import { onMounted, reactive, ref } from 'vue'
import { getRoles } from '../../../user-management/services/roleService'
import { getDepartments } from '../../../master-management/services/departmentService'
import { getSections } from '../../../master-management/services/sectionService'
import { useAuthorityMatrixStore } from '../../stores/authorityMatrixStore'
import { useToastStore } from '../../../../stores/toast'
import { useConfirm } from '../../../../composables/useConfirm'

const props = defineProps({ authorityMatrix: { type: Object, required: true } })
const emit = defineEmits(['close'])
const store = useAuthorityMatrixStore()
const toast = useToastStore()
const { confirm } = useConfirm()

const roles = ref([])
const departments = ref([])
const sections = ref([])
const optionsLoading = ref(false)
const loading = ref(false)

const travelRegions = ['Domestic', 'Singapore', 'Overseas'] // BARU
const actionTypes = [
  { title: 'Check', value: 'check' },
  { title: 'Approval', value: 'approve' },
]

const form = reactive({
  document_type: '',
  requester_role_id: null, // BARU
  travel_region: null, // BARU
  status: true,
  steps: [],
})
const errors = reactive({ document_type: '', steps: '' })

function createStep(step) {
  return { step, role_id: null, label: '', action_type: 'approve', role_level: null, is_specific_section: false, section_id: null, is_specific_department: false, department_id: null }
}

function isSectionRole(item) {
  const role = roles.value.find(role => String(role.id) === String(item.role_id))
  return role?.name?.trim().toLowerCase() === 'sect head'
}

function isDepartmentRole(item) {
  const role = roles.value.find(role => String(role.id) === String(item.role_id))
  return role?.name?.trim().toLowerCase() === 'dept head'
}

function changeRole(item) {
  if (!isSectionRole(item)) {
    item.is_specific_section = false
    item.section_id = null
  }
  if (!isDepartmentRole(item)) {
    item.is_specific_department = false
    item.department_id = null
  }
}

function closeModal() {
  if (loading.value) return
  emit('close')
}

async function fetchOptions() {
  optionsLoading.value = true
  try {
    const [roleRes, departmentRes, sectionRes] = await Promise.all([getRoles(), getDepartments(), getSections()])
    roles.value = roleRes.data ?? roleRes
    departments.value = departmentRes.data ?? departmentRes
    sections.value = sectionRes.data ?? sectionRes
  } catch (err) {
    toast.error(err.response?.data?.message || err.message || 'Failed to load options.')
  } finally {
    optionsLoading.value = false
  }
}

function loadData() {
  form.document_type = props.authorityMatrix.document_type
  form.requester_role_id = props.authorityMatrix.requester_role_id ?? null // BARU
  form.travel_region = props.authorityMatrix.travel_region ?? null // BARU
  form.status = !!props.authorityMatrix.status
  form.steps = (props.authorityMatrix.steps || []).map(item => ({
    id: item.id,
    step: item.step,
    role_id: item.role_id,
    label: item.label,
    action_type: item.action_type || 'approve',
    role_level: item.role_level,
    is_specific_section: !!item.is_specific_section,
    section_id: item.section_id,
    is_specific_department: !!item.is_specific_department,
    department_id: item.department_id,
  }))

  if (!form.steps.length) form.steps.push(createStep(1))
}

function addStep() {
  form.steps.push(createStep(form.steps.length + 1))
}

function removeStep(index) {
  if (form.steps.length === 1) return
  form.steps.splice(index, 1)
  form.steps.forEach((item, i) => item.step = i + 1)
}

function validate() {
  errors.document_type = form.document_type.trim() ? '' : 'Document type is required.'
  errors.steps = form.steps.every(item => item.role_id && item.label && (!item.is_specific_section || item.section_id) && (!item.is_specific_department || item.department_id)) ? '' : 'Please complete all step fields.'
  return !errors.document_type && !errors.steps
}

async function submit() {
  if (loading.value) return
  if (!validate()) return

  const confirmed = await confirm({
    title: 'Update Authority Matrix',
    text: 'Are you sure you want to update this authority matrix?',
  })

  if (!confirmed) return

  loading.value = true

  try {
    await store.editAuthorityMatrix(props.authorityMatrix.id, {
      document_type: form.document_type.trim(),
      requester_role_id: form.requester_role_id, // BARU
      travel_region: form.travel_region, // BARU
      status: form.status,
      steps: form.steps,
    })
    toast.success('Authority matrix has been updated successfully.')
    emit('close')
  } catch (err) {
    errors.document_type = err.response?.data?.errors?.document_type?.[0] || ''
    if (!errors.document_type) {
      errors.steps = err.response?.data?.message || err.message || 'Failed to update authority matrix.'
    }
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await fetchOptions()
  loadData()
})
</script>

<template>
  <VDialog :model-value="true" max-width="1000" persistent>
    <VCard rounded="lg" class="d-flex flex-column" style="max-height: 90vh">
      <div class="d-flex align-center justify-space-between px-5 bg-primary rounded-t-lg flex-shrink-0" style="height: 60px">
        <span class="text-title-large font-weight-bold text-white">Edit Authority Matrix</span>
        <VBtn icon="ri-close-line" variant="text" color="white" density="comfortable" @click="closeModal" />
      </div>

      <VForm class="d-flex flex-column" style="min-height: 0; flex: 1 1 auto" @submit.prevent="submit">
        <VCardText class="pa-5" style="overflow-y: auto; flex: 1 1 auto">
          <div class="mb-4">
<label class="text-body-medium font-weight-600 d-block mb-1">Document Type</label>
<VTextField v-model="form.document_type" :disabled="loading" :error-messages="errors.document_type ? [errors.document_type] : []" placeholder="Enter document type" hide-details="auto"/></div>

          <!-- BARU -->
          <VRow>
            <VCol cols="12" md="6">
              <div class="mb-4">
<label class="text-body-medium font-weight-600 d-block mb-1">Requester Role</label>
<VAutocomplete v-model="form.requester_role_id" :items="roles" item-title="name" item-value="id" placeholder="Search role..." hint="Leave empty for Document Types other than Travel Order" persistent-hint :loading="optionsLoading" :disabled="loading" clearable hide-details="auto"/></div>
            </VCol>
            <VCol cols="12" md="6">
              <div class="mb-4">
<label class="text-body-medium font-weight-600 d-block mb-1">Travel Region</label>
<VSelect v-model="form.travel_region" :items="travelRegions" hint="Leave empty for Document Types other than Travel Order" persistent-hint :disabled="loading" clearable placeholder="Select travel region" hide-details="auto"/></div>
            </VCol>
          </VRow>

          <div class="d-flex align-center justify-space-between mb-4 mt-6">
            <div class="text-subtitle-1 font-weight-medium">Process Step</div>
            <div class="text-caption text-medium-emphasis">from lowest to highest level</div>
          </div>

          <div class="steps-container">
            <div v-for="(item, index) in form.steps" :key="item.id || index" class="mb-4">
              <VCard rounded="lg" elevation="2">
                <VCardText>
                  <div class="d-flex align-center justify-space-between mb-4">
                    <VChip size="small" color="success" variant="tonal">Step {{ item.step }}</VChip>
                    <VBtn v-if="form.steps.length > 1" icon="ri-delete-bin-line" size="small" variant="text" color="error" :disabled="loading" @click="removeStep(index)" />
                  </div>

                  <VRow>
                    <VCol cols="12" md="3">
                      <div class="mb-4">
<label class="text-body-medium font-weight-600 d-block mb-1">Role</label>
<VAutocomplete v-model="item.role_id" :items="roles" item-title="name" item-value="id" placeholder="Search role..." :loading="optionsLoading" :disabled="loading" @update:model-value="changeRole(item)"  hide-details="auto"/></div>
                    </VCol>
                    <VCol cols="12" md="3">
                      <div class="mb-4">
<label class="text-body-medium font-weight-600 d-block mb-1">Label</label>
<VTextField v-model="item.label" :disabled="loading"  placeholder="Enter label" hide-details="auto"/></div>
                    </VCol>
                    <VCol cols="12" md="3">
                      <div class="mb-4">
<label class="text-body-medium font-weight-600 d-block mb-1">Action Type</label>
<VSelect v-model="item.action_type" :items="actionTypes" item-title="title" item-value="value" :disabled="loading" placeholder="Select action" hide-details="auto"/></div>
                    </VCol>
                    <VCol cols="12" md="3">
                      <div class="mb-4">
<label class="text-body-medium font-weight-600 d-block mb-1">Role Level</label>
<VTextField v-model="item.role_level" type="number" min="1" :disabled="loading"  placeholder="Enter role level" hide-details="auto"/></div>
                    </VCol>
                    <VCol v-if="isSectionRole(item)" cols="12" md="3">
                      <VSwitch :model-value="item.is_specific_section" label="Specific Section" color="primary" hide-details :disabled="loading" @update:model-value="item.is_specific_section = !!$event" />
                    </VCol>
                    <VCol v-if="isDepartmentRole(item)" cols="12" md="3">
                      <VSwitch :model-value="item.is_specific_department" label="Specific Department" color="primary" hide-details :disabled="loading" @update:model-value="item.is_specific_department = !!$event" />
                    </VCol>
                    <VCol v-if="item.is_specific_section" cols="12" md="4">
                      <div class="mb-4">
<label class="text-body-medium font-weight-600 d-block mb-1">Section</label>
<VAutocomplete v-model="item.section_id" :items="sections" item-title="name" item-value="id" :disabled="loading"  placeholder="Select section" hide-details="auto"/></div>
                    </VCol>
                    <VCol v-if="item.is_specific_department" cols="12" md="4">
                      <div class="mb-4">
<label class="text-body-medium font-weight-600 d-block mb-1">Department</label>
<VAutocomplete v-model="item.department_id" :items="departments" item-title="name" item-value="id" :disabled="loading"  placeholder="Select department" hide-details="auto"/></div>
                    </VCol>
                  </VRow>
                </VCardText>
              </VCard>
            </div>
          </div>

          <div v-if="errors.steps" class="text-error text-caption mb-3">{{ errors.steps }}</div>

          <VBtn color="primary" prepend-icon="ri-add-line" :disabled="loading" @click="addStep">Add Step</VBtn>
        </VCardText>

        <VDivider class="flex-shrink-0" />
        <VCardActions class="pa-5 flex-shrink-0">
          <VSpacer />
          <VBtn variant="tonal" :disabled="loading" @click="closeModal">
            Close
          </VBtn>

          <VBtn type="submit" color="primary" variant="flat" :loading="loading">
            Update
          </VBtn>
        </VCardActions>
      </VForm>
    </VCard>
  </VDialog>
</template>

<style>
.steps-container { max-height: 450px; overflow-y: auto; }
</style>