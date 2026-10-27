import { http } from '../../../plugins/axios'

const BASE = '/v1/master-rates'

export const getMasterRates = regionId =>
  http.get(BASE, { params: regionId ? { region_id: regionId } : {} }).then(res => res.data)

export const createMasterRate = data =>
  http.post(BASE, data).then(res => res.data)

export const getMasterRate = id =>
  http.get(`${BASE}/${id}`).then(res => res.data)

export const updateMasterRate = (id, data) =>
  http.put(`${BASE}/${id}`, data).then(res => res.data)

export const deleteMasterRate = id =>
  http.delete(`${BASE}/${id}`).then(res => res.data)