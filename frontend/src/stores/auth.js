import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToastStore } from './toast'

export const useAuthStore = defineStore('auth', () => {
  const router = useRouter()
  const toast = useToastStore()

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
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            email,
            password
          })
        }
      )

      const data = await response.json()
      console.log('LOGIN RESPONSE:', data)

      if (!response.ok) {
        return {
          success: false,
          message: data.message || 'Email atau password salah.'
        }
      }

      token.value = data.token
      user.value = data.user

      localStorage.setItem(
        'token',
        data.token
      )

      localStorage.setItem(
        'user',
        JSON.stringify(data.user)
      )

      const name = data.user.name

      toast.success(
        'Login Successfully',
        `Welcome back ${name}!`
      )

      await router.push('/dashboard')

      return {
        success: true
      }

    } catch (error) {
      return {
        success: false,
        message: 'Tidak dapat terhubung ke server.'
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
            'Accept': 'application/json',
            'Authorization': `Bearer ${token.value}`
          }
        }
      )

    } catch (error) {

    } finally {
      user.value = null
      token.value = null

      localStorage.removeItem('token')
      localStorage.removeItem('user')

      toast.success(
        'Logout Successfully',
        'You have been logged out successfully.'
      )

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
    isAuthenticated
  }
})