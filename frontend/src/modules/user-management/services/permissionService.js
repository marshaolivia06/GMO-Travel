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

  const text = await response.text()

  console.log('API:', `${API_URL}${url}`)
  console.log('STATUS:', response.status)
  console.log('RAW RESPONSE:', text)

  let data

  try {
    data = JSON.parse(text)
  } catch (error) {
    console.error('Response bukan JSON valid:', error)
    throw new Error(
      `Server mengembalikan response yang bukan JSON. Status: ${response.status}`
    )
  }

  if (!response.ok) {
    throw new Error(data.message || 'Terjadi kesalahan.')
  }

  return data
}


export const getPermissions = () => {
  return request('/permissions')
}

export const getPermission = (id) => {
  return request(`/permissions/${id}`)
}

export const createPermission = (permission) => {
  return request('/permissions', {
    method: 'POST',
    body: JSON.stringify(permission),
  })
}

export const updatePermissionsByModule = (
  oldModule,
  newModule,
  actions
) => {
  return request('/permissions/sync', {
    method: 'PUT',
    body: JSON.stringify({
      old_module: oldModule,
      module: newModule,
      actions,
    }),
  })
}

export const deletePermission = (id) => {
  return request(`/permissions/${id}`, {
    method: 'DELETE',
  })
}
