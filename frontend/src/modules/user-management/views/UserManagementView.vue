<template>
  <div
    class="mx-auto w-full max-w-[1180px] px-0 py-2 text-[#172033] max-[900px]:px-4 max-[600px]:pb-4"
  >
    <section
      class="shrink-0 rounded-xl bg-gradient-to-br from-[#1E4F8A] via-[#2D659C] to-[#DCEBF7] px-8 py-6 text-white shadow-lg shadow-blue-900/10"
    >
      <p class="mb-1.5 text-[11px] font-bold uppercase tracking-[.14em] text-[#DCEBF7]">
        GMO TRAVEL · ADMINISTRATION
      </p>

      <h1 class="mb-1.5 text-3xl font-bold tracking-tight">
        User Management
      </h1>

      <p class="text-sm text-blue-50">
        Manage users, roles, and permissions in one panel.
      </p>
    </section>

    <div class="mt-[18px] flex flex-wrap items-center gap-2">
  <button
    type="button"
    class="rounded-lg px-4 py-2 text-xs font-bold transition"
    :class="
      activeTab === 'users'
        ? 'bg-[#B8D8F0] text-[#174A7F] shadow-sm'
        : 'bg-[#E2E8F0] text-slate-600 hover:bg-[#CFE3F3] hover:text-[#174A7F]'
    "
    @click="activeTab = 'users'"
  >
    Users
  </button>

  <button
    type="button"
    class="rounded-lg px-4 py-2 text-xs font-bold transition"
    :class="
      activeTab === 'roles'
        ? 'bg-[#BFE3C7] text-[#176B2A] shadow-sm'
        : 'bg-[#E2E8F0] text-slate-600 hover:bg-[#D5EBD9] hover:text-[#176B2A]'
    "
    @click="activeTab = 'roles'"
  >
    Roles
  </button>

  <button
    type="button"
    class="rounded-lg px-4 py-2 text-xs font-bold transition"
    :class="
      activeTab === 'permissions'
        ? 'bg-[#F3D29D] text-[#985700] shadow-sm'
        : 'bg-[#E2E8F0] text-slate-600 hover:bg-[#F0DFBF] hover:text-[#985700]'
    "
    @click="activeTab = 'permissions'"
  >
    Permissions
  </button>
</div>

    <div v-if="activeTab === 'users'" class="w-full">
      <section class="mt-[22px] w-full">
        <div
          class="mb-3 flex shrink-0 items-end justify-between gap-4 max-[600px]:flex-col max-[600px]:items-start"
        >
          <div>
            <p class="mb-1 text-xs font-bold uppercase tracking-[.14em] text-[#1E4F8A]">
              Users
            </p>

            <h2 class="text-[22px] font-semibold">
              User List
            </h2>

            <div class="mt-2 flex items-center gap-2">
              <span class="text-xs font-medium text-slate-400">
                Show:
              </span>

              <select
                v-model.number="userPerPage"
                class="rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 outline-none focus:border-[#1E4F8A] focus:ring-2 focus:ring-[#1E4F8A]/10"
                @change="userPage = 1"
              >
                <option
                  v-for="size in PAGE_SIZE_OPTIONS"
                  :key="size"
                  :value="size"
                >
                  {{ size }}
                </option>
              </select>
            </div>
          </div>

          <button
            type="button"
            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-[7px] bg-green-600 px-[15px] py-2.5 text-[13px] font-semibold text-white transition hover:bg-green-700 max-[600px]:w-full"
            @click="showAddUser = true"
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

            Add User
          </button>
        </div>

        <div
          v-if="loading"
          class="flex flex-1 items-center justify-center p-8 text-center text-[13px] text-slate-500"
        >
          Loading users...
        </div>

        <div
          v-else-if="error"
          class="flex flex-1 items-center justify-center rounded-lg bg-red-50 p-8 text-center text-[13px] text-red-700"
        >
          {{ error }}
        </div>

        <div
          v-else
          class="overflow-hidden rounded-[10px] border border-slate-200 bg-white shadow-[0_5px_20px_rgba(15,23,42,.05)]"
        >
          <div class="overflow-x-auto">
            <table class="w-full table-fixed border-collapse max-[900px]:min-w-[700px]">
              <colgroup>
                <col class="w-1/4" />
                <col class="w-1/4" />
                <col class="w-1/4" />
                <col class="w-1/4" />
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
      Name
    </th>

    <th
      class="border-b border-[#B8D4EA] bg-[#DCEBF7] px-[18px] py-[13px] text-left text-[13px] font-bold text-[#1E4F8A]"
    >
      Email
    </th>

    <th
      class="border-b border-[#B8D4EA] bg-[#DCEBF7] px-[18px] py-[13px] text-left text-[13px] font-bold text-[#1E4F8A]"
    >
      Role
    </th>
  </tr>
