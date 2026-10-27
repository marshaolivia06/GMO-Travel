<template>
  <BrandCard>
    <template #stats>
      <VRow dense>
        <VCol v-for="tile in statusTiles" :key="tile.title" cols="6" sm="3">
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

    <template #action>
      <VBtn
        variant="flat"
        color="primary"
        prepend-icon="ri-add-line"
        @click="showForm = true"
      >
        Request Leave
      </VBtn>
    </template>

    <template #search>
      <VTextField
        v-model="search"
        prepend-inner-icon="ri-search-line"
        placeholder="Search name..."
        variant="outlined"
        density="comfortable"
        hide-details
        clearable
        style="min-width: 260px; max-width: 320px"
      />
    </template>

    <VDataTable
      :headers="headers"
      :items="filteredLeaves"
      item-value="id"
      hover
      no-data-text="No leave requests yet."
    >
      <template #item.name="{ item }">
        <div class="font-weight-medium">{{ item.name }}</div>
        <div class="text-caption text-medium-emphasis">{{ item.employeeId }}</div>
      </template>
      <template #item.period="{ item }">
        <span class="text-no-wrap">{{ item.startDate }} - {{ item.endDate }}</span>
      </template>
      <template #item.duration="{ item }">{{ item.duration }} day(s)</template>
      <template #item.status="{ item }">
        <VChip :color="statusColor[item.status]" size="small" variant="tonal">
          {{ getStatusLabel(item.status) }}
        </VChip>
      </template>
    </VDataTable>
  </BrandCard>

  <VDialog v-model="showForm" max-width="520">
    <VCard>
      <VCardTitle class="pa-4">Request Annual Leave</VCardTitle>
      <VForm @submit.prevent="submit">
        <VCardText>
          <VRow>
            <VCol cols="12">
              <VTextField v-model="form.name" label="Name" placeholder="Enter employee name" required />
            </VCol>
            <VCol cols="12" sm="6">
              <VTextField v-model="form.startDate" type="date" label="Start Date" required />
            </VCol>
            <VCol cols="12" sm="6">
              <VTextField v-model="form.endDate" type="date" label="End Date" required />
            </VCol>
            <VCol cols="12">
              <VTextarea v-model="form.reason" label="Reason" rows="4" placeholder="Enter leave reason..." required />
            </VCol>
          </VRow>
        </VCardText>
        <VCardActions class="justify-end ga-2 pa-4">
          <VBtn variant="tonal" @click="showForm = false">Cancel</VBtn>
          <VBtn variant="flat" color="primary" type="submit">Submit</VBtn>
        </VCardActions>
      </VForm>
    </VCard>
  </VDialog>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'

const showForm = ref(false)
const search = ref('')

const form = reactive({
  name: '',
  startDate: '',
  endDate: '',
  reason: ''
})

const leaves = ref([])

const pending = computed(() =>
  leaves.value.filter(
    leave => leave.status === 'pending'
  ).length
)

const approved = computed(() =>
  leaves.value.filter(
    leave => leave.status === 'approved'
  ).length
)

const rejected = computed(() =>
  leaves.value.filter(
    leave => leave.status === 'rejected'
  ).length
)

const filteredLeaves = computed(() => {
  const keyword = search.value.toLowerCase().trim()

  return leaves.value.filter(leave =>
    leave.name.toLowerCase().includes(keyword)
  )
})

const calculateDuration = (start, end) => {
  if (!start || !end) {
    return 0
  }

  const startDate = new Date(start)
  const endDate = new Date(end)

  const difference =
    endDate.getTime() - startDate.getTime()

  return Math.floor(
    difference / (1000 * 60 * 60 * 24)
  ) + 1
}

const submit = () => {
  const duration = calculateDuration(
    form.startDate,
    form.endDate
  )

  if (duration <= 0) {
    return
  }

  leaves.value.push({
    id: Date.now(),
    name: form.name,
    employeeId: 'EMP-NEW',
    startDate: form.startDate,
    endDate: form.endDate,
    duration,
    reason: form.reason,
    status: 'pending'
  })

  form.name = ''
  form.startDate = ''
  form.endDate = ''
  form.reason = ''

  showForm.value = false
}

const headers = [
  { title: 'Name', key: 'name' },
  { title: 'Period', key: 'period', sortable: false },
  { title: 'Duration', key: 'duration' },
  { title: 'Reason', key: 'reason', sortable: false },
  { title: 'Status', key: 'status' },
]

const statusTiles = computed(() => [
  { title: 'Total Requests', value: leaves.value.length, color: 'primary', icon: 'ri-file-list-3-line' },
  { title: 'Pending', value: pending.value, color: 'warning', icon: 'ri-time-line' },
  { title: 'Approved', value: approved.value, color: 'success', icon: 'ri-checkbox-circle-line' },
  { title: 'Rejected', value: rejected.value, color: 'error', icon: 'ri-close-circle-line' },
])

const statusColor = { pending: 'warning', approved: 'success', rejected: 'error' }

const getStatusLabel = (status) => {
  return {
    pending: 'Pending',
    approved: 'Approved',
    rejected: 'Rejected'
  }[status]
}
</script>
