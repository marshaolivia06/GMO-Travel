import { ref } from 'vue'
import { getUsers } from '../services/userService'

const users = ref([])
const loading = ref(false)
const error = ref('')

export function useUserStore() {
  const fetchUsers = async () => {
    loading.value = true
    error.value = ''

    try {
      users.value = await getUsers()
    } catch (err) {
      error.value = err.message
    } finally {
      loading.value = false
    }
  }

  return {
    users,
    loading,
    error,
    fetchUsers,
  }
}
