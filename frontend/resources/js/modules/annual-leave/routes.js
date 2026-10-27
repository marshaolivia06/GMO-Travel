import AnnualLeaveView
  from './views/AnnualLeaveView.vue'


export const annualLeaveRoutes = [

  {
    path: 'annual-leave',

    name: 'annual-leave',

    component: AnnualLeaveView,

    meta: {
      title: 'Annual Leave',
      requiresAuth: true,
    },

  },

]


