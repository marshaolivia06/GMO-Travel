import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import swal from '../plugins/swal'
import { http } from '../plugins/axios'

export const useAuthStore = defineStore('auth', () => {
  const router = useRouter()

  const user = ref(
    JSON.parse(localStorage.getItem('user') || 'null')
  )

  const token = ref(
    localStorage.getItem('token') || null
  )

  const loading = ref(false)

  const login = async (email, password) => {
    loading.value = true

    try {
      const { data } = await http.post('/login', { email, password })

      token.value = data.token
      user.value = data.user

      localStorage.setItem('token', data.token)
      localStorage.setItem(
        'user',
        JSON.stringify(data.user)
      )

      await router.push('/dashboard')

      swal.success(
        'Login Successful',
        `Welcome back, ${data.user.name}!`
      )

      return {
        success: true,
      }
    } catch (error) {
      return {
        success: false,
        message:
          error.response?.data?.message ||
          'Unable to connect to the server.',
      }
    } finally {
      loading.value = false
    }
  }

  const fetchMe = async () => {
    try {
      const { data } = await http.get('/user')
      user.value = data
      localStorage.setItem('user', JSON.stringify(data))
    } catch (error) {
      user.value = null
      token.value = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    }
  }

  const logout = async () => {
    loading.value = true

    try {
      await http.post('/logout')
    } catch (error) {
    } finally {
      user.value = null
      token.value = null

      localStorage.removeItem('token')
      localStorage.removeItem('user')

      await router.push('/login')

      loading.value = false
    }
  }

  const isAuthenticated = () => {
    return !!token.value
  }

  return {
    user,
    token,
    loading,
    login,
    fetchMe,
    logout,
    isAuthenticated,
  }
})