import { useAuthStore } from '../stores/auth'

export const ANNUAL_TICKET_PROCESSOR_ROLES = ['HR Admin', 'HR Supervisor', 'Admin-GMO']

export function canSeeAnnualTicket(auth = useAuthStore()) {
  return auth.user?.annual_ticket_eligible === true
    || ANNUAL_TICKET_PROCESSOR_ROLES.some(role => auth.hasRole(role))
}
