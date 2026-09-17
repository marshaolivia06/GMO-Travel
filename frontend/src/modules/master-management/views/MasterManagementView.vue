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

    <!-- Categories -->
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

                <Search
  class="pointer-events-none absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
/>
              </div>
            </div>
          </div>

          <button
            type="button"
            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-[7px] bg-green-600 px-[15px] py-2.5 text-[13px] font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50 max-[600px]:w-full"
            @click="addCategory"
          >
            <Plus class="h-4 w-4" />
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
                        <Edit class="h-4 w-4" />
                      </button>

                      <button
                        type="button"
                        title="Delete Category"
                        aria-label="Delete Category"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-red-600 text-white transition hover:bg-red-700"
                        @click="deleteSelectedCategory(category)"
                      >
                        <Trash2 class="h-4 w-4" />
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

          <AppPagination
            v-if="filteredCategories.length > 0"
            :current-page="categoryPage"
            :last-page="categoryTotalPages"
            :total="filteredCategories.length"
            :per-page="categoryPerPage"
            @change="categoryPage = $event"
          />
        </div>
      </section>
    </div>

    <!-- Departments -->
    <div v-else-if="activeTab === 'departments'" class="w-full">
      <section class="mt-[22px] w-full">
        <div
          class="mb-3 flex shrink-0 items-end justify-between gap-4 max-[600px]:flex-col max-[600px]:items-start"
        >
          <div class="w-full">
            <p class="mb-1 text-xs font-bold uppercase tracking-[.14em] text-[#1E4F8A]">
              Departments
            </p>

            <h2 class="text-[22px] font-semibold">
              Department List
            </h2>

            <div class="mt-2 flex flex-wrap items-center gap-2">
              <span class="text-xs font-medium text-slate-400">
                Show:
              </span>

              <select
                v-model.number="departmentPerPage"
                class="rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 outline-none focus:border-[#1E4F8A] focus:ring-2 focus:ring-[#1E4F8A]/10"
                @change="departmentPage = 1"
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
                  v-model="departmentSearch"
                  type="text"
                  placeholder="Search departments..."
                  class="w-[220px] rounded-md border border-slate-300 bg-white px-3 py-1.5 pr-8 text-xs text-slate-600 outline-none placeholder:text-slate-400 focus:border-[#1E4F8A] focus:ring-2 focus:ring-[#1E4F8A]/10 max-[600px]:w-full"
                  @input="departmentPage = 1"
                />

                <Search
  class="pointer-events-none absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
