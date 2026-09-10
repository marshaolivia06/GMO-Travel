<template>
  <div
    class="w-full px-0 py-2 text-[#172033] max-[900px]:px-4 max-[600px]:pb-4"
  >
  <section
    class="rounded-xl bg-gradient-to-br from-[#1E4F8A] via-[#2D659C] to-[#DCEBF7] px-[34px] py-[30px] text-white shadow-[0_10px_30px_rgba(30,79,138,0.14)] max-[600px]:px-6 max-[600px]:py-6"
    aria-labelledby="user-management-title"
  >
    <p
      class="mb-2 text-[11px] font-bold uppercase tracking-[0.14em] text-[#DCEBF7]"
    >
      GMO TRAVEL · ADMINISTRASI
    </p>

    <h1
      id="user-management-title"
      class="mb-2 text-[clamp(25px,3vw,34px)] font-bold tracking-[-0.02em]"
    >
      User Management
    </h1>

    <p
      class="mb-0 max-w-[590px] text-sm leading-[1.6] text-[#e5f2ff]"
    >
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
        @click="changeTab('users')"
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
        @click="changeTab('roles')"
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
        @click="changeTab('permissions')"
      >
        Permissions
      </button>
    </div>

    <div v-if="activeTab === 'users'" class="w-full">
      <section class="mt-[22px] w-full">
        <div
          class="mb-3 flex shrink-0 items-end justify-between gap-4 max-[600px]:flex-col max-[600px]:items-start"
        >
          <div class="w-full">
            <p class="mb-1 text-xs font-bold uppercase tracking-[.14em] text-[#1E4F8A]">
              Users
            </p>

            <h2 class="text-[22px] font-semibold">
              User List
            </h2>

            <div class="mt-2 flex flex-wrap items-center gap-2">
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

              <div class="relative ml-2 max-[600px]:ml-0">
                <input
                  v-model="userSearch"
                  type="text"
                  placeholder="Search users..."
                  class="w-[220px] rounded-md border border-slate-300 bg-white px-3 py-1.5 pr-8 text-xs text-slate-600 outline-none placeholder:text-slate-400 focus:border-[#1E4F8A] focus:ring-2 focus:ring-[#1E4F8A]/10 max-[600px]:w-full"
                  @input="userPage = 1"
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
            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-[7px] bg-green-600 px-[15px] py-2.5 text-[13px] font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-green-600 max-[600px]:w-full"
            :disabled="!hasPermission('user-management.create')"
            @click="addUser"
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
            <table class="w-full table-fixed border-collapse max-[900px]:min-w-[1050px]">
  <colgroup>
    <col class="w-[13%]" />
    <col class="w-[8%]" />
    <col class="w-[19%]" />
    <col class="w-[23%]" />
    <col class="w-[14%]" />
    <col class="w-[11.5%]" />
    <col class="w-[11.5%]" />
  </colgroup>
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
                    Email
                  </th>
                  <th class="border-b border-[#B8D4EA] bg-[#DCEBF7] px-[18px] py-[13px] text-left text-[13px] font-bold text-[#1E4F8A]">
                    Role
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
                        :disabled="!hasPermission('user-management.update')"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-green-600 text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-green-600"
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
                        :disabled="!hasPermission('user-management.delete')"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-red-600 text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-red-600"
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

                  <td class="border-b border-slate-200 px-[18px] py-[13px] text-[13px] font-semibold text-slate-600">
                    {{ user.id }}
                  </td>

                  <td class="break-words border-b border-slate-200 px-[18px] py-[13px] text-[13px] leading-6 text-slate-700">
                    {{ user.name }}
                  </td>

                  <td class="break-words border-b border-slate-200 px-[18px] py-[13px] text-[13px] leading-6 text-slate-700">
                    {{ user.email }}
                  </td>

                  <td class="border-b border-slate-200 px-[18px] py-[13px] text-[13px] text-slate-700">
                    <div class="flex flex-wrap gap-1.5">
                      <span
                        v-for="role in user.roles || []"
                        :key="role.id || role.name"
                        class="inline-flex rounded-md bg-[#DCFCE7] px-2.5 py-1 text-[12px] font-semibold text-[#16A34A]"
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

                  <td class="break-words border-b border-slate-200 px-[18px] py-[13px] text-[12px] leading-5 text-slate-600">
                    {{ formatDate(user.created_at) }}
                  </td>

                  <td class="break-words border-b border-slate-200 px-[18px] py-[13px] text-[12px] leading-5 text-slate-600">
                    {{ formatDate(user.updated_at) }}
                  </td>
                </tr>

                <tr v-if="filteredUsers.length === 0">
                  <td
                    colspan="7"
                    class="bg-white p-[30px] text-center text-[13px] text-slate-400"
                  >
                    No users found.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div
            v-if="filteredUsers.length > 0"
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
          <div class="w-full">
            <p class="mb-1 text-xs font-bold uppercase tracking-[.14em] text-[#1E4F8A]">
              Roles
            </p>

            <h2 class="text-[22px] font-semibold">
              Role List
            </h2>

            <div class="mt-2 flex flex-wrap items-center gap-2">
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

              <div class="relative ml-2 max-[600px]:ml-0">
                <input
                  v-model="roleSearch"
                  type="text"
                  placeholder="Search roles..."
                  class="w-[220px] rounded-md border border-slate-300 bg-white px-3 py-1.5 pr-8 text-xs text-slate-600 outline-none placeholder:text-slate-400 focus:border-[#1E4F8A] focus:ring-2 focus:ring-[#1E4F8A]/10 max-[600px]:w-full"
                  @input="rolePage = 1"
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
            :disabled="!hasPermission('user-management.create')"
            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-[7px] bg-green-600 px-[15px] py-2.5 text-[13px] font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-green-600 max-[600px]:w-full"
            @click="addRole"
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
            <table class="w-full table-fixed border-collapse max-[900px]:min-w-[950px]">
              <colgroup>
  <col class="w-[18%]" />
  <col class="w-[8%]" />
  <col class="w-[30%]" />
  <col class="w-[22%]" />
  <col class="w-[22%]" />
