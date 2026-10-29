import { http } from '../../../plugins/axios'

const BASE = '/v1/master-dependents'

export const getMasterDependents = (params = {}) => http.get(BASE, { params }).then(res => res.data)

export const createMasterDependent = data => http.post(BASE, data).then(res => res.data)

export const updateMasterDependent = (id, data) => http.put(`${BASE}/${id}`, data).then(res => res.data)
export const getDependentEmployeeOptions = () => http.get(`${BASE}/employee-options`).then(res => res.data)
export const deleteMasterDependent = id => http.delete(`${BASE}/${id}`).then(res => res.data)