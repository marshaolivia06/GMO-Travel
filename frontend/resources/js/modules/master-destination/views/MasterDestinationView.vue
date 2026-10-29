<script setup>
import { computed, onMounted, ref } from 'vue'
import { createMasterDestination, deleteMasterDestination, getMasterDestinations, updateMasterDestination } from '../services/masterDestinationService'
import AddMasterDestination from '../components/modal/add-master-destination.vue'
import EditMasterDestination from '../components/modal/edit-master-destination.vue'
import { useConfirm } from '../../../composables/useConfirm'
import { useToastStore } from '../../../stores/toast'

const { confirm } = useConfirm()
const toast = useToastStore()

const destinations = ref([])
const loading = ref(false)
const search = ref('')
const showAddModal = ref(false)
const showEditModal = ref(false)
const editingDestination = ref(null)
const deletingId = ref(null)

const loadDestinations = async () => {
  loading.value = true
  try {
    destinations.value = await getMasterDestinations()
  } finally {
    loading.value = false
  }
}

onMounted(loadDestinations)

const filteredRows = computed(() => {
  const query = search.value.trim().toLowerCase()
  return destinations.value.filter(row =>
    !query || `${row.destination_code} ${row.city_name} ${row.country}`.toLowerCase().includes(query),
  )
})

const existingCodes = computed(() => destinations.value.map(row => row.destination_code))

async function addDestination(payload) {
  const confirmed = await confirm({
    title: 'Add Destination',
    text: `Are you sure you want to add ${payload.city_name}?`,
  })

  if (!confirmed) return

  try {
    await createMasterDestination(payload)
    showAddModal.value = false
    toast.success('Destination has been added successfully.')
    await loadDestinations()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Failed to add destination.')
  }
}

function openEdit(row) {
  editingDestination.value = row
  showEditModal.value = true
}

function closeEdit() {
  showEditModal.value = false
  editingDestination.value = null
}

async function updateDestination(payload) {
  const { id, ...data } = payload

  const confirmed = await confirm({
    title: 'Update Destination',
    text: `Are you sure you want to save the changes to ${data.city_name}?`,
  })

  if (!confirmed) return

  try {
    await updateMasterDestination(id, data)
    closeEdit()
    toast.success('Destination has been updated successfully.')
    await loadDestinations()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Failed to update destination.')
  }
}

async function deleteDestination(row) {
  if (deletingId.value !== null) return

  const confirmed = await confirm({
    title: 'Delete Destination',
    text: `Are you sure you want to delete ${row.city_name}?`,
  })

  if (!confirmed) return

  deletingId.value = row.id

  try {
    await deleteMasterDestination(row.id)
    toast.success('Destination has been deleted successfully.')
    await loadDestinations()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Failed to delete destination.')
  } finally {
    deletingId.value = null
  }
}

const headers = [
  { title: 'Actions', key: 'actions', sortable: false, width: 120 },
  { title: 'ID', key: 'id', width: 80 },
  { title: 'Code', key: 'destination_code' },
  { title: 'City', key: 'city_name' },
  { title: 'Country', key: 'country' },
  { title: 'Status', key: 'is_active' },
]
</script>

<template>
  <BrandCard>
    <template #action>
      <VBtn variant="flat" color="primary" prepend-icon="ri-add-line" @click="showAddModal = true">
        Add Destination
      </VBtn>
    </template>

    <template #search>
      <VTextField
        v-model="search"
        prepend-inner-icon="ri-search-line"
        placeholder="Search destination..."
        variant="outlined"
        density="comfortable"
        hide-details
        clearable
        min-width="260"
        max-width="320"
      />
    </template>

    <VDataTable class="text-no-wrap" :headers="headers" :items="filteredRows" :loading="loading" item-value="id" hover>
      <template #item.actions="{ item }">
        <div class="d-flex align-center ga-1 flex-nowrap">
          <VBtn icon="ri-edit-line" size="small" variant="text" color="primary" @click="openEdit(item)" />
          <VBtn icon="ri-delete-bin-line" size="small" variant="text" color="error" :loading="deletingId === item.id" @click="deleteDestination(item)" />
        </div>
      </template>

      <template #item.is_active="{ item }">
        <VChip size="small" variant="tonal" :color="item.is_active ? 'success' : 'secondary'">
          {{ item.is_active ? 'Active' : 'Inactive' }}
        </VChip>
      </template>
    </VDataTable>
  </BrandCard>

  <AddMasterDestination
    v-if="showAddModal"
    :existing-codes="existingCodes"
    @close="showAddModal = false"
    @submit="addDestination"
  />

  <EditMasterDestination
    v-if="showEditModal"
    :destination="editingDestination"
    @close="closeEdit"
    @submit="updateDestination"
  />
</template>