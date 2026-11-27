<script setup>
import { ref, computed, watch } from 'vue'
import { useAuthStore } from '../../../../stores/auth.js'
import { useToastStore } from '../../../../stores/toast'
import { getMasterRateLimits, getTravelOrder } from '../../../travel-order/services/travelOrderService.js'
import { createTravelOrderExpenseReport } from '../../services/travelExpenseReportService.js'
import ExpenseEntryDialog from './ExpenseEntryDialog.vue'
import TravelExpenseSummary from './TravelExpenseSummary.vue'

const props = defineProps({ modelValue: { type: Boolean, default: false }, item: { type: Object, default: null } })
const emit = defineEmits(['update:modelValue', 'submitted'])
const authStore = useAuthStore()
const toast = useToastStore()
const selectedDay = ref(null)

const tripTypeLabels = { group: 'Group Trip', individual: 'Individual Trip', annual_ticket: 'Annual Ticket' }
const normalize = value => String(value ?? '').trim().toLowerCase().replaceAll(' ', '_')
const tripTypeLabel = type => tripTypeLabels[normalize(type)] ?? type ?? '-'
const tripTypeColor = type => {
  const key = normalize(type)
  return key === 'group' ? 'info' : key === 'annual_ticket' ? 'success' : 'primary'
}
const toDate = value => {
  if (!value) return null
  const parsed = new Date(value)
  if (Number.isNaN(parsed.getTime())) return null
  parsed.setHours(0, 0, 0, 0)
  return parsed
}
const formatShort = date => date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
const shortTime = value => (value ? String(value).slice(0, 5) : null)
const formatLong = date => date.toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' })

