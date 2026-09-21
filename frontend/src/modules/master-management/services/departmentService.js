import { http } from '../../../plugins/axios'

const BASE = '/v1/master-management/departments'

export const getDepartments = () => http.get(BASE).then(res => res.data)

export const createDepartment = department => http.post(BASE, department).then(res => res.data)

export const getDepartment = id => http.get(`${BASE}/${id}`).then(res => res.data)

export const updateDepartment = (id, department) =>
  http.put(`${BASE}/${id}`, department).then(res => res.data)

export const deleteDepartment = id => http.delete(`${BASE}/${id}`).then(res => res.data)

export const getDepartmentOptions = () => http.get(`${BASE}/options`).then(res => res.data)
