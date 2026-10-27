import { defineStore } from 'pinia'
import { http as axios } from '../plugins/axios'
import { router } from '../plugins/router'
import { useToastStore } from './toast'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token'),
  }),

  getters: {
    isAuthenticated: state => !!state.user,
    roles: state => state.user?.roles ?? [],
    permissions: state => state.user?.permissions ?? [],
  },

  actions: {
    async login(email, password) {
      const { data } = await axios.post('/login', { email, password }, { silent: true })

      this.token = data.token
      localStorage.setItem('token', data.token)
      this.user = data.user
      useToastStore().success(`Login successful! Welcome back, ${data.user.name}.`)
    },

    async logout() {
      try {
        await axios.post('/logout')
        useToastStore().info('Logout Successfully')
      } finally {
        this.clearSession()
        router.push('/login')
      }
    },

    async fetchUser() {
      try {
        const { data } = await axios.get('/user', { silent: true })

        this.user = data
      } catch {
        this.user = null
      }

      if (!this.user)
        this.clearSession()
    },

    clearSession() {
      this.user = null
      this.token = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    },

    hasRole(role) {
      return this.roles.includes(role)
    },

    can(permission) {
      return this.permissions.includes(permission)
    },
  },
})