</thead>


              <tbody>
                <tr
                  v-for="(user, index) in paginatedUsers"
                  :key="user.id"
                  class="transition hover:bg-[#EAF3FA]"
                  :class="index % 2 === 0 ? 'bg-white' : 'bg-[#EEF2F6]'"
                >
                  <td class="border-b border-slate-200 px-[18px] py-[13px] text-[13px]">
                    <div class="flex flex-wrap items-center gap-[7px]">
                      <button
                        type="button"
                        title="Edit User"
                        aria-label="Edit User"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-green-600 text-white transition hover:bg-green-700"
                        @click="openEditUser(user)"
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
                        title="Delete User"
                        aria-label="Delete User"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-red-600 text-white transition hover:bg-red-700"
                        @click="deleteSelectedUser(user)"
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

                  <td
                    class="break-words border-b border-slate-200 px-[18px] py-[13px] text-[13px] leading-6 text-slate-700"
                  >
                    {{ user.name }}
                  </td>

                  <td
                    class="break-words border-b border-slate-200 px-[18px] py-[13px] text-[13px] leading-6 text-slate-700"
                  >
                    {{ user.email }}
                  </td>

                  <td class="border-b border-slate-200 px-[18px] py-[13px] text-[13px] text-slate-700">
                    <div class="flex flex-wrap gap-1.5">
                      <span
                        v-for="role in user.roles || []"
                        :key="role.id || role.name"
                        class="inline-flex rounded-full bg-[#DCEBF7] px-2.5 py-1 text-xs font-bold text-[#1E4F8A]"
                      >
                        {{ role.name }}
                      </span>

                      <span
                        v-if="!user.roles?.length"
                        class="text-slate-400"
                      >
                        -
                      </span>
                    </div>
                  </td>
                </tr>

                <tr v-if="users.length === 0">
                  <td
                    colspan="4"
                    class="bg-white p-[30px] text-center text-[13px] text-slate-400"
                  >
                    No users found.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div
            v-if="users.length > 0"
            class="flex shrink-0 items-center justify-end gap-1.5 border-t border-slate-200 bg-white px-[18px] py-2.5 max-[600px]:flex-wrap"
          >
            <button
              type="button"
              class="inline-flex h-8 min-w-[34px] items-center justify-center rounded-md border border-slate-300 bg-white px-2 text-xs font-semibold text-slate-600 disabled:cursor-not-allowed disabled:opacity-45"
              :disabled="userPage === 1"
              @click="userPage--"
            >
              Previous
            </button>

            <button
              v-for="page in userTotalPages"
              :key="page"
              type="button"
              class="inline-flex h-8 min-w-[34px] items-center justify-center rounded-md border px-2 text-xs font-semibold"
              :class="
                userPage === page
                  ? 'border-[#1E4F8A] bg-[#1E4F8A] text-white'
                  : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50'
              "
              @click="userPage = page"
            >
              {{ page }}
            </button>

            <button
              type="button"
              class="inline-flex h-8 min-w-[34px] items-center justify-center rounded-md border border-slate-300 bg-white px-2 text-xs font-semibold text-slate-600 disabled:cursor-not-allowed disabled:opacity-45"
              :disabled="userPage === userTotalPages"
              @click="userPage++"
            >
              Next
            </button>
          </div>
        </div>
      </section>
    </div>

    <div v-else-if="activeTab === 'roles'" class="w-full">
      <section class="mt-[22px] w-full">
        <div
          class="mb-3 flex shrink-0 items-end justify-between gap-4 max-[600px]:flex-col max-[600px]:items-start"
        >
          <div>
            <p class="mb-1 text-xs font-bold uppercase tracking-[.14em] text-[#1E4F8A]">
              Roles
            </p>

            <h2 class="text-[22px] font-semibold">
              Role List
            </h2>

            <div class="mt-2 flex items-center gap-2">
              <span class="text-xs font-medium text-slate-400">
                Show:
              </span>

              <select
                v-model.number="rolePerPage"
                class="rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 outline-none focus:border-[#1E4F8A] focus:ring-2 focus:ring-[#1E4F8A]/10"
                @change="rolePage = 1"
              >
                <option
                  v-for="size in PAGE_SIZE_OPTIONS"
                  :key="size"
                  :value="size"
                >
                  {{ size }}
                </option>
              </select>
            </div>
          </div>

          <button
            type="button"
            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-[7px] bg-green-600 px-[15px] py-2.5 text-[13px] font-semibold text-white transition hover:bg-green-700 max-[600px]:w-full"
            @click="showAddRole = true"
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

            Add Role
          </button>
        </div>

        <div
          v-if="roleLoading"
          class="flex flex-1 items-center justify-center p-8 text-center text-[13px] text-slate-500"
        >
          Loading roles...
        </div>

        <div
          v-else-if="roleError"
          class="flex flex-1 items-center justify-center rounded-lg bg-red-50 p-8 text-center text-[13px] text-red-700"
        >
          {{ roleError }}
        </div>

        <div
          v-else
          class="overflow-hidden rounded-[10px] border border-slate-200 bg-white shadow-[0_5px_20px_rgba(15,23,42,.05)]"
        >
          <div class="overflow-x-auto">
            <table class="w-full table-fixed border-collapse max-[900px]:min-w-[700px]">
              <colgroup>
                <col class="w-1/3" />
                <col class="w-1/3" />
                <col class="w-1/3" />
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
      Name
    </th>

    <th
      class="border-b border-[#B8D4EA] bg-[#DCEBF7] px-[18px] py-[13px] text-left text-[13px] font-bold text-[#1E4F8A]"
    >
      Permissions
    </th>
  </tr>
