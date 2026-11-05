<script setup>
import { computed, reactive, ref } from 'vue'

const MAX_ELIGIBLE_AGE = 23
const props = defineProps({ employee: { type: Object, required: true } })
const emit = defineEmits(['close', 'submit'])

const formRef = ref(null)
const relationshipOptions = ['Spouse', 'Child']
const todayStr = new Date().toLocaleDateString('en-CA')
let seq = 0
const withKey = item => ({ id: item.id ?? null, key: item.id ?? `new-${seq++}`, name: item.name, relationship: item.relationship, dob: item.dob })
const dependents = reactive(props.employee.dependents.map(withKey))

const required = v => (v !== null && v !== undefined && v !== '') || 'This field is required'
const beforeToday = v => !v || v < todayStr || 'Date must be before today'
const spouseCount = computed(() => dependents.filter(d => d.relationship === 'Spouse').length)
const onlyOneSpouse = v => v !== 'Spouse' || spouseCount.value <= 1 || 'Only one spouse is allowed'

function ageInfo(item) {
  if (!item.dob || !item.relationship) return null
  const birth = new Date(item.dob)
  const today = new Date()
  let age = today.getFullYear() - birth.getFullYear()
  const month = today.getMonth() - birth.getMonth()
  if (month < 0 || (month === 0 && today.getDate() < birth.getDate())) age--
  return { age, eligible: item.relationship === 'Spouse' || age < MAX_ELIGIBLE_AGE }
}

const addRow = () => dependents.push(withKey({ id: null, name: '', relationship: null, dob: '' }))
const removeDependent = index => dependents.splice(index, 1)

async function submit() {
  const { valid } = await formRef.value.validate()
  if (!valid) return
  emit('submit', { employee_id: props.employee.id, dependents: dependents.map(({ id, name, relationship, dob }) => ({ id, name, relationship, dob })) })
}
</script>

<template>
  <VDialog :model-value="true" max-width="900" persistent>
    <VCard rounded="lg" class="d-flex flex-column" style="max-height: 90vh">
      <div class="d-flex align-center justify-space-between px-5 bg-primary rounded-t-lg flex-shrink-0" style="height: 60px">
        <div class="d-flex flex-column">
          <span class="text-title-large font-weight-bold text-white">Edit Dependents</span>
          <span class="text-body-small text-white">{{ props.employee.name }}</span>
        </div>
        <VBtn icon="ri-close-line" variant="text" color="white" density="comfortable" @click="emit('close')" />
      </div>

      <VForm ref="formRef" class="d-flex flex-column" style="min-height: 0; flex: 1 1 auto" @submit.prevent="submit">
        <VCardText class="pa-5" style="overflow-y: auto; flex: 1 1 auto">
          <div class="d-flex justify-end mb-4">
            <VBtn variant="tonal" color="primary" size="small" prepend-icon="ri-add-line" @click="addRow">Add Family Member</VBtn>
          </div>
          <VRow v-for="(item, index) in dependents" :key="item.key" dense>
            <VCol cols="12" md="4">
              <label class="text-body-medium font-weight-600 d-block mb-1">Dependent Name</label>
              <VTextField v-model="item.name" :rules="[required]" placeholder="Enter dependent name" hide-details="auto"/>
            </VCol>
            <VCol cols="12" md="3">
              <label class="text-body-medium font-weight-600 d-block mb-1">Relationship</label>
              <VAutocomplete v-model="item.relationship" :items="relationshipOptions" :rules="[required, onlyOneSpouse]" placeholder="Select relationship" hide-details="auto"/>
            </VCol>
            <VCol cols="12" md="3">
              <label class="text-body-medium font-weight-600 d-block mb-1">Date of Birth</label>
              <VTextField v-model="item.dob" type="date" :rules="[required, beforeToday]" hide-details="auto"/>
            </VCol>
            <VCol cols="10" md="1" class="d-flex align-end justify-center">
              <VChip v-if="ageInfo(item)" size="small" variant="tonal" :color="ageInfo(item).eligible ? 'success' : 'error'" class="text-no-wrap mb-2">{{ ageInfo(item).age }} th</VChip>
            </VCol>
            <VCol cols="2" md="1" class="d-flex align-end justify-center">
              <VBtn icon="ri-delete-bin-line" size="small" variant="text" color="error" :disabled="dependents.length === 1" @click="removeDependent(index)" />
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