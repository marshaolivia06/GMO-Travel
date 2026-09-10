import MasterManagementView from './views/MasterManagementView.vue'

export default [
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