</thead>


              <tbody>
                <tr
                  v-for="(role, index) in paginatedRoles"
                  :key="role.id"
                  class="transition hover:bg-[#EAF3FA]"
                  :class="index % 2 === 0 ? 'bg-white' : 'bg-[#EEF2F6]'"
                >
                  <td class="border-b border-slate-200 px-[18px] py-[13px] text-[13px]">
                    <div class="flex flex-wrap items-center gap-[7px]">
                      <button
                        type="button"
                        title="Edit Role"
                        aria-label="Edit Role"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-green-600 text-white transition hover:bg-green-700"
                        @click="openEditRole(role)"
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
                        title="Delete Role"
                        aria-label="Delete Role"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-red-600 text-white transition hover:bg-red-700"
                        @click="deleteSelectedRole(role)"
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

                  <td
                    class="break-words border-b border-slate-200 px-[18px] py-[13px] text-[13px] text-slate-700"
                  >
                    {{ role.name }}
                  </td>

                  <td class="border-b border-slate-200 px-[18px] py-[13px] text-[13px]">
                    <div class="flex flex-wrap gap-1.5">
                      <span
                        v-for="permission in role.permissions || []"
                        :key="permission.id || permission.name"
                        class="inline-flex rounded-md bg-[#DCEBF7] px-2.5 py-1 text-[11px] font-semibold text-[#1E4F8A]"
                      >
                        {{ permission.name }}
                      </span>

                      <span
                        v-if="!role.permissions?.length"
                        class="text-slate-400"
                      >
                        -
                      </span>
                    </div>
                  </td>
                </tr>

                <tr v-if="roles.length === 0">
                  <td
                    colspan="3"
                    class="bg-white p-[30px] text-center text-[13px] text-slate-400"
                  >
                    No roles found.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div
            v-if="roles.length > 0"
            class="flex shrink-0 items-center justify-end gap-1.5 border-t border-slate-200 bg-white px-[18px] py-2.5 max-[600px]:flex-wrap"
          >
            <button
              type="button"
              class="inline-flex h-8 min-w-[34px] items-center justify-center rounded-md border border-slate-300 bg-white px-2 text-xs font-semibold text-slate-600 disabled:cursor-not-allowed disabled:opacity-45"
              :disabled="rolePage === 1"
              @click="rolePage--"
            >
              Previous
            </button>

            <button
              v-for="page in roleTotalPages"
              :key="page"
              type="button"
              class="inline-flex h-8 min-w-[34px] items-center justify-center rounded-md border px-2 text-xs font-semibold"
              :class="
                rolePage === page
                  ? 'border-[#1E4F8A] bg-[#1E4F8A] text-white'
                  : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50'
              "
              @click="rolePage = page"
            >
              {{ page }}
            </button>

            <button
              type="button"
              class="inline-flex h-8 min-w-[34px] items-center justify-center rounded-md border border-slate-300 bg-white px-2 text-xs font-semibold text-slate-600 disabled:cursor-not-allowed disabled:opacity-45"
              :disabled="rolePage === roleTotalPages"
              @click="rolePage++"
            >
              Next
            </button>
          </div>
        </div>
      </section>
    </div>

    <div v-else-if="activeTab === 'permissions'" class="w-full">
      <section class="mt-[22px] w-full">
        <div
          class="mb-3 flex shrink-0 items-end justify-between gap-4 max-[600px]:flex-col max-[600px]:items-start"
        >
          <div>
            <p class="mb-1 text-xs font-bold uppercase tracking-[.14em] text-[#1E4F8A]">
              Permissions
            </p>

            <h2 class="text-[22px] font-semibold">
              Permission List
            </h2>

            <div class="mt-2 flex items-center gap-2">
              <span class="text-xs font-medium text-slate-400">
                Show:
              </span>

              <select
                v-model.number="permissionPerPage"
                class="rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 outline-none focus:border-[#1E4F8A] focus:ring-2 focus:ring-[#1E4F8A]/10"
                @change="permissionPage = 1"
              >
                <option
                  v-for="size in PAGE_SIZE_OPTIONS"
                  :key="size"
                  :value="size"
                >
                  {{ size }}
                </option>
              </select>
            </div>
          </div>

          <button
            type="button"
            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-[7px] bg-green-600 px-[15px] py-2.5 text-[13px] font-semibold text-white transition hover:bg-green-700 max-[600px]:w-full"
            @click="showAddPermission = true"
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

            Add Permission
          </button>
        </div>

        <div
          v-if="permissionLoading"
          class="flex flex-1 items-center justify-center p-8 text-center text-[13px] text-slate-500"
        >
          Loading permissions...
        </div>

        <div
          v-else-if="permissionError"
          class="flex flex-1 items-center justify-center rounded-lg bg-red-50 p-8 text-center text-[13px] text-red-700"
        >
          {{ permissionError }}
        </div>

        <div
          v-else
          class="overflow-hidden rounded-[10px] border border-slate-200 bg-white shadow-[0_5px_20px_rgba(15,23,42,.05)]"
        >
          <div class="overflow-x-auto">
            <table class="w-full table-fixed border-collapse max-[900px]:min-w-[700px]">
              <colgroup>
                <col class="w-1/4" />
                <col class="w-1/4" />
                <col class="w-1/2" />
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
      Module
    </th>

    <th
      class="border-b border-[#B8D4EA] bg-[#DCEBF7] px-[18px] py-[13px] text-left text-[13px] font-bold text-[#1E4F8A]"
    >
      Permissions
    </th>
  </tr>
