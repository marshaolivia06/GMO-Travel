import GroupTripView from './views/GroupTripView.vue'

export const groupTripRoutes = [
  {
    path: '/group-trip',
    name: 'group-trip',
    component: GroupTripView,
    meta: {
      title: 'Group Trip',
      requiresAuth: true,
      permission: 'group-trip.view'
    }
  }
]
