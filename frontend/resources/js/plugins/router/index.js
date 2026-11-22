import { createRouter, createWebHistory } from 'vue-router'
import routes from './routes'
import { useAuthStore } from '../../stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

const APP_TITLE = 'GMO Travel'

let guardInstalled = false

export default function (app) {
  app.use(router)

  if (!guardInstalled) {
    router.beforeEach(async to => {
      const auth = useAuthStore()

      if (!auth.user && auth.token)
        await auth.fetchUser()

      if (to.meta.requiresAuth && !auth.isAuthenticated)
        return { name: 'login' }

      if (to.meta.guestOnly && auth.isAuthenticated)
        return '/'

      if (to.meta.permission && !auth.can(to.meta.permission))
        return { name: 'Forbidden' }

      if (to.meta.check && !to.meta.check(auth))
        return { name: 'Forbidden' }

      return true
    })

    router.afterEach(to => {
      document.title = to.meta.title ? `${to.meta.title} - ${APP_TITLE}` : APP_TITLE
    })

    guardInstalled = true
  }
}

export { router }
