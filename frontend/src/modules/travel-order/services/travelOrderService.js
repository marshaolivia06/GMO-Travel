import { http } from '../../../plugins/axios'

const BASE = '/v1/travel-orders'

const toPayload = form => {
  const hasAdvance = [
  'Domestic',
  'Singapore',
  'Overseas',
].includes(form.travelRegion)

  const payload = {
  request_type: form.request_type,
  trip_type: form.trip_type,
  status: form.status,
  department_id: form.departmentId || undefined,

    travel_from: form.travelFrom,
    travel_to: form.travelTo,

    departure_date: form.departureDate,
    departure_time: form.departureTime,

    return_date: form.returnDate,
    return_time: form.returnTime,

    purpose: form.purpose,
    remarks: form.remarks,

    travel_region: form.travelRegion,
    country: form.country,

    ticket_type: form.ferryTicketType,
    ticket_arrangement: form.ferryArrangement,
    accommodation_arrangement: form.accommodationArrangement,

    meal_allowance: hasAdvance
      ? form.mealAllowance
      : null,

    pocket_money: hasAdvance
      ? form.pocketMoney
      : null,

    currency: hasAdvance
      ? form.currency
      : null,
  }

  console.log('TRAVEL ORDER PAYLOAD:', payload)

  return payload
}

export const getTravelOrders = params =>
  http
    .get(BASE, { params })
    .then(res => res.data)

    export const getTravelOrder = id =>
      http
        .get(`${BASE}/${id}`)
        .then(res => res.data)
    
        export const getTravelOrderPdf = id =>
          http
            .get(`${BASE}/${id}/pdf`, { responseType: 'blob' })
            .then(res => res.data)
        
        export const getTravelOrderHistory = id =>
          http
            .get(`${BASE}/${id}/history`)
            .then(res => res.data)
        
            export const saveTravelOrderBooking = (id, form) => {
              const payload = new FormData()
            
              Object.entries(form).forEach(([key, value]) => {
                if (value !== null && value !== undefined && value !== '') {
                  payload.append(key, value)
                }
              })
            
              return http
                .post(`${BASE}/${id}/booking`, payload)
                .then(res => res.data)
            }
            
            export const uploadTravelOrderETicket = (id, files) => {
              const payload = new FormData()
            
              if (files?.eTicket) payload.append('e_ticket', files.eTicket)
              if (files?.hotelReservation) payload.append('hotel_reservation', files.hotelReservation)
            
              return http
                .post(`${BASE}/${id}/e-ticket`, payload)
                .then(res => res.data)
            }
            
            export const completeGmoProcessing = id =>
              http
                .post(`${BASE}/${id}/complete-gmo-processing`)
                .then(res => res.data)

             export const getDepartmentLock = () =>
              http
                .get(`${BASE}/department-lock`)
                .then(res => res.data)

              export const approveTravelOrder = id =>
              http
                .post(`${BASE}/${id}/approve`)
                .then(res => res.data)
    
    export const rejectTravelOrder = (id, remark) =>
      http
        .post(`${BASE}/${id}/reject`, { remark })
        .then(res => res.data)
    
        
export const cancelTravelOrder = (id, remark, evidence) => {
  const formData = new FormData()

  formData.append('remark', remark)
  formData.append('evidence', evidence)

  return http.post(`${BASE}/${id}/cancel`, formData)
    .then(res => res.data)
}
    
export const revisionTravelOrder = (id, remark, evidence = null) => {
  if (evidence) {
    const formData = new FormData()
    formData.append('remark', remark)
    formData.append('evidence', evidence)

    return http
      .post(`${BASE}/${id}/revision`, formData)
      .then(res => res.data)
  }

  return http
    .post(`${BASE}/${id}/revision`, { remark })
    .then(res => res.data)
}

export const getTravelAdvanceLimit = (
  travelRegion,
  currency
) =>
  http
    .get('/v1/travel-advance-masters/limit', {
      params: {
        travel_region: travelRegion,
        currency,
      },
    })
    .then(res => res.data)

export const getTravelAdvanceLimitByGrade = (
  travelRegion,
  grade
) =>
  http
    .get('/v1/travel-advance-masters/limit-by-grade', {
      params: {
        travel_region: travelRegion,
        grade,
      },
    })
    .then(res => res.data)

export const getTravelAdvanceCountries = travelRegion =>
  http
    .get('/v1/travel-advance-masters/countries', {
      params: {
        travel_region: travelRegion,
      },
    })
    .then(res => res.data)

export const createTravelOrder = form =>
  http
    .post(BASE, toPayload(form))
    .then(res => res.data)
  
    export const updateTravelOrder = (id, form) =>
      http
        .put(`${BASE}/${id}`, toPayload(form))
        .then(res => res.data)
    