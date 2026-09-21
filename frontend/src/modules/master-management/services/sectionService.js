import { http } from '../../../plugins/axios'

const BASE = '/v1/master-management/sections'

export const getSections = () => http.get(BASE).then(res => res.data)

export const createSection = section => http.post(BASE, section).then(res => res.data)

export const getSection = id => http.get(`${BASE}/${id}`).then(res => res.data)

export const updateSection = (id, section) =>
  http.put(`${BASE}/${id}`, section).then(res => res.data)

export const deleteSection = id => http.delete(`${BASE}/${id}`).then(res => res.data)

export const getSectionOptions = () => http.get(`${BASE}/options`).then(res => res.data)