/>
              </div>
            </div>
          </div>

          <button
            type="button"
            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-[7px] bg-green-600 px-[15px] py-2.5 text-[13px] font-semibold text-white transition hover:bg-green-700 max-[600px]:w-full"
            @click="addDepartment"
          >
            <Plus class="h-4 w-4" />
            Add Department
          </button>
        </div>

        <div
          v-if="departmentLoading"
          class="flex flex-1 items-center justify-center p-8 text-center text-[13px] text-slate-500"
        >
          Loading departments...
        </div>

        <div
          v-else-if="departmentError"
          class="flex flex-1 items-center justify-center rounded-lg bg-red-50 p-8 text-center text-[13px] text-red-700"
        >
          {{ departmentError }}
        </div>

        <div
          v-else
          class="overflow-hidden rounded-[10px] border border-slate-200 bg-white shadow-[0_5px_20px_rgba(15,23,42,.05)]"
        >
          <div class="overflow-x-auto">
            <table class="w-full table-fixed border-collapse max-[900px]:min-w-[1200px]">
              <thead>
                <tr>
                  <th class="border-b border-[#B8D4EA] bg-[#DCEBF7] px-[18px] py-[13px] text-left text-[13px] font-bold text-[#1E4F8A]">
                    Actions
                  </th>
                  <th class="border-b border-[#B8D4EA] bg-[#DCEBF7] px-[18px] py-[13px] text-left text-[13px] font-bold text-[#1E4F8A]">
                    ID
                  </th>
                  <th class="border-b border-[#B8D4EA] bg-[#DCEBF7] px-[18px] py-[13px] text-left text-[13px] font-bold text-[#1E4F8A]">
                    Name
                  </th>
                  <th class="border-b border-[#B8D4EA] bg-[#DCEBF7] px-[18px] py-[13px] text-left text-[13px] font-bold text-[#1E4F8A]">
                    Category
                  </th>
                  <th class="border-b border-[#B8D4EA] bg-[#DCEBF7] px-[18px] py-[13px] text-left text-[13px] font-bold text-[#1E4F8A]">
                    Dept Head
                  </th>
                  <th class="border-b border-[#B8D4EA] bg-[#DCEBF7] px-[18px] py-[13px] text-left text-[13px] font-bold text-[#1E4F8A]">
                    Dept Admin
                  </th>
                  <th class="border-b border-[#B8D4EA] bg-[#DCEBF7] px-[18px] py-[13px] text-left text-[13px] font-bold text-[#1E4F8A]">
                    Division Head
                  </th>
                  <th class="border-b border-[#B8D4EA] bg-[#DCEBF7] px-[18px] py-[13px] text-left text-[13px] font-bold text-[#1E4F8A]">
                    Created
                  </th>
                  <th class="border-b border-[#B8D4EA] bg-[#DCEBF7] px-[18px] py-[13px] text-left text-[13px] font-bold text-[#1E4F8A]">
                    Updated
                  </th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="(department, index) in paginatedDepartments"
                  :key="department.id"
                  class="transition hover:bg-[#EAF3FA]"
                  :class="index % 2 === 0 ? 'bg-white' : 'bg-[#EEF2F6]'"
                >
                  <td class="border-b border-slate-200 px-[18px] py-[13px] text-[13px]">
                    <div class="flex flex-wrap items-center gap-[7px]">
                      <button
                        type="button"
                        title="Edit Department"
                        aria-label="Edit Department"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-green-600 text-white transition hover:bg-green-700"
                        @click="openEditDepartment(department)"
                      >
                        <Edit class="h-4 w-4" />
                      </button>

                      <button
                        type="button"
                        title="Delete Department"
                        aria-label="Delete Department"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-red-600 text-white transition hover:bg-red-700"
                        @click="deleteSelectedDepartment(department)"
                      >
                        <Trash2 class="h-4 w-4" />
                      </button>
                    </div>
                  </td>

                  <td class="border-b border-slate-200 px-[18px] py-[13px] text-[13px] font-semibold text-slate-600">
                    {{ department.id }}
                  </td>

                  <td class="border-b border-slate-200 px-[18px] py-[13px] text-[13px] text-slate-700">
                    {{ department.name }}
                  </td>

                  <td class="border-b border-slate-200 px-[18px] py-[13px] text-[13px] text-slate-700">
                    {{ department.category?.name || '-' }}
                  </td>

                  <td class="border-b border-slate-200 px-[18px] py-[13px] text-[13px] text-slate-700">
                    {{ department.dept_head?.name || '-' }}
                  </td>

                  <td class="border-b border-slate-200 px-[18px] py-[13px] text-[13px] text-slate-700">
                    {{ department.dept_admin?.name || '-' }}
                  </td>

                  <td class="border-b border-slate-200 px-[18px] py-[13px] text-[13px] text-slate-700">
                    {{ department.division_head?.division_name || '-' }}
                  </td>

                  <td class="border-b border-slate-200 px-[18px] py-[13px] text-[12px] text-slate-600">
                    {{ formatDate(department.created_at) }}
                  </td>

                  <td class="border-b border-slate-200 px-[18px] py-[13px] text-[12px] text-slate-600">
                    {{ formatDate(department.updated_at) }}
                  </td>
                </tr>

                <tr v-if="filteredDepartments.length === 0">
                  <td
                    colspan="9"
                    class="bg-white p-[30px] text-center text-[13px] text-slate-400"
                  >
                    No departments found.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <AppPagination
            v-if="filteredDepartments.length > 0"
            :current-page="departmentPage"
            :last-page="departmentTotalPages"
            :total="filteredDepartments.length"
            :per-page="departmentPerPage"
            @change="departmentPage = $event"
          />
        </div>
      </section>
    </div>

    <!-- Sections -->
    <div
      v-else-if="activeTab === 'sections'"
      class="mt-[22px] rounded-xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-400"
    >
      Cooming soon - depends on the author.
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

    <AddDepartment
      v-if="showDepartmentModal"
      :options="departmentOptions"
      @close="showDepartmentModal = false"
      @success="handleDepartmentCreated"
    />

    <EditDepartment
      v-if="showEditDepartmentModal && selectedDepartment"
      :department="selectedDepartment"
      :options="departmentOptions"
      @close="closeEditDepartment"
      @success="handleDepartmentUpdated"
    /> 

  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Edit,  Search, Plus, Trash2 } from 'lucide-vue-next'
import { getCategories, deleteCategory } from '../services/categoryService'

import { getDepartments, getDepartmentOptions, deleteDepartment } from '../services/departmentService'

import AddCategory from '../components/modal/category/add-category.vue'
import AddDepartment from '../components/modal/department/add-department.vue'
import EditCategory from '../components/modal/category/edit-category.vue'
import EditDepartment from '../components/modal/department/edit-department.vue'
import AppPagination from '../../../components/AppPagination.vue'
import swal from '../../../plugins/swal'

const route = useRoute()
const router = useRouter()

const PAGE_SIZE_OPTIONS = [ 5, 10, 25, 50, 100]
 
