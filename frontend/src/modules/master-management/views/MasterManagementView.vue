<template>
    <div class="w-full px-0 py-2 text-[#172033] max-[900px]:px-4 max-[600px]:pb-4">
      <section
        class="rounded-xl bg-gradient-to-br from-[#1E4F8A] via-[#2D659C] to-[#DCEBF7] px-[34px] py-[30px] text-white shadow-[0_10px_30px_rgba(30,79,138,0.14)] max-[600px]:px-6 max-[600px]:py-6"
      >
        <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.14em] text-[#DCEBF7]">
          GMO TRAVEL · ADMINISTRASI
        </p>
  
        <h1 class="mb-2 text-[clamp(25px,3vw,34px)] font-bold tracking-[-0.02em]">
          Master Management
        </h1>
  
        <p class="mb-0 max-w-[590px] text-sm leading-[1.6] text-[#e5f2ff]">
          Manage departments, sections, and categories in one panel.
        </p>
      </section>
  
      <div class="mt-[18px] flex flex-wrap items-center gap-2">
        <button
          type="button"
          class="rounded-lg px-4 py-2 text-xs font-bold transition"
          :class="
            activeTab === 'departments'
              ? 'bg-[#B8D8F0] text-[#174A7F] shadow-sm'
              : 'bg-[#E2E8F0] text-slate-600 hover:bg-[#CFE3F3] hover:text-[#174A7F]'
          "
          @click="changeTab('departments')"
        >
          Departments
        </button>
  
        <button
          type="button"
          class="rounded-lg px-4 py-2 text-xs font-bold transition"
          :class="
            activeTab === 'sections'
              ? 'bg-[#BFE3C7] text-[#176B2A] shadow-sm'
              : 'bg-[#E2E8F0] text-slate-600 hover:bg-[#D5EBD9] hover:text-[#176B2A]'
          "
          @click="changeTab('sections')"
        >
          Sections
        </button>
  
        <button
          type="button"
          class="rounded-lg px-4 py-2 text-xs font-bold transition"
          :class="
            activeTab === 'categories'
              ? 'bg-[#F3D29D] text-[#985700] shadow-sm'
              : 'bg-[#E2E8F0] text-slate-600 hover:bg-[#F0DFBF] hover:text-[#985700]'
          "
          @click="changeTab('categories')"
        >
          Categories
        </button>
      </div>
  
      <div v-if="activeTab === 'categories'" class="w-full">
        <section class="mt-[22px] w-full">
          <div
            class="mb-3 flex shrink-0 items-end justify-between gap-4 max-[600px]:flex-col max-[600px]:items-start"
          >
            <div class="w-full">
              <p class="mb-1 text-xs font-bold uppercase tracking-[.14em] text-[#1E4F8A]">
                Categories
              </p>
  
              <h2 class="text-[22px] font-semibold">
                Category List
              </h2>
  
              <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="text-xs font-medium text-slate-400">
                  Show:
                </span>
  
                <select
                  v-model.number="categoryPerPage"
                  class="rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 outline-none focus:border-[#1E4F8A] focus:ring-2 focus:ring-[#1E4F8A]/10"
                  @change="categoryPage = 1"
                >
                  <option
                    v-for="size in PAGE_SIZE_OPTIONS"
                    :key="size"
                    :value="size"
                  >
                    {{ size }}
                  </option>
                </select>
  
                <div class="relative ml-2 max-[600px]:ml-0">
                  <input
                    v-model="categorySearch"
                    type="text"
                    placeholder="Search categories..."
                    class="w-[220px] rounded-md border border-slate-300 bg-white px-3 py-1.5 pr-8 text-xs text-slate-600 outline-none placeholder:text-slate-400 focus:border-[#1E4F8A] focus:ring-2 focus:ring-[#1E4F8A]/10 max-[600px]:w-full"
                    @input="categoryPage = 1"
                  />
  
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="pointer-events-none absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"
                    />
                  </svg>
                </div>
              </div>
            </div>
  
            <button
              type="button"
              class="inline-flex shrink-0 items-center justify-center gap-2 rounded-[7px] bg-green-600 px-[15px] py-2.5 text-[13px] font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50 max-[600px]:w-full"
              @click="addCategory"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M12 4v16m8-8H4"
                />
              </svg>
  
              Add Category
            </button>
          </div>
  
          <div
            v-if="categoryLoading"
            class="flex flex-1 items-center justify-center p-8 text-center text-[13px] text-slate-500"
          >
            Loading categories...
          </div>
  
          <div
            v-else-if="categoryError"
            class="flex flex-1 items-center justify-center rounded-lg bg-red-50 p-8 text-center text-[13px] text-red-700"
          >
            {{ categoryError }}
          </div>
  
          <div
            v-else
            class="overflow-hidden rounded-[10px] border border-slate-200 bg-white shadow-[0_5px_20px_rgba(15,23,42,.05)]"
          >
            <div class="overflow-x-auto">
              <table class="w-full table-fixed border-collapse max-[900px]:min-w-[800px]">
                <colgroup>
                  <col class="w-[18%]" />
                  <col class="w-[10%]" />
                  <col class="w-[27%]" />
                  <col class="w-[22.5%]" />
                  <col class="w-[22.5%]" />
                </colgroup>
  
                <thead>
                  <tr>
                    <th
                      class="border-b border-[#B8D4EA] bg-[#DCEBF7] px-[18px] py-[13px] text-left text-[13px] font-bold text-[#1E4F8A]"
                    >
                      Actions
                    </th>
  
                    <th
                      class="border-b border-[#B8D4EA] bg-[#DCEBF7] px-[18px] py-[13px] text-left text-[13px] font-bold text-[#1E4F8A]"
                    >
                      ID
                    </th>
  
                    <th
                      class="border-b border-[#B8D4EA] bg-[#DCEBF7] px-[18px] py-[13px] text-left text-[13px] font-bold text-[#1E4F8A]"
                    >
                      Name
                    </th>
  
                    <th
                      class="border-b border-[#B8D4EA] bg-[#DCEBF7] px-[18px] py-[13px] text-left text-[13px] font-bold text-[#1E4F8A]"
                    >
                      Created 
                    </th>
  
                    <th
                      class="border-b border-[#B8D4EA] bg-[#DCEBF7] px-[18px] py-[13px] text-left text-[13px] font-bold text-[#1E4F8A]"
                    >
                      Updated 
                    </th>
                  </tr>
                </thead>
  
                <tbody>
                  <tr
                    v-for="(category, index) in paginatedCategories"
                    :key="category.id"
                    class="transition hover:bg-[#EAF3FA]"
                    :class="index % 2 === 0 ? 'bg-white' : 'bg-[#EEF2F6]'"
                  >
                    <td class="border-b border-slate-200 px-[18px] py-[13px] text-[13px]">
                      <div class="flex flex-wrap items-center gap-[7px]">
                        <button
                          type="button"
                          title="Edit Category"
                          aria-label="Edit Category"
                          class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-green-600 text-white transition hover:bg-green-700"
                          @click="openEditCategory(category)"
                        >
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                          >
                            <path
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.5-8.5a2.121 2.121 0 013 3L12 14l-4 1 1-4 8.5-8.5z"
                            />
                          </svg>
                        </button>
  
                        <button
                          type="button"
                          title="Delete Category"
                          aria-label="Delete Category"
                          class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-red-600 text-white transition hover:bg-red-700"
                          @click="deleteSelectedCategory(category)"
                        >
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                          >
                            <path
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-8 0h10"
                            />
                          </svg>
                        </button>
                      </div>
                    </td>
  
                    <td class="border-b border-slate-200 px-[18px] py-[13px] text-[13px] font-semibold text-slate-600">
                      {{ category.id }}
                    </td>
  
                    <td class="break-words border-b border-slate-200 px-[18px] py-[13px] text-[13px] text-slate-700">
                      {{ category.name }}
                    </td>
  
                    <td class="break-words border-b border-slate-200 px-[18px] py-[13px] text-[12px] leading-5 text-slate-600">
                      {{ formatDate(category.created_at) }}
                    </td>
  
                    <td class="break-words border-b border-slate-200 px-[18px] py-[13px] text-[12px] leading-5 text-slate-600">
                      {{ formatDate(category.updated_at) }}
                    </td>
                  </tr>
  
                  <tr v-if="filteredCategories.length === 0">
                    <td
                      colspan="5"
                      class="bg-white p-[30px] text-center text-[13px] text-slate-400"
                    >
                      No categories found.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
  
            <div
              v-if="filteredCategories.length > 0"
              class="flex shrink-0 items-center justify-end gap-1.5 border-t border-slate-200 bg-white px-[18px] py-2.5 max-[600px]:flex-wrap"
            >
              <button
                type="button"
                class="inline-flex h-8 min-w-[34px] items-center justify-center rounded-md border border-slate-300 bg-white px-2 text-xs font-semibold text-slate-600 disabled:cursor-not-allowed disabled:opacity-45"
                :disabled="categoryPage === 1"
                @click="categoryPage--"
              >
                Previous
              </button>
  
              <button
                v-for="page in categoryTotalPages"
                :key="page"
                type="button"
                class="inline-flex h-8 min-w-[34px] items-center justify-center rounded-md border px-2 text-xs font-semibold"
                :class="
                  categoryPage === page
                    ? 'border-[#1E4F8A] bg-[#1E4F8A] text-white'
                    : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50'
                "
                @click="categoryPage = page"
              >
                {{ page }}
              </button>
  
              <button
                type="button"
                class="inline-flex h-8 min-w-[34px] items-center justify-center rounded-md border border-slate-300 bg-white px-2 text-xs font-semibold text-slate-600 disabled:cursor-not-allowed disabled:opacity-45"
                :disabled="categoryPage === categoryTotalPages"
                @click="categoryPage++"
              >
                Next
              </button>
            </div>
          </div>
        </section>
      </div>
  
      <div
        v-else-if="activeTab === 'departments'"
        class="mt-[22px] rounded-xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-400"
      >
        Departments will be added here.
      </div>
  
      <div
  v-else-if="activeTab === 'sections'"
  class="mt-[22px] rounded-xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-400"
>
  Sections will be added here.
</div>

<AddCategory
  v-if="showCategoryModal"
  @close="showCategoryModal = false"
  @success="handleCategoryCreated"
/>

<EditCategory
  v-if="showEditCategoryModal && selectedCategory"
  :category="selectedCategory"
  @close="closeEditCategory"
  @success="handleCategoryUpdated"
/>
<!-- DELETE CATEGORY CONFIRMATION MODAL -->
<div
  v-if="showDeleteConfirmation"
  class="fixed inset-0 z-[999999] flex items-center justify-center bg-slate-900/45 p-5"
  @click.self="cancelDelete"
>
  <div
    class="w-[400px] max-w-full rounded-xl bg-white p-[30px_25px] text-center shadow-[0_20px_50px_rgba(15,23,42,0.20)]"
  >
    <div
      class="mx-auto mb-[18px] flex h-[54px] w-[54px] items-center justify-center rounded-full bg-[#EAF2F9] text-[27px] font-bold text-[#1E4F8A]"
    >
      ?
    </div>

    <p
      class="m-0 text-base font-medium leading-[1.6] text-[#172033]"
    >
      Are you sure you want to delete this category?
    </p>

    <div class="mt-[25px] flex items-center justify-center gap-2.5">
      <button
        type="button"
        class="min-w-[90px] rounded-lg bg-slate-100 px-5 py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="deleteLoading"
        @click="cancelDelete"
      >
        Cancel
      </button>

      <button
        type="button"
        class="min-w-[90px] rounded-lg bg-red-600 px-5 py-2.5 text-xs font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="deleteLoading"
        @click="confirmDelete"
      >
        {{ deleteLoading ? 'Deleting...' : 'Yes' }}
      </button>
    </div>
  </div>
</div>

</div>
</template>

  <script setup>
  import {
    computed,
    onMounted,
    ref,
  } from 'vue'
  
  import {
    useRoute,
    useRouter,
  } from 'vue-router'
  
  import {
    getCategories,
    deleteCategory,
  } from '../services/categoryService'

  import AddCategory from '../components/modal/category/add-category.vue'
  import EditCategory from '../components/modal/category/edit-category.vue'
  import { useToastStore } from '../../../stores/toast'

  const route = useRoute()
  const router = useRouter()
  const toast = useToastStore()
  
  const PAGE_SIZE_OPTIONS = [
    5,
    10,
    25,
    50,
    100,
  ]
  
  const activeTab = ref(
    ['departments', 'sections', 'categories'].includes(route.query.tab)
      ? route.query.tab
      : 'categories'
  )
  
  const changeTab = tab => {
    activeTab.value = tab
  
    router.replace({
      query: {
        ...route.query,
        tab,
      },
    })
  }
  
  const categorySearch = ref('')
  const categoryPerPage = ref(5)
  const categoryPage = ref(1)
  
  const categories = ref([])
  const categoryLoading = ref(false)
  const categoryError = ref('')

  const showCategoryModal = ref(false)
  const showEditCategoryModal = ref(false)

  const showDeleteConfirmation = ref(false)
  const deleteLoading = ref(false)

  const selectedCategory = ref(null)
  
  const fetchCategories = async () => {
    categoryLoading.value = true
    categoryError.value = ''
  
    try {
      const response = await getCategories()
  
      categories.value =
        response.data ??
        response
  
      if (
        categoryPage.value >
        categoryTotalPages.value
      ) {
        categoryPage.value =
          categoryTotalPages.value
      }
    } catch (err) {
      categoryError.value =
        err.message ||
        'Failed to load categories.'
    } finally {
      categoryLoading.value = false
    }
  }
  
  const filteredCategories = computed(() => {
    const keyword = categorySearch.value
      .trim()
      .toLowerCase()
  
    if (!keyword) {
      return categories.value
    }
  
    return categories.value.filter(category => {
      const name =
        category.name?.toLowerCase() || ''
  
      return name.includes(keyword)
    })
  })
  
  const categoryTotalPages = computed(() => {
    return Math.max(
      1,
      Math.ceil(
        filteredCategories.value.length /
        categoryPerPage.value
      )
    )
  })
  
  const paginatedCategories = computed(() => {
    const start =
      (categoryPage.value - 1) *
      categoryPerPage.value
  
    return filteredCategories.value.slice(
      start,
      start + categoryPerPage.value
    )
  })
  
  const formatDate = date => {
  if (!date) {
    return '-'
  }

  return new Intl.DateTimeFormat('sv-SE', {
    timeZone: 'Asia/Jakarta',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).format(new Date(date))
}
  
  const addCategory = () => {
  showCategoryModal.value = true
}
const handleCategoryCreated = async () => {
  showCategoryModal.value = false
  await fetchCategories()
} 
const handleCategoryUpdated = async () => {
  showEditCategoryModal.value = false
  selectedCategory.value = null
  await fetchCategories()
}

const closeEditCategory = () => {
  showEditCategoryModal.value = false
  selectedCategory.value = null
}
  
const openEditCategory = category => {
  selectedCategory.value = category
  showEditCategoryModal.value = true
}
  
const deleteSelectedCategory = category => {
  selectedCategory.value = category
  showDeleteConfirmation.value = true
}

const cancelDelete = () => {
  if (deleteLoading.value) {
    return
  }

  showDeleteConfirmation.value = false
  selectedCategory.value = null
}

const confirmDelete = async () => {
  if (deleteLoading.value || !selectedCategory.value) {
    return
  }

  deleteLoading.value = true

  try {
    await deleteCategory(selectedCategory.value.id)

    toast.success(
      'Category Deleted',
      'Category has been deleted successfully.'
    )

    showDeleteConfirmation.value = false
    selectedCategory.value = null

    await fetchCategories()
  } catch (err) {
    toast.error(
      'Action Failed',
      err.message || 'Failed to delete category.'
    )

    showDeleteConfirmation.value = false
    selectedCategory.value = null
  } finally {
    deleteLoading.value = false
  }
}
  
  onMounted(() => {
    fetchCategories()
  })
  </script>
  