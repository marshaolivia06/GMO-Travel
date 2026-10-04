<script setup>
defineProps({
  modelValue: Boolean,
  title: String,
  subtitle: String,
  items: { type: Array, default: () => [] },
  showBack: Boolean,
})

const emit = defineEmits(['update:modelValue', 'select', 'back'])
</script>

<template>
  <VDialog :model-value="modelValue" max-width="720" @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="xl">
      <VCardItem class="px-6 py-5">
        <template v-if="showBack" #prepend>
          <VBtn icon="ri-arrow-left-line" variant="tonal" color="primary" size="small" @click="emit('back')" />
        </template>
        <VCardTitle class="text-h6 font-weight-bold">{{ title }}</VCardTitle>
        <VCardSubtitle>{{ subtitle }}</VCardSubtitle>
        <template #append>
          <VBtn icon="ri-close-line" variant="text" size="small" @click="emit('update:modelValue', false)" />
        </template>
      </VCardItem>

      <VDivider />

      <VCardText class="pa-6">
        <VRow>
          <VCol v-for="item in items" :key="item.key" cols="6">
            <VCard variant="outlined" rounded="lg" hover class="pick-tile h-100" @click="emit('select', item.key)">
              <VCardText class="pa-6">
                <VAvatar color="primary" rounded="lg" size="56" class="mb-4"><span class="text-h6 font-weight-bold">{{ item.badge }}</span></VAvatar>
                <div class="text-h6 font-weight-bold mb-1">{{ item.title }}</div>
                <div class="text-body-2 text-medium-emphasis">{{ item.desc }}</div>
                <ul class="text-body-2 text-medium-emphasis ps-5 mt-4"><li v-for="point in item.points" :key="point">{{ point }}</li></ul>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style scoped>
.pick-tile { border-color: rgba(var(--v-border-color), var(--v-border-opacity)); transition: transform .2s, border-color .2s; }
.pick-tile:hover { transform: translateY(-4px); border-color: rgb(var(--v-theme-primary)); }
</style>