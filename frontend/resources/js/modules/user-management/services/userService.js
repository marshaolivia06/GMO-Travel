import { http } from '../../../plugins/axios'

export const getUsers = () => {
  return http.get('/v1/users').then(res => res.data)
}

export const getUser = (id) => {
  return http.get(`/v1/users/${id}`).then(res => res.data)
}

export const createUser = (user) => {
  return http.post('/v1/users', user).then(res => res.data)
}

export const getRoles = async () => {
  const roles = await http.get('/v1/roles').then(res => res.data)

  const uniqueRoles = []
  const seen = new Set()

  roles.forEach(role => {
    const key = role.name?.trim().toLowerCase()

    if (!key || seen.has(key)) {
      return
    }

    seen.add(key)

    uniqueRoles.push({
      ...role,
      name: role.name.trim(),
    })
  })

  return uniqueRoles
}

export const updateUser = (id, user) => {
  return http.put(`/v1/users/${id}`, user).then(res => res.data)
}

export const deleteUser = (id) => {
  return http.delete(`/v1/users/${id}`).then(res => res.data)
}

export const getOfficeLocations = async () => {
  const response = await http.get('/v1/company/office-locations').then(res => res.data)

  return response.data ?? []
}
