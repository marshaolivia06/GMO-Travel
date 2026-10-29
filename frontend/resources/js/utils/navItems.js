import { computed } from 'vue'

// action/subject match the CASL default-allow rule ({ action: 'manage', subject: 'all' })
// registered in plugins/casl/index.js — real authorization is enforced by the router & backend.
const nav = (title, icon, to) => ({ title, ...(icon && { icon: { icon } }), to, action: 'manage', subject: 'all' })

const NAV_ITEMS = [
  nav('User Management', 'ri-team-line', 'user-management'),
  {
    title: 'Master Data',
    icon: { icon: 'ri-database-2-line' },
    action: 'manage',
    subject: 'all',
    children: [
      nav('Master Management', null, 'master-management'),
      nav('Master Region', null, 'master-region'),
      nav('Master Rate', null, 'master-rate'),
      nav('Master Dependent', null, 'master-dependent'),
      nav('Master Destination', null, 'master-destination'),
    ],
  },
  nav('Authority Matrix', 'ri-git-branch-line', 'authority-matrix'),
  nav('Annual Ticket', 'ri-ticket-2-line', 'annual-ticket'),
  nav('Travel Order', 'ri-flight-takeoff-line', 'travel-order'),
  nav('Travel Expense Report', 'ri-receipt-line', 'travel-expense-report'),
]

export function useNavItems() {
  return computed(() => NAV_ITEMS)
}
