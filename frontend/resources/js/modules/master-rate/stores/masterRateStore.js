import { defineStore } from 'pinia'
import { ref } from 'vue'
import {
  getMasterRates,
  createMasterRate,
  updateMasterRate,
  deleteMasterRate,
} from '../services/masterRateService'

export const useMasterRateStore = defineStore('masterRate', () => {
  const masterRates = ref([])
  const loading = ref(false)
  const error = ref('')

  async function fetchMasterRates(regionId) {
    loading.value = true
    error.value = ''

    try {
      const response = await getMasterRates(regionId)
      masterRates.value = response.data ?? response
    } catch (err) {
      error.value =
        err.response?.data?.message ||
        err.message ||
        'Failed to load master rates.'
    } finally {
      loading.value = false
    }
  }

  async function addMasterRate(payload, regionId) {
    await createMasterRate(payload)
    await fetchMasterRates(regionId)
  }

  async function editMasterRate(id, payload, regionId) {
    await updateMasterRate(id, payload)
    await fetchMasterRates(regionId)
  }

  async function removeMasterRate(id, regionId) {
    await deleteMasterRate(id)
    await fetchMasterRates(regionId)
  }

  return {
    masterRates,
    loading,
    error,
    fetchMasterRates,
    addMasterRate,
    editMasterRate,
    removeMasterRate,
  }
})