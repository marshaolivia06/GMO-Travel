import axiosLib from 'axios'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import { router } from './router'

export const http = axiosLib.create({
  baseURL: `${import.meta.env.VITE_API_BASE_URL}/api`,
  headers: {
    Accept: 'application/json',
  },
})

http.interceptors.request.use(config => {
  const token = localStorage.getItem('token')
  if (token && !config.headers.Authorization)
    config.headers.Authorization = `Bearer ${token}`

  return config
})

// De-dupe flag: prevent multiple concurrent 401 responses from firing toast+redirect multiple times
let handlingExpiry = false

http.interceptors.response.use(
  response => response,
  error => {
    if (error.config?.silent)
      return Promise.reject(error)

    const toast = useToastStore()
    const auth = useAuthStore()
    const status = error.response?.status
    const serverMessage = error.response?.data?.message

    if (status === 401 && !handlingExpiry) {
      const wasAuthenticated = auth.isAuthenticated

      handlingExpiry = true
      auth.clearSession()
      if (wasAuthenticated) {
        router.push('/login')
        toast.warning('Session expired. Please log in again.')
      }
      setTimeout(() => { handlingExpiry = false }, 3000)
    } else if (status === 403) {
      toast.error(serverMessage || 'You do not have access to this resource.')
    } else if (status === 404) {
      toast.error(serverMessage || 'Data not found.')
    } else if (status === 422) {
      // Validation messages are shown by each form
    } else if (status === 429) {
      toast.error('Too many requests. Please wait a moment and try again.')
    } else if (status === 500) {
      toast.error('Server error. Please try again later.')
    } else if (!error.response) {
      toast.error('Unable to connect to the server. Check your internet connection.')
    }

    return Promise.reject(error)
  },
)

// Plugin function kept for registerPlugins compatibility.
// Interceptors already set up at module level above.
export default function (_app) {}
