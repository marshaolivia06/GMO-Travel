import axiosLib from 'axios'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import { router } from './router'

export const http = axiosLib.create({
  baseURL: `${import.meta.env.VITE_API_BASE_URL}/api`,
  headers: {
    Accept: 'application/json',
  },
  withCredentials: true,
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
})

// Mengambil cookie XSRF-TOKEN dari backend (wajib sebelum request yang butuh CSRF)
export const refreshCsrfCookie = () => axiosLib.get(`${import.meta.env.VITE_API_BASE_URL}/sanctum/csrf-cookie`, { withCredentials: true })

// Axios hanya melampirkan header CSRF otomatis untuk request same-origin.
// Frontend (:5173) dan API (:8000) beda port = lintas origin, jadi cookie dibaca manual.
const getXsrfToken = () => document.cookie
  .split('; ')
  .find(row => row.startsWith('XSRF-TOKEN='))
  ?.split('=')
  .slice(1)
  .join('=') ?? null

http.interceptors.request.use(config => {
  const token = localStorage.getItem('token')
  if (token && !config.headers.Authorization)
    config.headers.Authorization = `Bearer ${token}`

  const xsrf = getXsrfToken()

  if (xsrf && ['post', 'put', 'patch', 'delete'].includes(config.method))
    config.headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrf)

  return config
})

let handlingExpiry = false

http.interceptors.response.use(
  response => response,
  async error => {
    // CSRF token kedaluwarsa -> ambil cookie baru lalu ulangi request sekali
    // (berlaku juga untuk request silent, contoh: login)
    if (error.response?.status === 419 && error.config && !error.config.__csrfRetried) {
      error.config.__csrfRetried = true

      try {
        await refreshCsrfCookie()
      } catch {
        return Promise.reject(error)
      }

      return http.request(error.config)
    }

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

export default function (_app) {}
