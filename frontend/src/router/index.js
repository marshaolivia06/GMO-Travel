import { createRouter, createWebHistory } from 'vue-router'

import LoginView from '../views/LoginView.vue'
import MainLayout from '../layouts/MainLayout.vue'

import dashboardRoutes from '../modules/dashboard/router'
import annualLeaveRoutes from '../modules/annual-leave/router'
import travelOrderRoutes from '../modules/travel-order/router'
import userManagementRoutes from '../modules/user-management/router'

const routes = [
  {
    path: '/',
    redirect: '/login',
  },

  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: {
      title: 'Login',
    },
  },

  {
    path: '/',
    component: MainLayout,
    children: [
      ...dashboardRoutes,
      ...annualLeaveRoutes,
      ...travelOrderRoutes,
      ...userManagementRoutes,
    ],
  },

  {
    path: '/error/401',
    name: 'Unauthorized',
    component: () => import('../views/errors/401.vue'),
    meta: {
      title: '401 - Unauthorized',
    },
  },

  {
    path: '/error/403',
    name: 'Forbidden',
    component: () => import('../views/errors/403.vue'),
    meta: {
      title: '403 - Forbidden',
    },
  },

  {
    path: '/error/404',
    name: 'NotFound',
    component: () => import('../views/errors/404.vue'),
    meta: {
      title: '404 - Page Not Found',
    },
  },

  {
    path: '/error/422',
    name: 'UnprocessableEntity',
    component: () => import('../views/errors/422.vue'),
    meta: {
      title: '422 - Unprocessable Entity',
    },
  },

  {
    path: '/error/500',
    name: 'ServerError',
    component: () => import('../views/errors/500.vue'),
    meta: {
      title: '500 - Server Error',
    },
  },

  {
    path: '/:pathMatch(.*)*',
    name: 'NotFoundFallback',
    component: () => import('../views/errors/404.vue'),
    meta: {
      title: '404 - Page Not Found',
    },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.afterEach((to) => {
  document.title = to.meta.title || 'JMO Travel'
})

export default router
