import DashboardView from './views/DashboardView.vue'

export const dashboardRoutes = [
  {
    path: 'dashboard',
    name: 'dashboard',
    component: DashboardView,
    meta: {
      title: 'Dashboard',
      requiresAuth: true,
    },
  },
]

