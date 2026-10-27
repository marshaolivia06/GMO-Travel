<script setup>
import { onMounted, ref } from 'vue'
import { useMasterRegionStore } from '../stores/masterRegionStore'
import { useConfirm } from '../../../composables/useConfirm'
import { useToastStore } from '../../../stores/toast'
import AddMasterRegion from '../components/modal/add-master-region.vue'
import EditMasterRegion from '../components/modal/edit-master-region.vue'

const masterRegionStore = useMasterRegionStore()
const { confirm } = useConfirm()
const toast = useToastStore()

const showAddMasterRegionModal = ref(false)
const showEditMasterRegionModal = ref(false)
const selectedMasterRegion = ref(null)

function openEditMasterRegion(item) {
  selectedMasterRegion.value = item
  showEditMasterRegionModal.value = true
}

async function deleteMasterRegion(item) {
  const confirmed = await confirm({
    title: 'Delete Master Region',
    text: `Are you sure you want to delete ${item.code} - ${item.name}?`,
  })

  if (!confirmed) return

  try {
    await masterRegionStore.removeMasterRegion(item.id)
    toast.success('Master region has been deleted successfully.')
  } catch (err) {
    toast.error(err.response?.data?.message || err.message || 'Failed to delete master region.')
  }
} 

const search = ref('')

const headers = [
  { title: 'Actions', key: 'actions', sortable: false, width: 120 },
  { title: 'ID', key: 'id', width: 80 },
  { title: 'Code', key: 'code', width: 120 },
  { title: 'Region', key: 'name' },
  { title: 'Coverage', key: 'coverage' },
  { title: 'Currency', key: 'currency', width: 110 },
  { title: 'Travel Area', key: 'travel_area_label' },
  { title: 'No Daily Allowance', key: 'destinations', sortable: false },
  { title: 'Created', key: 'created_at' },
  { title: 'Updated', key: 'updated_at' },
]

onMounted(() => {
  masterRegionStore.fetchMasterRegions()
})
</script>

<template>
  <VCard rounded="lg" elevation="2">
    <VCardText>
      <VDataTable :headers="headers" :items="masterRegionStore.masterRegions" :loading="masterRegionStore.loading" :search="search" item-value="id" hover>
        <template #top>
          <div class="d-flex align-center justify-space-between flex-wrap ga-4 pa-4">
            <VBtn color="success" prepend-icon="ri-add-line" @click="showAddMasterRegionModal = true">Add Master Region</VBtn>

            <VTextField v-model="search" prepend-inner-icon="ri-search-line" placeholder="Search master region..." single-line clearable hide-details density="compact" max-width="260" />
          </div>

          <VDivider />
        </template>

        <template #headers="{ columns }">
          <tr>
            <th v-for="column in columns" :key="column.key" class="bg-grey-lighten-3">{{ column.title }}</th>
          </tr>
        </template>

        <template #item.travel_area_label="{ item }">{{ item.travel_area_label || '-' }}</template>

        <template #item.destinations="{ item }">{{ item.destinations?.length ? item.destinations.join(', ') : '-' }}</template>
        <template #item.actions="{ item }">
  <div class="d-flex align-center ga-1 flex-nowrap">
    <VBtn icon="ri-edit-line" size="small" variant="text" color="success" @click="openEditMasterRegion(item)" />
    <VBtn icon="ri-delete-bin-line" size="small" variant="text" color="error" @click="deleteMasterRegion(item)" />
  </div>
</template>
      </VDataTable>
    </VCardText>
  </VCard>
  <AddMasterRegion v-if="showAddMasterRegionModal" @close="showAddMasterRegionModal = false" />
  <EditMasterRegion v-if="showEditMasterRegionModal && selectedMasterRegion" :item="selectedMasterRegion" @close="showEditMasterRegionModal = false" />
</template>