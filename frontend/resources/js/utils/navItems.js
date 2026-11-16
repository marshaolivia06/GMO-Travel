import { computed } from 'vue'
import { useAuthStore } from '../stores/auth'
import { canSeeAnnualTicket } from './annualTicketAccess'

const nav = (title, icon, to, permission) => ({
  title,
  ...(icon && { icon: { icon } }),
  to,
  action: 'manage',
  subject: 'all',
  ...(permission && { permission }),
})

const NAV_ITEMS = [
  nav('User Management', 'ri-team-line', 'user-management', 'user-management.view'),
  {
    title: 'Master Data',
    icon: { icon: 'ri-database-2-line' },
    action: 'manage',
    subject: 'all',
    children: [
      nav('Master Management', null, 'master-management', 'master-management.view'),
      nav('Master Region', null, 'master-region', 'master-region.view'),
      nav('Master Rate', null, 'master-rate', 'master-rate.view'),
      nav('Master Dependent', null, 'master-dependent', 'master-dependent.view'),
      nav('Master Destination', null, 'master-destination', 'master-destination.view'),
      nav('Master Employee', null, 'master-employee', 'master-employee.view'),
    ],
  },
  nav('Authority Matrix', 'ri-git-branch-line', 'authority-matrix', 'authority-matrix.view'),
  { ...nav('Annual Ticket', 'ri-ticket-2-line', 'annual-ticket', 'annual-ticket.view'), visible: canSeeAnnualTicket },
  nav('Travel Order', 'ri-flight-takeoff-line', 'travel-order', 'travel-order.view'),
  nav('Group Trip', 'ri-group-line', 'group-trip', 'group-trip.view'),
  nav('Travel Expense Report', 'ri-receipt-line', 'travel-expense-report', 'travel-expense-report.view'),
  nav('External Form Request', 'ri-file-chart-line', 'external-form-request', 'external-form-request.view'),
]

export function getAccessibleNavItems(auth) {
  return NAV_ITEMS
    .map(item => item.children
      ? { ...item, children: item.children.filter(child => !child.permission || auth.can(child.permission)) }
      : item)
    .filter(item => (!item.permission || auth.can(item.permission))
      && (!item.visible || item.visible(auth))
      && (!item.children || item.children.length > 0))
}

export function firstAccessibleNavPath(auth) {
  const first = getAccessibleNavItems(auth).flatMap(item => item.children ?? [item])[0]

  return first?.to ? `/${String(first.to).replace(/^\/+/, '')}` : null
}

export function useNavItems() {
  const auth = useAuthStore()

  return computed(() => getAccessibleNavItems(auth))
}
