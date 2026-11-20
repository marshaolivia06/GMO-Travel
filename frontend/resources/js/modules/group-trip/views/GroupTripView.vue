<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import GroupTripForm from '../components/modal/GroupTripForm.vue'
import GroupTripDetailModal from '../components/modal/GroupTripDetailModal.vue'
import { getActivityCategories, getGroupTripUsers, getTravelOrderGroup, getTravelOrderGroups, createTravelOrderGroup, updateTravelOrderGroup, deleteTravelOrderGroup } from '../services/groupTripService'
import { useConfirm } from '../../../composables/useConfirm'
import { useToastStore } from '../../../stores/toast'

const toast = useToastStore()
const { confirm } = useConfirm()

const groups = ref([])
const loading = ref(false)
const search = ref('')
const statusFilter = ref('all')

const statusCards = computed(() => [
  { value: 'all', title: 'Total', icon: 'ri-stack-line', color: 'primary', count: groups.value.length },
  { value: 'open', title: 'Open', icon: 'ri-lock-unlock-line', color: 'warning', count: groups.value.filter(group => group.status === 'open').length },
  { value: 'closed', title: 'Closed', icon: 'ri-lock-line', color: 'error', count: groups.value.filter(group => group.status === 'closed').length },
])

const formDialog = ref(false)
const submitting = ref(false)
const editingGroup = ref(null)
const editingGroupId = ref(null)
const deletingGroupId = ref(null)
const detailDialog = ref(false)
const detailLoading = ref(false)
const selectedGroup = ref(null)
const memberOptions = ref([])
const activityCategoryOptions = ref([])

const showMessage = (text, type = 'success') => toast[type]?.(text)
const errorText = (error, fallback) => error?.response?.data?.errors ? Object.values(error.response.data.errors).flat()[0] : error?.response?.data?.message || fallback

const headerClass = 'px-3 py-3 text-caption font-weight-bold text-medium-emphasis bg-grey-lighten-4 text-no-wrap'
const cellClass = 'px-3 py-3'
const col = (title, key, extra = {}) => ({ title, key, sortable: false, headerProps: { class: headerClass }, cellProps: { class: cellClass }, ...extra })

const headers = [
  col('ACTION', 'action', { align: 'center', width: 180 }),
  col('STATUS', 'status', { align: 'center' }),
  col('TRIP NAME', 'trip_name'),
  col('ACTIVITY CATEGORY', 'activity_category_label', { align: 'center' }),
  col('ROUTE', 'route', { align: 'center' }),
  col('TRAVEL REGION', 'travel_region', { align: 'center' }),
  col('PERIOD', 'period', { align: 'center' }),
  col('DEPARTMENTS', 'departments'),
]

const groupStatusColor = { draft: 'success', open: 'warning', closed: 'error' }