</thead>


              <tbody>
                <tr
                  v-for="(permission, index) in paginatedPermissions"
                  :key="permission.module"
                  class="transition hover:bg-[#EAF3FA]"
                  :class="index % 2 === 0 ? 'bg-white' : 'bg-[#EEF2F6]'"
                >
                  <td class="border-b border-slate-200 px-[18px] py-[13px] text-[13px]">
                    <div class="flex flex-wrap items-center gap-[7px]">
                      <button
                        type="button"
                        title="Edit Permission"
                        aria-label="Edit Permission"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-green-600 text-white transition hover:bg-green-700"
                        @click="openEditPermission(permission)"
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
                        title="Delete Permission"
                        aria-label="Delete Permission"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-red-600 text-white transition hover:bg-red-700"
                        @click="deleteSelectedPermission(permission)"
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

                  <td
                    class="break-words border-b border-slate-200 px-[18px] py-[13px] text-[13px] text-slate-700"
                  >
                    {{ permission.module }}
                  </td>

                  <td class="border-b border-slate-200 px-[18px] py-[13px] text-[13px]">
                    <div class="flex flex-wrap gap-2">
                      <span
                        v-for="action in permission.actions"
                        :key="action"
                        class="inline-flex rounded-md bg-[#DCEBF7] px-2.5 py-1 text-[11px] font-semibold capitalize text-[#1E4F8A]"
                      >
                        {{ action }}
                      </span>

                      <span
                        v-if="permission.actions.length === 0"
                        class="text-slate-400"
                      >
                        -
                      </span>
                    </div>
                  </td>
                </tr>

                <tr v-if="groupedPermissions.length === 0">
                  <td
                    colspan="3"
                    class="bg-white p-[30px] text-center text-[13px] text-slate-400"
                  >
                    No permissions found.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div
            v-if="groupedPermissions.length > 0"
            class="flex shrink-0 items-center justify-end gap-1.5 border-t border-slate-200 bg-white px-[18px] py-2.5 max-[600px]:flex-wrap"
          >
            <button
              type="button"
              class="inline-flex h-8 min-w-[34px] items-center justify-center rounded-md border border-slate-300 bg-white px-2 text-xs font-semibold text-slate-600 disabled:cursor-not-allowed disabled:opacity-45"
              :disabled="permissionPage === 1"
              @click="permissionPage--"
            >
              Previous
            </button>

            <button
              v-for="page in permissionTotalPages"
              :key="page"
              type="button"
              class="inline-flex h-8 min-w-[34px] items-center justify-center rounded-md border px-2 text-xs font-semibold"
              :class="
                permissionPage === page
                  ? 'border-[#1E4F8A] bg-[#1E4F8A] text-white'
                  : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50'
              "
              @click="permissionPage = page"
            >
              {{ page }}
            </button>

            <button
              type="button"
              class="inline-flex h-8 min-w-[34px] items-center justify-center rounded-md border border-slate-300 bg-white px-2 text-xs font-semibold text-slate-600 disabled:cursor-not-allowed disabled:opacity-45"
              :disabled="permissionPage === permissionTotalPages"
              @click="permissionPage++"
            >
              Next
            </button>
          </div>
        </div>
      </section>
    </div>
  </div>

  <AddUser
    v-if="showAddUser"
    @close="showAddUser = false"
    @created="fetchUsers"
  />

  <EditUser
    v-if="showEditUser && selectedUser"
    :user="selectedUser"
    @close="showEditUser = false"
    @updated="fetchUsers"
  />

  <AddRole
    v-if="showAddRole"
    @close="showAddRole = false"
    @created="fetchRoles"
  />

  <EditRole
    v-if="showEditRole && selectedRole"
    :role="selectedRole"
    @close="showEditRole = false"
    @updated="fetchRoles"
  />

  <AddPermission
    v-if="showAddPermission"
    @close="showAddPermission = false"
    @created="fetchPermissions"
  />

  <EditPermission
    v-if="showEditPermission && selectedPermission"
    :permission="selectedPermission"
    @close="showEditPermission = false"
    @updated="fetchPermissions"
  />

  <div
    v-if="showDeleteModal"
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/45 p-5 backdrop-blur-[2px]"
    @click.self="cancelDelete"
  >
    <div
      class="w-[420px] max-w-full overflow-hidden rounded-2xl bg-white shadow-[0_25px_70px_rgba(15,23,42,.25)]"
    >
      <div class="h-1.5 bg-red-600" />

      <div class="px-6 pb-6 pt-6">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-[10px] font-bold uppercase tracking-[.14em] text-red-600">
              Delete Confirmation
            </p>

            <h2 class="mt-1 text-xl font-semibold text-[#172033]">
              Delete this {{ deleteType }}?
            </h2>
          </div>

          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-5 w-5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="1.8"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-8 0h10"
              />
            </svg>
          </div>
        </div>

        <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
          <p class="text-[10px] font-bold uppercase tracking-[.12em] text-slate-400">
            Selected {{ deleteType }}
          </p>

          <p class="mt-2 text-sm font-semibold text-[#172033]">
            {{ deleteItemName }}
          </p>
        </div>

        <p class="mt-4 text-xs leading-5 text-slate-500">
          This action cannot be undone. Are you sure you want to continue?
        </p>

        <div class="mt-6 flex gap-2.5 max-[500px]:flex-col-reverse">
          <button
            type="button"
            class="flex-1 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="deleting"
            @click="cancelDelete"
          >
            Cancel
          </button>

          <button
            type="button"
            class="flex-1 rounded-lg bg-red-600 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="deleting"
            @click="confirmDelete"
          >
            {{ deleting ? 'Deleting...' : 'Delete' }}
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

