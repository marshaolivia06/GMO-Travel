import { http } from '../../../plugins/axios'

const GROUP_BASE = '/v1/travel-order-groups'

const toGroupPayload = payload => ({
  trip_name: payload.trip_name,
  region_id: payload.region_id,
  activity_category: payload.activity_category,
  travel_from: payload.travel_from,
  travel_to: payload.travel_to,
  departure_date: payload.departure_date,
  return_date: payload.return_date,
  purpose: payload.purpose,
  members: payload.members,
})

export const getTravelOrderGroups = params => http.get(GROUP_BASE, { params }).then(res => res.data)
export const getTravelOrderGroup = id => http.get(`${GROUP_BASE}/${id}`).then(res => res.data)
export const createTravelOrderGroup = payload => http.post(GROUP_BASE, toGroupPayload(payload)).then(res => res.data)
export const updateTravelOrderGroup = (id, payload) => http.put(`${GROUP_BASE}/${id}`, toGroupPayload(payload)).then(res => res.data)
export const deleteTravelOrderGroup = id => http.delete(`${GROUP_BASE}/${id}`).then(res => res.data)
export const openTravelOrderGroup = id => http.post(`${GROUP_BASE}/${id}/open`).then(res => res.data)
export const closeTravelOrderGroup = id => http.post(`${GROUP_BASE}/${id}/close`).then(res => res.data)
export const addTravelOrderGroupMembers = (id, userIds) => http.post(`${GROUP_BASE}/${id}/members`, { user_ids: userIds }).then(res => res.data)
export const removeTravelOrderGroupMember = (id, userId) => http.delete(`${GROUP_BASE}/${id}/members/${userId}`).then(res => res.data)
export const getMyTravelOrderGroups = () => http.get(`${GROUP_BASE}/my`).then(res => res.data)
export const getGroupTripUsers = () => http.get('/v1/travel-orders/group-trip/users').then(res => res.data)
export const getActivityCategories = () => http.get(`${GROUP_BASE}/activity-categories`).then(res => res.data)
