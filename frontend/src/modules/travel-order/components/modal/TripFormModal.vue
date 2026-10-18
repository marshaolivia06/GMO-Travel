
<script setup>
import { ref, watch } from 'vue'
import GroupTripForm from './GroupTripForm.vue'
import IndividualTripForm from './IndividualTripForm.vue'

const individualForm = ref(null)

const props = defineProps({
  modelValue: Boolean,
  type: String,
  loading: Boolean,
  initialData: Object,
  memberOptions: { type: Array, default: () => [] },
})

const isSummary = ref(false)

watch(() => props.modelValue, value => {
  if (value) isSummary.value = false
})

const emit = defineEmits(['update:modelValue', 'back', 'summary-back', 'close', 'submit'])
</script>

<template>
  <VDialog :model-value="modelValue" max-width="1200" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <VCardTitle class="d-flex align-center justify-space-between pa-5">
        <div class="d-flex align-center ga-3">
          <VBtn icon="ri-arrow-left-line" variant="tonal" color="primary" size="small" :disabled="loading" @click="isSummary ? individualForm?.handleSummaryBack() : emit('back')" />
          <div class="text-h6 font-weight-bold">{{ isSummary ? 'Summary Travel Order' : 'Create Travel Order' }}</div>
        </div>
        <VBtn icon="ri-close-line" variant="text" @click="emit('close')" />
      </VCardTitle>
      <VDivider />

      <VCardText class="pa-6">
        <IndividualTripForm ref="individualForm" v-if="type === 'individual'" :loading="loading" :initial-data="initialData" @cancel="emit('close')" @submit="emit('submit', $event)" @summary-change="isSummary = $event" />
        <GroupTripForm v-else-if="type === 'group'" :loading="loading" :initial-data="initialData" :member-options="props.memberOptions" @cancel="emit('close')" @review="emit('submit', $event)" />
      </VCardText>
    </VCard>
  </VDialog>
</template>
