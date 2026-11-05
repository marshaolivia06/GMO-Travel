<script setup>
import { reactive, ref } from 'vue'

const props = defineProps({ destination: { type: Object, required: true } })
const emit = defineEmits(['close', 'submit'])

const formRef = ref(null)
const statusOptions = [{ title: 'Active', value: true }, { title: 'Inactive', value: false }]
const form = reactive({ destination_code: props.destination.destination_code, city_name: props.destination.city_name, country: props.destination.country, is_active: props.destination.is_active })

const required = v => (v !== null && v !== undefined && String(v).trim() !== '') || 'This field is required'

async function submit() {
  const { valid } = await formRef.value.validate()
  if (!valid) return
  emit('submit', { id: props.destination.id, city_name: form.city_name.trim(), country: form.country.trim(), is_active: form.is_active })
}
</script>

<template>
  <VDialog :model-value="true" max-width="600" persistent>
    <VCard rounded="lg" class="d-flex flex-column" style="max-height: 90vh">
      <div class="d-flex align-center justify-space-between px-5 bg-primary rounded-t-lg flex-shrink-0" style="height: 60px">
        <span class="text-title-large font-weight-bold text-white">Edit Destination</span>
        <VBtn icon="ri-close-line" variant="text" color="white" density="comfortable" @click="emit('close')" />
      </div>

      <VForm ref="formRef" class="d-flex flex-column" style="min-height: 0; flex: 1 1 auto" @submit.prevent="submit">
        <VCardText class="pa-5" style="overflow-y: auto; flex: 1 1 auto">
          <VRow dense>
            <VCol cols="12" md="6">
              <label class="text-body-medium font-weight-600 d-block mb-1">Destination Code</label>
              <VTextField v-model="form.destination_code" disabled hide-details="auto"/>
            </VCol>
            <VCol cols="12" md="6">
              <label class="text-body-medium font-weight-600 d-block mb-1">City Name</label>
              <VTextField v-model="form.city_name" placeholder="Enter city name" maxlength="100" :rules="[required]" hide-details="auto"/>
            </VCol>
            <VCol cols="12" md="6">
              <label class="text-body-medium font-weight-600 d-block mb-1">Country</label>
              <VTextField v-model="form.country" placeholder="Enter country" maxlength="100" :rules="[required]" hide-details="auto"/>
            </VCol>
            <VCol cols="12" md="6">
              <label class="text-body-medium font-weight-600 d-block mb-1">Status</label>
              <VAutocomplete v-model="form.is_active" :items="statusOptions" item-title="title" item-value="value" placeholder="Select status" :rules="[required]" hide-details="auto"/>
            </VCol>
          </VRow>
        </VCardText>

        <VDivider class="flex-shrink-0" />
        <VCardActions class="pa-5 flex-shrink-0">
          <VSpacer />
          <VBtn variant="tonal" @click="emit('close')">Close</VBtn>
          <VBtn type="submit" color="primary" variant="flat">Save Changes</VBtn>
        </VCardActions>
      </VForm>
    </VCard>
  </VDialog>
</template>