const info = computed(() => {
  const to = props.item
  if (!to) return null
  const start = toDate(to.departure_date)
  const end = toDate(to.return_date)
  const sameMonth = start && end && start.getMonth() === end.getMonth() && start.getFullYear() === end.getFullYear()
  const period = !start || !end ? '-' : sameMonth ? `${start.toLocaleDateString('en-GB', { day: '2-digit' })}–${end.toLocaleDateString('en-GB', { day: '2-digit' })} ${start.toLocaleDateString('en-GB', { month: 'short', year: 'numeric' })}` : `${formatShort(start)} – ${formatShort(end)}`
  const etd = shortTime(to.etd)
  const eta = shortTime(to.eta)
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
    { label: 'EXPENSE REPORT ID', value: data.expense_report_id },
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
    return { key: index + 1, type, title: `Day ${index + 1} · ${type}`, subtitle: time ? `${formatLong(date)} · Effective ${time}` : formatLong(date), date: formatLong(date), iso: isoDate(date), effective: time }
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
const showOf = item => claimed(item) || item?.payment_mode === 'paid_by_other'
const toItem = (title, item, extra = {}) => {
  const payFor = Array.isArray(item.pay_for) ? item.pay_for : []
  return { title, amount: num(item.amount), currency: item.currency, manualReview: String(item?.currency ?? 'IDR') !== (entitlements.value?.currency ?? 'IDR'), note: item.description, attachment: fileName(item.attachment) ?? item.existing_attachment_name ?? '', file: Array.isArray(item.attachment) ? (item.attachment[0] ?? null) : (item.attachment ?? null), attachmentUrl: item.attachment_url ?? item.existing_attachment_url ?? null, paymentMode: item.payment_mode ?? 'myself', notClaimedByMe: item.payment_mode === 'paid_by_other', paidByName: item.paid_by_name ?? '', payForCount: payFor.length, eligible: item.payment_mode === 'paid_by_other' ? 0 : undefined, ...extra }
}

const summaryDays = computed(() => days.value.map(day => {
  const entry = expenses.value[day.key]
  if (!entry) return null
  const items = [
    ...['breakfast', 'lunch', 'dinner'].filter(key => showOf(entry.meals?.[key])).map(key => toItem(key[0].toUpperCase() + key.slice(1), entry.meals[key])),
    showOf(entry.pocket_money) && toItem('Pocket Money', entry.pocket_money),
    showOf(entry.ticket) && toItem('Ticket', entry.ticket),
    showOf(entry.accommodation) && toItem('Accommodation', entry.accommodation, { note: [entry.accommodation.hotel_name, entry.accommodation.room_type].filter(Boolean).join(' · ') || entry.accommodation.description }),
    showOf(entry.laundry) && toItem('Laundry', entry.laundry),
    showOf(entry.telephone) && toItem('Telephone', entry.telephone),
    showOf(entry.office_telephone) && toItem('Office Telephone', entry.office_telephone),
    ...(entry.transports ?? []).filter(item => showOf(item)).map(item => toItem('Transport', item)),
    ...(entry.others ?? []).filter(item => showOf(item)).map(item => toItem('Other', item)),
  ].filter(Boolean)
  return items.length ? { key: day.key, title: `Day ${day.key}`, date: day.date, effective: day.effective, items } : null
}).filter(Boolean))

const closeEntry = () => { expenseEntryOpen.value = false; selectedDay.value = null }
const onSelectDayFromEntry = ({ target, payload, hasData }) => {
  if (hasData) expenses.value[payload.day] = { ...payload, status: 'draft' }
  const targetDay = days.value.find(day => day.key === target)
  if (targetDay) onSelectDay(targetDay)
}
const onSaveExpense = payload => {
  expenses.value[payload.day] = { ...payload, status: 'saved' }
  const next = days.value.find(day => day.key === payload.day + 1)
  if (next) onSelectDay(next)
  else { closeEntry(); summaryOpen.value = true }
}
const TELEPHONE_BUDGET = 15000
const submitting = ref(false)
const isoDate = date => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
const fileOf = attachment => (Array.isArray(attachment) ? attachment[0] : attachment) ?? null
const expenseIncluded = item => claimed(item) || item?.payment_mode === 'paid_by_other'
const mealPerMealValue = computed(() => { const value = entitlements.value?.meal_limit_per_meal; return value == null ? null : Number(value) })
const pocketBudgetValue = computed(() => { const value = entitlements.value?.pocket_money_limit; return value == null ? null : Number(value) })
const isFixedCategory = category => ['breakfast', 'lunch', 'dinner', 'pocket_money', 'telephone'].includes(category)

// Eligible per item mengikuti tampilan UI: fixed basis dari entitlement, receipt-based dari eligible estimate;
// paid by other = null (tidak diklaim) dan currency beda tujuan = null (manual OC review)
const eligibleOfExpense = (item, category) => {
  if (item?.payment_mode === 'paid_by_other') return null
  if (String(item?.currency ?? 'IDR') !== (entitlements.value?.currency ?? 'IDR')) return null
  if (item?.payment_mode === 'pay_for_others' && isFixedCategory(category)) return null
  if (['breakfast', 'lunch', 'dinner'].includes(category)) return mealAtCost() ? null : mealPerMealValue.value
  if (category === 'pocket_money') return pocketBudgetValue.value
  if (category === 'telephone') return TELEPHONE_BUDGET
  return num(item?.eligible_estimate) || null
}
const mealAtCost = () => entitlements.value?.meal_basis === 'at_cost' && mealPerMealValue.value == null

const toExpense = (dayKey, category, item, extra = {}, files = []) => {
  const file = fileOf(item?.attachment)
  const key = `${dayKey}-${category}${extra.item_index ? `-${extra.item_index}` : ''}`
  const expense = {
    category,
    item_index: extra.item_index ?? 1,
    kind: extra.kind ?? null,
    description: item.description ?? null,
    currency: item.currency ?? 'IDR',
    actual_amount: num(item.amount),
    eligible_amount: eligibleOfExpense(item, category),
    manual_review: String(item?.currency ?? 'IDR') !== (entitlements.value?.currency ?? 'IDR'),
    payment_mode: item.payment_mode ?? 'myself',
    paid_by: item.paid_by ?? null,
    paid_for: Array.isArray(item.pay_for) ? item.pay_for : [],
    dinner_type: item.dinner_type ?? null,
    justification: item.justification ?? null,
    hotel_name: item.hotel_name ?? null,
    room_type: item.room_type ?? null,
    hotel_standard: item.hotel_standard ?? null,
    attachment_key: file ? key : null,
    existing_attachment_path: item.existing_attachment_path ?? null,
    existing_attachment_name: item.existing_attachment_name ?? null,
    existing_attachment_url: item.existing_attachment_url ?? null,
  }
  if (expense.attachment_key && file) files.push({ key, file })
  return expense
}

const buildDayExpenses = (dayKey, entry, files) => {
  const list = []
  const push = (category, item, extra = {}) => { if (expenseIncluded(item)) list.push(toExpense(dayKey, category, item, extra, files)) }

  ;['breakfast', 'lunch', 'dinner'].forEach(meal => push(meal, entry.meals?.[meal], { dinner_type: meal === 'dinner' ? entry.meals?.[meal]?.dinner_type ?? null : null, justification: meal === 'dinner' ? entry.meals?.[meal]?.justification ?? null : null }))
  push('pocket_money', entry.pocket_money)
  push('ticket', entry.ticket, { kind: entry.ticket?.ticket_type ?? null })
  push('accommodation', entry.accommodation)
  push('laundry', entry.laundry)
  push('telephone', entry.telephone)
  push('office_telephone', entry.office_telephone)
  ;(entry.transports ?? []).forEach((item, index) => push('transport', item, { item_index: index + 1, kind: item.kind === 'perjalanan' ? 'travel' : 'others' }))
  ;(entry.others ?? []).forEach((item, index) => push('other', item, { item_index: index + 1 }))

  // Entitlement tetap per hari tetap direkam walaupun tidak diklaim (eligible akumulasi per hari di summary)
  const virtualFixed = (category, eligible) => {
    if (eligible == null || list.some(expense => expense.category === category)) return
    list.push({ category, item_index: 1, kind: null, description: null, currency: entitlements.value?.currency ?? 'IDR', actual_amount: 0, eligible_amount: entry.different_countries ? null : eligible, manual_review: !!entry.different_countries, payment_mode: 'myself', paid_by: null, paid_for: [], dinner_type: null, justification: null, hotel_name: null, room_type: null, hotel_standard: null, attachment_key: null })
  }
  if (!mealAtCost() && mealPerMealValue.value != null) { virtualFixed('breakfast', mealPerMealValue.value); virtualFixed('lunch', mealPerMealValue.value); virtualFixed('dinner', mealPerMealValue.value) }
  if ((pocketBudgetValue.value ?? 0) > 0) virtualFixed('pocket_money', pocketBudgetValue.value)
  virtualFixed('telephone', TELEPHONE_BUDGET)
  return list
}

const buildTerPayload = files => days.value.map(day => {
  const entry = expenses.value[day.key]
  if (!entry) return null
  const dayExpenses = buildDayExpenses(day.key, entry, files)
  if (!dayExpenses.length) return null
  return { day_number: day.key, date: day.iso, type: String(day.type ?? 'stay').toLowerCase(), effective_time: day.effective ?? null, different_countries: !!entry.different_countries, expenses: dayExpenses }
}).filter(Boolean)

// Draft & submit memakai payload yang sama - draft boleh hanya sebagian hari yang terisi.
// Setelah draft/submit, semua modal ditutup dan kembali ke index
const saveTer = async action => {
  if (submitting.value) return
  submitting.value = true
  try {
    const files = []
    const payloadDays = buildTerPayload(files)
    if (!payloadDays.length) {
      toast.error(action === 'draft' ? 'No expense has been filled in yet.' : 'No expense has been claimed yet.')
      return
    }
    await createTravelOrderExpenseReport(travelOrderId.value, action, payloadDays, files)
    if (action === 'draft') savedAsDraft.value = true
    toast.success(action === 'draft' ? 'Travel Expense Report draft has been saved successfully.' : 'Travel Expense Report has been submitted successfully.')
    summaryOpen.value = false
    close()
    emit('submitted')
  } catch (err) {
    toast.error(err.response?.data?.message || err.response?.data?.error || (action === 'draft' ? 'Failed to save the draft.' : 'Failed to submit the Travel Expense Report.'))
  } finally {
    submitting.value = false
  }
}
const onSubmitExpense = () => saveTer('submit')
// Tombol Draft di dialog hari: simpan progres ke backend, tutup semua modal, kembali ke index
const onSaveDraft = payload => {
  expenses.value[payload.day] = { ...payload, status: 'draft' }
  closeEntry()
  saveTer('draft')
}

// Resume draft: hidupkan kembali rincian yang tersimpan ke state per hari
const hydrateDraft = () => {
  const ter = props.item?.ter
  if (!ter || (!ter.days?.length && !ter.expenses?.length)) return
  const byDay = {}
  ;(ter.days ?? []).forEach(day => {
    byDay[day.day_number] = { day: day.day_number, status: 'saved', different_countries: !!day.different_countries, meals: {}, pocket_money: null, ticket: null, accommodation: null, laundry: null, telephone: null, office_telephone: null, transports: [], others: [] }
  })
  ;(ter.expenses ?? []).forEach(row => {
    const entry = byDay[row.day_number]
    if (!entry) return
    const item = {
      currency: row.currency ?? 'IDR',
      amount: row.actual_amount != null && Number(row.actual_amount) > 0 ? String(row.actual_amount) : '',
      description: row.description ?? '',
      paid_by_others: false,
      attachment: null,
      existing_attachment_path: row.attachment_path ?? null,
      existing_attachment_name: row.attachment_name ?? null,
      existing_attachment_url: row.attachment_url ?? null,
      payment_mode: row.payment_mode ?? 'myself',
      paid_by: row.paid_by ?? null,
      paid_by_name: row.payer?.name ?? '',
      pay_for: Array.isArray(row.paid_for) ? row.paid_for : [],
      eligible_estimate: row.eligible_amount != null ? String(row.eligible_amount) : '',
    }
    if (['breakfast', 'lunch', 'dinner'].includes(row.category)) {
      if (row.category === 'dinner') { item.dinner_type = row.dinner_type ?? 'Regular Dinner'; item.justification = row.justification ?? '' }
      entry.meals[row.category] = item
    } else if (row.category === 'pocket_money') entry.pocket_money = item
    else if (row.category === 'ticket') { item.ticket_type = row.kind ?? ''; entry.ticket = item }
    else if (row.category === 'accommodation') { item.hotel_name = row.hotel_name ?? ''; item.room_type = row.room_type ?? ''; item.hotel_standard = row.hotel_standard ?? ''; entry.accommodation = item }
    else if (row.category === 'laundry') entry.laundry = item
    else if (row.category === 'telephone') entry.telephone = item
    else if (row.category === 'office_telephone') entry.office_telephone = item
    else if (row.category === 'transport') entry.transports.push({ ...item, kind: row.kind === 'travel' ? 'perjalanan' : 'others' })
    else if (row.category === 'other') entry.others.push(item)
  })
  Object.entries(byDay).forEach(([key, entry]) => { expenses.value[key] = entry })
}

const entitlements = ref(null)
const loadEntitlements = async () => {
  entitlements.value = null
  const item = props.item
  if (!item?.region_id || !item?.departure_date || !item?.return_date) return
  try {
    const response = await getMasterRateLimits({ region_id: item.region_id, destination: item.travel_to, departure_date: item.departure_date, return_date: item.return_date })
    entitlements.value = response?.data ?? response ?? null
  } catch { entitlements.value = null }
}

const isGroupTrip = computed(() => normalize(props.item?.trip_type) === 'group')
// Untuk resume draft: item.id adalah id TER, bukan id Travel Order
const travelOrderId = computed(() => props.item?.travel_order_id ?? props.item?.id)
// Chip status di kartu info: New (claim baru) / Draft (sudah ada draft) / Revision Required
const savedAsDraft = ref(false)
const statusChip = computed(() => {
  const status = normalize(props.item?.ter?.status)
  if (status === 'revision_required') return { text: 'Revision Required', color: 'info' }
  if (status === 'draft' || savedAsDraft.value) return { text: 'Draft', color: 'secondary' }
  return { text: 'New', color: 'warning' }
})
const group = ref(null)
const loadGroup = async () => {
  group.value = null
  if (!isGroupTrip.value || !travelOrderId.value) return
  try {
    const response = await getTravelOrder(travelOrderId.value)
    group.value = response?.data?.group ?? response?.group ?? null
  } catch { group.value = null }
}
const groupMembers = computed(() => (group.value?.members ?? []).map(member => ({
  id: Number(member.user_id ?? member.user?.id),
  name: member.user?.name ?? '-',
  roles: (member.user?.roles ?? []).map(role => role?.name ?? role),
  department: member.department_name ?? 'No Department',
})))

watch(() => props.modelValue, open => { if (open) { selectedDay.value = null; expenses.value = {}; hydrateDraft(); loadEntitlements(); loadGroup() } else selectedDay.value = null })
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
            <VChip :color="statusChip.color" variant="tonal" size="small">{{ statusChip.text }}</VChip>
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
              <div class="text-body-2 font-weight-bold">{{ day.title }}</div>
              <div class="text-body-2 text-medium-emphasis">{{ day.subtitle }}</div>
            </VCard>
          </div>
          <VDivider />
          <div class="d-flex justify-end pa-4 bg-grey-lighten-5">
            <VBtn color="primary" variant="flat" rounded="md" class="text-none font-weight-semibold" :disabled="!allSaved" @click="onViewSummary">View Summary</VBtn>
          </div>
        </VCard>
      </VCardText>
      <ExpenseEntryDialog v-model="expenseEntryOpen" :day="activeDay" :total-days="days.length" :initial="expenses[activeDay?.key]" :is-last="isLastDay" :used-elsewhere="usedElsewhere" :entitlements="entitlements" :ticket-arrangement="item?.ticket_arrangement" :accommodation-arrangement="item?.accommodation_arrangement" :group-members="groupMembers" :is-group="isGroupTrip" @save="onSaveExpense" @draft="onSaveDraft" @select-day="onSelectDayFromEntry" />
      <VDialog v-model="summaryOpen" max-width="1280" width="90%">
        <TravelExpenseSummary :days="summaryDays" :total-days="days.length" :entitlements="entitlements" :advance-meal="item?.advance_meal_allowance ?? 0" :advance-pocket="item?.advance_pocket_money ?? 0" @back="summaryOpen = false" @submit="onSubmitExpense" />
      </VDialog>
    </VCard>
  </VDialog>
</template>