import { useUserStore } from '../stores/userStore'
import { useToastStore } from '../../../stores/toast'

import AddUser from '../components/modal/user/add-user.vue'
import EditUser from '../components/modal/user/edit-user.vue'

import {
  deleteUser,
} from '../services/userService'

import {
  getRoles,
  deleteRole,
} from '../services/roleService'

import AddRole from '../components/modal/role/add-role.vue'
import EditRole from '../components/modal/role/edit-role.vue'

import {
  getPermissions,
  deletePermission,
} from '../services/permissionService'

import AddPermission from '../components/modal/permission/add-permission.vue'
import EditPermission from '../components/modal/permission/edit-permission.vue'

const toast = useToastStore()

const {
  users,
  loading,
  error,
  fetchUsers,
} = useUserStore()

const activeTab = ref('users')

const PAGE_SIZE_OPTIONS = [
  5,
  10,
  25,
  50,
  100,
]

const userPerPage = ref(5)
const userPage = ref(1)

const userTotalPages = computed(() => {
  return Math.max(
    1,
    Math.ceil(
      users.value.length /
      userPerPage.value
    )
  )
})

const paginatedUsers = computed(() => {
  const start =
    (userPage.value - 1) *
    userPerPage.value

  return users.value.slice(
    start,
    start + userPerPage.value
  )
})

