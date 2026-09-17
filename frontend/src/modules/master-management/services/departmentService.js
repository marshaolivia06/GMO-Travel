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
    throw new Error(
      data.message || 'An error occurred.'
    )
  }

  return data
}

export const getDepartments = () => {
  return request('/master-management/departments')
}

export const createDepartment = department => {
  return request('/master-management/departments', {
    method: 'POST',
    body: JSON.stringify(department),
  })
}

export const getDepartment = id => {
  return request(
    `/master-management/departments/${id}`
  )
}

export const updateDepartment = (id, department) => {
  return request(
    `/master-management/departments/${id}`,
    {
      method: 'PUT',
      body: JSON.stringify(department),
    }
  )
}

export const deleteDepartment = id => {
  return request(
    `/master-management/departments/${id}`,
    {
      method: 'DELETE',
    }
  )
}

export const getDepartmentOptions = () => {
  return request('/master-management/departments/options')
}
