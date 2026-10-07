<script setup>
defineProps({ modelValue: Boolean, loading: Boolean, rounds: { type: Array, default: () => [] } })
const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <VDialog :model-value="modelValue" max-width="640" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <VCardItem class="px-6 py-4">
        <VCardTitle class="text-h6 font-weight-bold">History Log</VCardTitle>
        <template #append><VBtn icon="ri-close-line" variant="text" size="small" color="grey-darken-1" @click="emit('update:modelValue', false)" /></template>
      </VCardItem>
      <VDivider />
      <VProgressLinear v-if="loading" indeterminate color="grey" />
      <VCardText class="px-6 py-5">
        <div v-if="!loading && !rounds.length" class="text-body-2 text-medium-emphasis text-center py-6">No history found.</div>
        <div v-for="(group, index) in rounds" :key="group.round" :class="index ? 'mt-6' : ''">
          <div class="d-flex align-center ga-2 mb-3">
            <span class="text-subtitle-2 font-weight-bold text-no-wrap">Round {{ group.round }}</span>
          </div>
          <VTimeline side="end" align="start" density="compact" line-thickness="1" line-color="grey-lighten-2">
            <VTimelineItem v-for="item in group.items" :key="item.key" dot-color="grey" size="x-small">
                <div class="d-flex align-center ga-3">
  <span class="text-body-2 font-weight-bold">{{ item.chipText }}</span>
  <span class="text-caption text-medium-emphasis text-no-wrap">{{ item.time }}</span>
</div>
              <div class="text-caption text-medium-emphasis mt-1">{{ item.role }} · {{ item.name }}</div>
              <VCard v-if="item.note" variant="flat" color="grey-lighten-4" rounded="md" class="mt-2">
  <VCardText class="pa-2 text-body-2">
    <span class="font-weight-medium">Remark:</span> {{ item.note }}
  </VCardText>
</VCard>
            </VTimelineItem>
          </VTimeline>
        </div>
      </VCardText>
    </VCard>
  </VDialog>
</template>