const rolePerPage = ref(5)
const rolePage = ref(1)

const roles = ref([])
const roleLoading = ref(false)
const roleError = ref('')

const roleTotalPages = computed(() => {
  return Math.max(
    1,
    Math.ceil(
      roles.value.length /
      rolePerPage.value
    )
  )
})

const paginatedRoles = computed(() => {
  const start =
    (rolePage.value - 1) *
    rolePerPage.value

  return roles.value.slice(
    start,
    start + rolePerPage.value
  )
})

const fetchRoles = async () => {
  roleLoading.value = true
  roleError.value = ''

  try {
    const response = await getRoles()

    roles.value =
      response.data ??
      response

    if (
      rolePage.value >
      roleTotalPages.value
    ) {
      rolePage.value =
        roleTotalPages.value
    }
  } catch (err) {
    roleError.value =
      err.message ||
      'Failed to load roles.'
  } finally {
    roleLoading.value = false
  }
}

const permissionPerPage = ref(5)
const permissionPage = ref(1)

const permissions = ref([])
const permissionLoading = ref(false)
const permissionError = ref('')

const groupedPermissions = computed(() => {
  const groups = {}

  permissions.value.forEach(permission => {
    const moduleName =
      permission.module ||
      permission.name
        ?.split('.')
        .slice(0, -1)
        .join('.') ||
      'Unknown Module'

    if (!groups[moduleName]) {
      groups[moduleName] = {
        module: moduleName,
        actions: [],
        ids: [],
        items: [],
      }
    }

    const action =
      permission.name
        ?.split('.')
        .pop()
        ?.toLowerCase()

    if (
      action &&
      [
        'view',
        'create',
        'update',
        'delete',
      ].includes(action) &&
      !groups[moduleName].actions.includes(action)
    ) {
      groups[moduleName].actions.push(action)
    }

    groups[moduleName].ids.push(permission.id)
    groups[moduleName].items.push(permission)
  })

  const actionOrder = [
    'view',
    'create',
    'update',
    'delete',
  ]

  return Object.values(groups)
    .map(group => {
      group.actions.sort(
        (a, b) =>
          actionOrder.indexOf(a) -
          actionOrder.indexOf(b)
      )

      return group
    })
})

