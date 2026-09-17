import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import swal from '../plugins/swal'

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
      const response = await fetch(
        'http://127.0.0.1:8000/api/login',
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
          },
          body: JSON.stringify({
            email,
            password,
          }),
        }
      )

      const data = await response.json()

      if (!response.ok) {
        return {
          success: false,
          message:
            data.message || 'Invalid email or password.',
        }
      }

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
        message: 'Unable to connect to the server.',
      }
    } finally {
      loading.value = false
    }
  }

  const logout = async () => {
    loading.value = true

    try {
      await fetch(
        'http://127.0.0.1:8000/api/logout',
        {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${token.value}`,
          },
        }
      )
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
    logout,
    isAuthenticated,
  }
})