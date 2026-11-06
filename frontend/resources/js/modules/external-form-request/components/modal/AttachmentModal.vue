<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: Boolean,
  attachmentUrl: { type: String, default: null },
})

const emit = defineEmits(['update:modelValue'])

const isImage = computed(() => /\.(png|jpe?g|gif|webp|bmp|svg)(\?.*)?$/i.test(props.attachmentUrl || ''))
const isPdf = computed(() => /\.pdf(\?.*)?$/i.test(props.attachmentUrl || ''))
</script>

<template>
  <VDialog :model-value="modelValue" max-width="900" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <VCardTitle class="d-flex align-center justify-space-between px-5 py-4">
        <div><div class="text-h6 font-weight-semibold">Release Attachment</div><div class="text-body-2 text-medium-emphasis mt-1">View the document sent to the requester.</div></div>
        <VBtn icon="ri-close-line" variant="text" @click="emit('update:modelValue', false)" />
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-0">
        <div class="pa-4 d-flex justify-center align-center bg-grey-lighten-4" style="min-height: 400px">
          <img v-if="isImage" :src="attachmentUrl" alt="Release Attachment" style="max-width: 100%; max-height: 60vh; object-fit: contain" />
          <iframe v-else-if="isPdf" :src="attachmentUrl" style="width: 100%; height: 65vh; border: 0" />
          <VBtn v-else variant="tonal" color="primary" :href="attachmentUrl" target="_blank" prepend-icon="ri-external-link-line">Open File</VBtn>
        </div>
      </VCardText>
    </VCard>
  </VDialog>
</template>
