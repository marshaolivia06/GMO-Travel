<template>
  <BrandCard>
    <template #stats>
      <VRow dense>
        <VCol v-for="tile in statTiles" :key="tile.title" cols="6" sm="3">
          <VCard variant="outlined" class="pa-3 d-flex align-center" style="gap: 12px">
            <VAvatar :color="tile.color" variant="tonal" rounded="lg" size="40">
              <VIcon :icon="tile.icon" size="20" />
            </VAvatar>
            <div>
              <div class="text-h6 font-weight-bold">{{ tile.value }}</div>
              <div class="text-caption text-medium-emphasis">{{ tile.title }}</div>
            </div>
          </VCard>
        </VCol>
      </VRow>
    </template>

    <div class="pa-4 pt-0">
      <VRow>
        <VCol v-for="menu in quickMenus" :key="menu.title" cols="12" md="4">
          <VCard
            variant="outlined"
            class="pa-4 h-100 d-flex flex-column"
            :to="menu.to"
          >
            <VAvatar color="primary" variant="tonal" rounded="lg" size="44" class="mb-4">
              <VIcon :icon="menu.icon" size="22" />
            </VAvatar>
            <div class="text-h6 font-weight-bold mb-1">{{ menu.title }}</div>
            <div class="text-body-2 text-medium-emphasis mb-4">{{ menu.description }}</div>
            <div v-if="menu.to" class="mt-auto text-primary text-body-2 font-weight-medium d-flex align-center ga-1">
              Open {{ menu.title }}
              <VIcon icon="ri-arrow-right-line" size="16" />
            </div>
          </VCard>
        </VCol>
      </VRow>
    </div>
  </BrandCard>
</template>

<script setup>
import { computed, reactive } from 'vue'
const stats = reactive({
  totalTravel: 0,
  totalLeave: 0,
  pending: 0,
  approved: 0
})

const statTiles = computed(() => [
  { title: 'Total Travel Order', value: stats.totalTravel, color: 'primary', icon: 'ri-flight-takeoff-line' },
  { title: 'Annual Leave', value: stats.totalLeave, color: 'info', icon: 'ri-calendar-check-line' },
  { title: 'Awaiting Approval', value: stats.pending, color: 'warning', icon: 'ri-time-line' },
  { title: 'Approved', value: stats.approved, color: 'success', icon: 'ri-checkbox-circle-line' },
])

const quickMenus = [
  { title: 'Annual Leave', icon: 'ri-calendar-check-line', to: '/annual-leave', description: 'Manage annual leave requests and information in one place.' },
  { title: 'Travel Order', icon: 'ri-flight-takeoff-line', to: '/travel-order', description: 'Manage business trip requests and travel information.' },
  { title: 'Summary', icon: 'ri-bar-chart-box-line', to: null, description: 'View a summary of travel activity and the latest requests.' },
]
</script>
