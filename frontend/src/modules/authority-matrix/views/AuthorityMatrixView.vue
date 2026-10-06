<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAuthorityMatrixStore } from '../stores/authorityMatrixStore'
import AddAuthorityMatrix from '../components/modal/add-authority-matrix.vue'
import EditAuthorityMatrix from '../components/modal/edit-authority-matrix.vue'
import { useToastStore } from '../../../stores/toast'
import { useConfirm } from '../../../composables/useConfirm'

const authorityMatrixStore = useAuthorityMatrixStore()
const toast = useToastStore()
const { confirm } = useConfirm()
const search = ref('')
const showAddModal = ref(false)
const showEditModal = ref(false)
const selectedAuthorityMatrix = ref(null)

const headers = [
  { title: 'Actions', key: 'actions', sortable: false, width: 100 },
  { title: 'ID', key: 'id', width: 70 },
  { title: 'Document Type', key: 'document_type', width: 180 },
  { title: 'Steps', key: 'steps', width: 80 },
  { title: 'Created', key: 'created_at', width: 120 },
  { title: 'Updated', key: 'updated_at', width: 120 },
]

const authorityMatrices = computed(() =>
  authorityMatrixStore.authorityMatrices.filter(item => Number(item.status) === 1),
)

function formatDate(date) {
  if (!date) return '-'
  return new Intl.DateTimeFormat('sv-SE', { timeZone: 'Asia/Jakarta', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date(date))
}

function openEditModal(item) {
  selectedAuthorityMatrix.value = item
  showEditModal.value = true
}

async function removeAuthorityMatrix(item) {
  const confirmed = await confirm({
    title: 'Delete Authority Matrix',
    text: 'Are you sure you want to delete this authority matrix?',
  })

  if (!confirmed) return

  try {
    await authorityMatrixStore.removeAuthorityMatrix(item.id)
    toast.success('Authority matrix has been deleted successfully.')
  } catch (err) {
    toast.error(err.response?.data?.message || err.message || 'Failed to delete authority matrix.')
  }
}

onMounted(() => authorityMatrixStore.fetchAuthorityMatrices())
</script>

<template>
  <VCard rounded="lg" elevation="2">
    <VCardText>
      <VDataTable :headers="headers" :items="authorityMatrices" :loading="authorityMatrixStore.loading" :search="search" item-value="id" density="default" hover>
        <template #top>
          <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px; flex-wrap: wrap">
           <VBtn color="success" prepend-icon="ri-add-line" style="flex-shrink: 0" @click="showAddModal = true">Add Authority Matrix</VBtn>
            <VTextField v-model="search" prepend-inner-icon="ri-search-line" placeholder="Search authority matrix..." single-line clearable hide-details density="compact" style="width: 260px !important; flex: 0 0 260px" />
          </div>
          <VDivider />
        </template>

        <template #headers="{ columns }">
          <tr>
  <th v-for="column in columns" :key="column.key" class="bg-grey-lighten-3" :style="{ width: column.width ? `${column.width}px` : undefined }">{{ column.title }}</th>
</tr>
        </template>

        <template #item.steps="{ item }">{{ item.steps?.length || 0 }}</template>
        <template #item.created_at="{ item }">{{ formatDate(item.created_at) }}</template>
        <template #item.updated_at="{ item }">{{ formatDate(item.updated_at) }}</template>

        <template #item.actions="{ item }">
          <div class="d-flex align-center ga-1 flex-nowrap">
           <VBtn icon="ri-edit-line" size="small" variant="text" color="success" @click="openEditModal(item)" />
           <VBtn icon="ri-delete-bin-line" size="small" variant="text" color="error" @click="removeAuthorityMatrix(item)" />
          </div>
        </template>
      </VDataTable>
    </VCardText>
  </VCard>
   <AddAuthorityMatrix v-if="showAddModal" @close="showAddModal = false" />
<EditAuthorityMatrix v-if="showEditModal" :authority-matrix="selectedAuthorityMatrix" @close="showEditModal = false" />
</template>