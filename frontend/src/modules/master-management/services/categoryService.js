import { http } from '../../../plugins/axios'

const BASE = '/v1/master-management/categories'

export const getCategories = (params = {}) => http.get(BASE, { params }).then(res => res.data)

export const createCategory = category => http.post(BASE, category).then(res => res.data)

export const getCategory = id => http.get(`${BASE}/${id}`).then(res => res.data)

export const updateCategory = (id, category) =>
  http.put(`${BASE}/${id}`, category).then(res => res.data)

export const deleteCategory = id => http.delete(`${BASE}/${id}`).then(res => res.data)
