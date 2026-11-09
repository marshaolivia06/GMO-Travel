<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({
  modelValue: Boolean,
  mode: { type: String, default: 'review' },
  submitting: Boolean,
  loadingDivHeads: Boolean,
  divHeadItems: { type: Array, default: () => [] },
  isHotel: Boolean,
  departureDate: { type: String, default: null },
  returnDate: { type: String, default: null },
  checkInDate: { type: String, default: null },
  checkOutDate: { type: String, default: null },
  prefill: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['update:modelValue', 'back', 'submit'])

const isRelease = computed(() => props.mode === 'release')

const blankTrip = () => ({ operator: '', flight_vessel_no: '', atd: '', ata: '', booking_code: '' })
const blank = () => ({
  div_head_id: null,
  departure: blankTrip(),
  return: blankTrip(),
  hotel: '',
  room_type: '',
  reservation_no: '',
  email: { to: '', cc: '', subject: '', message: '' },
})
const form = ref(blank())
const attachment = ref(null)
const divHeadError = ref('')
const emailError = ref('')

const isRoundTrip = computed(() => !!props.returnDate)

const trips = computed(() => {
  const list = [{ key: 'departure', title: 'Departure Trip', dateLabel: 'Departure Travel Date', date: props.departureDate }]
  if (isRoundTrip.value) list.push({ key: 'return', title: 'Return Trip', dateLabel: 'Return Travel Date', date: props.returnDate })
  return list
})

const toInputDateTime = v => (v ? String(v).slice(0, 16).replace(' ', 'T') : '')

watch(() => props.modelValue, open => {
  if (!open) return

  const p = props.prefill ?? {}
  const trip = key => {
    const t = blankTrip()
    const src = p[key] ?? {}
    t.operator = src.operator ?? ''
    t.flight_vessel_no = src.flight_vessel_no ?? ''
    t.atd = toInputDateTime(src.atd)
    t.ata = toInputDateTime(src.ata)

    return t
  }

  form.value = {
    div_head_id: p.div_head_id ?? null,
    departure: trip('departure'),
    return: trip('return'),
    hotel: p.hotel ?? '',
    room_type: p.room_type ?? '',
    reservation_no: '',
    email: { to: '', cc: '', subject: '', message: '' },
  }
  attachment.value = null
  divHeadError.value = ''
  emailError.value = ''
})

const tripFilled = t => t.operator.trim() && t.flight_vessel_no.trim() && t.atd && t.ata

const emailFilled = computed(() => {
  const e = form.value.email

  return !!(e.to.trim() && e.subject.trim() && attachment.value)
})

const filled = computed(() => {
  if (isRelease.value) {
    if (props.isHotel) {
      return form.value.hotel.trim() && form.value.room_type.trim() && form.value.reservation_no.trim() && emailFilled.value
    }

    const tripsOk = trips.value.every(t => tripFilled(form.value[t.key]))
      && form.value.departure.booking_code.trim()
      && (!isRoundTrip.value || form.value.return.booking_code.trim())

    return tripsOk && emailFilled.value
  }

  if (props.isHotel) return form.value.div_head_id && form.value.hotel.trim() && form.value.room_type.trim()

  return form.value.div_head_id && trips.value.every(t => tripFilled(form.value[t.key]))
})

const toDateTime = v => v.replace('T', ' ')

function submitRelease() {
  const e = form.value.email
  const payload = {
    email: { to: e.to.trim(), cc: e.cc.trim(), subject: e.subject.trim(), message: e.message.trim() },
    attachment: attachment.value,
  }

  if (props.isHotel) {
    payload.hotel = form.value.hotel.trim()
    payload.room_type = form.value.room_type.trim()
    payload.reservation_confirmation_no = form.value.reservation_no.trim()
  } else {
    const d = form.value.departure
    payload.operator = d.operator.trim()
    payload.flight_vessel_no = d.flight_vessel_no.trim()
    payload.departure_atd = toDateTime(d.atd)
    payload.departure_ata = toDateTime(d.ata)
    payload.departure_booking_code = d.booking_code.trim()

    if (isRoundTrip.value) {
      const r = form.value.return
      payload.return_operator = r.operator.trim()
      payload.return_flight_vessel_no = r.flight_vessel_no.trim()
      payload.return_atd = toDateTime(r.atd)
      payload.return_ata = toDateTime(r.ata)
      payload.return_booking_code = r.booking_code.trim()
    }
  }

  emit('submit', payload)
}

function onSubmit() {
  if (!filled.value) return

  if (isRelease.value) {
    submitRelease()

    return
  }

  if (props.isHotel) {
    emit('submit', {
      div_head_id: form.value.div_head_id,
      hotel: form.value.hotel.trim(),
      room_type: form.value.room_type.trim(),
    })

    return
  }

  const d = form.value.departure
  const payload = {
    div_head_id: form.value.div_head_id,
    operator: d.operator.trim(),
    flight_vessel_no: d.flight_vessel_no.trim(),
    departure_atd: toDateTime(d.atd),
    departure_ata: toDateTime(d.ata),
  }

  if (isRoundTrip.value) {
    const r = form.value.return
    Object.assign(payload, {
      return_operator: r.operator.trim(),
      return_flight_vessel_no: r.flight_vessel_no.trim(),
      return_atd: toDateTime(r.atd),
      return_ata: toDateTime(r.ata),
    })
  }

  emit('submit', payload)
}
</script>

<template>
  <VDialog :model-value="modelValue" max-width="800" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <VCardItem class="px-4 pt-4 pb-0">
        <div class="d-flex align-start ga-1">
          <VBtn icon="ri-arrow-left-line" size="small" variant="text" @click="emit('back')" />
          <div>
            <VCardTitle class="pa-0 text-subtitle-1 font-weight-bold">{{ isRelease ? 'Booking & Payment' : 'GMO Review' }}</VCardTitle>
            <VCardSubtitle class="text-body-2 text-medium-emphasis">{{ isRelease ? 'Verify details, complete booking info and release to the requester.' : 'Assign Div Head and fill the GMO travel detail for this trip.' }}</VCardSubtitle>
          </div>
        </div>
        <template #append>
          <VBtn icon="ri-close-line" size="small" variant="text" @click="emit('update:modelValue', false)" />
        </template>
      </VCardItem>

      <VCardText class="px-6 py-5">
        <template v-if="!isRelease">
          <div class="text-caption text-medium-emphasis mb-1">Div Head <span class="text-error"></span></div>
          <VAutocomplete
            v-model="form.div_head_id"
            :items="divHeadItems"
            :loading="loadingDivHeads"
            :error-messages="divHeadError"
            placeholder="Select Div Head"
            item-title="name"
            item-value="id"
            variant="outlined"
            density="comfortable"
            hide-details="auto"
          />
        </template>

        <template v-if="!isRelease">
          <VCard variant="outlined" rounded="lg" class="mt-5">
            <VCardItem class="py-3">
              <div class="d-flex align-center justify-space-between">
                <div class="text-subtitle-1 font-weight-bold">{{ isHotel ? 'GMO Review & Reservation Detail' : 'GMO Travel Detail' }}</div>
                <VChip v-if="isRoundTrip && !isHotel" size="x-small" color="primary" variant="tonal" label>ROUND-TRIP</VChip>
              </div>
            </VCardItem>

            <VDivider />

            <VCardText>
              <template v-if="isHotel">
                <div class="text-overline font-weight-bold mb-3">Hotel Reservation</div>

                <VRow dense>
                  <VCol cols="12" md="6">
                    <div class="text-caption font-weight-bold mb-1">Check In Date</div>
                    <VTextField :model-value="checkInDate" placeholder="-" variant="outlined" density="comfortable" bg-color="grey-lighten-4" disabled hide-details="auto" />
                  </VCol>

                  <VCol cols="12" md="6">
                    <div class="text-caption font-weight-bold mb-1">Check Out Date</div>
                    <VTextField :model-value="checkOutDate" placeholder="-" variant="outlined" density="comfortable" bg-color="grey-lighten-4" disabled hide-details="auto" />
                  </VCol>

                  <VCol cols="12" md="6">
                    <div class="text-caption text-medium-emphasis font-weight-bold mb-1">Hotel</div>
                    <VTextField v-model="form.hotel" placeholder="e.g. Harris Batam Center" variant="outlined" density="comfortable" hide-details="auto" />
                  </VCol>

                  <VCol cols="12" md="6">
                    <div class="text-caption text-medium-emphasis font-weight-bold mb-1">Room Type</div>
                    <VTextField v-model="form.room_type" placeholder="e.g. Deluxe Twin" variant="outlined" density="comfortable" hide-details="auto" />
                  </VCol>
                </VRow>
              </template>

              <template v-else>
                <template v-for="(t, i) in trips" :key="t.key">
                  <div :class="{ 'mt-4': i > 0 }">
                    <div class="text-overline font-weight-bold mb-3">{{ t.title }}</div>

                    <VRow dense>
                      <VCol cols="12" md="6">
                        <div class="text-caption text-medium-emphasis font-weight-bold mb-1">{{ t.dateLabel }}</div>
                        <VTextField :model-value="t.date" placeholder="-" variant="outlined" density="comfortable" bg-color="grey-lighten-4" disabled hide-details="auto" />
                      </VCol>

                      <VCol cols="12" md="6">
                        <div class="text-caption text-medium-emphasis font-weight-bold mb-1">Airline / Ferry Operator</div>
                        <VTextField v-model="form[t.key].operator" placeholder="Garuda Indonesia / Batam Fast Ferry" variant="outlined" density="comfortable" hide-details="auto" />
                      </VCol>

                      <VCol cols="12" md="6">
                        <div class="text-caption text-medium-emphasis font-weight-bold mb-1">Flight / Vessel Number</div>
                        <VTextField v-model="form[t.key].flight_vessel_no" placeholder="Garuda - GA 154" variant="outlined" density="comfortable" hide-details="auto" />
                      </VCol>

                      <VCol cols="12" md="6">
                        <div class="text-caption text-medium-emphasis font-weight-bold mb-1">Departure Time (ATD)</div>
                        <VTextField v-model="form[t.key].atd" type="datetime-local" variant="outlined" density="comfortable" hide-details="auto" />
                      </VCol>

                      <VCol cols="12" md="6">
                        <div class="text-caption text-medium-emphasis font-weight-bold mb-1">Arrival Time (ATA)</div>
                        <VTextField v-model="form[t.key].ata" type="datetime-local" variant="outlined" density="comfortable" hide-details="auto" />
                      </VCol>
                    </VRow>
                  </div>
                </template>
              </template>
            </VCardText>
          </VCard>
        </template>

        <template v-else>
          <template v-if="isHotel">
            <div class="text-overline font-weight-bold mb-3">Hotel Reservation</div>

            <VRow dense>
              <VCol cols="12" md="6">
                <div class="text-caption text-medium-emphasis font-weight-bold mb-1">Check In Date</div>
                <VTextField :model-value="checkInDate" placeholder="-" variant="outlined" density="comfortable" bg-color="grey-lighten-4" disabled hide-details="auto" />
              </VCol>

              <VCol cols="12" md="6">
                <div class="text-caption text-medium-emphasis font-weight-bold mb-1">Check Out Date</div>
                <VTextField :model-value="checkOutDate" placeholder="-" variant="outlined" density="comfortable" bg-color="grey-lighten-4" disabled hide-details="auto" />
              </VCol>

              <VCol cols="12" md="6">
                <div class="text-caption text-medium-emphasis font-weight-bold mb-1">Hotel</div>
                <VTextField v-model="form.hotel" placeholder="e.g. Harris Batam Center" variant="outlined" density="comfortable" hide-details="auto" />
              </VCol>

              <VCol cols="12" md="6">
                <div class="text-caption text-medium-emphasis font-weight-bold mb-1">Room Type</div>
                <VTextField v-model="form.room_type" placeholder="e.g. Deluxe Twin" variant="outlined" density="comfortable" hide-details="auto" />
              </VCol>

              <VCol cols="12" md="6">
                <div class="text-caption text-medium-emphasis font-weight-bold mb-1">Reservation / Confirmation Number <span class="text-error"></span></div>
                <VTextField v-model="form.reservation_no" placeholder="e.g. HRES-2026-001234" variant="outlined" density="comfortable" hide-details="auto" />
              </VCol>
            </VRow>
          </template>

          <template v-else>
            <template v-for="(t, i) in trips" :key="t.key">
              <div :class="{ 'mt-4': i > 0 }">
                <div class="text-overline font-weight-bold mb-3">{{ t.title }}</div>

                <VRow dense>
                  <VCol cols="12" md="6">
                    <div class="text-caption text-medium-emphasis font-weight-bold mb-1">{{ t.dateLabel }}</div>
                    <VTextField :model-value="t.date" placeholder="-" variant="outlined" density="comfortable" bg-color="grey-lighten-4" disabled hide-details="auto" />
                  </VCol>

                  <VCol cols="12" md="6">
                    <div class="text-caption text-medium-emphasis font-weight-bold mb-1">Airline / Ferry Operator</div>
                    <VTextField v-model="form[t.key].operator" placeholder="Garuda Indonesia / Batam Fast Ferry" variant="outlined" density="comfortable" hide-details="auto" />
                  </VCol>

                  <VCol cols="12" md="6">
                    <div class="text-caption text-medium-emphasis font-weight-bold mb-1">Flight / Vessel Number</div>
                    <VTextField v-model="form[t.key].flight_vessel_no" placeholder="Garuda - GA 154" variant="outlined" density="comfortable" hide-details="auto" />
                  </VCol>

                  <VCol cols="12" md="6">
                    <div class="text-caption text-medium-emphasis font-weight-bold mb-1">Booking Code <span class="text-error"></span></div>
                    <VTextField v-model="form[t.key].booking_code" placeholder="e.g. GA-154K / BTF-771" variant="outlined" density="comfortable" hide-details="auto" />
                  </VCol>

                  <VCol cols="12" md="6">
                    <div class="text-caption text-medium-emphasis font-weight-bold mb-1">Departure Time (ATD)</div>
                    <VTextField v-model="form[t.key].atd" type="datetime-local" variant="outlined" density="comfortable" hide-details="auto" />
                  </VCol>

                  <VCol cols="12" md="6">
                    <div class="text-caption text-medium-emphasis font-weight-bold mb-1">Arrival Time (ATA)</div>
                    <VTextField v-model="form[t.key].ata" type="datetime-local" variant="outlined" density="comfortable" hide-details="auto" />
                  </VCol>
                </VRow>
              </div>
            </template>
          </template>
        </template>

        <template v-if="isRelease">
          <div class="text-subtitle-1 font-weight-bold mt-5 mb-1">Email to Requester</div>

          <VRow dense>
            <VCol cols="12" md="6">
              <div class="text-caption text-medium-emphasis font-weight-bold mb-1">To <span class="text-error"></span></div>
              <VTextField v-model="form.email.to" type="email" placeholder="requester@gmail.com" variant="outlined" density="comfortable" hide-details="auto" />
            </VCol>

            <VCol cols="12" md="6">
              <div class="text-caption text-medium-emphasis font-weight-bold mb-1">CC</div>
              <VTextField v-model="form.email.cc" placeholder="cc1@company.com (Optional)" variant="outlined" density="comfortable" hide-details="auto" />
            </VCol>

            <VCol cols="12">
              <div class="text-caption text-medium-emphasis font-weight-bold mb-1">Subject <span class="text-error"></span></div>
              <VTextField v-model="form.email.subject" placeholder="e.g. Your travel ticket / hotel reservation" variant="outlined" density="comfortable" hide-details="auto" />
            </VCol>

            <VCol cols="12">
              <div class="text-caption text-medium-emphasis font-weight-bold mb-1">Message</div>
              <VTextarea v-model="form.email.message" rows="4" auto-grow placeholder="Message (Optional)" variant="outlined" hide-details="auto" />
            </VCol>

            <VCol cols="12">
              <div class="text-caption text-medium-emphasis font-weight-bold mb-1">Email Attachment <span class="text-error"></span></div>
              <div class="d-flex align-center w-100" style="background:#fff;border:1px solid #bdbdbd;border-radius:8px;padding:8px 10px;min-height:48px">
                <input type="file" accept=".pdf,.jpg,.jpeg,.png" class="w-100" style="font-size:13px;border:0;background:transparent;outline:none" @change="e => { attachment = e.target.files?.[0] ?? null; emailError = '' }">
              </div>
              <div v-if="emailError" class="text-caption text-error mt-1">{{ emailError }}</div>
            </VCol>
          </VRow>
        </template>
      </VCardText>

      <VDivider />

      <VCardActions class="px-6 py-4 justify-end ga-3">
        <VBtn variant="flat" color="primary" :disabled="!filled" :loading="submitting" @click="onSubmit">{{ isRelease ? (isHotel ? 'Release Reservation' : 'Release Ticket') : 'Reviewed' }}</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
:deep(.v-field--disabled .v-field__input) {
  color: #9e9e9e;
}
</style>
