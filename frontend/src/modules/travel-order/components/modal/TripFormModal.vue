<script setup>
import GroupTripForm from './GroupTripForm.vue'
import IndividualTripForm from './IndividualTripForm.vue'

defineProps({
  modelValue: Boolean,
  type: String,
  loading: Boolean,
  initialData: Object,
})

const emit = defineEmits(['update:modelValue', 'back', 'close', 'submit'])
</script>

<template>
  <VDialog :model-value="modelValue" max-width="1200" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <VCardTitle class="d-flex align-center justify-space-between pa-5">
        <div class="d-flex align-center ga-3">
          <VBtn icon="ri-arrow-left-line" variant="tonal" color="primary" size="small" :disabled="loading" @click="emit('back')" />
          <VAvatar color="primary" variant="tonal" size="42"><VIcon :icon="type === 'individual' ? 'ri-user-line' : 'ri-group-line'" size="22" /></VAvatar>
          <div>
            <div class="text-subtitle-1 font-weight-bold">{{ type === 'individual' ? 'Individual Trip' : 'Group Trip' }}</div>
            <div class="text-caption text-medium-emphasis">{{ type === 'individual' ? 'Create an individual business trip request.' : 'Create a group business trip request.' }}</div>
          </div>
        </div>
        <VBtn icon="ri-close-line" variant="text" @click="emit('close')" />
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-6">
        <IndividualTripForm v-if="type === 'individual'" :loading="loading" :initial-data="initialData" @cancel="emit('close')" @submit="emit('submit', $event)" />
        <GroupTripForm v-else-if="type === 'group'" :loading="loading" :initial-data="initialData" @cancel="emit('close')" @review="emit('submit', $event)" />
      </VCardText>
    </VCard>
  </VDialog>
</template>