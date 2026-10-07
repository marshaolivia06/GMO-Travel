<script setup>
defineProps({
  modelValue: Boolean,
  loading: Boolean,
  cards: { type: Array, default: () => [] },
  canApprove: Boolean,
})

const emit = defineEmits(['update:modelValue', 'review'])
</script>

<template>
  <VDialog :model-value="modelValue" max-width="1000" @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <VCardTitle class="px-6 py-4 text-h6 font-weight-bold">Travel Order Approval</VCardTitle>
      <VProgressLinear v-if="loading" indeterminate />

      <VCardText class="pa-6">
        <VRow align="stretch">
          <VCol v-for="card in cards" :key="card.key" cols="12" md="4">
            <VCard variant="flat" rounded="lg" height="120" class="approval-card bg-grey-lighten-5">
    <VCardText class="flex-grow-1">
      <div class="d-flex align-start justify-space-between ga-2">
        <div class="text-subtitle-1 font-weight-medium">{{ card.title }}</div>
        <VChip size="small" variant="flat" :color="card.chipColor" class="text-no-wrap">{{ card.chipText }}</VChip>
      </div>
      <div class="text-body-2 text-medium-emphasis mt-1">{{ card.caption }}</div>
      <div v-if="card.name" class="text-body-2 font-weight-bold mt-1">{{ card.name }}</div>
      <div v-if="card.time" class="text-body-2 text-medium-emphasis">{{ card.time }}</div>
      <div v-if="card.note" class="text-body-2 mt-2"><span class="font-weight-bold">Note:</span> {{ card.note }}</div>
      <div v-if="card.isCurrent && canApprove" class="d-flex justify-end mt-3">
        <VBtn size="small" color="amber" elevation="3" @click="emit('review')">Approve Here</VBtn>
      </div>
    </VCardText>
  </VCard>
</VCol>
</VRow>
      </VCardText>

      <VCardActions class="px-6 pb-4 justify-end">
        <VBtn variant="flat" color="grey" @click="emit('update:modelValue', false)">Close</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.approval-card { transition: background-color .2s ease, border-color .2s ease, box-shadow .2s ease; }
.approval-card:hover { background: rgb(var(--v-theme-surface)) !important; border-color: rgba(var(--v-theme-primary), .2); box-shadow: 0 3px 10px rgba(0, 0, 0, .07); }
</style>