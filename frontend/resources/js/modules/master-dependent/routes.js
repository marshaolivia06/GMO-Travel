export const masterDependentRoutes = [
    {
      path: '/master-dependent',
      name: 'master-dependent',
      component: () => import('./views/MasterDependentView.vue'),
      meta: {
        title: 'Master Dependent',
        requiresAuth: true,
      },
    },
  ]