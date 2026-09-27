import { http } from '../../../plugins/axios'

const BASE = '/v1/travel-advance-masters'

export const getTravelAdvanceMasters = () =>
  http.get(BASE).then(res => res.data)

export const createTravelAdvanceMaster = data =>
  http.post(BASE, data).then(res => res.data)

export const getTravelAdvanceMaster = id =>
  http.get(`${BASE}/${id}`).then(res => res.data)

export const updateTravelAdvanceMaster = (id, data) =>
  http.put(`${BASE}/${id}`, data).then(res => res.data)

export const deleteTravelAdvanceMaster = id =>
  http.delete(`${BASE}/${id}`).then(res => res.data)