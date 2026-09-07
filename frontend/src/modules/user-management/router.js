import UserManagementView from './views/UserManagementView.vue'

const userManagementRoutes = [
  {
    path: '/user-management',
    name: 'user-management',
    component: UserManagementView,
    meta: {
      title: 'User Management',
    },
  },
]

export default userManagementRoutes
