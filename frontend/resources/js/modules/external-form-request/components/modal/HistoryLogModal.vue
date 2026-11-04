<script setup>
defineProps({
  modelValue: Boolean,
  loading: Boolean,
  logs: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue'])

const eventMeta = {
  submit: { text: 'Submitted', color: 'primary' },
  draft: { text: 'Draft Saved', color: 'secondary' },
}

const formatTime = value => {
  if (!value) return ''

  return new Intl.DateTimeFormat('en-GB', { timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false }).format(new Date(value))
}
</script>

<template>
  <VDialog :model-value="modelValue" max-width="640" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <VCardItem class="px-6 py-4">
        <VCardTitle class="text-h6 font-weight-bold">History Log</VCardTitle>
        <template #append>
          <VBtn icon="ri-close-line" variant="text" size="small" color="grey-darken-1" @click="emit('update:modelValue', false)" />
        </template>
      </VCardItem>

      <VDivider />
      <VProgressLinear v-if="loading" indeterminate color="grey" />

      <VCardText class="px-6 py-5">
        <div v-if="!loading && !logs.length" class="text-body-2 text-medium-emphasis text-center py-6">
          No history found.
        </div>

        <VTimeline v-else side="end" align="start" density="compact" :line-thickness="1" line-color="grey-lighten-2">
          <VTimelineItem v-for="item in logs" :key="item.id" dot-color="grey" size="x-small">
            <div class="d-flex align-center ga-3">
              <VChip size="x-small" variant="tonal" :color="eventMeta[item.event]?.color || 'grey'" class="text-no-wrap">
                {{ eventMeta[item.event]?.text || item.event }}
              </VChip>
              <span class="text-caption text-medium-emphasis text-no-wrap">{{ formatTime(item.created_at) }}</span>
            </div>

            <div class="text-caption text-medium-emphasis mt-1">{{ item.causer || '-' }}</div>

            <div class="text-body-2 mt-1">{{ item.description }}</div>
          </VTimelineItem>
        </VTimeline>
      </VCardText>
    </VCard>
  </VDialog>
</template>
