import { http } from '../../../plugins/axios'

export const getMyDependents = () => http.get('/v1/master-dependents/my-dependents').then(res => res.data)

export const getAnnualTickets = () => http.get('/v1/annualtickets').then(res => res.data)

export const getAnnualTicketHistory = id => http.get(`/v1/annualtickets/${id}/history`).then(res => res.data)

export const getAnnualTicketPdf = id => http.get(`/v1/annualtickets/${id}/pdf`, { responseType: 'blob' }).then(res => res.data)

export const createAnnualTicket = (payload, evidence) => {
  const form = new FormData()

  form.append('quota_year', payload.quota_year)
  form.append('route_condition', payload.route_condition)
  form.append('ticket_arrangement', payload.ticket_arrangement)

  if (payload.remarks)
    form.append('remarks', payload.remarks)

  if (evidence)
    form.append('evidence', evidence)

  form.append('travelers', JSON.stringify(payload.travelers))

  return http.post('/v1/annualtickets', form, { headers: { 'Content-Type': 'multipart/form-data' } }).then(res => res.data)
}
