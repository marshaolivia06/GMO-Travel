import { defineStore } from 'pinia'
import { ref } from 'vue'
import {
  getAuthorityMatrices,
  createAuthorityMatrix,
  updateAuthorityMatrix,
  deleteAuthorityMatrix,
} from '../services/authorityMatrixService'

export const useAuthorityMatrixStore = defineStore('authorityMatrix', () => {
  const authorityMatrices = ref([])
  const loading = ref(false)
  const error = ref('')

  async function fetchAuthorityMatrices() {
    loading.value = true
    error.value = ''

    try {
      const response = await getAuthorityMatrices()
      authorityMatrices.value = response.data ?? response
    } catch (err) {
      error.value =
        err.response?.data?.message ||
        err.message ||
        'Failed to load authority matrices.'
    } finally {
      loading.value = false
    }
  }

  async function addAuthorityMatrix(payload) {
    await createAuthorityMatrix(payload)
    await fetchAuthorityMatrices()
  }

  async function editAuthorityMatrix(id, payload) {
    await updateAuthorityMatrix(id, payload)
    await fetchAuthorityMatrices()
  }

  async function removeAuthorityMatrix(id) {
    await deleteAuthorityMatrix(id)
    await fetchAuthorityMatrices()
  }

  return {
    authorityMatrices,
    loading,
    error,
    fetchAuthorityMatrices,
    addAuthorityMatrix,
    editAuthorityMatrix,
    removeAuthorityMatrix,
  }
})