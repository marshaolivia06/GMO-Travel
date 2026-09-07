import AnnualLeaveView
  from './views/AnnualLeaveView.vue'


const annualLeaveRoutes = [

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


export default annualLeaveRoutes