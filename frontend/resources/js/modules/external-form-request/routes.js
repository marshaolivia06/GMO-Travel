export const externalFormRequestRoutes = [
  {
    path: '/external-form-request',
    name: 'external-form-request',
    component: () => import('./views/ExternalFormRequestView.vue'),
    meta: {
      title: 'External Form Request',
      requiresAuth: true,
      permission: 'external-form-request.view',
    },
  },
]
