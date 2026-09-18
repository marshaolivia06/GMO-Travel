import axios from 'axios'

export const http = axios.create({
  baseURL: 'http://127.0.0.1:8000/api',
  headers: { Accept: 'application/json' },
})

let interceptorSetup = false

export default function setupAxios() {
  if (interceptorSetup) return

  http.interceptors.request.use((config) => {
    const token = localStorage.getItem('token')
    if (token) config.headers.Authorization = `Bearer ${token}`
    return config
  })

  http.interceptors.response.use(
    (response) => response,
    (error) => {
      if (error.response?.status === 401) {
        localStorage.removeItem('token')
        localStorage.removeItem('user')
      }
      return Promise.reject(error)
    },
  )

  interceptorSetup = true
}
