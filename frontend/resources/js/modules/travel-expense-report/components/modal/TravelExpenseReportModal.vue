<script setup>
import { ref, computed, watch } from 'vue'
import { useAuthStore } from '../../../../stores/auth.js'
import ExpenseEntryDialog from './ExpenseEntryDialog.vue'
import TravelExpenseSummary from './TravelExpenseSummary.vue'

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
    return { key: index + 1, type, title: `Day ${index + 1} · ${type}`, subtitle: time ? `${formatLong(date)} · Effective ${time}` : formatLong(date), date: formatLong(date), effective: time }
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
const onViewSummary = () => { summaryOpen.value = true }
const expenses = ref({})
const isLastDay = computed(() => activeDay.value?.key === days.value.length)
const dayStatus = day => expenses.value[day.key]?.status ?? null
const allSaved = computed(() => days.value.length > 0 && days.value.every(day => dayStatus(day) === 'saved'))
const sumMeals = item => Object.values(item.meals ?? {}).reduce((sum, meal) => sum + (Number(meal.amount) || 0), 0)
const usedElsewhere = computed(() => {
  const others = Object.values(expenses.value).filter(item => item.day !== activeDay.value?.key)
  return { meals: others.reduce((sum, item) => sum + sumMeals(item), 0), pocket_money: others.reduce((sum, item) => sum + (Number(item.pocket_money?.amount) || 0), 0) }
})

const summaryOpen = ref(false)
const num = value => Number(value) || 0
const fileName = attachment => (Array.isArray(attachment) ? attachment[0] : attachment)?.name ?? null
const claimed = item => !!item && !item.paid_by_others && num(item.amount) > 0
const toItem = (title, item, extra = {}) => ({ title, amount: num(item.amount), currency: item.currency, note: item.description, attachment: fileName(item.attachment), ...extra })

const summaryDays = computed(() => days.value.map(day => {
  const entry = expenses.value[day.key]
  if (!entry) return null
  const items = [
    ...['breakfast', 'lunch', 'dinner'].filter(key => claimed(entry.meals?.[key])).map(key => toItem(key[0].toUpperCase() + key.slice(1), entry.meals[key])),
    claimed(entry.pocket_money) && toItem('Pocket Money', entry.pocket_money),
    claimed(entry.ticket) && toItem('Ticket', entry.ticket),
    claimed(entry.accommodation) && toItem('Accommodation', entry.accommodation, { note: [entry.accommodation.hotel_name, entry.accommodation.room_type].filter(Boolean).join(' · ') || entry.accommodation.description }),
    claimed(entry.laundry) && toItem('Laundry', entry.laundry),
    claimed(entry.telephone) && toItem('Telephone', entry.telephone),
    ...(entry.transports ?? []).map((item, index) => ({ item, index })).filter(({ item }) => claimed(item)).map(({ item, index }) => toItem(`Transport ${item.kind === 'perjalanan' ? 'Travel' : 'Others'} ${entry.transports.slice(0, index + 1).filter(entryItem => entryItem.kind === item.kind).length}`, item)),
    ...(entry.others ?? []).map((item, index) => claimed(item) && toItem(`Other ${index + 1}`, item)),
  ].filter(Boolean)
  return items.length ? { key: day.key, title: `Day ${day.key}`, date: day.date, effective: day.effective, items } : null
}).filter(Boolean))

const closeEntry = () => { expenseEntryOpen.value = false; selectedDay.value = null }
const onSaveDraft = payload => { expenses.value[payload.day] = { ...payload, status: 'draft' }; closeEntry() }
const onSaveExpense = payload => {
  expenses.value[payload.day] = { ...payload, status: 'saved' }
  const next = days.value.find(day => day.key === payload.day + 1)
  if (next) onSelectDay(next)
  else { closeEntry(); summaryOpen.value = true }
}
const onSubmitExpense = () => console.log('Submit TER:', info.value?.travel_order, expenses.value)

watch(() => props.modelValue, open => { if (!open) selectedDay.value = null })
</script>

<template>
  <VDialog :model-value="modelValue" max-width="1000" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <div class="d-flex align-center justify-space-between px-4 py-3">
  <div class="d-flex align-center ga-2">
    <VBtn icon="ri-arrow-left-line" variant="text" size="small" @click="close"/>
    <span class="text-h6 font-weight-bold">Travel Expense Report</span>
  </div>
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
            <VCard v-for="day in days" :key="day.key" link rounded="lg" variant="text" :class="['px-3 py-2', selectedDay === day.key ? 'bg-white elevation-2' : 'bg-grey-lighten-3']" @click="onSelectDay(day)">
              <div class="text-body-2 font-weight-medium">{{ day.title }}</div>
              <div class="text-body-2 text-medium-emphasis">{{ day.subtitle }}</div>
              <VChip v-if="dayStatus(day)" :color="dayStatus(day) === 'saved' ? 'success' : 'warning'" variant="tonal" size="x-small" class="mt-1">{{ dayStatus(day) === 'saved' ? 'Saved' : 'Draft' }}</VChip>
            </VCard>
          </div>
          <VDivider />
          <div class="d-flex justify-end pa-4 bg-grey-lighten-5">
            <VBtn color="primary" variant="flat" rounded="md" class="text-none font-weight-semibold" :disabled="!allSaved" @click="onViewSummary">View Summary</VBtn>
          </div>
        </VCard>
      </VCardText>
      <ExpenseEntryDialog v-model="expenseEntryOpen" :day="activeDay" :total-days="days.length" :initial="expenses[activeDay?.key]" :is-last="isLastDay" :used-elsewhere="usedElsewhere" @save="onSaveExpense" @draft="onSaveDraft" />
      <VDialog v-model="summaryOpen" max-width="1400" width="95%">
        <TravelExpenseSummary :days="summaryDays" @back="summaryOpen = false" @submit="onSubmitExpense" />
      </VDialog>
    </VCard>
  </VDialog>
</template>