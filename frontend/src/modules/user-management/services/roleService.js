const API_URL = 'http://127.0.0.1:8000/api/v1'

const getToken = () => {
  return localStorage.getItem('token')
}

const request = async (url, options = {}) => {
  const response = await fetch(`${API_URL}${url}`, {
    ...options,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      Authorization: `Bearer ${getToken()}`,
      ...(options.headers || {}),
    },
  })

  const data = await response.json()

  if (!response.ok) {
    throw new Error(data.message || 'Terjadi kesalahan.')
  }

  return data
}

// GET ALL ROLES
export const getRoles = () => {
  return request('/roles')
}

// CREATE ROLE
export const createRole = (role) => {
  return request('/roles', {
    method: 'POST',
    body: JSON.stringify(role),
  })
}

// GET ROLE BY ID
export const getRole = (id) => {
  return request(`/roles/${id}`)
}

// UPDATE ROLE
export const updateRole = (id, role) => {
  return request(`/roles/${id}`, {
    method: 'PUT',
    body: JSON.stringify(role),
  })
}

// DELETE ROLE
export const deleteRole = (id) => {
  return request(`/roles/${id}`, {
    method: 'DELETE',
  })
}
