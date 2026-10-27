import BlankLayout from '../../layouts/blank.vue'
import DefaultLayoutWithVerticalNav from '../../layouts/components/DefaultLayoutWithVerticalNav.vue'
import LoginView from '../../views/LoginView.vue'
import { dashboardRoutes } from '../../modules/dashboard/routes'
import { annualLeaveRoutes } from '../../modules/annual-leave/routes'
import { travelOrderRoutes } from '../../modules/travel-order/routes'
import { travelExpenseReportRoutes } from '../../modules/travel-expense-report/routes'
import { userManagementRoutes } from '../../modules/user-management/routes'
import { masterManagementRoutes } from '../../modules/master-management/routes'
import { masterAdvanceRoutes } from '../../modules/master-advance/routes'
import { masterRegionRoutes } from '../../modules/master-region/routes'
import { masterRateRoutes } from '../../modules/master-rate/routes'
import { authorityMatrixRoutes } from '../../modules/authority-matrix/routes'

const routes = [
  // ─── Blank layout (login) ───────────────────────────────
  {
    path: '/login',
    component: BlankLayout,
    children: [
      { path: '', name: 'login', component: LoginView, meta: { guestOnly: true, title: 'Login' } },
    ],
  },

  // ─── Default layout (app pages) ─────────────────────────
  {
    path: '/',
    component: DefaultLayoutWithVerticalNav,
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: { name: 'user-management' } },
      ...dashboardRoutes,
      ...annualLeaveRoutes,
      ...travelOrderRoutes,
      ...travelExpenseReportRoutes,
      ...userManagementRoutes,
      ...masterManagementRoutes,
      ...masterAdvanceRoutes,
      ...masterRegionRoutes,
      ...masterRateRoutes,
      ...authorityMatrixRoutes,
    ],
  },

  // ─── Error pages ────────────────────────────────────────
  {
    path: '/error',
    component: BlankLayout,
    children: [
      { path: '403', name: 'Forbidden', component: () => import('../../views/errors/403.vue'), meta: { title: '403 - Forbidden' } },
      { path: '500', name: 'ServerError', component: () => import('../../views/errors/500.vue'), meta: { title: '500 - Server Error' } },
    ],
  },

  {
    path: '/:pathMatch(.*)*',
    component: BlankLayout,
    children: [
      { path: '', name: 'NotFound', component: () => import('../../views/errors/404.vue'), meta: { title: '404 - Page Not Found' } },
    ],
  },
]

export default routes
