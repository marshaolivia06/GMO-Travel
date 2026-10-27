<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({ modelValue: Boolean, groups: { type: Array, default: () => [] }, loading: Boolean })
const emit = defineEmits(['update:modelValue', 'select', 'back'])

const search = ref('')

function departmentNames(group) {
  return (group.departments || []).map(department => typeof department === 'object' ? department?.name : department).filter(Boolean)
}

function formatDate(value) {
  if (!value) return '-'
  const date = new Date(value)
  return isNaN(date.getTime()) ? '-' : date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

const filteredGroups = computed(() => {
  const query = (search.value || '').trim().toLowerCase()
  if (!query) return props.groups

  return props.groups.filter(group => [group.trip_name, group.travel_from, group.travel_to, group.travel_region, ...departmentNames(group)]
    .join(' ').toLowerCase().includes(query))
})

watch(() => props.modelValue, open => { if (open) search.value = '' })
</script>

<template>
  <VDialog :model-value="modelValue" max-width="800" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
        <VCardItem class="px-4 py-3">
  <template #prepend>
    <VBtn icon variant="flat" color="#E8ECF4" :size="32" rounded="circle" @click="emit('back')">
      <VIcon icon="ri-arrow-left-line" :size="16" class="text-blue-darken-3" />
    </VBtn>
  </template>
  <div>
    <VCardTitle class="pa-0 text-subtitle-1 font-weight-bold">Select Group Trip</VCardTitle>
    <div class="text-caption text-medium-emphasis">Select a group trip to continue</div>
  </div>
  <template #append>
    <VBtn icon="ri-close-line" variant="text" size="small" @click="emit('update:modelValue', false)" />
  </template>
</VCardItem>
      <VDivider />
      <VProgressLinear v-if="loading" indeterminate color="primary" />
      <VCardText v-else class="bg-grey-lighten-5 pa-4">
        <VRow dense justify="end" class="mb-2">
  <VCol cols="12" md="4">
    <VTextField v-model="search" placeholder="Search" prepend-inner-icon="ri-search-line" variant="outlined" density="compact" bg-color="white" hide-details clearable />
  </VCol>
</VRow>

        <div class="d-flex flex-column ga-3">
          <VCard v-for="group in filteredGroups" :key="group.id" rounded="lg" border elevation="0">
            <VCardText class="d-flex align-center justify-space-between ga-4 pa-4">
              <div>
                <div class="text-body-1 font-weight-bold mb-1">{{ group.trip_name }}</div>
                <div class="text-caption text-medium-emphasis mb-2">
                  <span class="font-weight-bold">{{ group.travel_from }} - {{ group.travel_to }}</span>
                  · {{ formatDate(group.departure_date) }} - {{ formatDate(group.return_date) }}
                </div>
                <div class="d-flex flex-wrap ga-2">
                  <VChip v-for="name in departmentNames(group)" :key="name" size="small" variant="tonal" color="primary">{{ name }}</VChip>
                </div>
              </div>
              <VBtn color="primary" @click="emit('select', group)">Select</VBtn>
            </VCardText>
          </VCard>

          <div v-if="!filteredGroups.length" class="text-center text-medium-emphasis py-6">
            {{ groups.length ? 'No group trips match your search.' : 'You are not assigned to any open Group Trip.' }}
          </div>
        </div>
      </VCardText>
    </VCard>
  </VDialog>
</template>