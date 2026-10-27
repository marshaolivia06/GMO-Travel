import MasterManagementView from './views/MasterManagementView.vue'

export const masterManagementRoutes = [
  {
    path: '/master-management',
    name: 'master-management',
    component: MasterManagementView,
    meta: {
      title: 'Master Management',
      requiresAuth: true,
    },
  },
]
