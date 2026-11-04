import { http } from '../../../plugins/axios'

export const getMyDependents = () => http.get('/v1/master-dependents/my-dependents').then(res => res.data)

export const getAnnualTickets = () => http.get('/v1/annualtickets').then(res => res.data)

export const getAnnualTicketQuota = () => http.get('/v1/annualtickets/quota').then(res => res.data)

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

export const resubmitAnnualTicket = (id, payload, evidence) => {
  const form = new FormData()

  form.append('quota_year', payload.quota_year)
  form.append('route_condition', payload.route_condition)
  form.append('ticket_arrangement', payload.ticket_arrangement)

  if (payload.remarks)
    form.append('remarks', payload.remarks)

  if (evidence)
    form.append('evidence', evidence)

  form.append('travelers', JSON.stringify(payload.travelers))

  return http.post(`/v1/annualtickets/${id}/resubmit`, form, { headers: { 'Content-Type': 'multipart/form-data' } }).then(res => res.data)
}

export const checkAnnualTicket = (id, note) => http.post(`/v1/annualtickets/${id}/check`, { note }).then(res => res.data)

export const approveAnnualTicket = (id, note) => http.post(`/v1/annualtickets/${id}/approve`, { note }).then(res => res.data)

export const revisionAnnualTicket = (id, remark) => http.post(`/v1/annualtickets/${id}/revision`, { remark }).then(res => res.data)

export const processAnnualTicketByGmo = (id, note) => http.post(`/v1/annualtickets/${id}/process`, { note }).then(res => res.data)

export const updateAnnualTicketSchedule = (id, { reschedules }) => http.post(`/v1/annualtickets/${id}/schedule`, { reschedules }).then(res => res.data)

export const addAnnualTicketBookingDocuments = (id, documents) => {
  const form = new FormData()

  documents.forEach(file => form.append('documents[]', file))

  return http.post(`/v1/annualtickets/${id}/booking-documents`, form, { headers: { 'Content-Type': 'multipart/form-data' } }).then(res => res.data)
}

export const cancelAnnualTicketLeg = (id, legId, { reason, refundStatus, originalAmount, refundAmount, nonRefundableAmount, confirmation }) => {
  const form = new FormData()

  form.append('reason', reason)
  form.append('refund_status', refundStatus)
  form.append('original_amount', originalAmount ?? 0)
  form.append('refund_amount', refundAmount ?? 0)
  form.append('non_refundable_amount', nonRefundableAmount ?? 0)

  if (confirmation)
    form.append('confirmation', confirmation)

  return http.post(`/v1/annualtickets/${id}/legs/${legId}/cancel`, form, { headers: { 'Content-Type': 'multipart/form-data' } }).then(res => res.data)
}

export const updateAnnualTicketLegRefund = (id, legId, { reason, refundStatus, refundAmount, nonRefundableAmount, confirmation }) => {
  const form = new FormData()

  form.append('reason', reason)
  form.append('refund_status', refundStatus)
  form.append('refund_amount', refundAmount ?? 0)
  form.append('non_refundable_amount', nonRefundableAmount ?? 0)

  if (confirmation)
    form.append('confirmation', confirmation)

  return http.post(`/v1/annualtickets/${id}/legs/${legId}/refund`, form, { headers: { 'Content-Type': 'multipart/form-data' } }).then(res => res.data)
}

export const resendAnnualTicketEmail = (id, { to, cc, subject, message }) => http.post(`/v1/annualtickets/${id}/resend-email`, { to, cc, subject, message }).then(res => res.data)

export const requestAnnualTicketCancellation = (id, reason) => http.post(`/v1/annualtickets/${id}/cancel-request`, { reason }).then(res => res.data)

export const finalizeAnnualTicketCancellation = (id, note) => http.post(`/v1/annualtickets/${id}/cancel-finalize`, { note }).then(res => res.data)

export const rejectAnnualTicketCancellation = (id, remark) => http.post(`/v1/annualtickets/${id}/cancel-reject`, { remark }).then(res => res.data)

export const processAnnualTicketByGmoWithFare = (id, { fareReferences, attachments, note, booking, eTicket, email }) => {
  const form = new FormData()

  if (note)
    form.append('note', note)

  form.append('fare_references', JSON.stringify(fareReferences))
  attachments.forEach(file => form.append('attachments[]', file))

  if (booking) {
    form.append('booking_currency', booking.currency || 'IDR')
    form.append('booking_amount', booking.amount)
  }

  if (eTicket)
    form.append('e_ticket', eTicket)

  if (email)
    form.append('email', JSON.stringify(email))

  return http.post(`/v1/annualtickets/${id}/process`, form, { headers: { 'Content-Type': 'multipart/form-data' } }).then(res => res.data)
}
