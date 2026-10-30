<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({
  modelValue: Boolean,
  ticket: { type: Object, default: null },
  loading: Boolean,
})

const emit = defineEmits(['update:modelValue'])
const activeTab = ref('')

const fareAttachments = computed(() => (props.ticket?.fare_reference_attachments ?? []).map((attachment, index) => ({
  key: `fare-${attachment.fare_reference_attachment_id}`,
  title: `Fare Reference`,
  path: attachment.file_url,
})))

const attachments = computed(() => [
  { key: 'hermes', title: 'Haermes Evidence', path: props.ticket?.evidence_url },
  ...fareAttachments.value,
].filter(item => item.path))

function isImage(path) {
  return /\.(png|jpe?g|gif|webp|bmp|svg)(\?.*)?$/i.test(path || '')
}

function isPdf(path) {
  return /\.pdf(\?.*)?$/i.test(path || '')
}

watch(
  () => [props.modelValue, attachments.value],
  ([open, items]) => {
    if (open && !items.some(item => item.key === activeTab.value)) {
      activeTab.value = items[0]?.key || ''
    }
  },
  { immediate: true },
)
</script>

<template>
  <VDialog :model-value="modelValue" max-width="900" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <VCardTitle class="d-flex align-center justify-space-between px-5 py-4">
        <div>
          <div class="text-h6 font-weight-semibold">Annual Ticket Attachments</div>
          <div class="text-body-2 text-medium-emphasis mt-1">View evidence and final travel documents.</div>
        </div>
        <VBtn icon="ri-close-line" variant="text" @click="emit('update:modelValue', false)" />
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-0">
        <VTabs v-model="activeTab" density="comfortable">
          <VTab v-for="item in attachments" :key="item.key" :value="item.key">{{ item.title }}</VTab>
        </VTabs>
        <VWindow v-model="activeTab">
          <VWindowItem v-for="item in attachments" :key="item.key" :value="item.key">
            <div class="pa-4 d-flex justify-center align-center bg-grey-lighten-4" style="min-height: 400px">
              <img v-if="isImage(item.path)" :src="item.path" :alt="item.title" style="max-width: 100%; max-height: 60vh; object-fit: contain" />
              <iframe v-else-if="isPdf(item.path)" :src="item.path" style="width: 100%; height: 65vh; border: 0" />
              <VBtn v-else variant="tonal" color="primary" :href="item.path" target="_blank" prepend-icon="ri-external-link-line">Open File</VBtn>
            </div>
          </VWindowItem>
        </VWindow>
        <div v-if="!attachments.length" class="pa-6 text-center text-medium-emphasis">No attachments.</div>
      </VCardText>
    </VCard>
  </VDialog>
</template>
