import { defineStore } from 'pinia'
import { ref } from 'vue'
import {
  getMasterRegions,
  createMasterRegion,
  updateMasterRegion,
  deleteMasterRegion,
} from '../services/masterRegionService'

export const useMasterRegionStore = defineStore('masterRegion', () => {
  const masterRegions = ref([])
  const loading = ref(false)
  const error = ref('')

  async function fetchMasterRegions() {
    loading.value = true
    error.value = ''

    try {
      const response = await getMasterRegions()
      masterRegions.value = response.data ?? response
    } catch (err) {
      error.value =
        err.response?.data?.message ||
        err.message ||
        'Failed to load master regions.'
    } finally {
      loading.value = false
    }
  }

  async function addMasterRegion(payload) {
    await createMasterRegion(payload)
    await fetchMasterRegions()
  }

  async function editMasterRegion(id, payload) {
    await updateMasterRegion(id, payload)
    await fetchMasterRegions()
  }

  async function removeMasterRegion(id) {
    await deleteMasterRegion(id)
    await fetchMasterRegions()
  }

  return {
    masterRegions,
    loading,
    error,
    fetchMasterRegions,
    addMasterRegion,
    editMasterRegion,
    removeMasterRegion,
  }
})