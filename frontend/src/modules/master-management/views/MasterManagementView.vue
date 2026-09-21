<template>
  <div class="w-full px-0 py-2 text-[#172033] max-[900px]:px-4 max-[600px]:pb-4">

    <div class="flex flex-wrap items-center gap-2">
      <button
        type="button"
        class="rounded-lg px-4 py-2 text-xs font-bold transition"
        :class="
      activeTab === 'departments'
        ? 'bg-[#93C5FD] text-[#124A80] shadow-[0_3px_10px_rgba(30,79,138,0.22)]'
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
        ? 'bg-[#86EFAC] text-[#14532D] shadow-[0_3px_10px_rgba(22,163,74,0.25)]'
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
        ? 'bg-[#FBBF24] text-[#78350F] shadow-[0_3px_10px_rgba(217,119,6,0.25)]'
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

        <AppLoading
  v-if="categoryLoading"
  container-class="min-h-[180px] rounded-[10px] border border-slate-200 bg-white shadow-[0_5px_20px_rgba(15,23,42,.05)]"
  text="Loading categories..."
/>

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

        <AppLoading
  v-if="departmentLoading"
  container-class="min-h-[180px] rounded-[10px] border border-slate-200 bg-white shadow-[0_5px_20px_rgba(15,23,42,.05)]"
  text="Loading departments..."
/>
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
    <!-- Sections -->
<div v-else-if="activeTab === 'sections'" class="w-full">
  <section class="mt-[22px] w-full">
    <div
      class="mb-3 flex shrink-0 items-end justify-between gap-4 max-[600px]:flex-col max-[600px]:items-start"
    >
      <div class="w-full">
        <p class="mb-1 text-xs font-bold uppercase tracking-[.14em] text-[#1E4F8A]">
          Sections
        </p>

        <h2 class="text-[22px] font-semibold">
          Section List
        </h2>

        <div class="mt-2 flex flex-wrap items-center gap-2">
          <span class="text-xs font-medium text-slate-400">
            Show:
          </span>

          <select
            v-model.number="sectionPerPage"
            class="rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 outline-none focus:border-[#1E4F8A] focus:ring-2 focus:ring-[#1E4F8A]/10"
            @change="sectionPage = 1"
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
              v-model="sectionSearch"
              type="text"
              placeholder="Search sections..."
              class="w-[220px] rounded-md border border-slate-300 bg-white px-3 py-1.5 pr-8 text-xs text-slate-600 outline-none placeholder:text-slate-400 focus:border-[#1E4F8A] focus:ring-2 focus:ring-[#1E4F8A]/10 max-[600px]:w-full"
              @input="sectionPage = 1"
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
        @click="addSection"
      >
        <Plus class="h-4 w-4" />
        Add Section
      </button>
    </div>

    <AppLoading
  v-if="sectionLoading"
  container-class="min-h-[180px] rounded-[10px] border border-slate-200 bg-white shadow-[0_5px_20px_rgba(15,23,42,.05)]"
  text="Loading sections..."
/>

    <div
      v-else-if="sectionError"
      class="flex flex-1 items-center justify-center rounded-lg bg-red-50 p-8 text-center text-[13px] text-red-700"
    >
      {{ sectionError }}
    </div>

    <div
      v-else
      class="overflow-hidden rounded-[10px] border border-slate-200 bg-white shadow-[0_5px_20px_rgba(15,23,42,.05)]"
    >
      <div class="overflow-x-auto">
        <table class="w-full table-fixed border-collapse max-[900px]:min-w-[1000px]">
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
                Sect Head
              </th>

              <th class="border-b border-[#B8D4EA] bg-[#DCEBF7] px-[18px] py-[13px] text-left text-[13px] font-bold text-[#1E4F8A]">
                Department
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
              v-for="(section, index) in paginatedSections"
              :key="section.id"
              class="transition hover:bg-[#EAF3FA]"
              :class="index % 2 === 0 ? 'bg-white' : 'bg-[#EEF2F6]'"
            >
              <td class="border-b border-slate-200 px-[18px] py-[13px] text-[13px]">
                <div class="flex flex-wrap items-center gap-[7px]">
                  <button
                    type="button"
                    title="Edit Section"
                    aria-label="Edit Section"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-green-600 text-white transition hover:bg-green-700"
                    @click="openEditSection(section)"
                  >
                    <Edit class="h-4 w-4" />
                  </button>

                  <button
                    type="button"
                    title="Delete Section"
                    aria-label="Delete Section"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-red-600 text-white transition hover:bg-red-700"
                    @click="deleteSelectedSection(section)"
                  >
                    <Trash2 class="h-4 w-4" />
                  </button>
                </div>
              </td>

              <td class="border-b border-slate-200 px-[18px] py-[13px] text-[13px] font-semibold text-slate-600">
                {{ section.id }}
              </td>

              <td class="border-b border-slate-200 px-[18px] py-[13px] text-[13px] text-slate-700">
                {{ section.name }}
              </td>

              <td class="border-b border-slate-200 px-[18px] py-[13px] text-[13px] text-slate-700">
                {{ section.section_head?.name || section.sectionHead?.name || '-' }}
              </td>

              <td class="border-b border-slate-200 px-[18px] py-[13px] text-[13px] text-slate-700">
                {{ section.department?.name || '-' }}
              </td>

              <td class="border-b border-slate-200 px-[18px] py-[13px] text-[12px] text-slate-600">
                {{ formatDate(section.created_at) }}
              </td>

              <td class="border-b border-slate-200 px-[18px] py-[13px] text-[12px] text-slate-600">
                {{ formatDate(section.updated_at) }}
              </td>
            </tr>

            <tr v-if="filteredSections.length === 0">
              <td
                colspan="7"
                class="bg-white p-[30px] text-center text-[13px] text-slate-400"
              >
                No sections found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <AppPagination
        v-if="filteredSections.length > 0"
        :current-page="sectionPage"
        :last-page="sectionTotalPages"
        :total="filteredSections.length"
        :per-page="sectionPerPage"
        @change="sectionPage = $event"
      />
    </div>
  </section>
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

