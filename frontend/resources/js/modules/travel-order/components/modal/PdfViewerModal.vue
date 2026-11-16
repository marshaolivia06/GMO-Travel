<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({
  modelValue: Boolean,
  title: String,
  pdfUrl: String,
  loading: Boolean,
  canDecide: Boolean,
  approving: Boolean,
  showBack: Boolean,
  hideBack: Boolean,
  attachmentUrl: { type: String, default: null },
  attachments: { type: Array, default: () => [] },
  confirmText: { type: String, default: 'I confirm that I have reviewed the Travel Order document and agree that it is ready for approval.' },
})

const emit = defineEmits(['update:modelValue', 'back', 'reject', 'revision', 'approve'])
const agreed = ref(false)
const pdfReady = ref(false)
const activeTab = ref('document')
const activeAttachmentTab = ref('')

// Path relatif storage di-resolve ke URL API - path absolut (http) dilewati apa adanya
function getFileUrl(path) {
  if (!path) return ''
  if (/^https?:\/\//i.test(path)) return path
  const baseUrl = String(import.meta.env.VITE_API_BASE_URL || '').replace(/\/+$/, '')
  const filePath = String(path).replace(/^\/+/, '').replace(/^storage\//, '')
  return `${baseUrl}/storage/${filePath}`
}

// Travel Order: beberapa lampiran dikelompokkan ke dalam satu tab "Attachment" (sub-tab di dalamnya)
const groupedAttachments = computed(() => (props.attachments || [])
  .filter(item => item.path || item.url)
  .map(item => ({ key: item.key, title: item.title, url: item.url || getFileUrl(item.path) })))

// Module lain (mis. External Form Request): satu lampiran langsung jadi tab "Attachment"
const flatAttachments = computed(() => props.attachmentUrl ? [{ key: 'attachment', title: 'Attachment', url: props.attachmentUrl }] : [])

const isImage = url => /\.(png|jpe?g|gif|webp|bmp|svg)(\?.*)?$/i.test(url || '')
const isPdf = url => /\.pdf(\?.*)?$/i.test(url || '')

watch(() => [props.modelValue, props.pdfUrl], () => {
  agreed.value = false
  pdfReady.value = false
  activeTab.value = 'document'
  activeAttachmentTab.value = groupedAttachments.value[0]?.key || ''
})
</script>

<template>
  <VDialog :model-value="modelValue" max-width="1100" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <VCardItem class="px-4 py-2">
        <div class="d-flex align-center ga-1">
          <VBtn v-if="(canDecide || showBack) && !hideBack" icon="ri-arrow-left-line" size="small" variant="text" @click="emit('back')" />
          <VCardTitle class="pa-0 text-subtitle-1 font-weight-bold">{{ title }}</VCardTitle>
        </div>
        <template #append>
          <VBtn icon="ri-close-line" size="small" variant="text" @click="emit('update:modelValue', false)" />
        </template>
      </VCardItem>

      <VDivider />

      <VCardText class="bg-grey-lighten-3 pa-0">
        <!-- Lampiran terkelompok: tab utama Document | Attachment, lampiran jadi sub-tab di dalam Attachment -->
        <template v-if="groupedAttachments.length">
          <VTabs v-model="activeTab" color="primary" density="comfortable" class="bg-white">
            <VTab value="document" class="text-none">Document</VTab>
            <VTab value="attachments" class="text-none">Attachment</VTab>
          </VTabs>

          <VWindow v-model="activeTab">
            <VWindowItem value="document">
              <VProgressLinear v-if="loading || (pdfUrl && !pdfReady)" indeterminate />
              <iframe v-if="pdfUrl" :key="pdfUrl" :src="pdfUrl" title="Document PDF" height="750" class="d-block w-100 border-0" @load="pdfReady = true" />
              <div v-else-if="!loading" class="pa-6 text-center text-medium-emphasis">PDF is not available.</div>
            </VWindowItem>

            <VWindowItem value="attachments">
              <template v-if="groupedAttachments.length > 1">
                <VTabs v-model="activeAttachmentTab" color="primary" density="comfortable" class="bg-white">
                  <VTab v-for="tab in groupedAttachments" :key="tab.key" :value="tab.key" class="text-none">{{ tab.title }}</VTab>
                </VTabs>
                <VWindow v-model="activeAttachmentTab">
                  <VWindowItem v-for="tab in groupedAttachments" :key="tab.key" :value="tab.key">
                    <div class="pa-4 d-flex justify-center align-center" style="min-height: 400px">
                      <img v-if="isImage(tab.url)" :src="tab.url" :alt="tab.title" style="max-width: 100%; max-height: 70vh; object-fit: contain" />
                      <iframe v-else-if="isPdf(tab.url)" :src="tab.url" :title="tab.title" class="d-block w-100 border-0" height="750" />
                      <VBtn v-else variant="tonal" color="primary" :href="tab.url" target="_blank" prepend-icon="ri-external-link-line">Open File</VBtn>
                    </div>
                  </VWindowItem>
                </VWindow>
              </template>
              <div v-else class="pa-4 d-flex justify-center align-center" style="min-height: 400px">
                <img v-if="isImage(groupedAttachments[0].url)" :src="groupedAttachments[0].url" :alt="groupedAttachments[0].title" style="max-width: 100%; max-height: 70vh; object-fit: contain" />
                <iframe v-else-if="isPdf(groupedAttachments[0].url)" :src="groupedAttachments[0].url" :title="groupedAttachments[0].title" class="d-block w-100 border-0" height="750" />
                <VBtn v-else variant="tonal" color="primary" :href="groupedAttachments[0].url" target="_blank" prepend-icon="ri-external-link-line">Open File</VBtn>
              </div>
            </VWindowItem>
          </VWindow>
        </template>

        <!-- Lampiran tunggal (module lain): PDF | Attachment -->
        <template v-else-if="flatAttachments.length">
          <VTabs v-model="activeTab" color="primary" density="comfortable" class="bg-white">
            <VTab value="document" class="text-none">Document</VTab>
            <VTab value="attachment" class="text-none">Attachment</VTab>
          </VTabs>

          <VWindow v-model="activeTab">
            <VWindowItem value="document">
              <VProgressLinear v-if="loading || (pdfUrl && !pdfReady)" indeterminate />
              <iframe v-if="pdfUrl" :key="pdfUrl" :src="pdfUrl" title="Document PDF" height="750" class="d-block w-100 border-0" @load="pdfReady = true" />
              <div v-else-if="!loading" class="pa-6 text-center text-medium-emphasis">PDF is not available.</div>
            </VWindowItem>
            <VWindowItem value="attachment">
              <div class="pa-4 d-flex justify-center align-center" style="min-height: 400px">
                <img v-if="isImage(flatAttachments[0].url)" :src="flatAttachments[0].url" :alt="flatAttachments[0].title" style="max-width: 100%; max-height: 70vh; object-fit: contain" />
                <iframe v-else-if="isPdf(flatAttachments[0].url)" :src="flatAttachments[0].url" :title="flatAttachments[0].title" class="d-block w-100 border-0" height="750" />
                <VBtn v-else variant="tonal" color="primary" :href="flatAttachments[0].url" target="_blank" prepend-icon="ri-external-link-line">Open File</VBtn>
              </div>
            </VWindowItem>
          </VWindow>
        </template>

        <!-- Tanpa lampiran: PDF saja -->
        <template v-else>
          <VProgressLinear v-if="loading || (pdfUrl && !pdfReady)" indeterminate />
          <iframe v-if="pdfUrl" :key="pdfUrl" :src="pdfUrl" title="Document PDF" height="750" class="d-block w-100 border-0" @load="pdfReady = true" />
          <div v-else-if="!loading" class="pa-6 text-center text-medium-emphasis">PDF is not available.</div>
        </template>
      </VCardText>

<template v-if="canDecide && pdfUrl && pdfReady && !loading">
  <VDivider />
  <VCardText class="px-8 py-5">
    <div class="d-flex align-start ga-3">
      <input v-model="agreed" type="checkbox" class="mt-1 flex-shrink-0">
      <div class="text-body-2">{{ confirmText }}</div>
    </div>
  </VCardText>
  <slot name="decide" :agreed="agreed" :deciding="approving">
    <VCardActions v-if="agreed" class="px-8 pb-5 pt-0 justify-end ga-3">
      <VBtn variant="flat" color="error" :disabled="approving" @click="emit('reject')">Reject</VBtn>
      <VBtn variant="flat" color="warning" :disabled="approving" @click="emit('revision')">Revision</VBtn>
      <VBtn variant="flat" color="primary" :loading="approving" @click="emit('approve')">Approve</VBtn>
    </VCardActions>
  </slot>
</template>

<template v-else-if="pdfUrl && pdfReady && !loading && $slots.actions">
  <slot name="actions" />
</template>

    </VCard>
  </VDialog>
</template>
