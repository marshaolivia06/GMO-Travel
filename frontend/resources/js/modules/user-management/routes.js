import UserManagementView from './views/UserManagementView.vue'

export const userManagementRoutes = [
  {
    path: '/user-management',
    name: 'user-management',
    component: UserManagementView,
    meta: {
      title: 'User Management',
      requiresAuth: true,
      permission: 'user-management.view',
    },
  },
]