const activeTab = ref(
  ['departments', 'sections', 'categories'].includes(route.query.tab)
    ? route.query.tab
    : 'departments'
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

// Categories
const categorySearch = ref('')
const categoryPerPage = ref(5)
const categoryPage = ref(1)

const categories = ref([])
const categoryLoading = ref(false)
const categoryError = ref('')

const showCategoryModal = ref(false)
const showEditCategoryModal = ref(false)
const deleteLoading = ref(false)
const selectedCategory = ref(null)

const fetchCategories = async () => {
  categoryLoading.value = true
  categoryError.value = ''

  try {
    const response = await getCategories()

    categories.value = response.data ?? response

    if (categoryPage.value > categoryTotalPages.value) {
      categoryPage.value = categoryTotalPages.value
    }
  } catch (err) {
    categoryError.value =
      err.message || 'Failed to load categories.'
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
    const name = category.name?.toLowerCase() || ''

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

const deleteSelectedCategory = async category => {
  const result = await swal.confirm(
    'Are you sure you want to delete this category?',
    'delete'
  )

  if (!result.isConfirmed) {
    return
  }

  deleteLoading.value = true

  try {
    await deleteCategory(category.id)

    await swal.success(
      'Category Deleted',
      'Category has been deleted successfully.'
    )

    await fetchCategories()
  } catch (err) {
    await swal.error(
      'Action Failed',
      err.message || 'Failed to delete category.'
    )
  } finally {
    deleteLoading.value = false
  }
}

// Departments
const departments = ref([])
const departmentLoading = ref(false)
const departmentError = ref('')

const showDepartmentModal = ref(false)
const showEditDepartmentModal = ref(false)
const departmentDeleteLoading = ref(false)
const selectedDepartment = ref(null)

const departmentOptions = ref({
  categories: [],
  dept_heads: [],
  dept_admins: [],
  division_heads: [],
})

const departmentSearch = ref('')
const departmentPerPage = ref(5)
const departmentPage = ref(1)

const fetchDepartments = async () => {
  departmentLoading.value = true
  departmentError.value = ''

  try {
    const response = await getDepartments()

    departments.value = response.data ?? response

    if (departmentPage.value > departmentTotalPages.value) {
      departmentPage.value = departmentTotalPages.value
    }
  } catch (err) {
    departmentError.value =
      err.message || 'Failed to load departments.'
  } finally {
    departmentLoading.value = false
  }
}

const fetchDepartmentOptions = async () => {
  try {
    const response = await getDepartmentOptions()

    departmentOptions.value = response.data ?? response
  } catch (err) {
    await swal.error(
      'Action Failed',
      err.message || 'Failed to load department options.'
    )
  }
}

const addDepartment = () => {
  showDepartmentModal.value = true
}

const handleDepartmentCreated = async () => {
  showDepartmentModal.value = false
  await fetchDepartments()
}

const openEditDepartment = department => {
  selectedDepartment.value = department
  showEditDepartmentModal.value = true
}

const deleteSelectedDepartment = async department => {
  const result = await swal.confirm(
    'Are you sure you want to delete this department?',
    'delete'
  )

  if (!result.isConfirmed) {
    return
  }

  departmentDeleteLoading.value = true

  try {
    await deleteDepartment(department.id)

    await swal.success(
      'Department Deleted',
      'Department has been deleted successfully.'
    )

    await fetchDepartments()
  } catch (err) {
    await swal.error(
      'Action Failed',
      err.message || 'Failed to delete department.'
    )
  } finally {
    departmentDeleteLoading.value = false
  }
}

const filteredDepartments = computed(() => {
  const keyword = departmentSearch.value
    .trim()
    .toLowerCase()

  if (!keyword) {
    return departments.value
  }

  return departments.value.filter(department => {
    const name =
      department.name?.toLowerCase() || ''

    const category =
      department.category?.name?.toLowerCase() || ''

    const deptHead =
      department.dept_head?.name?.toLowerCase() || ''

    const deptAdmin =
      department.dept_admin?.name?.toLowerCase() || ''

    const divisionHead =
      department.division_head?.division_name?.toLowerCase() || ''

    return (
      name.includes(keyword) ||
      category.includes(keyword) ||
      deptHead.includes(keyword) ||
      deptAdmin.includes(keyword) ||
      divisionHead.includes(keyword)
    )
  })
})

const closeEditDepartment = () => {
  showEditDepartmentModal.value = false
  selectedDepartment.value = null
}

const handleDepartmentUpdated = async () => {
  showEditDepartmentModal.value = false
  selectedDepartment.value = null
  await fetchDepartments()
}

const departmentTotalPages = computed(() => {
  return Math.max(
    1,
    Math.ceil(
      filteredDepartments.value.length /
        departmentPerPage.value
    )
  )
})

const paginatedDepartments = computed(() => {
  const start =
    (departmentPage.value - 1) *
    departmentPerPage.value

  return filteredDepartments.value.slice(
    start,
    start + departmentPerPage.value
  )
})

// Utilities
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

onMounted(() => {
  fetchCategories()
  fetchDepartments()
  fetchDepartmentOptions()
})

</script>