</colgroup>

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
                    Created 
                  </th>
                  <th class="border-b border-[#B8D4EA] bg-[#DCEBF7] px-[18px] py-[13px] text-left text-[13px] font-bold text-[#1E4F8A]">
                    Updated 
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
                        :disabled="!hasPermission('user-management.update')"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-green-600 text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-green-600"
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
                        :disabled="!hasPermission('user-management.delete')"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-red-600 text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-red-600"
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

                  <td class="border-b border-slate-200 px-[18px] py-[13px] text-[13px] font-semibold text-slate-600">
                    {{ role.id }}
                  </td>

                  <td class="break-words border-b border-slate-200 px-[18px] py-[13px] text-[13px] text-slate-700">
                    {{ role.name }}
                  </td>

                  <td class="break-words border-b border-slate-200 px-[18px] py-[13px] text-[12px] leading-5 text-slate-600">
                    {{ formatDate(role.created_at) }}
                  </td>

                  <td class="break-words border-b border-slate-200 px-[18px] py-[13px] text-[12px] leading-5 text-slate-600">
                    {{ formatDate(role.updated_at) }}
                  </td>
                </tr>

                <tr v-if="filteredRoles.length === 0">
                  <td
                    colspan="6"
                    class="bg-white p-[30px] text-center text-[13px] text-slate-400"
                  >
                    No roles found.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div
            v-if="filteredRoles.length > 0"
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
          <div class="w-full">
            <p class="mb-1 text-xs font-bold uppercase tracking-[.14em] text-[#1E4F8A]">
              Permissions
            </p>

            <h2 class="text-[22px] font-semibold">
              Permission List
            </h2>

            <div class="mt-2 flex flex-wrap items-center gap-2">
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

              <div class="relative ml-2 max-[600px]:ml-0">
                <input
                  v-model="permissionSearch"
                  type="text"
                  placeholder="Search permissions..."
                  class="w-[220px] rounded-md border border-slate-300 bg-white px-3 py-1.5 pr-8 text-xs text-slate-600 outline-none placeholder:text-slate-400 focus:border-[#1E4F8A] focus:ring-2 focus:ring-[#1E4F8A]/10 max-[600px]:w-full"
                  @input="permissionPage = 1"
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
            :disabled="!hasPermission('user-management.create')"
            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-[7px] bg-green-600 px-[15px] py-2.5 text-[13px] font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-green-600 max-[600px]:w-full"
            @click="addPermission"
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
            <table class="w-full table-fixed border-collapse max-[900px]:min-w-[1050px]">
              <colgroup>
                <col class="w-[15%]" />
                <col class="w-[10%]" />
                <col class="w-[20%]" />
                <col class="w-[25%]" />
                <col class="w-[15%]" />
                <col class="w-[15%]" />
              </colgroup>

              <thead>
                <tr>
                  <th class="border-b border-[#B8D4EA] bg-[#DCEBF7] px-[18px] py-[13px] text-left text-[13px] font-bold text-[#1E4F8A]">
                    Actions
                  </th>
                  <th class="border-b border-[#B8D4EA] bg-[#DCEBF7] px-[18px] py-[13px] text-left text-[13px] font-bold text-[#1E4F8A]">
                    ID
                  </th>
                  <th class="border-b border-[#B8D4EA] bg-[#DCEBF7] px-[18px] py-[13px] text-left text-[13px] font-bold text-[#1E4F8A]">
                    Module
                  </th>
                  <th class="border-b border-[#B8D4EA] bg-[#DCEBF7] px-[18px] py-[13px] text-left text-[13px] font-bold text-[#1E4F8A]">
                    Permissions
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
                        :disabled="!hasPermission('user-management.update')"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-green-600 text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-green-600"
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
                        :disabled="!hasPermission('user-management.delete')"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-red-600 text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-red-600"
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

                  <td class="border-b border-slate-200 px-[18px] py-[13px] text-[13px] font-semibold text-slate-600">
                    {{ permission.items?.[0]?.id || '-' }}
                  </td>

                  <td class="break-words border-b border-slate-200 px-[18px] py-[13px] text-[13px] text-slate-700">
                    {{ permission.module }}
                  </td>

                  <td class="border-b border-slate-200 px-[18px] py-[13px] text-[13px]">
                    <div class="flex flex-wrap gap-2">
                      <span
                        v-for="action in permission.actions"
                        :key="action"
                        class="inline-flex rounded-md px-2.5 py-1 text-[11px] font-semibold capitalize"
                        :class="{
  'bg-[#E0EDFF] text-[#2563EB]': action?.toLowerCase() === 'view',
  'bg-[#DCFCE7] text-[#16A34A]': action?.toLowerCase() === 'create',
  'bg-[#FEF3C7] text-[#D97706]': action?.toLowerCase() === 'update',
  'bg-[#FEE2E2] text-[#DC2626]': action?.toLowerCase() === 'delete',
  'bg-[#F3E8FF] text-[#9333EA]': action?.toLowerCase() === 'approve',
}"
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

                  <td class="break-words border-b border-slate-200 px-[18px] py-[13px] text-[12px] leading-5 text-slate-600">
                    {{ formatDate(permission.items?.[0]?.created_at) }}
                  </td>

                  <td class="break-words border-b border-slate-200 px-[18px] py-[13px] text-[12px] leading-5 text-slate-600">
                    {{ formatDate(permission.items?.[0]?.updated_at) }}
                  </td>
                </tr>

                <tr v-if="filteredPermissions.length === 0">
                  <td
                    colspan="6"
                    class="bg-white p-[30px] text-center text-[13px] text-slate-400"
                  >
                    No permissions found.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div
            v-if="filteredPermissions.length > 0"
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
  class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/40 p-5"
  @click.self="cancelDelete"
