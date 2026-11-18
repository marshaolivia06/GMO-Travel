import { http } from '../../../plugins/axios'

export const getTravelExpenseReports = () => http.get('/v1/travel-expense-reports').then(res => res.data)

export const getTravelExpenseReport = id => http.get(`/v1/travel-expense-reports/${id}`).then(res => res.data)

export const getTravelExpenseReportHistory = id => http.get(`/v1/travel-expense-reports/${id}/history`).then(res => res.data)

export const getTravelExpenseReportPdf = id => http.get(`/v1/travel-expense-reports/${id}/pdf`, { responseType: 'blob' }).then(res => res.data)

export const createTravelExpenseReport = (payload, attachments = [], proofOfPayment = null) => {
  const form = new FormData()

  form.append('action', payload.action ?? 'draft')
  form.append('annual_ticket_id', payload.annual_ticket_id)

  if (payload.total_actual_amount !== undefined && payload.total_actual_amount !== null)
    form.append('total_actual_amount', payload.total_actual_amount)

  attachments.forEach(file => form.append('attachments[]', file))

  if (proofOfPayment)
    form.append('proof_of_payment', proofOfPayment)

  return http.post('/v1/travel-expense-reports', form, { headers: { 'Content-Type': 'multipart/form-data' } }).then(res => res.data)
}

export const updateTravelExpenseReportDraft = (id, payload = {}, attachments = [], proofOfPayment = null, removeProofOfPayment = false) => {
  const form = new FormData()

  if (payload.total_actual_amount !== undefined && payload.total_actual_amount !== null)
    form.append('total_actual_amount', payload.total_actual_amount)

  attachments.forEach(file => form.append('attachments[]', file))

  if (proofOfPayment)
    form.append('proof_of_payment', proofOfPayment)

  if (removeProofOfPayment)
    form.append('remove_proof_of_payment', '1')

  return http.post(`/v1/travel-expense-reports/${id}/update`, form, { headers: { 'Content-Type': 'multipart/form-data' } }).then(res => res.data)
}

// Hapus lampiran lama yang sudah tersimpan (draft / revisi)
export const deleteTravelExpenseReportAttachment = (id, attachmentId) => http.delete(`/v1/travel-expense-reports/${id}/attachments/${attachmentId}`).then(res => res.data)

export const submitTravelExpenseReport = id => http.post(`/v1/travel-expense-reports/${id}/submit`).then(res => res.data)

export const deleteTravelExpenseReportDraft = id => http.delete(`/v1/travel-expense-reports/${id}`).then(res => res.data)

// Aksi approver sesuai tahap matrix: check (review) / approve / kembalikan untuk revisi
export const checkTravelExpenseReport = (id, note = null) => http.post(`/v1/travel-expense-reports/${id}/check`, { note }).then(res => res.data)

export const approveTravelExpenseReport = (id, note = null) => http.post(`/v1/travel-expense-reports/${id}/approve`, { note }).then(res => res.data)

export const revisionTravelExpenseReport = (id, remark) => http.post(`/v1/travel-expense-reports/${id}/revision`, { remark }).then(res => res.data)