<AddSection
  v-if="showSectionModal"
  @close="showSectionModal = false"
  @success="handleSectionCreated"
/>

<EditSection
  v-if="showEditSectionModal && selectedSection"
  :section="selectedSection"
  @close="closeEditSection"
  @success="handleSectionUpdated"
/> 

  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Edit,  Search, Plus, Trash2 } from 'lucide-vue-next'
import { getCategories, deleteCategory } from '../services/categoryService'

import { getDepartments, getDepartmentOptions, deleteDepartment } from '../services/departmentService'
import { getSections, deleteSection } from '../services/sectionService'
  
import AddCategory from '../components/modal/category/add-category.vue'
import AddDepartment from '../components/modal/department/add-department.vue'
import EditCategory from '../components/modal/category/edit-category.vue'
import EditDepartment from '../components/modal/department/edit-department.vue'
import AddSection from '../components/modal/section/add-section.vue'
import EditSection from '../components/modal/section/edit-section.vue'

import AppPagination from '../../../components/AppPagination.vue'
import AppLoading from '../../../components/AppLoading.vue'
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

// Sections
const sections = ref([])
const sectionLoading = ref(false)
const sectionError = ref('')

const showSectionModal = ref(false)
const showEditSectionModal = ref(false)
const sectionDeleteLoading = ref(false)
const selectedSection = ref(null)

const sectionSearch = ref('')
const sectionPerPage = ref(5)
const sectionPage = ref(1)

const fetchSections = async () => {
  sectionLoading.value = true
  sectionError.value = ''

  try {
    const response = await getSections()

    sections.value = response.data ?? response

    if (sectionPage.value > sectionTotalPages.value) {
      sectionPage.value = sectionTotalPages.value
    }
  } catch (err) {
    sectionError.value =
      err.message || 'Failed to load sections.'
  } finally {
    sectionLoading.value = false
  }
}

const filteredSections = computed(() => {
  const keyword = sectionSearch.value
    .trim()
    .toLowerCase()

  if (!keyword) {
    return sections.value
  }

  return sections.value.filter(section => {
    const name =
      section.name?.toLowerCase() || ''

    const sectionHead =
      section.section_head?.name?.toLowerCase() ||
      section.sectionHead?.name?.toLowerCase() ||
      ''

    const department =
      section.department?.name?.toLowerCase() || ''

    return (
      name.includes(keyword) ||
      sectionHead.includes(keyword) ||
      department.includes(keyword)
    )
  })
})

const sectionTotalPages = computed(() => {
  return Math.max(
    1,
    Math.ceil(
      filteredSections.value.length /
        sectionPerPage.value
    )
  )
})

const paginatedSections = computed(() => {
  const start =
    (sectionPage.value - 1) *
    sectionPerPage.value

  return filteredSections.value.slice(
    start,
    start + sectionPerPage.value
  )
})

const addSection = () => {
  showSectionModal.value = true
}

const handleSectionCreated = async () => {
  showSectionModal.value = false
  await fetchSections()
}

const openEditSection = section => {
  selectedSection.value = section
  showEditSectionModal.value = true
}

const closeEditSection = () => {
  showEditSectionModal.value = false
  selectedSection.value = null
}

const handleSectionUpdated = async () => {
  showEditSectionModal.value = false
  selectedSection.value = null
  await fetchSections()
}

const deleteSelectedSection = async section => {
  const result = await swal.confirm(
    'Are you sure you want to delete this section?',
    'delete'
  )

  if (!result.isConfirmed) {
    return
  }

  sectionDeleteLoading.value = true

  try {
    await deleteSection(section.id)

    await swal.success(
      'Section Deleted',
      'Section has been deleted successfully.'
    )

    await fetchSections()
  } catch (err) {
    await swal.error(
      'Action Failed',
      err.message || 'Failed to delete section.'
    )
  } finally {
    sectionDeleteLoading.value = false
  }
}

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
  fetchSections()
})

</script>