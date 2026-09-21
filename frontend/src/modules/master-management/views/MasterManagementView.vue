<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useConfirm } from '../../../composables/useConfirm'
import { useToastStore } from '../../../stores/toast'
import { useCategoryStore } from '../stores/categoryStore'
import { useDepartmentStore } from '../stores/departmentStore'
import { useSectionStore } from '../stores/sectionStore'

import AddCategory from '../components/modal/category/add-category.vue'
import EditCategory from '../components/modal/category/edit-category.vue'
import AddDepartment from '../components/modal/department/add-department.vue'
import EditDepartment from '../components/modal/department/edit-department.vue'
import AddSection from '../components/modal/section/add-section.vue'
import EditSection from '../components/modal/section/edit-section.vue'

const route = useRoute()
const router = useRouter()
const { confirm } = useConfirm()
const toast = useToastStore()

const categoryStore = useCategoryStore()
const departmentStore = useDepartmentStore()
const sectionStore = useSectionStore()

const tabs = ['departments', 'sections', 'categories']
const activeTab = ref(tabs.includes(route.query.tab) ? route.query.tab : 'departments')

function changeTab(tab) {
  activeTab.value = tab
  router.replace({ query: { ...route.query, tab } })
}

function formatDate(date) {
  if (!date) return '-'

  return new Intl.DateTimeFormat('sv-SE', {
    timeZone: 'Asia/Jakarta',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).format(new Date(date))
}

function errorMessage(err, fallback) {
  return err.response?.data?.message || err.message || fallback
}

// ---- Categories ----
const categoryHeaders = [
  { title: 'ID', key: 'id', width: 80 },
  { title: 'Name', key: 'name' },
  { title: 'Created', key: 'created_at' },
  { title: 'Updated', key: 'updated_at' },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' },
]

const showCategoryModal = ref(false)
const showEditCategoryModal = ref(false)
const selectedCategory = ref(null)

function openEditCategory(category) {
  selectedCategory.value = category
  showEditCategoryModal.value = true
}

function closeEditCategory() {
  showEditCategoryModal.value = false
  selectedCategory.value = null
}

async function deleteSelectedCategory(category) {
  const confirmed = await confirm({
    title: 'Delete Category',
    text: `Delete "${category.name}"? This cannot be undone.`,
    color: 'error',
  })

  if (!confirmed) return

  try {
    await categoryStore.removeCategory(category.id)
    toast.success('Category deleted successfully.')
  } catch (err) {
    toast.error(errorMessage(err, 'Failed to delete category.'))
  }
}

// ---- Departments ----
const departmentHeaders = [
  { title: 'ID', key: 'id', width: 80 },
  { title: 'Name', key: 'name' },
  { title: 'Category', key: 'category' },
  { title: 'Dept Head', key: 'dept_head' },
  { title: 'Dept Admin', key: 'dept_admin' },
  { title: 'Division Head', key: 'division_head' },
  { title: 'Created', key: 'created_at' },
  { title: 'Updated', key: 'updated_at' },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' },
]

const departmentSearch = ref('')
const showDepartmentModal = ref(false)
const showEditDepartmentModal = ref(false)
const selectedDepartment = ref(null)

function openEditDepartment(department) {
  selectedDepartment.value = department
  showEditDepartmentModal.value = true
}

function closeEditDepartment() {
  showEditDepartmentModal.value = false
  selectedDepartment.value = null
}

async function deleteSelectedDepartment(department) {
  const confirmed = await confirm({
    title: 'Delete Department',
    text: `Delete "${department.name}"? This cannot be undone.`,
    color: 'error',
  })

  if (!confirmed) return

  try {
    await departmentStore.removeDepartment(department.id)
    toast.success('Department deleted successfully.')
  } catch (err) {
    toast.error(errorMessage(err, 'Failed to delete department.'))
  }
}

// ---- Sections ----
const sectionHeaders = [
  { title: 'ID', key: 'id', width: 80 },
  { title: 'Name', key: 'name' },
  { title: 'Sect Head', key: 'section_head' },
  { title: 'Department', key: 'department' },
  { title: 'Created', key: 'created_at' },
  { title: 'Updated', key: 'updated_at' },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' },
]

const sectionSearch = ref('')
const showSectionModal = ref(false)
const showEditSectionModal = ref(false)
const selectedSection = ref(null)

function openEditSection(section) {
  selectedSection.value = section
  showEditSectionModal.value = true
}

function closeEditSection() {
  showEditSectionModal.value = false
  selectedSection.value = null
}

async function deleteSelectedSection(section) {
  const confirmed = await confirm({
    title: 'Delete Section',
    text: `Delete "${section.name}"? This cannot be undone.`,
    color: 'error',
  })

  if (!confirmed) return

  try {
    await sectionStore.removeSection(section.id)
    toast.success('Section deleted successfully.')
  } catch (err) {
    toast.error(errorMessage(err, 'Failed to delete section.'))
  }
}

onMounted(() => {
  departmentStore.fetchDepartments()
  departmentStore.fetchOptions()
  sectionStore.fetchSections()
  sectionStore.fetchOptions()
})
</script>

<template>
  <VCard rounded="lg">
    <VTabs :model-value="activeTab" color="primary" @update:model-value="changeTab">
      <VTab value="departments" prepend-icon="ri-building-4-line">Departments</VTab>
      <VTab value="sections" prepend-icon="ri-git-branch-line">Sections</VTab>
      <VTab value="categories" prepend-icon="ri-price-tag-3-line">Categories</VTab>
    </VTabs>

    <VDivider />

    <VWindow :model-value="activeTab">
      <VWindowItem value="departments">
        <VCardText>
          <VAlert v-if="departmentStore.error" type="error" class="mb-4">{{ departmentStore.error }}</VAlert>

          <VDataTable
            :headers="departmentHeaders"
            :items="departmentStore.departments"
            :loading="departmentStore.loading"
            :search="departmentSearch"
            item-value="id"
            density="comfortable"
            hover
          >
            <template #top>
              <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px; flex-wrap: nowrap">
                <VBtn color="success" prepend-icon="ri-add-line" @click="showDepartmentModal = true">
                  Add Department
                </VBtn>
                <VTextField
                  v-model="departmentSearch"
                  prepend-inner-icon="ri-search-line"
                  placeholder="Search departments..."
                  single-line
                  clearable
                  hide-details
                  density="compact"
                  style="max-width: 220px"
                />
              </div>
            </template>
            <template #item.category="{ item }">{{ item.category?.name || '-' }}</template>
            <template #item.dept_head="{ item }">{{ item.dept_head?.name || '-' }}</template>
            <template #item.dept_admin="{ item }">{{ item.dept_admin?.name || '-' }}</template>
            <template #item.division_head="{ item }">{{ item.division_head?.division_name || '-' }}</template>
            <template #item.created_at="{ item }">{{ formatDate(item.created_at) }}</template>
            <template #item.updated_at="{ item }">{{ formatDate(item.updated_at) }}</template>
            <template #item.actions="{ item }">
              <VBtn icon="ri-edit-line" size="small" variant="text" color="success" @click="openEditDepartment(item)" />
              <VBtn icon="ri-delete-bin-line" size="small" variant="text" color="error" @click="deleteSelectedDepartment(item)" />
            </template>
          </VDataTable>
        </VCardText>
      </VWindowItem>

      <VWindowItem value="sections">
        <VCardText>
          <VAlert v-if="sectionStore.error" type="error" class="mb-4">{{ sectionStore.error }}</VAlert>

          <VDataTable
            :headers="sectionHeaders"
            :items="sectionStore.sections"
            :loading="sectionStore.loading"
            :search="sectionSearch"
            item-value="id"
            density="comfortable"
            hover
          >
            <template #top>
              <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px; flex-wrap: nowrap">
                <VBtn color="success" prepend-icon="ri-add-line" @click="showSectionModal = true">
                  Add Section
                </VBtn>
                <VTextField
                  v-model="sectionSearch"
                  prepend-inner-icon="ri-search-line"
                  placeholder="Search sections..."
                  single-line
                  clearable
                  hide-details
                  density="compact"
                  style="max-width: 220px"
                />
              </div>
            </template>
            <template #item.section_head="{ item }">{{ item.section_head?.name || item.sectionHead?.name || '-' }}</template>
            <template #item.department="{ item }">{{ item.department?.name || '-' }}</template>
            <template #item.created_at="{ item }">{{ formatDate(item.created_at) }}</template>
            <template #item.updated_at="{ item }">{{ formatDate(item.updated_at) }}</template>
            <template #item.actions="{ item }">
              <VBtn icon="ri-edit-line" size="small" variant="text" color="success" @click="openEditSection(item)" />
              <VBtn icon="ri-delete-bin-line" size="small" variant="text" color="error" @click="deleteSelectedSection(item)" />
            </template>
          </VDataTable>
        </VCardText>
      </VWindowItem>

      <VWindowItem value="categories">
        <VCardText>
          <VAlert v-if="categoryStore.error" type="error" class="mb-4">{{ categoryStore.error }}</VAlert>

          <VDataTableServer
            v-model:items-per-page="categoryStore.perPage"
            :headers="categoryHeaders"
            :items="categoryStore.categories"
            :items-length="categoryStore.total"
            :loading="categoryStore.loading"
            item-value="id"
            density="comfortable"
            hover
            @update:options="opts => categoryStore.fetchCategories(opts.page)"
          >
            <template #top>
              <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px; flex-wrap: nowrap">
                <VBtn color="success" prepend-icon="ri-add-line" @click="showCategoryModal = true">
                  Add Category
                </VBtn>
                <VTextField
                  v-model="categoryStore.search"
                  prepend-inner-icon="ri-search-line"
                  placeholder="Search categories..."
                  single-line
                  clearable
                  hide-details
                  density="compact"
                  style="max-width: 220px"
                  @update:model-value="categoryStore.fetchCategories(1)"
                />
              </div>
            </template>
            <template #item.created_at="{ item }">{{ formatDate(item.created_at) }}</template>
            <template #item.updated_at="{ item }">{{ formatDate(item.updated_at) }}</template>
            <template #item.actions="{ item }">
              <VBtn icon="ri-edit-line" size="small" variant="text" color="success" @click="openEditCategory(item)" />
              <VBtn icon="ri-delete-bin-line" size="small" variant="text" color="error" @click="deleteSelectedCategory(item)" />
            </template>
          </VDataTableServer>
        </VCardText>
      </VWindowItem>
    </VWindow>
  </VCard>

  <AddCategory v-if="showCategoryModal" @close="showCategoryModal = false" />
  <EditCategory v-if="showEditCategoryModal && selectedCategory" :category="selectedCategory" @close="closeEditCategory" />

  <AddDepartment v-if="showDepartmentModal" @close="showDepartmentModal = false" />
  <EditDepartment v-if="showEditDepartmentModal && selectedDepartment" :department="selectedDepartment" @close="closeEditDepartment" />

  <AddSection v-if="showSectionModal" @close="showSectionModal = false" />
  <EditSection v-if="showEditSectionModal && selectedSection" :section="selectedSection" @close="closeEditSection" />
</template>
