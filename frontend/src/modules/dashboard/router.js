import DashboardView from './views/DashboardView.vue'

const dashboardRoutes = [
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

export default dashboardRoutes
