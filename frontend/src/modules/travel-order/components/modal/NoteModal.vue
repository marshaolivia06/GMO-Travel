<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  modelValue: Boolean,
  meta: { type: Object, required: true },
})

const emit = defineEmits(['update:modelValue', 'submit'])
const text = ref('')
const evidence = ref(null)
const error = ref('')
const fileError = ref('')
const isCancellation = () => props.meta.action === 'cancelled'

watch(() => props.modelValue, open => {
  if (open) {
    text.value = ''
    evidence.value = null
    error.value = ''
    fileError.value = ''
  }
})

function setEvidenceFile(event) {
  evidence.value = event.target.files?.[0] ?? null
  fileError.value = ''
}

function submit() {
  if (!text.value.trim()) {
    error.value = 'Please fill in this field.'
    return
  }
  if (isCancellation() && !evidence.value) {
    fileError.value = 'Please upload cancellation evidence.'
    return
  }
  emit('submit', { text: text.value.trim(), evidence: isCancellation() ? evidence.value : null })
}
</script>

<template>
  <VDialog :model-value="modelValue" max-width="440" @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <VCardTitle class="px-5 py-3 text-subtitle-1 font-weight-bold">{{ meta.title }}</VCardTitle>
      <VDivider />
      <VCardText class="px-5 py-4">
        <div class="text-body-2 text-medium-emphasis mb-2">Remark</div>
        <VTextarea v-model="text" placeholder="Enter remark" variant="outlined" rows="3" auto-grow :error-messages="error" @update:model-value="error = ''" />
        <template v-if="isCancellation()">
  <div class="text-caption font-weight-medium mb-1 mt-0">Upload Evidence <span class="text-error"></span></div>
  <div class="d-flex align-center w-100" style="background:#fff;border:1px solid #bdbdbd;border-radius:8px;padding:8px 10px;min-height:48px">
  <input type="file" accept=".pdf,.jpg,.jpeg,.png" class="w-100" style="font-size:13px;border:0;background:transparent;outline:none" @change="setEvidenceFile" />
</div>
  <div v-if="fileError" class="text-caption text-error mt-1">{{ fileError }}</div>
</template>
      </VCardText>
      <VCardActions class="px-5 pb-3 pt-0 justify-end ga-2">
        <VBtn variant="tonal" color="secondary" @click="emit('update:modelValue', false)">Cancel</VBtn>
        <VBtn variant="flat" :color="meta.color" @click="submit">{{ meta.button }}</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>