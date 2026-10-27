<script setup>
const props = defineProps({
  lastPage: { type: Number, default: 1 },
  total: { type: Number, default: 0 },
  perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
  totalVisible: { type: Number, default: 5 },
})

const page = defineModel('page', { type: Number, required: true })
const perPage = defineModel('perPage', { type: Number, required: true })
</script>

<template>
  <div class="d-flex align-center justify-space-between mt-4 px-2 flex-wrap ga-3">
    <div class="d-flex align-center ga-2">
      <span class="text-caption text-medium-emphasis">Rows per page:</span>
      <VSelect
        v-model="perPage"
        :items="props.perPageOptions"
        density="compact"
        variant="outlined"
        hide-details
        style="max-width: 100px"
      />
      <span class="text-caption text-medium-emphasis ms-2">{{ props.total }} total</span>
    </div>
    <VPagination
      v-model="page"
      :length="props.lastPage"
      :total-visible="props.totalVisible"
    />
  </div>
</template>
