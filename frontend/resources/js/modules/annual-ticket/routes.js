export const annualTicketRoutes = [
    {
      path: '/annual-ticket',
      name: 'annual-ticket',
      component: () => import('./views/AnnualTicketView.vue'),
      meta: {
        title: 'Annual Ticket',
        requiresAuth: true,
      },
    },
  ]