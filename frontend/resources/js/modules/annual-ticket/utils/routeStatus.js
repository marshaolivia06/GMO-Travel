const norm = value => String(value ?? '').trim().toLowerCase()

// Origin cadangan: kota asal yang paling sering muncul pada departure legs
// (office_location karyawan bisa kosong di data)
function dominantOrigin(legs) {
  const origins = (legs ?? []).filter(leg => leg.direction === 'departure').map(leg => norm(leg.travel_from)).filter(Boolean)
  const counts = {}
  for (const origin of origins) counts[origin] = (counts[origin] || 0) + 1
  return Object.entries(counts).sort((a, b) => b[1] - a[1])[0]?.[0] ?? ''
}

// Route status per leg:
// - Tiket di-declare 'Same Route' -> semua leg Same Route (deklarasi user saat pengajuan)
// - Tiket 'Different Route' -> bandingkan tiap leg dengan rute standar (office <-> destinasi eligible),
//   origin fallback ke kota asal terbanyak jika office_location kosong
export function legRouteStatus(ticket, leg, allLegs) {
  const legs = allLegs ?? ticket?.legs ?? []

  if (norm(ticket?.route_condition) === 'same route') return 'Same Route'

  const origin = norm(ticket?.employee?.office_location) || dominantOrigin(legs)
  const destination = norm(ticket?.employee?.eligible_destination?.city_name)
  const expectedFrom = leg.direction === 'departure' ? origin : destination
  const expectedTo = leg.direction === 'departure' ? destination : origin

  return norm(leg.travel_from) === expectedFrom && norm(leg.travel_to) === expectedTo ? 'Same Route' : 'Different Route'
}
