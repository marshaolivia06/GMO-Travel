<script setup>
import { reactive, ref } from 'vue'

const props = defineProps({
  destination: { type: Object, required: true },
})

const emit = defineEmits(['close', 'submit'])

const formRef = ref(null)

const statusOptions = [
  { title: 'Active', value: true },
  { title: 'Inactive', value: false },
]

const form = reactive({
  destination_code: props.destination.destination_code,
  city_name: props.destination.city_name,
  country: props.destination.country,
  is_active: props.destination.is_active,
})

const required = v => (v !== null && v !== undefined && String(v).trim() !== '') || 'This field is required'

async function submit() {
  const { valid } = await formRef.value.validate()

  if (!valid) return

  emit('submit', {
    id: props.destination.id,
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
        <span class="text-h6">Edit Destination</span>
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
                disabled
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
        <VBtn variant="flat" color="primary" @click="submit">Save Changes</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>