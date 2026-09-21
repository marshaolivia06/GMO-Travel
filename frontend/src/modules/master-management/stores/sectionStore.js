import { defineStore } from 'pinia'
import { ref } from 'vue'
import {
  getSections,
  getSectionOptions,
  createSection,
  updateSection,
  deleteSection,
} from '../services/sectionService'

export const useSectionStore = defineStore('masterManagement/section', () => {
  const sections = ref([])
  const loading = ref(false)
  const error = ref('')

  const options = ref({ section_heads: [], departments: [] })
  const optionsLoading = ref(false)

  async function fetchSections() {
    loading.value = true
    error.value = ''

    try {
      const response = await getSections()
      sections.value = response.data ?? response
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Failed to load sections.'
    } finally {
      loading.value = false
    }
  }

  async function fetchOptions() {
    optionsLoading.value = true

    try {
      const response = await getSectionOptions()
      options.value = {
        section_heads: response.section_heads ?? [],
        departments: response.departments ?? [],
      }
    } finally {
      optionsLoading.value = false
    }
  }

  async function addSection(payload) {
    await createSection(payload)
    await fetchSections()
  }

  async function editSection(id, payload) {
    await updateSection(id, payload)
    await fetchSections()
  }

  async function removeSection(id) {
    await deleteSection(id)
    await fetchSections()
  }

  return {
    sections,
    loading,
    error,
    options,
    optionsLoading,
    fetchSections,
    fetchOptions,
    addSection,
    editSection,
    removeSection,
  }
})
