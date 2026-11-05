import { http } from '../../../plugins/axios'

const BASE = '/v1/master-employees'

export const getMasterEmployees = () => http.get(BASE).then(res => res.data)

export const createMasterEmployee = data => http.post(BASE, data).then(res => res.data)

export const updateMasterEmployee = (id, data) => http.put(`${BASE}/${id}`, data).then(res => res.data)

export const deleteMasterEmployee = id => http.delete(`${BASE}/${id}`).then(res => res.data)

export const getEmployeeDestinationOptions = () => http.get('/v1/master-destinations').then(res => res.data)
