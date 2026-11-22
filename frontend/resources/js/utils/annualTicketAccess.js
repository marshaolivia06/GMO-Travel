import { useAuthStore } from '../stores/auth'

export function canSeeAnnualTicket(auth = useAuthStore()) {
  return auth.can('annual-ticket.eligible')
    || auth.can('annual-ticket.approve')
}
