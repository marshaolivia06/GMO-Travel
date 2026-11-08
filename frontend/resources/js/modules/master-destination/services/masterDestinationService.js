import { http } from '../../../plugins/axios'

const BASE = '/v1/master-destinations'

export const getMasterDestinations = (params = {}) => http.get(BASE, { params }).then(res => res.data)

export const createMasterDestination = data => http.post(BASE, data).then(res => res.data)

export const updateMasterDestination = (id, data) => http.put(`${BASE}/${id}`, data).then(res => res.data)

export const deleteMasterDestination = id => http.delete(`${BASE}/${id}`).then(res => res.data)