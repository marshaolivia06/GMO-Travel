import { http } from '../../../plugins/axios'

const BASE = '/v1/master-regions'

export const getMasterRegions = () =>
  http.get(BASE).then(res => res.data)

export const createMasterRegion = data =>
  http.post(BASE, data).then(res => res.data)

export const getMasterRegion = id =>
  http.get(`${BASE}/${id}`).then(res => res.data)

export const updateMasterRegion = (id, data) =>
  http.put(`${BASE}/${id}`, data).then(res => res.data)

export const deleteMasterRegion = id =>
  http.delete(`${BASE}/${id}`).then(res => res.data)