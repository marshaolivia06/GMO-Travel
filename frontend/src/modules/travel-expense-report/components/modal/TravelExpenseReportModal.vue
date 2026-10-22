<script setup>
import { ref, computed, watch } from 'vue'
import { useAuthStore } from '../../../../stores/auth.js'

const props = defineProps({ modelValue: { type: Boolean, default: false }, item: { type: Object, default: null } })
const emit = defineEmits(['update:modelValue'])

const authStore = useAuthStore()
const selectedDay = ref(null)

const tripTypeLabels = { group: 'Group Trip', individual: 'Individual Trip' }
const normalize = value => String(value ?? '').trim().toLowerCase().replaceAll(' ', '_')
const tripTypeLabel = type => tripTypeLabels[normalize(type)] ?? type ?? '-'
const tripTypeColor = type => normalize(type) === 'group' ? 'primary' : 'deep-purple'
const toDate = value => {
  if (!value) return null
  const parsed = new Date(value)
  if (Number.isNaN(parsed.getTime())) return null
  parsed.setHours(0, 0, 0, 0)
  return parsed
}
const formatShort = date => date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
const formatLong = date => date.toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' })

const info = computed(() => {
  const to = props.item
  if (!to) return null
  const start = toDate(to.departure_date)
  const end = toDate(to.return_date)
  const sameMonth = start && end && start.getMonth() === end.getMonth() && start.getFullYear() === end.getFullYear()
  const period = !start || !end ? '-' : sameMonth ? `${start.toLocaleDateString('en-GB', { day: '2-digit' })}–${end.toLocaleDateString('en-GB', { day: '2-digit' })} ${start.toLocaleDateString('en-GB', { month: 'short', year: 'numeric' })}` : `${formatShort(start)} – ${formatShort(end)}`
  const etd = to.etd ?? null
  const eta = to.eta ?? null
  return {
    expense_report_id: null,
    travel_order: to.to_number,
    employee: authStore.user?.name ?? null,
    trip_type: to.trip_type,
    travel_region: to.travel_region,
    purpose: to.purpose,
    route: to.travel_from && to.travel_to ? `${to.travel_from} - ${to.travel_to}` : null,
    period,
    etd,
    eta,
    schedule: etd || eta ? `ETD ${etd ?? '-'} - ETA ${eta ?? '-'}` : null,
  }
})

const fields = computed(() => {
  const data = info.value ?? {}
  return [
    { label: 'TRAVEL ORDER', value: data.travel_order },
    { label: 'EMPLOYEE', value: data.employee },
    { label: 'TRIP TYPE', value: data.trip_type, type: 'trip_type' },
    { label: 'TRAVEL REGION', value: data.travel_region },
    { label: 'PURPOSE', value: data.purpose },
    { label: 'ROUTE', value: data.route },
    { label: 'PERIOD', value: data.period },
    { label: 'EFFECTIVE SCHEDULE', value: data.schedule },
  ]
})

const days = computed(() => {
  const start = toDate(props.item?.departure_date)
  const end = toDate(props.item?.return_date)
  if (!start || !end || end < start) return []
  const total = Math.round((end - start) / 86400000) + 1
  return Array.from({ length: total }, (_, index) => {
    const date = new Date(start)
    date.setDate(start.getDate() + index)
    const type = index === 0 ? 'Departure' : index === total - 1 ? 'Return' : 'Stay'
    const time = index === 0 ? info.value?.etd : index === total - 1 ? info.value?.eta : null
    return { key: index + 1, title: `Day ${index + 1} · ${type}`, subtitle: time ? `${formatLong(date)} · Effective ${time}` : formatLong(date) }
  })
})

const close = () => emit('update:modelValue', false)
const expenseEntryOpen = ref(false)
const activeDay = ref(null)

const onSelectDay = day => {
  selectedDay.value = day.key
  activeDay.value = day
  expenseEntryOpen.value = true
}
const onViewSummary = () => console.log('View Summary TER:', info.value?.travel_order)

watch(() => props.modelValue, open => { if (!open) selectedDay.value = null })
</script>

<template>
  <VDialog :model-value="modelValue" max-width="1000" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <div class="d-flex align-center justify-space-between px-4 py-3">
        <span class="text-h6 font-weight-bold">Travel Expense Report</span>
        <VBtn icon="ri-close-line" variant="text" size="small" @click="close" />
      </div>
      <VDivider />
      <VCardText class="pa-4">
        <VCard rounded="lg" elevation="0" border class="mb-4">
          <div class="d-flex align-center justify-space-between px-4 py-3">
            <span class="text-subtitle-1 font-weight-bold">Travel and Report Information</span>
            <VChip color="warning" variant="tonal" size="small">New</VChip>
          </div>
          <VDivider />
          <div class="pa-4">
            <VRow no-gutters class="border rounded-lg overflow-hidden">
              <VCol v-for="(field, index) in fields" :key="field.label" cols="6" :class="['px-4 py-3', { 'border-e': index % 2 === 0, 'border-b': index < fields.length - 1 }]">
                <div class="text-caption text-medium-emphasis">{{ field.label }}</div>
                <VChip v-if="field.type === 'trip_type' && field.value" :color="tripTypeColor(field.value)" variant="tonal" size="small" class="mt-1">{{ tripTypeLabel(field.value) }}</VChip>
                <div v-else class="text-body-2 font-weight-bold mt-1">{{ field.value || '-' }}</div>
              </VCol>
              <VCol v-if="fields.length % 2 === 1" cols="6" />
            </VRow>
          </div>
        </VCard>
        <VCard rounded="lg" elevation="0" border>
          <div class="d-flex align-center justify-space-between px-4 py-3">
            <span class="text-subtitle-1 font-weight-bold">Expense by Day</span>
            <span class="text-caption text-medium-emphasis">Select a day to continue expense entry.</span>
          </div>
          <VDivider />
          <div class="d-flex flex-wrap ga-3 pa-4">
  <span v-if="!days.length" class="text-body-2 text-medium-emphasis">No travel days found</span>
  <VCard v-for="day in days" :key="day.key" link rounded="lg" :color="selectedDay === day.key ? 'primary' : undefined" :variant="selectedDay === day.key ? 'tonal' : 'text'" class="px-3 py-2" @click="onSelectDay(day)">
  <div class="text-body-2 font-weight-medium">{{ day.title }}</div>
  <div class="text-body-2 text-medium-emphasis">{{ day.subtitle }}</div>
</VCard>
</div>
          <VDivider />
          <div class="d-flex justify-end pa-4 bg-grey-lighten-5">
            <VBtn color="primary" variant="flat" rounded="md" class="text-none font-weight-semibold" @click="onViewSummary">View Summary</VBtn>
          </div>
        </VCard>
      </VCardText>
    </VCard>
  </VDialog>
</template>