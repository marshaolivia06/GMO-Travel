import { http } from '../../../plugins/axios'

export const getPermissions = () => {
  return http.get('/v1/permissions').then(res => res.data)
}

export const getPermission = (id) => {
  return http.get(`/v1/permissions/${id}`).then(res => res.data)
}

export const createPermission = (permission) => {
  return http.post('/v1/permissions', permission).then(res => res.data)
}

export const updatePermissionsByModule = (oldModule, newModule, actions) => {
  return http.put('/v1/permissions/sync', {
    old_module: oldModule,
    module: newModule,
    actions,
  }).then(res => res.data)
}

export const deletePermission = (id) => {
  return http.delete(`/v1/permissions/${id}`).then(res => res.data)
}
