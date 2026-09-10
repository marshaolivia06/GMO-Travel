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
      data.message || 'Terjadi kesalahan.'
    )
  }

  return data
}

// GET ALL CATEGORIES
export const getCategories = () => {
  return request('/master-management/categories')
}

// CREATE CATEGORY
export const createCategory = (category) => {
  return request('/master-management/categories', {
    method: 'POST',
    body: JSON.stringify(category),
  })
}

// GET CATEGORY BY ID
export const getCategory = (id) => {
  return request(
    `/master-management/categories/${id}`
  )
}

// UPDATE CATEGORY
export const updateCategory = (id, category) => {
  return request(
    `/master-management/categories/${id}`,
    {
      method: 'PUT',
      body: JSON.stringify(category),
    }
  )
}

// DELETE CATEGORY
export const deleteCategory = (id) => {
  return request(
    `/master-management/categories/${id}`,
    {
      method: 'DELETE',
    }
  )
}
