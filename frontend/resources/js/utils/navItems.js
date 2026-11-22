import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { canSeeAnnualTicket } from './annualTicketAccess'

const NAV_ITEMS = [
  { title: 'User Management', icon: { icon: 'ri-team-line' }, to: 'user-management', permission: 'user-management.view' },
  {
    title: 'Master Data',
    icon: { icon: 'ri-database-2-line' },
    children: [
      { title: 'Master Management', to: 'master-management', permission: 'master-management.view' },
      { title: 'Master Region', to: 'master-region', permission: 'master-region.view' },
      { title: 'Master Rate', to: 'master-rate', permission: 'master-rate.view' },
      { title: 'Master Dependent', to: 'master-dependent', permission: 'master-dependent.view' },
      { title: 'Master Destination', to: 'master-destination', permission: 'master-destination.view' },
      { title: 'Master Employee', to: 'master-employee', permission: 'master-employee.view' },
    ],
  },
  { title: 'Authority Matrix', icon: { icon: 'ri-git-branch-line' }, to: 'authority-matrix', permission: 'authority-matrix.view' },
  { title: 'Annual Ticket', icon: { icon: 'ri-ticket-2-line' }, to: 'annual-ticket', permission: 'annual-ticket.view', visible: canSeeAnnualTicket },
  { title: 'Travel Order', icon: { icon: 'ri-flight-takeoff-line' }, to: 'travel-order', permission: 'travel-order.view' },
  { title: 'Group Trip', icon: { icon: 'ri-group-line' }, to: 'group-trip', permission: 'group-trip.view' },
  { title: 'Travel Expense Report', icon: { icon: 'ri-receipt-line' }, to: 'travel-expense-report', permission: 'travel-expense-report.view' },
  { title: 'External Form Request', icon: { icon: 'ri-file-chart-line' }, to: 'external-form-request', permission: 'external-form-request.view' },
]

const canSeeItem = (item, auth) => {
  if (item.permission && !auth.can(item.permission)) return false
  if (item.visible && !item.visible(auth)) return false

  return true
}

export function getAccessibleNavItems(auth) {
  return NAV_ITEMS
    .map(item => {
      if (item.children) {
        const children = item.children.filter(child => canSeeItem(child, auth))

        return children.length ? { ...item, children } : null
      }

      return canSeeItem(item, auth) ? item : null
    })
    .filter(Boolean)
}

// Dipakai router: arahkan user ke menu pertama yang boleh dia akses
export function firstAccessibleNavPath(auth) {
  const first = getAccessibleNavItems(auth).flatMap(item => item.children ?? [item])[0]

  return first?.to ? `/${first.to}` : null
}

export function useNavItems() {
  const auth = useAuthStore()

  return computed(() => getAccessibleNavItems(auth))
}