const permissionTotalPages = computed(() => {
  return Math.max(
    1,
    Math.ceil(
      groupedPermissions.value.length /
      permissionPerPage.value
    )
  )
})

const paginatedPermissions = computed(() => {
  const start =
    (permissionPage.value - 1) *
    permissionPerPage.value

  return groupedPermissions.value.slice(
    start,
    start + permissionPerPage.value
  )
})

const fetchPermissions = async () => {
  permissionLoading.value = true
  permissionError.value = ''

  try {
    const response = await getPermissions()

    permissions.value =
      response.data ??
      response

    if (
      permissionPage.value >
      permissionTotalPages.value
    ) {
      permissionPage.value =
        permissionTotalPages.value
    }
  } catch (err) {
    permissionError.value =
      err.message ||
      'Failed to load permissions.'
  } finally {
    permissionLoading.value = false
  }
}

const showAddUser = ref(false)
const showEditUser = ref(false)
const selectedUser = ref(null)

const openEditUser = user => {
  selectedUser.value = user
  showEditUser.value = true
}

const showAddRole = ref(false)
const showEditRole = ref(false)
const selectedRole = ref(null)

const openEditRole = role => {
  selectedRole.value = role
  showEditRole.value = true
}

const showAddPermission = ref(false)
const showEditPermission = ref(false)
const selectedPermission = ref(null)

const openEditPermission = permission => {
  selectedPermission.value = permission
  showEditPermission.value = true
}

const showDeleteModal = ref(false)
const deleteType = ref(null)
const dataToDelete = ref(null)
const deleting = ref(false)

const deleteItemName = computed(() => {
  if (!dataToDelete.value) {
    return ''
  }

  if (deleteType.value === 'permission') {
    return dataToDelete.value.module
  }

  return dataToDelete.value.name || '-'
})

const deleteSelectedUser = user => {
  deleteType.value = 'user'
  dataToDelete.value = user
  showDeleteModal.value = true
}

const deleteSelectedRole = role => {
  deleteType.value = 'role'
  dataToDelete.value = role
  showDeleteModal.value = true
}

const deleteSelectedPermission = permission => {
  deleteType.value = 'permission'
  dataToDelete.value = permission
  showDeleteModal.value = true
}

const cancelDelete = () => {
  if (deleting.value) {
    return
  }

  showDeleteModal.value = false
  deleteType.value = null
  dataToDelete.value = null
}

const confirmDelete = async () => {
  if (
    !dataToDelete.value ||
    deleting.value
  ) {
    return
  }

  deleting.value = true

  const item = dataToDelete.value
  const type = deleteType.value

  try {
    if (type === 'user') {
      await deleteUser(item.id)
      await fetchUsers()

      if (
        userPage.value >
        userTotalPages.value
      ) {
        userPage.value =
          userTotalPages.value
      }

      showDeleteModal.value = false

      toast.success(
        'User Deleted',
        `"${item.name}" has been deleted successfully.`
      )
    } else if (type === 'role') {
      await deleteRole(item.id)
      await fetchRoles()

      if (
        rolePage.value >
        roleTotalPages.value
      ) {
        rolePage.value =
          roleTotalPages.value
      }

      showDeleteModal.value = false

      toast.success(
        'Role Deleted',
        `"${item.name}" has been deleted successfully.`
      )
    } else if (type === 'permission') {
      const ids = item.ids || []

      await Promise.all(
        ids.map(id =>
          deletePermission(id)
        )
      )

      await fetchPermissions()

      if (
        permissionPage.value >
        permissionTotalPages.value
      ) {
        permissionPage.value =
          permissionTotalPages.value
      }

      showDeleteModal.value = false

      toast.success(
        'Permission Deleted',
        `"${item.module}" has been deleted successfully.`
      )
    }

    deleteType.value = null
    dataToDelete.value = null
  } catch (err) {
    toast.error(
      'Delete Failed',
      err.message ||
        'Failed to delete data.'
    )
  } finally {
    deleting.value = false
  }
}

onMounted(() => {
  fetchUsers()
  fetchRoles()
  fetchPermissions()
})
</script>
