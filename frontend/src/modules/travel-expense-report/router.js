import TravelExpenseReportView from './views/TravelExpenseView.vue'

const travelExpenseReportRoutes = [
  {
    path: '/travel-expense-report',
    name: 'travel-expense-report',
    component: TravelExpenseReportView,
    meta: {
      title: 'Travel Expense Report'
    }
  }
]

export default travelExpenseReportRoutes