import { ref, computed } from 'vue'

const leaves = ref([
  {
    id: 1,
    name: 'Andi Pratama',
    employeeId: 'EMP-001',
    startDate: '01 Sep 2026',
    endDate: '03 Sep 2026',
    duration: 3,
    reason: 'Keperluan keluarga',
    status: 'pending',
  },
  {
    id: 2,
    name: 'Siti Rahma',
    employeeId: 'EMP-002',
    startDate: '07 Sep 2026',
    endDate: '08 Sep 2026',
    duration: 2,
    reason: 'Acara keluarga',
    status: 'approved',
  },
  {
    id: 3,
    name: 'Budi Santoso',
    employeeId: 'EMP-003',
    startDate: '14 Sep 2026',
    endDate: '16 Sep 2026',
    duration: 3,
    reason: 'Keperluan pribadi',
    status: 'pending',
  },
])

const search = ref('')

const total = computed(() => leaves.value.length)

const pending = computed(() =>
  leaves.value.filter(item => item.status === 'pending').length
)

const approved = computed(() =>
  leaves.value.filter(item => item.status === 'approved').length
)

const rejected = computed(() =>
  leaves.value.filter(item => item.status === 'rejected').length
)

const filteredLeaves = computed(() => {
  const keyword = search.value.toLowerCase().trim()

  if (!keyword) {
    return leaves.value
  }

  return leaves.value.filter(item =>
    item.name.toLowerCase().includes(keyword) ||
    item.employeeId.toLowerCase().includes(keyword)
  )
})

const addLeave = (leave) => {
  leaves.value.unshift({
    id: Date.now(),
    ...leave,
  })
}

export const useAnnualLeaveStore = () => {
  return {
    leaves,
    search,
    total,
    pending,
    approved,
    rejected,
    filteredLeaves,
    addLeave,
  }
}