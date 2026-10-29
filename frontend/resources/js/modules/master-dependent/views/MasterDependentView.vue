<script setup>
import { computed, onMounted, ref } from 'vue'
import { getMasterDependents, createMasterDependent, updateMasterDependent, deleteMasterDependent, getDependentEmployeeOptions } from '../services/masterDependentService'
import AddMasterDependent from '../components/modal/add-master-dependent.vue'
import EditMasterDependent from '../components/modal/edit-master-dependent.vue'
import { useConfirm } from '../../../composables/useConfirm'
import { useToastStore } from '../../../stores/toast'

const MAX_ELIGIBLE_AGE = 23
const { confirm } = useConfirm()
const deletingId = ref(null)
const toast = useToastStore()
const showAddModal = ref(false)
const showEditModal = ref(false)
const editingEmployee = ref(null)
const search = ref('')
const detailDialog = ref(false)
const selectedEmployee = ref(null)

const employees = ref([])
const loading = ref(false)
const employeeOptions = ref([])

async function openAdd() {
  employeeOptions.value = await getDependentEmployeeOptions()
  showAddModal.value = true
}

const loadEmployees = async () => {
  loading.value = true
  try {
    employees.value = await getMasterDependents()
  } finally {
    loading.value = false
  }
}

onMounted(loadEmployees)

function calcAge(dob) {
  const birth = new Date(dob)
  const today = new Date()
  let age = today.getFullYear() - birth.getFullYear()
  const month = today.getMonth() - birth.getMonth()

  if (month < 0 || (month === 0 && today.getDate() < birth.getDate())) age--
  return age
}

function formatDate(value) {
  const date = new Date(value)
  return isNaN(date.getTime()) ? '-' : date.toLocaleDateString('en-GB', {
    day: '2-digit', month: 'short', year: 'numeric',
  })
}

const withAge = dependent => {
  const age = calcAge(dependent.dob)
  return { ...dependent, age, eligible: dependent.relationship === 'Spouse' || age < MAX_ELIGIBLE_AGE }
}

const rows = computed(() => employees.value.map(employee => {
  const dependents = employee.dependents.map(withAge)
  return {
    ...employee,
    dependents,
    spouse: dependents.find(d => d.relationship === 'Spouse')?.name || '-',
    children: dependents.filter(d => d.relationship === 'Child').length,
    total: dependents.length,
  }
}))

const filteredRows = computed(() => {
  const query = search.value.trim().toLowerCase()
  return rows.value.filter(row =>
    !query || row.name.toLowerCase().includes(query) ||
    row.dependents.some(d => d.name.toLowerCase().includes(query)),
  )
})

function openDetail(row) {
  selectedEmployee.value = row
  detailDialog.value = true
}

async function addDependent(payload) {
  const employeeName = employeeOptions.value.find(e => e.id === payload.employee_id)?.name

  const confirmed = await confirm({
    title: 'Add Dependents',
    text: `Are you sure you want to add ${payload.dependents.length} dependent(s) for ${employeeName}?`,
  })

  if (!confirmed) return

  try {
    await createMasterDependent(payload)
    showAddModal.value = false
    toast.success('Dependents have been added successfully.')
    await loadEmployees()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Failed to add dependents.')
  }
}

function openEdit(row) {
  editingEmployee.value = row
  showEditModal.value = true
}

function closeEdit() {
  showEditModal.value = false
  editingEmployee.value = null
}

async function updateDependents(payload) {
  const employee = employees.value.find(e => e.id === payload.employee_id)
  if (!employee) return

  const confirmed = await confirm({
    title: 'Update Dependents',
    text: `Are you sure you want to save the changes to the dependents of ${employee.name}?`,
  })

  if (!confirmed) return

try {
  await updateMasterDependent(payload.employee_id, { dependents: payload.dependents })
  closeEdit()
  toast.success('Dependents have been updated successfully.')
  await loadEmployees()
} catch (error) {
  toast.error(error.response?.data?.message || 'Failed to update dependents.')
}
}

