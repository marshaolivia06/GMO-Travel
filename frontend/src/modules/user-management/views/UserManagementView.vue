<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useAuthStore } from '../../../stores/auth'
import { useUserStore } from '../stores/userStore'
import swal from '../../../plugins/swal'

import AddUser from '../components/modal/user/add-user.vue'
import EditUser from '../components/modal/user/edit-user.vue'
import AddRole from '../components/modal/role/add-role.vue'
import EditRole from '../components/modal/role/edit-role.vue'
import AddPermission from '../components/modal/permission/add-permission.vue'
import EditPermission from '../components/modal/permission/edit-permission.vue'

import { deleteUser } from '../services/userService'
import { getRoles, deleteRole } from '../services/roleService'
import { getPermissions, deletePermission } from '../services/permissionService'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { users, loading, error, fetchUsers } = useUserStore()

const PERMISSION_ACTION_ORDER = ['view', 'create', 'update', 'delete', 'approve']

const formatDate = date => {
  if (!date) return '-'

  return new Intl.DateTimeFormat('sv-SE', {
    timeZone: 'Asia/Jakarta',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).format(new Date(date))
}

const hasPermission = permission =>
  (authStore.user?.permissions || []).includes(permission)

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

// USERS
const userSearch = ref('')
const userPerPage = ref(5)

const filteredUsers = computed(() => {
  const keyword = userSearch.value.trim().toLowerCase()

  if (!keyword) return users.value

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

// ROLES
const roleSearch = ref('')
const rolePerPage = ref(5)
const roles = ref([])
const roleLoading = ref(false)
const roleError = ref('')

const filteredRoles = computed(() => {
  const keyword = roleSearch.value.trim().toLowerCase()

  if (!keyword) return roles.value

  return roles.value.filter(role => {
    const name = role.name?.toLowerCase() || ''
    const permissions = (role.permissions || [])
      .map(permission => permission.name?.toLowerCase() || '')
      .join(' ')

    return (
      name.includes(keyword) ||
      permissions.includes(keyword)
    )
  })
})

const fetchRoles = async () => {
  roleLoading.value = true
  roleError.value = ''

  try {
    const response = await getRoles()
    roles.value = response.data ?? response
  } catch (err) {
    roleError.value = err.message || 'Failed to load roles.'
  } finally {
    roleLoading.value = false
  }
}

// PERMISSIONS
const permissionSearch = ref('')
const permissionPerPage = ref(5)
const permissions = ref([])
const permissionLoading = ref(false)
const permissionError = ref('')

const groupedPermissions = computed(() => {
  const groups = {}

  permissions.value.forEach(permission => {
    const moduleName =
      permission.module ||
      permission.name?.split('.').slice(0, -1).join('.') ||
      'Unknown Module'

    if (!groups[moduleName]) {
      groups[moduleName] = {
        module: moduleName,
        actions: [],
        ids: [],
        items: [],
      }
    }

    const action = permission.name?.split('.').pop()?.toLowerCase()

    if (
      action &&
      PERMISSION_ACTION_ORDER.includes(action) &&
      !groups[moduleName].actions.includes(action)
    ) {
      groups[moduleName].actions.push(action)
    }

    groups[moduleName].ids.push(permission.id)
    groups[moduleName].items.push(permission)
  })

  return Object.values(groups).map(group => {
    group.actions.sort(
      (a, b) =>
        PERMISSION_ACTION_ORDER.indexOf(a) -
        PERMISSION_ACTION_ORDER.indexOf(b)
    )

    return group
  })
})

const filteredPermissions = computed(() => {
  const keyword = permissionSearch.value.trim().toLowerCase()

  if (!keyword) return groupedPermissions.value

  return groupedPermissions.value.filter(permission => {
    const moduleName = permission.module?.toLowerCase() || ''
    const actions = permission.actions?.join(' ').toLowerCase() || ''

    return (
      moduleName.includes(keyword) ||
      actions.includes(keyword)
    )
  })
})

const fetchPermissions = async () => {
  permissionLoading.value = true
  permissionError.value = ''

  try {
    const response = await getPermissions()
    permissions.value = response.data ?? response
  } catch (err) {
    permissionError.value =
      err.message || 'Failed to load permissions.'
  } finally {
    permissionLoading.value = false
  }
}

// USER MODALS
const showAddUser = ref(false)
const showEditUser = ref(false)
const selectedUser = ref(null)

const addUser = () => {
  showAddUser.value = true
}

const openEditUser = user => {
  selectedUser.value = user
  showEditUser.value = true
}

// ROLE MODALS
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

// PERMISSION MODALS
const showAddPermission = ref(false)
const showEditPermission = ref(false)
const selectedPermission = ref(null)

const addPermission = () => {
  showAddPermission.value = true
}

const openEditPermission = permission => {
  selectedPermission.value = {
    module: permission.module,
    actions: [...(permission.actions || [])],
    ids: [...(permission.ids || [])],
    items: [...(permission.items || [])],
  }

  showEditPermission.value = true
}

// DELETE USER
const deleteSelectedUser = async user => {
  const result = await swal.confirm(
    'Are you sure you want to delete this user?',
    'delete'
  )

  if (!result.isConfirmed) return

  try {
    await deleteUser(user.id)
    await fetchUsers()

    await swal.success(
      'User Deleted',
      `"${user.name}" has been deleted successfully.`
    )
  } catch (err) {
    if (err.status === 403) {
      router.push('/error/403')
      return
    }

    await swal.error(
      'Delete Failed',
      err.message || 'Failed to delete user.'
    )
  }
}

// DELETE ROLE
const deleteSelectedRole = async role => {
  const result = await swal.confirm(
    'Are you sure you want to delete this role?',
    'delete'
  )

  if (!result.isConfirmed) return

  try {
    await deleteRole(role.id)
    await fetchRoles()

    await swal.success(
      'Role Deleted',
      `"${role.name}" has been deleted successfully.`
    )
  } catch (err) {
    if (err.status === 403) {
      router.push('/error/403')
      return
    }

    await swal.error(
      'Delete Failed',
      err.message || 'Failed to delete role.'
    )
  }
}

// DELETE PERMISSION
const deleteSelectedPermission = async permission => {
  const result = await swal.confirm(
    'Are you sure you want to delete this permission?',
    'delete'
  )

  if (!result.isConfirmed) return

  try {
    await Promise.all(
      (permission.ids || []).map(id => deletePermission(id))
    )

    await fetchPermissions()

    await swal.success(
      'Permission Deleted',
      `"${permission.module}" has been deleted successfully.`
    )
  } catch (err) {
    if (err.status === 403) {
      router.push('/error/403')
      return
    }

    await swal.error(
      'Delete Failed',
      err.message || 'Failed to delete permission.'
    )
  }
}

onMounted(() => {
  fetchUsers()
  fetchRoles()
  fetchPermissions()
})
</script>

<template>
  <VCard rounded="lg" elevation="2">
    <VTabs :model-value="activeTab" color="primary" class="px-2" @update:model-value="changeTab">
      <VTab value="users" prepend-icon="ri-user-line">Users</VTab>
      <VTab value="roles" prepend-icon="ri-shield-user-line">Roles</VTab>
      <VTab value="permissions" prepend-icon="ri-key-2-line">Permissions</VTab>
    </VTabs>

    <VDivider />

    <VWindow :model-value="activeTab">
      <VWindowItem value="users">
        <VCardText>
          <VAlert v-if="error" type="error" rounded="lg" class="mb-4">{{ error }}</VAlert>

          <VDataTable :headers="[
  { title: 'Actions', key: 'actions', sortable: false, width: 100 },
  { title: 'ID', key: 'id', width: 80 },
  { title: 'Name', key: 'name' },
  { title: 'Email', key: 'email' },
  { title: 'Role', key: 'role' },
  { title: 'Created', key: 'created_at' },
  { title: 'Updated', key: 'updated_at' },
]" :items="filteredUsers" :loading="loading" :items-per-page="userPerPage" item-value="id" density="default" hover @update:items-per-page="value => userPerPage = value">
  <template #top>
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px; flex-wrap: wrap">
      <VBtn color="success" prepend-icon="ri-add-line" style="flex-shrink: 0" :disabled="!hasPermission('user-management.create')" @click="addUser">Add User</VBtn>
      <VTextField v-model="userSearch" prepend-inner-icon="ri-search-line" placeholder="Search users..." single-line clearable hide-details density="compact" style="width: 220px !important; flex: 0 0 220px" />

    </div>
    <VDivider />
  </template>

  <template #headers="{ columns }">
    <tr>
      <th v-for="column in columns" :key="column.key" class="bg-grey-lighten-3">{{ column.title }}</th>
    </tr>
  </template>

  <template #item.role="{ item }">
    <div class="d-flex flex-wrap ga-1">
      <VChip v-for="role in item.roles || []" :key="role.id || role.name" color="success" size="small" variant="tonal">{{ role.name }}</VChip>
      <span v-if="!item.roles?.length">-</span>
    </div>
  </template>

  <template #item.created_at="{ item }">{{ formatDate(item.created_at) }}</template>
  <template #item.updated_at="{ item }">{{ formatDate(item.updated_at) }}</template>

  <template #item.actions="{ item }">
    <div class="d-flex align-center ga-1 flex-nowrap">
      <VBtn icon="ri-edit-line" size="small" variant="text" color="success" :disabled="!hasPermission('user-management.update')" @click="openEditUser(item)" />
      <VBtn icon="ri-delete-bin-line" size="small" variant="text" color="error" :disabled="!hasPermission('user-management.delete')" @click="deleteSelectedUser(item)" />
    </div>
  </template>
</VDataTable>
        </VCardText>
      </VWindowItem>

      <VWindowItem value="roles">
        <VCardText>
          <VAlert v-if="roleError" type="error" rounded="lg" class="mb-4">{{ roleError }}</VAlert>

          <VDataTable :headers="[
  { title: 'Actions', key: 'actions', sortable: false, width: 100 },
  { title: 'ID', key: 'id', width: 80 },
  { title: 'Name', key: 'name' },
  { title: 'Created', key: 'created_at' },
  { title: 'Updated', key: 'updated_at' },
]" :items="filteredRoles" :loading="roleLoading" :items-per-page="rolePerPage" item-value="id" density="default" hover @update:items-per-page="value => rolePerPage = value">
  <template #top>
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px; flex-wrap: wrap">
      <VBtn color="success" prepend-icon="ri-add-line" style="flex-shrink: 0" :disabled="!hasPermission('user-management.create')" @click="addRole">Add Role</VBtn>
      <VTextField v-model="roleSearch" prepend-inner-icon="ri-search-line" placeholder="Search role..." single-line clearable hide-details density="compact" style="width: 220px !important; flex: 0 0 220px" />
    </div>
    <VDivider />
  </template>

  <template #headers="{ columns }">
    <tr>
      <th v-for="column in columns" :key="column.key" class="bg-grey-lighten-3">{{ column.title }}</th>
    </tr>
  </template>

  <template #item.created_at="{ item }">{{ formatDate(item.created_at) }}</template>
  <template #item.updated_at="{ item }">{{ formatDate(item.updated_at) }}</template>

  <template #item.actions="{ item }">
    <div class="d-flex align-center ga-1 flex-nowrap">
      <VBtn icon="ri-edit-line" size="small" variant="text" color="success" :disabled="!hasPermission('user-management.update')" @click="openEditRole(item)" />
      <VBtn icon="ri-delete-bin-line" size="small" variant="text" color="error" :disabled="!hasPermission('user-management.delete')" @click="deleteSelectedRole(item)" />
    </div>
  </template>
</VDataTable>
        </VCardText>
      </VWindowItem>

      <VWindowItem value="permissions">
        <VCardText>
          <VAlert v-if="permissionError" type="error" rounded="lg" class="mb-4">{{ permissionError }}</VAlert>

          <VDataTable :headers="[
  { title: 'Actions', key: 'actions', sortable: false, width: 100 },
  { title: 'ID', key: 'id', width: 80 },
  { title: 'Module', key: 'module' },
  { title: 'Permissions', key: 'permissions' },
  { title: 'Created', key: 'created_at' },
  { title: 'Updated', key: 'updated_at' },
]" :items="filteredPermissions" :loading="permissionLoading" :items-per-page="permissionPerPage" item-value="module" density="default" hover @update:items-per-page="value => permissionPerPage = value">
  <template #top>
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px; flex-wrap: wrap">
      <VBtn color="success" prepend-icon="ri-add-line" style="flex-shrink: 0" :disabled="!hasPermission('user-management.create')" @click="addPermission">Add Permission</VBtn>
      <VTextField v-model="permissionSearch" prepend-inner-icon="ri-search-line" placeholder="Search permission..." single-line clearable hide-details density="compact" style="width: 220px !important; flex: 0 0 220px" />
    </div>
    <VDivider />
  </template>

  <template #headers="{ columns }">
    <tr>
      <th v-for="column in columns" :key="column.key" class="bg-grey-lighten-3">{{ column.title }}</th>
    </tr>
  </template>

  <template #item.id="{ item }">{{ item.items?.[0]?.id || '-' }}</template>

  <template #item.permissions="{ item }">
    <div class="d-flex flex-wrap ga-1">
      <VChip v-for="action in item.actions" :key="action" :color="action === 'view' ? 'primary' : action === 'create' ? 'success' : action === 'update' ? 'warning' : action === 'delete' ? 'error' : 'purple'" size="small" variant="tonal">{{ action }}</VChip>
      <span v-if="!item.actions?.length">-</span>
    </div>
  </template>

  <template #item.created_at="{ item }">{{ formatDate(item.items?.[0]?.created_at) }}</template>
  <template #item.updated_at="{ item }">{{ formatDate(item.items?.[0]?.updated_at) }}</template>

  <template #item.actions="{ item }">
    <div class="d-flex align-center ga-1 flex-nowrap">
      <VBtn icon="ri-edit-line" size="small" variant="text" color="success" :disabled="!hasPermission('user-management.update')" @click="openEditPermission(item)" />
      <VBtn icon="ri-delete-bin-line" size="small" variant="text" color="error" :disabled="!hasPermission('user-management.delete')" @click="deleteSelectedPermission(item)" />
    </div>
  </template>
</VDataTable>
        </VCardText>
      </VWindowItem>
    </VWindow>
  </VCard>

  <AddUser v-if="showAddUser" @close="showAddUser = false" @created="fetchUsers" />
  <EditUser v-if="showEditUser && selectedUser" :user="selectedUser" @close="showEditUser = false" @updated="fetchUsers" />
  <AddRole v-if="showAddRole" @close="showAddRole = false" @created="fetchRoles" />
  <EditRole v-if="showEditRole && selectedRole" :role="selectedRole" @close="showEditRole = false" @updated="fetchRoles" />
  <AddPermission v-if="showAddPermission" @close="showAddPermission = false" @created="fetchPermissions" />
  <EditPermission v-if="showEditPermission && selectedPermission" :permission="selectedPermission" @close="showEditPermission = false" @updated="fetchPermissions" />
</template>
