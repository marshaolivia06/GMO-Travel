<script setup>
import { onMounted, reactive, ref } from 'vue'
import { getRoles } from '../../../user-management/services/roleService'
import { getDepartments } from '../../../master-management/services/departmentService'
import { getSections } from '../../../master-management/services/sectionService'
import { useAuthorityMatrixStore } from '../../stores/authorityMatrixStore'
import { useToastStore } from '../../../../stores/toast'
import { useConfirm } from '../../../../composables/useConfirm'

const emit = defineEmits(['close'])
const store = useAuthorityMatrixStore()
const toast = useToastStore()
const { confirm } = useConfirm()

const roles = ref([])
const departments = ref([])
const sections = ref([])
const optionsLoading = ref(false)
const loading = ref(false)

const form = reactive({ document_type: '', status: true, steps: [createStep(1)] })
const errors = reactive({ document_type: '', steps: '' })

function createStep(step) {
  return { step, role_id: null, label: '', role_level: null, is_specific_section: false, section_id: null, is_specific_department: false, department_id: null }
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
    title: 'Add Authority Matrix',
    text: 'Are you sure you want to add this authority matrix?',
  })

  if (!confirmed) return

  loading.value = true

  try {
    await store.addAuthorityMatrix({
      document_type: form.document_type.trim(),
      status: form.status,
      steps: form.steps,
    })
    toast.success('Authority matrix has been added successfully.')
    emit('close')
  } catch (err) {
    errors.document_type = err.response?.data?.errors?.document_type?.[0] || ''
    if (!errors.document_type) {
      errors.steps = err.response?.data?.message || err.message || 'Failed to create authority matrix.'
    }
  } finally {
    loading.value = false
  }
}

onMounted(fetchOptions)
</script>

<template>
  <VDialog :model-value="true" max-width="1000" persistent>
    <VCard>
      <VCardTitle class="pa-4">Add Authority Matrix</VCardTitle>
      <VCardSubtitle class="px-4">Create a new authority matrix.</VCardSubtitle>

      <VForm @submit.prevent="submit">
        <VCardText>
          <VTextField
            v-model="form.document_type"
            label="Document Type"
            :disabled="loading"
            :error-messages="errors.document_type ? [errors.document_type] : []"
          />

          <div class="d-flex align-center justify-space-between mb-4">
            <div class="text-subtitle-1 font-weight-medium">Approval Step</div>
            <div class="text-caption text-medium-emphasis">dari level rendah ke tinggi</div>
          </div>

          <div class="steps-container">
            <div v-for="(item, index) in form.steps" :key="index" class="mb-4">
              <VCard rounded="lg" elevation="2">
                <VCardText>
                  <div class="d-flex align-center justify-space-between mb-4">
                    <VChip size="small" color="success" variant="tonal">Step {{ item.step }}</VChip>
                    <VBtn v-if="form.steps.length > 1" icon="ri-delete-bin-line" size="small" variant="text" color="error" :disabled="loading" @click="removeStep(index)" />
                  </div>

                  <VRow>
                    <VCol cols="12" md="3">
                      <VAutocomplete v-model="item.role_id" :items="roles" item-title="name" item-value="id" label="Role" placeholder="Search role..." :loading="optionsLoading" :disabled="loading" @update:model-value="changeRole(item)" />
                    </VCol>
                    <VCol cols="12" md="3">
                      <VTextField v-model="item.label" label="Label" :disabled="loading" />
                    </VCol>
                    <VCol cols="12" md="3">
                      <VTextField v-model="item.role_level" label="Role Level" type="number" min="1" :disabled="loading" />
                    </VCol>
                    <VCol v-if="isSectionRole(item)" cols="12" md="3">
                      <VSwitch :model-value="item.is_specific_section" label="Specific Section" color="success" hide-details :disabled="loading" @update:model-value="item.is_specific_section = !!$event" />
                    </VCol>
                    <VCol v-if="isDepartmentRole(item)" cols="12" md="3">
                      <VSwitch :model-value="item.is_specific_department" label="Specific Department" color="success" hide-details :disabled="loading" @update:model-value="item.is_specific_department = !!$event" />
                    </VCol>
                    <VCol v-if="item.is_specific_section" cols="12" md="4">
                      <VAutocomplete v-model="item.section_id" :items="sections" item-title="name" item-value="id" label="Section" :disabled="loading" />
                    </VCol>
                    <VCol v-if="item.is_specific_department" cols="12" md="4">
                      <VAutocomplete v-model="item.department_id" :items="departments" item-title="name" item-value="id" label="Department" :disabled="loading" />
                    </VCol>
                  </VRow>
                </VCardText>
              </VCard>
            </div>
          </div>

          <div v-if="errors.steps" class="text-error text-caption mb-3">{{ errors.steps }}</div>

          <VBtn color="success" prepend-icon="ri-add-line" :disabled="loading" @click="addStep">Add Step</VBtn>
        </VCardText>

        <VCardActions class="justify-end gap-2 pa-4">
          <VBtn variant="tonal" :disabled="loading" @click="closeModal">
            Cancel
          </VBtn>

          <VBtn type="submit" color="success" variant="flat" :loading="loading">
            Add
          </VBtn>
        </VCardActions>
      </VForm>
    </VCard>
  </VDialog>
</template>

<style>
.steps-container { max-height: 450px; overflow-y: auto; }
</style>