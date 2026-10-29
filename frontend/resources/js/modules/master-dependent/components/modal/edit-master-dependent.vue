<script setup>
import { computed, reactive, ref } from 'vue'

const MAX_ELIGIBLE_AGE = 23

const props = defineProps({
  employee: { type: Object, required: true },
})

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

  emit('submit', {
    employee_id: props.employee.id,
    dependents: dependents.map(({ id, name, relationship, dob }) => ({ id, name, relationship, dob })),
  })
}
</script>

<template>
  <VDialog :model-value="true" max-width="900" persistent scrollable>
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-5">
        <div>
          <div class="text-h6">Edit Dependents</div>
          <div class="text-body-2 text-medium-emphasis">{{ props.employee.name }}</div>
        </div>
        <DialogCloseBtn @click="emit('close')" />
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-5">
        <div class="d-flex justify-end mb-4">
          <VBtn variant="tonal" color="primary" size="small" prepend-icon="ri-add-line" @click="addRow">
            Add Family Member
          </VBtn>
        </div>

        <VForm ref="formRef" @submit.prevent="submit">
          <VRow v-for="(item, index) in dependents" :key="item.key" align="center">
            <VCol cols="12" md="4">
              <AppTextField v-model="item.name" label="Dependent Name" placeholder="Enter dependent name" :rules="[required]" />
            </VCol>

            <VCol cols="12" md="3">
              <AppSelect v-model="item.relationship" :items="relationshipOptions" label="Relationship" placeholder="Select relationship" :rules="[required, onlyOneSpouse]" />
            </VCol>

            <VCol cols="12" md="3">
              <AppTextField v-model="item.dob" type="date" label="Date of Birth" :rules="[required, beforeToday]" />
            </VCol>

            <VCol cols="10" md="1" class="text-center">
              <VChip v-if="ageInfo(item)" size="small" variant="tonal" :color="ageInfo(item).eligible ? 'success' : 'error'" class="text-no-wrap">
                {{ ageInfo(item).age }} th
              </VChip>
            </VCol>

            <VCol cols="2" md="1" class="text-center">
              <VBtn icon="ri-delete-bin-line" size="small" variant="text" color="error" :disabled="dependents.length === 1" @click="removeDependent(index)" />
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