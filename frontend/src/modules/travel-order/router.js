import TravelOrderView from './views/TravelOrderView.vue'

const travelOrderRoutes = [
  {
    path: '/travel-order',
    name: 'travel-order',
    component: TravelOrderView,
    meta: {
      title: 'Travel Order'
    }
  }
]

export default travelOrderRoutes
