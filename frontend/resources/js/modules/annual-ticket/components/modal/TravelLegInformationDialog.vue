<script setup>
import { computed, reactive, watch } from 'vue'

const props = defineProps({
  travelers: { type: Array, required: true },
  dateCreated: { type: [String, Date], default: () => new Date() },
  quotaYear: { type: Number, default: () => new Date().getFullYear() },
  route: { type: Object, default: () => ({ origin: 'Batam', destination: '' }) },
  draft: { type: Object, default: null },
  validateDateWindow: { type: Boolean, default: true },
})

const emit = defineEmits(['update', 'next'])

const directions = [{ key: 'departure', title: 'Departure Legs', dateLabel: 'Departure Date' }, { key: 'return', title: 'Return Legs', dateLabel: 'Return Date' }]
const EARLIEST_OFFSET_DAYS = 7
const LATEST_OFFSET_DAYS = 61

const baseDate = computed(() => typeof props.dateCreated === 'string' ? new Date(`${props.dateCreated.slice(0, 10)}T00:00:00`) : new Date(new Date(props.dateCreated).setHours(0, 0, 0, 0)))

function toIso(date) {
  const pad = value => String(value).padStart(2, '0')

  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
}

function shift(days) {
  const date = new Date(baseDate.value)

  date.setDate(date.getDate() + days)

  return toIso(date)
}

const minDate = computed(() => shift(EARLIEST_OFFSET_DAYS))
const maxDate = computed(() => shift(LATEST_OFFSET_DAYS))

function build(direction) {
  const from = direction === 'departure' ? props.route.origin : props.route.destination
  const to = direction === 'departure' ? props.route.destination : props.route.origin

  return props.travelers.filter(item => item[direction]).map(item => ({ type: item.type, id: item.id, name: item.name, relationship: item.relationship, date: '', from, to }))
}

const legs = reactive(props.draft ? JSON.parse(JSON.stringify(props.draft)) : { departure: build('departure'), return: build('return') })

function change(direction, item, field, value) {
  item[field] = value
}

function isSameRoute(direction, item) {
  const expectedFrom = direction === 'departure' ? props.route.origin : props.route.destination
  const expectedTo = direction === 'departure' ? props.route.destination : props.route.origin

  return item.from.trim().toLowerCase() === expectedFrom.trim().toLowerCase() && item.to.trim().toLowerCase() === expectedTo.trim().toLowerCase()
}

function floorOf(direction, item) {
  if (direction === 'departure')
    return minDate.value

  const out = legs.departure.find(row => row.type === item.type && row.id === item.id)

  return out && out.date > minDate.value ? out.date : minDate.value
}

function dateError(direction, item) {
  if (!item.date)
    return ''

  if (props.validateDateWindow && (item.date < minDate.value || item.date > maxDate.value))
    return `Date must be between ${minDate.value} and ${maxDate.value}.`

  if (direction === 'return' && item.date < floorOf(direction, item))
    return 'Return Date must not precede the departure date.'

  return ''
}

const allLegs = computed(() => directions.flatMap(entry => legs[entry.key].map(item => ({ direction: entry.key, item }))))
const isValid = computed(() => allLegs.value.every(({ direction, item }) => item.date && item.from.trim() && item.to.trim() && !dateError(direction, item)))

watch([legs, isValid], () => emit('update', { legs: JSON.parse(JSON.stringify(legs)), valid: isValid.value }), { deep: true, immediate: true })

function next() {
  emit('next')
}
</script>

<template>
  <VCardText class="px-6 py-6">
    <VAlert type="warning" variant="tonal" density="compact" class="mt-4 mb-6">
      <strong>Departure Date policy:</strong> The earliest eligible departure is <strong>{{ minDate }}</strong> and the latest is <strong>{{ maxDate }}</strong> (inclusive), calculated from the Date Created. The Return Date must not precede departure.
    </VAlert>

    <div class="d-flex align-center justify-space-between mb-4">
      <span class="text-h6 font-weight-bold">Departure & Return Legs</span>
    </div>

    <VCard variant="outlined" class="px-5 py-4 mb-6">
      <div class="text-caption text-medium-emphasis">Annual Ticket Quota Year</div>
      <div class="text-subtitle-1 font-weight-bold">{{ props.quotaYear }}</div>
    </VCard>

    <VRow>
      <VCol v-for="entry in directions" :key="entry.key" cols="12" md="6">
        <VCard variant="outlined" class="pa-4">
          <div class="d-flex align-center justify-space-between mb-4">
            <span class="text-h6 font-weight-bold">{{ entry.title }}</span>
            <VChip size="small" color="primary" variant="tonal">{{ legs[entry.key].length }} Travelers</VChip>
          </div>

          <VCard v-for="item in legs[entry.key]" :key="`${entry.key}-${item.type}-${item.id}`" variant="outlined" class="px-4 py-3 mb-4">
            <div class="d-flex align-center justify-space-between mb-3">
              <div class="d-flex align-baseline ga-2">
                <span class="text-subtitle-2 font-weight-bold">{{ item.name }}</span>
                <span class="text-caption text-medium-emphasis">{{ item.type === 'employee' ? 'Employee' : item.relationship }}</span>
              </div>
              <VChip size="small" :color="isSameRoute(entry.key, item) ? 'success' : 'warning'" variant="tonal">{{ isSameRoute(entry.key, item) ? 'Same Route' : 'Different Route' }}</VChip>
            </div>

            <VRow dense>
              <VCol cols="12" md="4">
                <div class="text-caption text-medium-emphasis mb-1">{{ entry.dateLabel }} *</div>
                <VTextField :model-value="item.date" type="date" :min="floorOf(entry.key, item)" :max="maxDate" :error-messages="dateError(entry.key, item)" density="comfortable" @update:model-value="change(entry.key, item, 'date', $event)" />
              </VCol>
              <VCol cols="12" md="4">
                <div class="text-caption text-medium-emphasis mb-1">Travel From *</div>
                <VTextField :model-value="item.from" density="comfortable" @update:model-value="change(entry.key, item, 'from', $event)" />
              </VCol>
              <VCol cols="12" md="4">
                <div class="text-caption text-medium-emphasis mb-1">Travel To *</div>
                <VTextField :model-value="item.to" density="comfortable" @update:model-value="change(entry.key, item, 'to', $event)" />
              </VCol>
            </VRow>
          </VCard>
        </VCard>
      </VCol>
    </VRow>
  </VCardText>

  <VDivider />

  <VCardActions class="px-6 py-4 ga-2">
    <VSpacer />
    <VBtn variant="flat" color="primary" :disabled="!isValid" @click="next">Review Annual Ticket Request</VBtn>
  </VCardActions>
</template>