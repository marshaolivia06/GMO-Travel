import { defineStore } from 'pinia'
import { ref } from 'vue'
import {
  getDepartments,
  getDepartmentOptions,
  createDepartment,
  updateDepartment,
  deleteDepartment,
} from '../services/departmentService'

export const useDepartmentStore = defineStore('masterManagement/department', () => {
  const departments = ref([])
  const loading = ref(false)
  const error = ref('')

  const options = ref({ categories: [], dept_heads: [], dept_admins: [], division_heads: [] })
  const optionsLoading = ref(false)

  async function fetchDepartments() {
    loading.value = true
    error.value = ''

    try {
      const response = await getDepartments()
      departments.value = response.data ?? response
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Failed to load departments.'
    } finally {
      loading.value = false
    }
  }

  async function fetchOptions() {
    optionsLoading.value = true

    try {
      const response = await getDepartmentOptions()
      options.value = response.data ?? response
    } finally {
      optionsLoading.value = false
    }
  }

  async function addDepartment(payload) {
    await createDepartment(payload)
    await fetchDepartments()
  }

  async function editDepartment(id, payload) {
    await updateDepartment(id, payload)
    await fetchDepartments()
  }

  async function removeDepartment(id) {
    await deleteDepartment(id)
    await fetchDepartments()
  }

  return {
    departments,
    loading,
    error,
    options,
    optionsLoading,
    fetchDepartments,
    fetchOptions,
    addDepartment,
    editDepartment,
    removeDepartment,
  }
})
