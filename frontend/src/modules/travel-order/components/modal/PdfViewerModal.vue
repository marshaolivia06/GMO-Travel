<script setup>
defineProps({
  modelValue: Boolean,
  title: String,
  pdfUrl: String,
  loading: Boolean,
  canDecide: Boolean,
  approving: Boolean,
})
const emit = defineEmits(['update:modelValue', 'back', 'reject', 'revision', 'approve'])
</script>

<template>
  <VDialog :model-value="modelValue" max-width="1100" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <VCardItem class="px-4 py-2">
        <div class="d-flex align-center ga-1">
          <VBtn v-if="canDecide" icon="ri-arrow-left-line" size="small" variant="text" @click="emit('back')" />
          <VCardTitle class="pa-0 text-subtitle-1 font-weight-bold">{{ title }}</VCardTitle>
        </div>
        <template #append>
          <VBtn icon="ri-close-line" size="small" variant="text" @click="emit('update:modelValue', false)" />
        </template>
      </VCardItem>

      <VDivider />

      <VCardText class="bg-grey-lighten-3 pa-0">
        <VProgressLinear v-if="loading" indeterminate />
        <iframe v-if="pdfUrl" :src="pdfUrl" title="Travel Order PDF" height="750" class="d-block w-100 border-0" />
        <div v-else-if="!loading" class="pa-6 text-center text-medium-emphasis">PDF is not available.</div>
      </VCardText>

      <template v-if="canDecide">
        <VDivider />
        <VCardActions class="px-6 py-3 justify-end ga-2">
          <VBtn variant="flat" color="error" :disabled="approving" @click="emit('reject')">Reject</VBtn>
          <VBtn variant="flat" color="warning" :disabled="approving" @click="emit('revision')">Revision</VBtn>
          <VBtn variant="flat" color="success" :loading="approving" @click="emit('approve')">Approve</VBtn>
        </VCardActions>
      </template>
    </VCard>
  </VDialog>
</template>