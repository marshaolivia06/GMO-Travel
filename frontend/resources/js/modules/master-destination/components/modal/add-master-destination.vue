<script setup>
import { reactive, ref } from 'vue'

const props = defineProps({
  existingCodes: { type: Array, default: () => [] },
})

const emit = defineEmits(['close', 'submit'])

const formRef = ref(null)

const statusOptions = [
  { title: 'Active', value: true },
  { title: 'Inactive', value: false },
]

const form = reactive({
  destination_code: '',
  city_name: '',
  country: 'Indonesia',
  is_active: true,
})

const required = v => (v !== null && v !== undefined && String(v).trim() !== '') || 'This field is required'
const uniqueCode = v => !props.existingCodes.includes(String(v).trim().toUpperCase()) || 'Code is already used'

async function submit() {
  const { valid } = await formRef.value.validate()

  if (!valid) return

  emit('submit', {
    destination_code: form.destination_code.trim().toUpperCase(),
    city_name: form.city_name.trim(),
    country: form.country.trim(),
    is_active: form.is_active,
  })
}
</script>

<template>
  <VDialog :model-value="true" max-width="600" persistent scrollable>
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-5">
        <span class="text-h6">Add Destination</span>
        <DialogCloseBtn @click="emit('close')" />
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-5">
        <VForm ref="formRef" @submit.prevent="submit">
            <VRow>
            <VCol cols="12" md="6">
              <AppTextField
                v-model="form.destination_code"
                label="Destination Code"
                placeholder="e.g. MDN"
                maxlength="20"
                :rules="[required, uniqueCode]"
              />
            </VCol>

            <VCol cols="12" md="6">
              <AppTextField
                v-model="form.city_name"
                label="City Name"
                placeholder="Enter city name"
                maxlength="100"
                :rules="[required]"
              />
            </VCol>

            <VCol cols="12" md="6">
              <AppTextField
                v-model="form.country"
                label="Country"
                placeholder="Enter country"
                maxlength="100"
                :rules="[required]"
              />
            </VCol>

            <VCol cols="12" md="6">
              <AppSelect
                v-model="form.is_active"
                :items="statusOptions"
                item-title="title"
                item-value="value"
                label="Status"
                placeholder="Select status"
                :rules="[required]"
              />
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