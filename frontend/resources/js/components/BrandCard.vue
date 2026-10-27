<template>
  <VContainer fluid>
    <VCard elevation="3" rounded="lg" class="brand-card">
      <div v-if="$slots.stats" class="pa-4 pb-0">
        <slot name="stats" />
      </div>

      <div class="d-flex align-center justify-space-between pa-4" style="gap: 16px">
        <slot name="action" />
        <slot name="search" />
      </div>

      <slot />
    </VCard>
  </VContainer>
</template>
