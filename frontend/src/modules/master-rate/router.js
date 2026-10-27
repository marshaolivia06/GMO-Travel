export default [
    {
      path: '/master-rate',
      name: 'master-rate',
      component: () => import('./views/MasterRateView.vue'),
      meta: {
        title: 'Master Rate',
        requiresAuth: true,
      },
    },
  ]