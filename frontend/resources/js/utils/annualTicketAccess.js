import { useAuthStore } from '../stores/auth'

export function canSeeAnnualTicket(auth = useAuthStore()) {
  return auth.user?.annual_ticket_eligible === true
    || auth.can('annual-ticket.approve')
}
