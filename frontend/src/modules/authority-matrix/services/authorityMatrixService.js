import { http } from '../../../plugins/axios'

const BASE = '/v1/authoritymatrices'

export const getAuthorityMatrices = () =>
  http.get(BASE).then(res => res.data)

export const createAuthorityMatrix = data =>
  http.post(BASE, data).then(res => res.data)

export const getAuthorityMatrix = id =>
  http.get(`${BASE}/${id}`).then(res => res.data)

export const updateAuthorityMatrix = (id, data) =>
  http.put(`${BASE}/${id}`, data).then(res => res.data)

export const deleteAuthorityMatrix = id =>
  http.delete(`${BASE}/${id}`).then(res => res.data)