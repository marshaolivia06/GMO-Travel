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
    router.beforeEach(async (to, from, next) => {
      const auth = useAuthStore()

      if (!auth.user && auth.token)
        await auth.fetchUser()

      if (to.meta.requiresAuth && !auth.isAuthenticated)
        return next({ name: 'login' })

      if (to.meta.guestOnly && auth.isAuthenticated)
        return next('/')

      if (to.meta.permission && !auth.can(to.meta.permission))
        return next({ name: 'Forbidden' })

      next()
    })

    router.afterEach(to => {
      document.title = to.meta.title ? `${to.meta.title} - ${APP_TITLE}` : APP_TITLE
    })

    guardInstalled = true
  }
}

export { router }
