import { ref } from 'vue'
import {
  getRoles,
  createRole,
  updateRole,
  deleteRole,
} from '../services/roleService'

export const useRoleStore = () => {
  const roles = ref([])
  const loading = ref(false)
  const error = ref(null)

  const fetchRoles = async () => {
    loading.value = true
    error.value = null

    try {
      const response = await getRoles()

      roles.value = response.data ?? response
    } catch (err) {
      error.value = err.message
    } finally {
      loading.value = false
    }
  }

  const addRole = async (role) => {
    try {
      await createRole(role)
      await fetchRoles()
    } catch (err) {
      throw err
    }
  }

  const editRole = async (id, role) => {
    try {
      await updateRole(id, role)
      await fetchRoles()
    } catch (err) {
      throw err
    }
  }

  const removeRole = async (id) => {
    try {
      await deleteRole(id)
      await fetchRoles()
    } catch (err) {
      throw err
    }
  }

  return {
    roles,
    loading,
    error,
    fetchRoles,
    addRole,
    editRole,
    removeRole,
  }
}
