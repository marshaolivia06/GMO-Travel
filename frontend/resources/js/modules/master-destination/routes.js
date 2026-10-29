export const masterDestinationRoutes = [
    {
      path: '/master-destination',
      name: 'master-destination',
      component: () => import('./views/MasterDestinationView.vue'),
      meta: {
        title: 'Master Destination',
        requiresAuth: true,
      },
    },
  ]