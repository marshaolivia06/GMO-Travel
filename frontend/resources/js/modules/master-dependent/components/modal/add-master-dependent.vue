<script setup>
import { computed, reactive, ref } from 'vue'

const MAX_ELIGIBLE_AGE = 23

const props = defineProps({
  employees: { type: Array, default: () => [] },
})

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

  emit('submit', {
    employee_id: form.employee_id,
    dependents: form.dependents.map(d => ({ name: d.name, relationship: d.relationship, dob: d.dob })),
  })
}
</script>

<template>
  <VDialog :model-value="true" max-width="800" persistent scrollable>
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-5">
        <span class="text-h6">Add Dependent</span>
        <DialogCloseBtn @click="emit('close')" />
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-5">
        <VForm ref="formRef" @submit.prevent="submit">
          <VRow>
            <VCol cols="12">
              <AppSelect
                v-model="form.employee_id"
                :items="props.employees"
                item-title="name"
                item-value="id"
                label="Employee"
                placeholder="Select employee"
                :rules="[required]"
              />
            </VCol>

            <VCol cols="12">
              <div class="d-flex align-center justify-space-between">
                <span class="text-subtitle-1 font-weight-medium">Family Members</span>
                <VBtn variant="tonal" color="primary" size="small" prepend-icon="ri-add-line" @click="addRow">
                  Add Family Member
                </VBtn>
              </div>
            </VCol>

            <VCol v-for="(dependent, index) in form.dependents" :key="index" cols="12">
              <VCard variant="outlined" class="pa-4">
                <VRow>
                  <VCol cols="12" md="5">
                    <AppTextField
                      v-model="dependent.name"
                      label="Dependent Name"
                      placeholder="Enter dependent name"
                      :rules="[required]"
                    />
                  </VCol>

                  <VCol cols="12" md="3">
                    <AppSelect
                      v-model="dependent.relationship"
                      :items="relationshipOptions"
                      label="Relationship"
                      placeholder="Select"
                      :rules="[required, onlyOneSpouse]"
                    />
                  </VCol>

                  <VCol cols="12" md="3">
                    <AppTextField
                      v-model="dependent.dob"
                      type="date"
                      label="Date of Birth"
                      :rules="[required, beforeToday]"
                    />
                  </VCol>

                  <VCol cols="12" md="1" class="d-flex align-center justify-center">
                    <VBtn icon="ri-delete-bin-line" size="small" variant="text" color="error" :disabled="form.dependents.length === 1" @click="removeRow(index)" />
                  </VCol>

                  <VCol v-if="agePreview(dependent)" cols="12">
                    <VAlert :type="agePreview(dependent).eligible ? 'success' : 'error'" variant="tonal" density="compact">
                      Age {{ agePreview(dependent).age }} — {{ agePreview(dependent).eligible ? 'Eligible for Annual Ticket' : 'Not eligible for Annual Ticket' }}
                    </VAlert>
                  </VCol>
                </VRow>
              </VCard>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

      <VDivider />

      <VCardActions class="pa-5 justify-end ga-2">
        <VBtn variant="outlined" color="secondary" @click="emit('close')">Cancel</VBtn>
        <VBtn variant="flat" color="primary" @click="submit">Save</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>