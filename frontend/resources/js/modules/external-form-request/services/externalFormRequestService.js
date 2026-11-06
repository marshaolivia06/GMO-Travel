import { http } from '../../../plugins/axios'

const BASE = '/v1/externalformrequests'

const pick = value => (value === undefined || value === null || value === '' ? null : value)

const toPayload = (type, form, action) => ({
  type,
  action,
  transport_mode: pick(form.transport_mode),
  vendor_customer: pick(form.vendor_customer),
  purpose: pick(form.purpose),
  trip_type: pick(form.trip_type),
  travel_from: pick(form.travel_from),
  departure_date: pick(form.departure_date),
  travel_to: pick(form.travel_to),
  return_date: pick(form.return_date),
  check_in_date: pick(form.check_in_date),
  check_out_date: pick(form.check_out_date),
  remark: pick(form.remark),
  travelers: (form.travelers ?? form.guests ?? [])
    .map(item => ({ name: item.name?.trim() ?? '' }))
    .filter(item => item.name !== ''),
})

export const getExternalFormRequests = () => http.get(BASE).then(res => res.data)

export const getDivHeads = () => http.get(`${BASE}/div-heads`).then(res => res.data)

export const getExternalFormRequestHistory = id => http.get(`${BASE}/${id}/history`).then(res => res.data)

export const getExternalFormRequestPdf = id => http.get(`${BASE}/${id}/pdf`, { responseType: 'blob' }).then(res => res.data)

export const createExternalFormRequest = (type, form, action = 'submit') => http.post(BASE, toPayload(type, form, action)).then(res => res.data)

export const updateExternalFormRequest = (id, type, form, action = 'submit') => http.put(`${BASE}/${id}`, toPayload(type, form, action)).then(res => res.data)

export const approveExternalFormRequest = (id, payload = {}) => http.post(`${BASE}/${id}/approve`, payload).then(res => res.data)

export const releaseExternalFormRequest = (id, payload) => {
  const body = new FormData()

  Object.entries(payload).forEach(([key, value]) => {
    if (value === null || value === undefined) return

    if (key === 'email' && typeof value === 'object') {
      Object.entries(value).forEach(([subKey, subValue]) => body.append(`email[${subKey}]`, subValue))

      return
    }

    body.append(key, value)
  })

  return http.post(`${BASE}/${id}/release`, body).then(res => res.data)
}

export const revisionExternalFormRequest = (id, remark) => http.post(`${BASE}/${id}/revision`, { remark }).then(res => res.data)

export const rejectExternalFormRequest = (id, remark) => http.post(`${BASE}/${id}/reject`, { remark }).then(res => res.data)
