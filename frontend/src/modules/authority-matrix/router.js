export default [
  {
    path: '/authority-matrix',
    name: 'authority-matrix',
    component: () => import('./views/AuthorityMatrixView.vue'),
    meta: {
      title: 'Authority Matrix',
      requiresAuth: true,
    },
  },
]