function formatDate(value) {
  if (!value) return '-'
  const date = new Date(value)
  return isNaN(date.getTime()) ? '-' : date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

function regionColor(region) {
  const value = String(region || '').trim().toLowerCase()
  if (value.includes('non singapore') || value.includes('overseas')) return 'purple'
  if (value.includes('singapore')) return 'success'
  if (value.includes('domestic') || value === 'indonesia') return 'info'
  return 'secondary'
}

function groupDepartments(group) {
  return (group.departments || []).map(department => typeof department === 'object' ? department?.name : department).filter(Boolean)
}

function groupSubtitle(group) {
  const orders = group.travel_orders_count || 0
  const status = group.status ? group.status.charAt(0).toUpperCase() + group.status.slice(1) : '-'
  return `${status} · ${orders ? `${orders} Travel Order${orders > 1 ? 's' : ''}` : 'No Travel Orders'}`
}

const rows = computed(() => {
  const query = search.value.trim().toLowerCase()
  return groups.value.filter(group => (statusFilter.value === 'all' || group.status === statusFilter.value) && (!query || `${group.trip_name || ''} ${group.travel_from || ''} ${group.travel_to || ''}`.toLowerCase().includes(query)))
})

async function loadGroups() {
  loading.value = true
  try {
    const response = await getTravelOrderGroups({ per_page: 1000 })
    const data = response.data ?? response
    groups.value = Array.isArray(data) ? data : data.data || []
  } catch (error) {
    showMessage(error?.response?.data?.message || 'Failed to load Group Trips.', 'error')
  } finally {
    loading.value = false
  }
}

async function loadMemberOptions() {
  try {
    const response = await getGroupTripUsers()
    memberOptions.value = Array.isArray(response) ? response : response.data ?? []
  } catch (error) {
    console.error('Failed to load Group Trip users:', error)
    memberOptions.value = []
  }
}

async function loadActivityCategoryOptions() {
  try {
    const response = await getActivityCategories()
    activityCategoryOptions.value = Array.isArray(response) ? response : response.data ?? []
  } catch (error) {
    console.error('Failed to load Activity Categories:', error)
    activityCategoryOptions.value = []
  }
}

function openCreate() {
  editingGroup.value = null
  formDialog.value = true
}

async function openEdit(group) {
  if (editingGroupId.value !== null) return
  editingGroupId.value = group.id
  try {
    const response = await getTravelOrderGroup(group.id)
    editingGroup.value = response?.data?.data ?? response?.data ?? response
    formDialog.value = true
  } catch (error) {
    showMessage(error?.response?.data?.message || 'Failed to load Group Trip.', 'error')
  } finally {
    editingGroupId.value = null
  }
}

async function submitGroup(payload) {
  if (submitting.value) return
  submitting.value = true
  const isEdit = !!editingGroup.value?.id
  try {
    if (isEdit) await updateTravelOrderGroup(editingGroup.value.id, payload)
    else await createTravelOrderGroup(payload)
    formDialog.value = false
    editingGroup.value = null
    await loadGroups()
    showMessage(isEdit ? 'Group Trip updated successfully.' : 'Group Trip created successfully.')
  } catch (error) {
    showMessage(errorText(error, `Failed to ${isEdit ? 'update' : 'create'} Group Trip.`), 'error')
  } finally {
    submitting.value = false
  }
}

async function openDetail(group) {
  detailDialog.value = true
  detailLoading.value = true
  selectedGroup.value = group
  try {
    const response = await getTravelOrderGroup(group.id)
    selectedGroup.value = response?.data?.data ?? response?.data ?? response
  } catch (error) {
    console.error('Failed to load Group Trip:', error)
  } finally {
    detailLoading.value = false
  }
}

async function removeGroup(group) {
  if (deletingGroupId.value !== null) return
  const ok = await confirm({ title: 'Delete Group Trip', message: 'Are you sure you want to delete this group?' })
  if (!ok) return
  deletingGroupId.value = group.id
  try {
    await deleteTravelOrderGroup(group.id)
    await loadGroups()
    showMessage('Group Trip deleted successfully.')
  } catch (error) {
    showMessage(error?.response?.data?.message || 'Failed to delete Group Trip.', 'error')
  } finally {
    deletingGroupId.value = null
  }
}

watch(formDialog, open => { if (!open) editingGroup.value = null })

onMounted(() => { loadGroups(); loadMemberOptions(); loadActivityCategoryOptions() })
</script>

<template>
  <div>
    <BrandCard>
      <template #stats>
        <VRow dense>
          <VCol v-for="card in statusCards" :key="card.value" cols="6" sm="4">
            <VCard :variant="statusFilter === card.value ? 'tonal' : 'outlined'" :color="statusFilter === card.value ? card.color : undefined" class="pa-3 d-flex align-center cursor-pointer stat-tile h-100" style="gap: 12px" @click="statusFilter = card.value">
              <VAvatar :color="card.color" variant="tonal" rounded="lg" size="40"><VIcon :icon="card.icon" size="20" /></VAvatar>
              <div>
                <div class="text-h6 font-weight-bold">{{ card.count }}</div>
                <div class="text-caption text-medium-emphasis">{{ card.title }}</div>
              </div>
            </VCard>
          </VCol>
        </VRow>
      </template>

      <template #action>
        <VBtn variant="flat" color="primary" prepend-icon="ri-add-line" @click="openCreate">Create Group Trip</VBtn>
      </template>

      <template #search>
        <VTextField v-model="search" prepend-inner-icon="ri-search-line" placeholder="Search group trip..." variant="outlined" density="comfortable" hide-details clearable style="min-width: 260px; max-width: 320px" />
      </template>

      <VDataTable class="orders-table" :headers="headers" :items="rows" :loading="loading" show-current-page item-value="id" hover no-data-text="No group trips found.">
        <template #item.action="{ item }">
          <VBtnGroup density="compact" variant="tonal" divided>
            <VBtn color="blue-grey-darken-1" :loading="detailLoading && selectedGroup?.id === item.id" @click="openDetail(item)"><VIcon icon="ri-eye-line" /><VTooltip activator="parent" location="top">View</VTooltip></VBtn>
            <VBtn color="blue-grey-darken-1" :loading="editingGroupId === item.id" @click="openEdit(item)"><VIcon icon="ri-edit-line" /><VTooltip activator="parent" location="top">Edit</VTooltip></VBtn>
            <VBtn color="blue-grey-darken-1" :loading="deletingGroupId === item.id" @click="removeGroup(item)"><VIcon icon="ri-delete-bin-line" /><VTooltip activator="parent" location="top">Delete</VTooltip></VBtn>
          </VBtnGroup>
        </template>
        <template #item.trip_name="{ item }"><div class="font-weight-bold text-body-1">{{ item.trip_name }}</div><div class="text-caption text-medium-emphasis">{{ groupSubtitle(item) }}</div></template>
        <template #item.activity_category_label="{ item }"><span class="text-body-1 text-no-wrap">{{ item.activity_category_label || '-' }}</span></template>
        <template #item.route="{ item }"><span class="text-body-1 text-no-wrap">{{ item.travel_from }} - {{ item.travel_to }}</span></template>
        <template #item.travel_region="{ item }"><VChip variant="tonal" :color="regionColor(item.travel_region)" class="text-no-wrap">{{ item.travel_region }}</VChip></template>
        <template #item.period="{ item }"><span class="text-body-1 text-no-wrap">{{ formatDate(item.departure_date) }} - {{ formatDate(item.return_date) }}</span></template>
        <template #item.departments="{ item }">
          <div class="d-flex flex-wrap ga-1">
            <VChip v-for="name in groupDepartments(item)" :key="name" size="small" color="grey" variant="tonal" class="text-no-wrap">{{ name }}</VChip>
            <span v-if="!groupDepartments(item).length" class="text-body-2">-</span>
          </div>
        </template>
        <template #item.status="{ item }"><VChip variant="tonal" :color="groupStatusColor[item.status] || 'grey'" class="text-no-wrap text-capitalize"><VIcon icon="ri-checkbox-blank-circle-fill" size="8" start />{{ item.status }}</VChip></template>
      </VDataTable>
    </BrandCard>

    <VDialog v-model="formDialog" max-width="1000" scrollable>
      <GroupTripForm v-if="formDialog" :loading="submitting" :member-options="memberOptions" :activity-category-options="activityCategoryOptions" :initial-data="editingGroup" @cancel="formDialog = false" @submit="submitGroup" />
    </VDialog>
    <GroupTripDetailModal v-model="detailDialog" :group="selectedGroup" :loading="detailLoading" :member-options="memberOptions" />
  </div>
</template>
