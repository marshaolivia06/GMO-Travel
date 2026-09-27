import { defineStore } from 'pinia'
import { ref } from 'vue'
import {
  getTravelAdvanceMasters,
  createTravelAdvanceMaster,
  updateTravelAdvanceMaster,
  deleteTravelAdvanceMaster,
} from '../services/masterAdvanceService'

export const useMasterAdvanceStore = defineStore('masterAdvance', () => {
  const travelAdvanceMasters = ref([])
  const loading = ref(false)
  const error = ref('')

  async function fetchTravelAdvanceMasters() {
    loading.value = true
    error.value = ''

    try {
      const response = await getTravelAdvanceMasters()
      travelAdvanceMasters.value = response.data ?? response
    } catch (err) {
      error.value =
        err.response?.data?.message ||
        err.message ||
        'Failed to load travel advance masters.'
    } finally {
      loading.value = false
    }
  }

  async function addTravelAdvanceMaster(payload) {
    await createTravelAdvanceMaster(payload)
    await fetchTravelAdvanceMasters()
  }

  async function editTravelAdvanceMaster(id, payload) {
    await updateTravelAdvanceMaster(id, payload)
    await fetchTravelAdvanceMasters()
  }

  async function removeTravelAdvanceMaster(id) {
    await deleteTravelAdvanceMaster(id)
    await fetchTravelAdvanceMasters()
  }

  return {
    travelAdvanceMasters,
    loading,
    error,
    fetchTravelAdvanceMasters,
    addTravelAdvanceMaster,
    editTravelAdvanceMaster,
    removeTravelAdvanceMaster,
  }
})