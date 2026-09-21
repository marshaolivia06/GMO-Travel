import { defineStore } from 'pinia'
import { ref } from 'vue'
import { getCategories, createCategory, updateCategory, deleteCategory } from '../services/categoryService'

export const useCategoryStore = defineStore('masterManagement/category', () => {
  const categories = ref([])
  const loading = ref(false)
  const error = ref('')

  const page = ref(1)
  const lastPage = ref(1)
  const total = ref(0)
  const perPage = ref(15)
  const search = ref('')

  async function fetchCategories(p = 1) {
    loading.value = true
    error.value = ''

    try {
      const response = await getCategories({
        page: p,
        per_page: perPage.value,
        search: search.value || undefined,
      })

      const meta = response.data
      categories.value = meta.data
      page.value = meta.current_page
      lastPage.value = meta.last_page
      total.value = meta.total
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Failed to load categories.'
    } finally {
      loading.value = false
    }
  }

  async function addCategory(payload) {
    await createCategory(payload)
    await fetchCategories(page.value)
  }

  async function editCategory(id, payload) {
    await updateCategory(id, payload)
    await fetchCategories(page.value)
  }

  async function removeCategory(id) {
    await deleteCategory(id)
    await fetchCategories(page.value)
  }

  return {
    categories,
    loading,
    error,
    page,
    lastPage,
    total,
    perPage,
    search,
    fetchCategories,
    addCategory,
    editCategory,
    removeCategory,
  }
})
