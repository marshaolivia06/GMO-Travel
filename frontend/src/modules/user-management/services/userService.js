const API_URL = 'http://127.0.0.1:8000/api/v1'

const getToken = () => {
  return localStorage.getItem('token')
}

const request = async (url, options = {}) => {
  const response = await fetch(`${API_URL}${url}`, {
    ...options,
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${getToken()}`,
      ...(options.headers || {}),
    },
  })

  const data = await response.json()

  if (!response.ok) {
    throw new Error(data.message || 'Terjadi kesalahan.')
  }

  return data
}

export const getUsers = () => {
  return request('/users')
}

export const createUser = (user) => {
  return request('/users', {
    method: 'POST',
    body: JSON.stringify(user),
  })
}

export const getRoles = () => {
  return request('/roles')
}
export const updateUser = (id, user) => {
  return request(`/users/${id}`, {
    method: 'PUT',
    body: JSON.stringify(user),
  })
}
export const deleteUser = (id) => {
  return request(`/users/${id}`, {
    method: 'DELETE',
  })
}

