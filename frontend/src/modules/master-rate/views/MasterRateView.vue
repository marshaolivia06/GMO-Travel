<script setup>
import { onMounted, ref, watch } from 'vue'
import { useMasterRateStore } from '../stores/masterRateStore'
import { useMasterRegionStore } from '../../master-region/stores/masterRegionStore'
import AddMasterRate from '../components/modal/add-master-rate.vue'

const masterRateStore = useMasterRateStore()
const masterRegionStore = useMasterRegionStore()

const search = ref('')
const regionFilter = ref(null)
const showAddMasterRateModal = ref(false)

const headers = [
  { title: 'Actions', key: 'actions', sortable: false, width: 120 },
  { title: 'ID', key: 'id', width: 80 },
  { title: 'Region', key: 'region' },
  { title: 'Grade', key: 'grade_min' },
  { title: 'Hotel Star', key: 'hotel_star' },
  { title: 'Hotel Rate', key: 'hotel_rate' },
  { title: 'Meal Basis', key: 'meal_basis' },
  { title: 'Meal Limit / Meal', key: 'meal_limit_per_meal' },
  { title: 'Daily Allowance', key: 'daily_allowance' },
  { title: 'Max Days', key: 'daily_allowance_max_days' },
  { title: 'Max / Trip', key: 'daily_allowance_max_per_trip' },
  { title: 'Flight Class', key: 'flight_class' },
  { title: 'Effective From', key: 'effective_from' },
]

const mealBasisLabel = { limit: 'Limit', at_cost: 'At Cost' }
const flightClassLabel = { economy: 'Economy', economy_business: 'Economy / Business' }

function formatAmount(value) {
  if (value === null || value === undefined || value === '') return '-'
  return Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

watch(regionFilter, value => {
  masterRateStore.fetchMasterRates(value)
})

onMounted(() => {
  masterRegionStore.fetchMasterRegions()
  masterRateStore.fetchMasterRates()
})
</script>

<template>
  <VCard rounded="lg" elevation="2">
    <VCardText>
        <VDataTable class="text-no-wrap" :headers="headers" :items="masterRateStore.masterRates" :loading="masterRateStore.loading" :search="search" item-value="id" hover>
        <template #top>
          <div class="d-flex align-center justify-space-between flex-wrap ga-4 pa-4">
            <div class="d-flex align-center ga-4">
              <VBtn color="success" prepend-icon="ri-add-line" @click="showAddMasterRateModal = true">Add Master Rate</VBtn>

              <VSelect v-model="regionFilter" :items="masterRegionStore.masterRegions" item-title="name" item-value="id" label="Region" placeholder="All Regions" clearable density="compact" hide-details min-width="200" />
            </div>

            <VTextField v-model="search" prepend-inner-icon="ri-search-line" placeholder="Search master rate..." single-line clearable hide-details density="compact" max-width="260" />
          </div>

          <VDivider />
        </template>

        <template #headers="{ columns }">
          <tr>
            <th v-for="column in columns" :key="column.key" class="bg-grey-lighten-3">{{ column.title }}</th>
          </tr>
        </template>

        <template #item.region="{ item }">{{ item.region ? `${item.region.code} - ${item.region.name}` : '-' }}</template>
        <template #item.grade_min="{ item }">{{ item.grade_min }} - {{ item.grade_max }}</template>
        <template #item.hotel_rate="{ item }">{{ item.hotel_rate_currency }} {{ formatAmount(item.hotel_rate) }}</template>
        <template #item.meal_basis="{ item }">{{ mealBasisLabel[item.meal_basis] || item.meal_basis }}</template>
        <template #item.meal_limit_per_meal="{ item }">{{ formatAmount(item.meal_limit_per_meal) }}</template>
        <template #item.daily_allowance="{ item }">{{ formatAmount(item.daily_allowance) }}</template>
        <template #item.daily_allowance_max_days="{ item }">{{ item.daily_allowance_max_days ?? '-' }}</template>
        <template #item.daily_allowance_max_per_trip="{ item }">{{ formatAmount(item.daily_allowance_max_per_trip) }}</template>
        <template #item.flight_class="{ item }">{{ flightClassLabel[item.flight_class] || item.flight_class }}</template>
        <template #item.effective_to="{ item }">{{ item.effective_to || '-' }}</template>

        <template #item.actions>
          <div class="d-flex align-center ga-1 flex-nowrap">
            <VBtn icon="ri-edit-line" size="small" variant="text" color="success" />
            <VBtn icon="ri-delete-bin-line" size="small" variant="text" color="error" />
          </div>
        </template>
      </VDataTable>
    </VCardText>
  </VCard>

  <AddMasterRate v-if="showAddMasterRateModal" :region-filter="regionFilter" @close="showAddMasterRateModal = false" />
</template>