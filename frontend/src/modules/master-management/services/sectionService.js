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

export const getSections = () => {
  return request('/master-management/sections')
}

export const createSection = section => {
  return request('/master-management/sections', {
    method: 'POST',
    body: JSON.stringify(section),
  })
}

export const getSection = id => {
  return request(
    `/master-management/sections/${id}`
  )
}

export const updateSection = (id, section) => {
  return request(
    `/master-management/sections/${id}`,
    {
      method: 'PUT',
      body: JSON.stringify(section),
    }
  )
}

export const deleteSection = id => {
  return request(
    `/master-management/sections/${id}`,
    {
      method: 'DELETE',
    }
  )
}

export const getSectionOptions = () => {
  return request('/master-management/sections/options')
}