async function deleteDependents(row) {
  if (deletingId.value !== null) return

  const confirmed = await confirm({
    title: 'Delete Dependents',
    text: `Are you sure you want to delete all ${row.total} dependent(s) of ${row.name}?`,
  })

  if (!confirmed) return

  deletingId.value = row.id

try {
  await deleteMasterDependent(row.id)
  toast.success('Dependents have been deleted successfully.')
  await loadEmployees()
} catch (error) {
  toast.error(error.response?.data?.message || 'Failed to delete dependents.')
} finally {
  deletingId.value = null
}
}

const headers = [
  { title: 'Actions', key: 'actions', sortable: false, width: 160 },
  { title: 'ID', key: 'id', width: 80 },
  { title: 'Employee', key: 'name' },
  { title: 'Spouse', key: 'spouse' },
  { title: 'Children', key: 'children' },
  { title: 'Family Members', key: 'total' },
]

const dependentHeaders = [
  { title: 'Dependent Name', key: 'name', sortable: false },
  { title: 'Relationship', key: 'relationship', sortable: false },
  { title: 'Date of Birth', key: 'dob', sortable: false },
  { title: 'Age', key: 'age', sortable: false },
  { title: 'Eligible', key: 'eligible', sortable: false },
]
</script>

<template>
  <BrandCard>
    <template #action>
        <VBtn variant="flat" color="primary" prepend-icon="ri-add-line" @click="openAdd">
        Add Dependent
      </VBtn>
    </template>

    <template #search>
      <VTextField
        v-model="search"
        prepend-inner-icon="ri-search-line"
        placeholder="Search employee / dependent..."
        variant="outlined"
        density="comfortable"
        hide-details
        clearable
        style="min-width: 260px; max-width: 320px"
      />
    </template>

    <VDataTable class="text-no-wrap" :headers="headers" :items="filteredRows" :loading="loading" item-value="id" hover>
      <template #item.actions="{ item }">
        <div class="d-flex align-center ga-1 flex-nowrap">
          <VBtn icon="ri-eye-line" size="small" variant="text" color="info" @click="openDetail(item)" />
          <VBtn icon="ri-edit-line" size="small" variant="text" color="primary" @click="openEdit(item)" />
          <VBtn icon="ri-delete-bin-line" size="small" variant="text" color="error" :disabled="!item.total" :loading="deletingId === item.id" @click="deleteDependents(item)" />
        </div>
      </template>
    </VDataTable>
  </BrandCard>

  <VDialog v-model="detailDialog" max-width="900" scrollable>
    <VCard v-if="selectedEmployee">
      <VCardTitle class="d-flex align-center justify-space-between pa-5">
        <div>
          <div class="text-h6">Family Members</div>
          <div class="text-body-2 text-medium-emphasis">{{ selectedEmployee.name }}</div>
        </div>
        <DialogCloseBtn @click="detailDialog = false" />
      </VCardTitle>

      <VDivider />

      <VDataTable
        class="text-no-wrap"
        :headers="dependentHeaders"
        :items="selectedEmployee.dependents"
        :items-per-page="-1"
        item-value="id"
        hover
        hide-default-footer
        no-data-text="No dependents registered."
      >
        <template #item.dob="{ item }">{{ formatDate(item.dob) }}</template>
        <template #item.eligible="{ item }">
          <VChip size="small" variant="tonal" :color="item.eligible ? 'success' : 'error'">
            {{ item.eligible ? 'Eligible' : 'Not Eligible' }}
          </VChip>
        </template>
      </VDataTable>
    </VCard>
  </VDialog>

  <AddMasterDependent
    v-if="showAddModal"
    :employees="employeeOptions"
    @close="showAddModal = false"
    @submit="addDependent"
  />

  <EditMasterDependent
    v-if="showEditModal"
    :employee="editingEmployee"
    @close="closeEdit"
    @submit="updateDependents"
  />
</template>
