export default [
  {
    path: '/master-advance',
    name: 'master-advance',
    component: () => import('./views/MasterAdvanceView.vue'),
    meta: {
      title: 'Master Advance',
      requiresAuth: true,
    },
  },
]