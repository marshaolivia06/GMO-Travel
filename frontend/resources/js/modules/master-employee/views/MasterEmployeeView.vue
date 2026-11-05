<script setup>
import { computed, onMounted, ref } from 'vue'
import { deleteMasterEmployee, getMasterEmployees } from '../services/masterEmployeeService'
import AddMasterEmployee from '../components/modal/add-master-employee.vue'
import EditMasterEmployee from '../components/modal/edit-master-employee.vue'
import { useAuthStore } from '../../../stores/auth'
import { useConfirm } from '../../../composables/useConfirm'
import { useToastStore } from '../../../stores/toast'

const authStore = useAuthStore()
const { confirm } = useConfirm()
const toast = useToastStore()

const rows = ref([])
const loading = ref(false)
const search = ref('')
const showAddEmployeeModal = ref(false)
const showEditEmployeeModal = ref(false)
const selectedEmployee = ref(null)

const canCreate = computed(() => authStore.can('master-employee.create'))
const canUpdate = computed(() => authStore.can('master-employee.update'))
const canDelete = computed(() => authStore.can('master-employee.delete'))

const headers = [
  { title: 'Actions', key: 'actions', sortable: false, width: 120 },
  { title: 'ID', key: 'id', width: 80 },
  { title: 'Employee', key: 'name' },
  { title: 'Grade', key: 'grade' },
  { title: 'Eligible Destination', key: 'eligible_destination' },
  { title: 'Date of Join', key: 'date_of_join' },
  { title: 'Office Location', key: 'office_location' },
]

// user yang belum punya data employee - untuk mode Add
const userOptions = computed(() => rows.value.filter(row => !row.id).map(row => ({ id: row.user_id, name: row.name, email: row.email })))

const hasEmployee = row => !!row?.id

async function loadEmployees() {
  loading.value = true
  try {
    const response = await getMasterEmployees()
    rows.value = response?.data ?? response ?? []
  } catch (error) {
    toast.error(error?.response?.data?.message || 'Failed to load employees.')
  } finally {
    loading.value = false
  }
}

function openEdit(item) {
  if (!canUpdate.value) return
  selectedEmployee.value = item
  showEditEmployeeModal.value = true
}

function closeAdd() {
  showAddEmployeeModal.value = false
  loadEmployees()
}

function closeEdit() {
  showEditEmployeeModal.value = false
  selectedEmployee.value = null
  loadEmployees()
}

async function deleteRow(row) {
  if (!canDelete.value || !hasEmployee(row)) return
  const ok = await confirm({ title: 'Delete Employee', text: `Are you sure you want to delete employee data for ${row.name}?` })
  if (!ok) return
  try {
    await deleteMasterEmployee(row.id)
    await loadEmployees()
    toast.success('Employee deleted successfully.')
  } catch (error) {
    toast.error(error?.response?.data?.message || 'Failed to delete employee.')
  }
}

function formatDate(value) {
  if (!value) return '-'
  const date = new Date(value)
  if (isNaN(date.getTime())) return '-'
  const dd = String(date.getDate()).padStart(2, '0')
  const mm = String(date.getMonth() + 1).padStart(2, '0')
  return `${dd}-${mm}-${date.getFullYear()}`
}

onMounted(loadEmployees)
</script>

<template>
  <BrandCard>
    <template #action>
      <VBtn
        variant="flat"
        color="primary"
        prepend-icon="ri-add-line"
        :disabled="!canCreate"
        @click="showAddEmployeeModal = true"
      >
        Add Employee
      </VBtn>
    </template>

    <template #search>
      <VTextField
        v-model="search"
        prepend-inner-icon="ri-search-line"
        placeholder="Search employee..."
        variant="outlined"
        density="comfortable"
        hide-details
        clearable
        style="min-width: 260px; max-width: 320px"
      />
    </template>

    <VDataTable :headers="headers" :items="rows" :loading="loading" :search="search" item-value="user_id" hover>
      <template #item.actions="{ item }">
        <div class="d-flex align-center ga-1 flex-nowrap">
          <VBtn icon="ri-edit-line" size="small" variant="text" color="primary" :disabled="!canUpdate" @click="openEdit(item)" />
          <VBtn icon="ri-delete-bin-line" size="small" variant="text" color="error" :disabled="!canDelete" @click="deleteRow(item)" />
        </div>
      </template>

      <template #item.grade="{ item }">{{ item.grade ?? '-' }}</template>

      <template #item.eligible_destination="{ item }">{{ item.eligible_destination?.city_name || '-' }}</template>

      <template #item.date_of_join="{ item }">{{ formatDate(item.date_of_join) }}</template>

      <template #item.office_location="{ item }">{{ item.office_location || '-' }}</template>
    </VDataTable>
  </BrandCard>

  <AddMasterEmployee v-if="showAddEmployeeModal" :user-options="userOptions" @close="closeAdd" />
  <EditMasterEmployee v-if="showEditEmployeeModal && selectedEmployee" :item="selectedEmployee" @close="closeEdit" />
</template>