>
  <div
    class="w-[380px] max-w-full rounded-xl bg-white px-6 py-7 text-center shadow-[0_15px_40px_rgba(15,23,42,0.18)]"
  >
    <!-- Question Icon -->
    <div
      class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-[#EAF3FA] text-[#1E4F8A]"
    >
      <span class="text-2xl font-bold">
        ?
      </span>
    </div>

    <!-- Message -->
    <p class="mt-5 text-[15px] font-medium leading-6 text-[#172033]">
      Are you sure you want to delete this {{ deleteType }}?
    </p>

    <!-- Buttons -->
    <div class="mt-6 flex justify-center gap-2.5">
      <button
  type="button"
  class="min-w-[90px] rounded-lg bg-slate-100 px-5 py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-60"
  :disabled="deleting"
  @click="cancelDelete"
>
  Cancel
</button>

      <button
        type="button"
        class="min-w-[90px] rounded-lg bg-red-600 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="deleting"
        @click="confirmDelete"
      >
        {{ deleting ? 'Deleting...' : 'Yes' }}
      </button>
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

import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../../stores/auth'
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

const toast = useToastStore()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const addUser = () => {
  showAddUser.value = true
}

const hasPermission = permission => {
  return (authStore.user?.permissions || [])
    .some(item => item === permission)
}

const {
  users,
  loading,
  error,
  fetchUsers,
} = useUserStore()

const activeTab = ref(
  ['users', 'roles', 'permissions'].includes(route.query.tab)
    ? route.query.tab
    : 'users'
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

const PAGE_SIZE_OPTIONS = [
  5,
  10,
  25,
  50,
  100,
]

const userSearch = ref('')
const userPerPage = ref(5)
const userPage = ref(1)

const filteredUsers = computed(() => {
  const keyword = userSearch.value
    .trim()
    .toLowerCase()

  if (!keyword) {
    return users.value
  }

  return users.value.filter(user => {
    const name = user.name?.toLowerCase() || ''
    const email = user.email?.toLowerCase() || ''
    const roles = (user.roles || [])
      .map(role => role.name?.toLowerCase() || '')
      .join(' ')

    return (
      name.includes(keyword) ||
      email.includes(keyword) ||
      roles.includes(keyword)
    )
  })
})

const userTotalPages = computed(() => {
  return Math.max(
    1,
    Math.ceil(
      filteredUsers.value.length /
      userPerPage.value
    )
  )
})

const paginatedUsers = computed(() => {
  const start =
    (userPage.value - 1) *
    userPerPage.value

  return filteredUsers.value.slice(
    start,
    start + userPerPage.value
  )
})

const roleSearch = ref('')
const rolePerPage = ref(5)
const rolePage = ref(1)

const roles = ref([])
const roleLoading = ref(false)
const roleError = ref('')

const filteredRoles = computed(() => {
  const keyword = roleSearch.value
    .trim()
    .toLowerCase()

  if (!keyword) {
    return roles.value
  }

  return roles.value.filter(role => {
    const name =
      role.name?.toLowerCase() || ''

    const permissions =
      (role.permissions || [])
        .map(permission =>
          permission.name?.toLowerCase() || ''
        )
        .join(' ')

    return (
      name.includes(keyword) ||
      permissions.includes(keyword)
    )
  })
})

const roleTotalPages = computed(() => {
  return Math.max(
    1,
    Math.ceil(
      filteredRoles.value.length /
      rolePerPage.value
    )
  )
})

const paginatedRoles = computed(() => {
  const start =
    (rolePage.value - 1) *
    rolePerPage.value

  return filteredRoles.value.slice(
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

const permissionSearch = ref('')
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
    'approve',
  ].includes(action) &&
  !groups[moduleName].actions.includes(action)
)
{ groups[moduleName].actions.push(action)
    }

    groups[moduleName].ids.push(permission.id)
    groups[moduleName].items.push(permission)
  })

  const actionOrder = [
  'view',
  'create',
  'update',
  'delete',
  'approve',
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

const filteredPermissions = computed(() => {
  const keyword = permissionSearch.value
    .trim()
    .toLowerCase()

  if (!keyword) {
    return groupedPermissions.value
  }

  return groupedPermissions.value.filter(permission => {
    const moduleName =
      permission.module?.toLowerCase() || ''

    const actions =
      (permission.actions || [])
        .join(' ')
        .toLowerCase()

    return (
      moduleName.includes(keyword) ||
      actions.includes(keyword)
    )
  })
})

const permissionTotalPages = computed(() => {
  return Math.max(
    1,
    Math.ceil(
      filteredPermissions.value.length /
      permissionPerPage.value
    )
  )
})

const paginatedPermissions = computed(() => {
  const start =
    (permissionPage.value - 1) *
    permissionPerPage.value

  return filteredPermissions.value.slice(
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

const addRole = () => {
  showAddRole.value = true
}

const openEditRole = role => {
  selectedRole.value = role
  showEditRole.value = true
}

const showAddPermission = ref(false)
const showEditPermission = ref(false)
const selectedPermission = ref(null)

const openEditPermission = permission => {
  selectedPermission.value = {
    module: permission.module,
    actions: [...(permission.actions || [])],
    ids: [...(permission.ids || [])],
    items: [...(permission.items || [])],
  }

  showEditPermission.value = true
}

const addPermission = () => {
  showAddPermission.value = true
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
    if (err.status === 403) {
      router.push('/error/403')
      return
    }

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