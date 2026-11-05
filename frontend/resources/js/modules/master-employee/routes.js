export const masterEmployeeRoutes = [
  {
    path: '/master-employee',
    name: 'master-employee',
    component: () => import('./views/MasterEmployeeView.vue'),
    meta: {
      title: 'Master Employee',
      requiresAuth: true,
      permission: 'master-employee.view',
    },
  },
]
