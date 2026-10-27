export const masterRegionRoutes = [
    {
      path: '/master-region',
      name: 'master-region',
      component: () => import('./views/MasterRegionView.vue'),
      meta: {
        title: 'Master Region',
        requiresAuth: true,
      },
    },
  ]