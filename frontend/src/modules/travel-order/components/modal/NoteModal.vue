<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  modelValue: Boolean,
  meta: { type: Object, required: true },
})

const emit = defineEmits(['update:modelValue', 'submit'])

const text = ref('')
const error = ref('')

watch(() => props.modelValue, open => {
  if (open) {
    text.value = ''
    error.value = ''
  }
})

function submit() {
  const value = text.value.trim()

  if (!value) {
    error.value = 'Explanation is required.'
    return
  }

  emit('submit', value)
}
</script>

<template>
  <VDialog :model-value="modelValue" max-width="440" @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <VCardTitle class="px-5 py-3 text-subtitle-1 font-weight-bold">{{ meta.title }}</VCardTitle>

      <VDivider />

      <VCardText class="px-5 py-4">
        <div class="text-body-2 text-medium-emphasis mb-2">Remark</div>
        <VTextarea
          v-model="text"
          :label="meta.label"
          variant="outlined"
          rows="3"
          auto-grow
          counter="500"
          maxlength="500"
          :error-messages="error"
          @update:model-value="error = ''"
        />
      </VCardText>

      <VCardActions class="px-5 pb-3 pt-0 justify-end ga-2">
        <VBtn variant="tonal" color="secondary" @click="emit('update:modelValue', false)">Cancel</VBtn>
        <VBtn variant="flat" :color="meta.color" @click="submit">{{ meta.button }}</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>