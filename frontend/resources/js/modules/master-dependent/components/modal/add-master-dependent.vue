<script setup>
import { computed, reactive, ref } from 'vue'

const MAX_ELIGIBLE_AGE = 23
const props = defineProps({ employees: { type: Array, default: () => [] } })
const emit = defineEmits(['close', 'submit'])

const formRef = ref(null)
const relationshipOptions = ['Spouse', 'Child']
const todayStr = new Date().toLocaleDateString('en-CA')
const newRow = () => ({ name: '', relationship: null, dob: '' })
const form = reactive({ employee_id: null, dependents: [newRow()] })

const required = v => (v !== null && v !== undefined && v !== '') || 'This field is required'
const beforeToday = v => !v || v < todayStr || 'Date must be before today'
const spouseCount = computed(() => form.dependents.filter(d => d.relationship === 'Spouse').length)
const onlyOneSpouse = v => v !== 'Spouse' || spouseCount.value <= 1 || 'Only one spouse is allowed'

function agePreview(dependent) {
  if (!dependent.dob || !dependent.relationship) return null
  const birth = new Date(dependent.dob)
  const today = new Date()
  let age = today.getFullYear() - birth.getFullYear()
  const month = today.getMonth() - birth.getMonth()
  if (month < 0 || (month === 0 && today.getDate() < birth.getDate())) age--
  return { age, eligible: dependent.relationship === 'Spouse' || age < MAX_ELIGIBLE_AGE }
}

const addRow = () => form.dependents.push(newRow())
const removeRow = index => form.dependents.splice(index, 1)

async function submit() {
  const { valid } = await formRef.value.validate()
  if (!valid) return
  emit('submit', { employee_id: form.employee_id, dependents: form.dependents.map(d => ({ name: d.name, relationship: d.relationship, dob: d.dob })) })
}
</script>

<template>
  <VDialog :model-value="true" max-width="800" persistent>
    <VCard rounded="lg" class="d-flex flex-column" style="max-height: 90vh">
      <div class="d-flex align-center justify-space-between px-5 bg-primary rounded-t-lg flex-shrink-0" style="height: 60px">
        <span class="text-title-large font-weight-bold text-white">Add Dependent</span>
        <VBtn icon="ri-close-line" variant="text" color="white" density="comfortable" @click="emit('close')" />
      </div>

      <VForm ref="formRef" class="d-flex flex-column" style="min-height: 0; flex: 1 1 auto" @submit.prevent="submit">
        <VCardText class="pa-5" style="overflow-y: auto; flex: 1 1 auto">
          <VRow dense>
            <VCol cols="12">
              <label class="text-body-medium font-weight-600 d-block mb-1">Employee</label>
              <VAutocomplete v-model="form.employee_id" :items="props.employees" item-title="name" item-value="id" :rules="[required]" placeholder="Select employee" hide-details="auto"/>
            </VCol>
            <VCol cols="12" class="d-flex align-center justify-space-between">
              <span class="text-body-medium font-weight-600">Family Members</span>
              <VBtn variant="tonal" color="primary" size="small" prepend-icon="ri-add-line" @click="addRow">Add Family Member</VBtn>
            </VCol>
            <VCol v-for="(dependent, index) in form.dependents" :key="index" cols="12">
              <VCard variant="outlined" class="pa-4">
                <VRow dense>
                  <VCol cols="12" md="5">
                    <label class="text-body-medium font-weight-600 d-block mb-1">Dependent Name</label>
                    <VTextField v-model="dependent.name" :rules="[required]" placeholder="Enter dependent name" hide-details="auto"/>
                  </VCol>
                  <VCol cols="12" md="3">
                    <label class="text-body-medium font-weight-600 d-block mb-1">Relationship</label>
                    <VAutocomplete v-model="dependent.relationship" :items="relationshipOptions" :rules="[required, onlyOneSpouse]" placeholder="Select" hide-details="auto"/>
                  </VCol>
                  <VCol cols="12" md="3">
                    <label class="text-body-medium font-weight-600 d-block mb-1">Date of Birth</label>
                    <VTextField v-model="dependent.dob" type="date" :rules="[required, beforeToday]" hide-details="auto"/>
                  </VCol>
                  <VCol cols="12" md="1" class="d-flex align-end justify-center">
                    <VBtn icon="ri-delete-bin-line" size="small" variant="text" color="error" :disabled="form.dependents.length === 1" @click="removeRow(index)" />
                  </VCol>
                  <VCol v-if="agePreview(dependent)" cols="12">
                    <VAlert :type="agePreview(dependent).eligible ? 'success' : 'error'" variant="tonal" density="compact">Age {{ agePreview(dependent).age }} — {{ agePreview(dependent).eligible ? 'Eligible for Annual Ticket' : 'Not eligible for Annual Ticket' }}</VAlert>
                  </VCol>
                </VRow>
              </VCard>
            </VCol>
          </VRow>
        </VCardText>

        <VDivider class="flex-shrink-0" />
        <VCardActions class="pa-5 flex-shrink-0">
          <VSpacer />
          <VBtn variant="tonal" @click="emit('close')">Close</VBtn>
          <VBtn type="submit" color="primary" variant="flat">Save</VBtn>
        </VCardActions>
      </VForm>
    </VCard>
  </VDialog>
</template>