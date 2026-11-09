import { http } from '../../../plugins/axios'

// GET ALL ROLES
export const getRoles = () => {
  return http.get('/v1/roles').then(res => res.data)
}

// CREATE ROLE
export const createRole = (role) => {
  return http.post('/v1/roles', role).then(res => res.data)
}

// GET ROLE BY ID
export const getRole = (id) => {
  return http.get(`/v1/roles/${id}`).then(res => res.data)
}

// UPDATE ROLE
export const updateRole = (id, role) => {
  return http.put(`/v1/roles/${id}`, role).then(res => res.data)
}

// DELETE ROLE
export const deleteRole = (id) => {
  return http.delete(`/v1/roles/${id}`).then(res => res.data)
}
