<script setup>
import { computed, onMounted, ref } from 'vue'
import { useMasterAdvanceStore } from '../stores/masterAdvanceStore'
import { useConfirm } from '../../../composables/useConfirm'
import { useToastStore } from '../../../stores/toast'
import AddMasterAdvance from '../components/modal/add-master-advance.vue'
import EditMasterAdvance from '../components/modal/edit-master-advance.vue'

const masterAdvanceStore = useMasterAdvanceStore()
const { confirm } = useConfirm()
const toast = useToastStore()

const search = ref('')
const regionFilter = ref('Domestic')
const showMasterAdvanceModal = ref(false)
const showEditMasterAdvanceModal = ref(false)
const selectedMasterAdvance = ref(null)

const regionOptions = ['Domestic', 'Singapore', 'Overseas']

const headers = [
  { title: 'Actions', key: 'actions', sortable: false, width: 120 },
  { title: 'ID', key: 'id_travel_advance_master', width: 80 },
  { title: 'Travel Region', key: 'travel_region' },
  { title: 'Grade', key: 'grade' },
  { title: 'Country', key: 'country' },
  { title: 'Currency', key: 'currency' },
  { title: 'Pocket Money Limit', key: 'pocket_money_limit' },
  { title: 'Meal Allowance Limit', key: 'meal_allowance_limit' },
  { title: 'Created', key: 'created_at' },
  { title: 'Updated', key: 'updated_at' },
]

const filteredMasterAdvances = computed(() => {
  return masterAdvanceStore.travelAdvanceMasters.filter(item => item.travel_region === regionFilter.value)
})

function formatDate(date) {
  if (!date) return '-'
  return new Intl.DateTimeFormat('sv-SE', {
    timeZone: 'Asia/Jakarta',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).format(new Date(date))
}

function formatAmount(value) {
  if (value === null || value === undefined || value === '') return '-'
  return Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function openEditMasterAdvance(item) {
  selectedMasterAdvance.value = item
  showEditMasterAdvanceModal.value = true
}

async function deleteMasterAdvance(item) {
  const confirmed = await confirm({
    title: 'Delete Master Advance',
    text: `Are you sure you want to delete ${item.travel_region} - ${item.currency}?`,
  })

  if (!confirmed) return

  try {
    await masterAdvanceStore.removeTravelAdvanceMaster(item.id_travel_advance_master)
    toast.success('Travel Advance master has been deleted successfully.')
  } catch (err) {
    toast.error(err.response?.data?.message || err.message || 'Failed to delete travel advance master.')
  }
}

onMounted(() => {
  masterAdvanceStore.fetchTravelAdvanceMasters()
})
</script>

<template>
  <VCard rounded="lg" elevation="2">
    <VCardText>
      <VDataTable :headers="headers" :items="filteredMasterAdvances" :loading="masterAdvanceStore.loading" :search="search" item-value="id_travel_advance_master" density="default" hover>
        <template #top>
          <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px; flex-wrap: wrap">
            <div style="display: flex; align-items: center; gap: 16px">
              <VBtn color="success" prepend-icon="ri-add-line" style="flex-shrink: 0" @click="showMasterAdvanceModal = true">Add Master Advance</VBtn>

              <VSelect v-model="regionFilter" :items="regionOptions" label="Travel Region" density="compact" hide-details style="width: 180px" />
            </div>

            <VTextField v-model="search" prepend-inner-icon="ri-search-line" placeholder="Search master advance..." single-line clearable hide-details density="compact" style="width: 260px !important; flex: 0 0 260px" />
          </div>

          <VDivider />
        </template>

        <template #headers="{ columns }">
          <tr>
            <th v-for="column in columns" :key="column.key" class="bg-grey-lighten-3">{{ column.title }}</th>
          </tr>
        </template>

        <template #item.pocket_money_limit="{ item }">{{ formatAmount(item.pocket_money_limit) }}</template>
        <template #item.meal_allowance_limit="{ item }">{{ formatAmount(item.meal_allowance_limit) }}</template>
        <template #item.created_at="{ item }">{{ formatDate(item.created_at) }}</template>
        <template #item.updated_at="{ item }">{{ formatDate(item.updated_at) }}</template>

        <template #item.actions="{ item }">
          <div class="d-flex align-center ga-1 flex-nowrap">
            <VBtn icon="ri-edit-line" size="small" variant="text" color="success" @click="openEditMasterAdvance(item)" />
            <VBtn icon="ri-delete-bin-line" size="small" variant="text" color="error" @click="deleteMasterAdvance(item)" />
          </div>
        </template>
      </VDataTable>
    </VCardText>
  </VCard>

  <AddMasterAdvance v-if="showMasterAdvanceModal" @close="showMasterAdvanceModal = false" />
  <EditMasterAdvance v-if="showEditMasterAdvanceModal && selectedMasterAdvance" :item="selectedMasterAdvance" @close="showEditMasterAdvanceModal = false" />
</template>