<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({ modelValue: { type: Boolean, default: false }, day: { type: Object, default: null }, totalDays: { type: Number, default: 0 }, initial: { type: Object, default: null }, isLast: { type: Boolean, default: false }, usedElsewhere: { type: Object, default: () => ({ meals: 0, pocket_money: 0 }) }, entitlements: { type: Object, default: null }, ticketArrangement: { type: String, default: '' }, accommodationArrangement: { type: String, default: '' } })
const emit = defineEmits(['update:modelValue', 'save', 'draft', 'select-day'])

const DINNER_AT_HOTEL = 'Dinner at Hotel'
const DINNER_TYPES = ['Regular Dinner', DINNER_AT_HOTEL]
const TELEPHONE_BUDGET = 15000
const LAUNDRY_FROM_DAY = 6
const formatIDR = value => `IDR ${new Intl.NumberFormat('id-ID').format(value)}`
const formatMoney = (value, currency = 'IDR') => (value == null ? '—' : `${currency ?? 'IDR'} ${new Intl.NumberFormat('id-ID').format(Number(value))}`)

const createItem = (amount = '', description = '') => ({ currency: 'IDR', amount: String(amount), description, paid_by_others: false, attachment: null })
const cloneItem = item => ({ ...item, attachment: item?.attachment ?? null })
const cloneForm = value => ({
  different_countries: !!value?.different_countries,
  meals: { breakfast: cloneItem(value?.meals?.breakfast ?? createItem()), lunch: cloneItem(value?.meals?.lunch ?? createItem()), dinner: value?.meals?.dinner ? { ...cloneItem(value.meals.dinner), dinner_type: value.meals.dinner.dinner_type ?? DINNER_TYPES[0], justification: value.meals.dinner.justification ?? '' } : { ...createItem(), dinner_type: DINNER_TYPES[0], justification: '' } },
  pocket_money: value?.pocket_money ? cloneItem(value.pocket_money) : createItem(),
  ticket: value?.ticket ? { ...cloneItem(value.ticket), ticket_type: value.ticket.ticket_type ?? '', eligible_estimate: value.ticket.eligible_estimate ?? '' } : { ...createItem(), ticket_type: '', eligible_estimate: '' },
  accommodation: value?.accommodation ? { ...cloneItem(value.accommodation), hotel_name: value.accommodation.hotel_name ?? '', room_type: value.accommodation.room_type ?? '', hotel_standard: value.accommodation.hotel_standard ?? '', eligible_estimate: value.accommodation.eligible_estimate ?? '' } : { ...createItem(), hotel_name: '', room_type: '', hotel_standard: '', eligible_estimate: '' },
  laundry: value?.laundry ? { ...cloneItem(value.laundry), eligible_estimate: value.laundry.eligible_estimate ?? '' } : { ...createItem(), eligible_estimate: '' },
  telephone: value?.telephone ? cloneItem(value.telephone) : createItem(),
  office_telephone: value?.office_telephone ? { ...cloneItem(value.office_telephone), eligible_estimate: value.office_telephone.eligible_estimate ?? '' } : { ...createItem(), eligible_estimate: '' },
  transports: (value?.transports ?? []).map(transport => ({ ...cloneItem(transport), eligible_estimate: transport?.eligible_estimate ?? '' })),
  others: (value?.others ?? []).map(other => ({ ...cloneItem(other), eligible_estimate: other?.eligible_estimate ?? '' })),
})
const form = ref(cloneForm(null))
watch(() => props.initial, value => { form.value = cloneForm(value) }, { immediate: true })

const dayNumber = computed(() => props.day?.key ?? 1)
const showLaundry = computed(() => props.totalDays >= LAUNDRY_FROM_DAY)
const laundryLocked = computed(() => dayNumber.value < LAUNDRY_FROM_DAY)
const breakfastLockReason = computed(() => {
  if (props.day?.type === 'Stay') return 'Breakfast is already provided by the hotel. No additional claim is allowed for this day.'
  if (props.day?.type === 'Return' && props.day?.effective) {
    const hours = Number(String(props.day.effective).split(':')[0])
    if (!Number.isNaN(hours) && hours >= 9) return 'Breakfast is not eligible because return time is 09:00 or later. No additional claim is allowed for this day.'
  }
  return ''
})
const breakfastLocked = computed(() => !!breakfastLockReason.value)
const isDirectPayment = arrangement => ['direct payment', 'reimbursable'].includes(String(arrangement ?? '').trim().toLowerCase())
const ticketClaimable = computed(() => isDirectPayment(props.ticketArrangement))
const accommodationClaimable = computed(() => isDirectPayment(props.accommodationArrangement))
const tripNights = computed(() => Math.max(0, (props.totalDays ?? 0) - 1))
const hotelStar = computed(() => { const star = props.entitlements?.hotel_star; return star == null ? null : Number(star) })
const hotelRatePerNight = computed(() => { const value = props.entitlements?.hotel_rate; return value == null ? null : Number(value) })
const hotelRateCurrency = computed(() => props.entitlements?.hotel_rate_currency ?? 'IDR')
const hotelEligibleTotal = computed(() => (hotelRatePerNight.value == null || tripNights.value <= 0) ? null : hotelRatePerNight.value * tripNights.value)
const accommodationNoOvernight = computed(() => tripNights.value <= 0)
const accommodationReturnLocked = computed(() => props.day?.type === 'Return')
const accommodationDayLocked = computed(() => dayNumber.value !== 1)
const accommodationNotEligible = computed(() => accommodationNoOvernight.value || accommodationReturnLocked.value || accommodationDayLocked.value)
const isNotClaimable = c => (c.key === 'ticket' && !ticketClaimable.value) || (c.key === 'accommodation' && !accommodationClaimable.value)
const usedMeals = computed(() => Number(props.usedElsewhere?.meals) || 0)
const usedPocket = computed(() => Number(props.usedElsewhere?.pocket_money) || 0)
const moneyCurrency = computed(() => props.entitlements?.currency ?? 'IDR')
const isManualReview = item => !!item && String(item.currency ?? 'IDR') !== moneyCurrency.value
const currencyLocked = computed(() => !form.value.different_countries)
const currencyItems = computed(() => (currencyLocked.value ? ['IDR'] : CURRENCIES))
watch(() => form.value.different_countries, enabled => {
  if (enabled) return
  const items = [form.value.meals.breakfast, form.value.meals.lunch, form.value.meals.dinner, form.value.pocket_money, form.value.ticket, form.value.accommodation, form.value.laundry, form.value.telephone, form.value.office_telephone, ...form.value.transports, ...form.value.others]
  items.forEach(item => { item.currency = 'IDR' })
})
const mealPerMeal = computed(() => { const value = props.entitlements?.meal_limit_per_meal; return value == null ? null : Number(value) })
const pocketBudget = computed(() => { const value = props.entitlements?.pocket_money_limit; return value == null ? null : Number(value) })
// Limit 0 = tujuan tidak menerima daily allowance -> kategori tampil Not Eligible, bukan "IDR 0"
const pocketNotEligible = computed(() => pocketBudget.value === 0)

const mealLimit = () => { const budget = mealPerMeal.value; const max = budget == null ? null : Math.max(0, budget - usedMeals.value); return { budgetLabel: 'BUDGET / MEAL', budget, max, remaining: max, note: `Meals claimed on other days: ${formatIDR(usedMeals.value)}. Maximum input for this day is adjusted automatically.` } }
const pocketLimit = computed(() => { const budget = pocketBudget.value; const max = budget == null ? null : Math.max(0, budget - usedPocket.value); return { budgetLabel: 'TRIP BUDGET', budget, max, remaining: max, note: `Pocket money claimed on other days: ${formatIDR(usedPocket.value)}.` } })

const claimed = item => !!item && !item.paid_by_others && Number(item.amount) > 0
const hasAttachment = item => !!(Array.isArray(item.attachment) ? item.attachment.length : item.attachment)
const touched = item => !!item && (item.paid_by_others || Number(item.amount) > 0 || !!String(item.description ?? '').trim())
const errors = computed(() => {
  const map = {}
  const check = (key, item, { max = null } = {}) => { if (!claimed(item)) return; if (max !== null && Number(item.amount) > max) map[key] ??= `Amount exceeds the maximum allowed (${formatIDR(max)}).`; if (!String(item.description ?? '').trim()) map[key] ??= 'Description is required.' }
  Object.entries(form.value.meals).forEach(([key, item]) => { if (key === 'breakfast' && breakfastLocked.value) return; check(`meals.${key}`, item, { max: mealLimit().max, fixed: true }) })
  if (claimed(form.value.meals.dinner) && form.value.meals.dinner.dinner_type === DINNER_AT_HOTEL && !String(form.value.meals.dinner.justification ?? '').trim()) map['meals.dinner'] ??= 'Reason / Justification is required.'
  check('pocket_money', form.value.pocket_money, { max: pocketLimit.value.max, fixed: true })
  if (ticketClaimable.value) check('ticket', form.value.ticket)
  if (accommodationClaimable.value && !accommodationNotEligible.value) check('accommodation', form.value.accommodation)
  if (showLaundry.value && !laundryLocked.value) check('laundry', form.value.laundry)
  check('telephone', form.value.telephone, { max: TELEPHONE_BUDGET })
  check('office_telephone', form.value.office_telephone)
  form.value.transports.forEach((item, index) => check(`transports.${index}`, item))
  form.value.others.forEach((item, index) => check(`others.${index}`, item))
  return map
})
const attachmentViolations = computed(() => {
  const map = {}
  const requireAttach = (key, item) => { if (!claimed(item) || hasAttachment(item)) return; map[key] ??= 'Attachment is required.' }
  requireAttach('ticket', form.value.ticket)
  if (accommodationClaimable.value && !accommodationNotEligible.value) requireAttach('accommodation', form.value.accommodation)
  if (showLaundry.value && !laundryLocked.value) requireAttach('laundry', form.value.laundry)
  form.value.transports.forEach((item, index) => requireAttach(`transports.${index}`, item))
  form.value.others.forEach((item, index) => requireAttach(`others.${index}`, item))
  return map
})
const errorOf = key => errors.value[key] ?? ''
const submitAttempted = ref(false)
// Error deskripsi/justifikasi tidak mengunci tombol save - tampil & divalidasi setelah klik save
const canSave = computed(() => Object.values(errors.value).every(err => !err.startsWith('Description is required') && !err.includes('Justification is required')))
const formHasData = value => value.different_countries || value.transports.length > 0 || value.others.length > 0 || Object.values(value.meals).some(touched) || touched(value.pocket_money) || touched(value.ticket) || touched(value.accommodation) || touched(value.laundry) || touched(value.telephone) || touched(value.office_telephone) || value.transports.some(touched) || value.others.some(touched)

const SIMPLE_CLAIM_KEYS = ['breakfast', 'lunch', 'dinner', 'pocket_money']
const isSimpleClaim = c => SIMPLE_CLAIM_KEYS.includes(c.key)
const itemOf = c => (c.key === 'pocket_money' ? form.value.pocket_money : form.value.meals[c.key])
const errorKeyOf = c => (c.key === 'pocket_money' ? 'pocket_money' : `meals.${c.key}`)
const errOf = c => errorOf(errorKeyOf(c))
// Pesan error hanya tampil setelah user menekan save
const showAfterAttempt = err => (submitAttempted.value ? err : '')
const descError = c => showAfterAttempt(errOf(c) === 'Description is required.' ? errOf(c) : '')
const dinnerJustificationError = () => showAfterAttempt(errorOf('meals.dinner') === 'Reason / Justification is required.' ? errorOf('meals.dinner') : '')
const amountError = c => showAfterAttempt(errOf(c).startsWith('Amount exceeds') ? errOf(c) : '')
const attachError = c => (submitAttempted.value && attachmentViolations.value[errorKeyOf(c)] ? attachmentViolations.value[errorKeyOf(c)] : '')
const amountDisplay = item => (item.amount === '' || item.amount == null ? '' : new Intl.NumberFormat('id-ID').format(Number(item.amount)))
const simpleMaxOf = c => (c.key === 'pocket_money' ? pocketLimit.value.max : mealLimit().max)
const setClampedAmount = (item, value, max) => { const digits = String(value).replace(item.currency === 'IDR' ? /[^0-9]/g : /[^0-9.]/g, ''); item.amount = max != null && Number(digits) > max ? String(max) : digits }
const blockAtMaxKeydown = (event, current, max) => { if (max == null || !/^[0-9]$/.test(event.key)) return; const el = event.target; if (el && el.selectionStart !== el.selectionEnd) return; if (Number(current || '0') >= max) event.preventDefault() }
const onAmountInput = (c, value) => setClampedAmount(itemOf(c), value, simpleMaxOf(c))
const onTelephoneAmountInput = value => setClampedAmount(form.value.telephone, value, TELEPHONE_BUDGET)
const firstFileName = attachment => (Array.isArray(attachment) ? attachment : attachment ? [attachment] : [])[0]?.name ?? ''
const onAttachment = (c, event) => { itemOf(c).attachment = Array.from(event.target.files) }

const TICKET_TYPES = ['Airplane', 'Train', 'Ferry']
const CURRENCIES = ['IDR', 'SGD', 'MYR', 'USD', 'EUR', 'JPY']
const HOTEL_STANDARDS = ['1 Star', '2 Star', '3 Star', '4 Star', '5 Star']
const ticketErr = () => errorOf('ticket')
const ticketDescError = () => showAfterAttempt(ticketErr() === 'Description is required.' ? ticketErr() : '')
const ticketAttachError = () => (submitAttempted.value && attachmentViolations.value.ticket ? 'Attachment is required.' : '')
const accErr = () => errorOf('accommodation')
const accDescError = () => showAfterAttempt(accErr() === 'Description is required.' ? accErr() : '')
const accAttachError = () => (submitAttempted.value && attachmentViolations.value.accommodation ? 'Attachment is required.' : '')
const launErr = () => errorOf('laundry')
const launDescError = () => showAfterAttempt(launErr() === 'Description is required.' ? launErr() : '')
const launAttachError = () => (submitAttempted.value && attachmentViolations.value.laundry ? 'Attachment is required.' : '')
const telErr = () => errorOf('telephone')
const telDescError = () => showAfterAttempt(telErr() === 'Description is required.' ? telErr() : '')
const telAmountError = () => showAfterAttempt(telErr().startsWith('Amount exceeds') ? telErr() : '')
const telAttachError = () => ''
const otErr = () => errorOf('office_telephone')
const otDescError = () => showAfterAttempt(otErr() === 'Description is required.' ? otErr() : '')
const otAttachError = () => ''
const transportErr = index => errorOf(`transports.${index}`)
const transportDescError = index => showAfterAttempt(transportErr(index) === 'Description is required.' ? transportErr(index) : '')
const transportAttachError = index => (submitAttempted.value && attachmentViolations.value[`transports.${index}`] ? 'Attachment is required.' : '')
const otherErr = index => errorOf(`others.${index}`)
const otherDescError = index => showAfterAttempt(otherErr(index) === 'Description is required.' ? otherErr(index) : '')
const otherAttachError = index => (submitAttempted.value && attachmentViolations.value[`others.${index}`] ? 'Attachment is required.' : '')
const amountDisplayFor = item => { const value = item.amount; if (value === '' || value == null || Number.isNaN(Number(value))) return ''; return new Intl.NumberFormat(item.currency === 'IDR' ? 'id-ID' : 'en-US', { maximumFractionDigits: 2 }).format(Number(value)) }
const onAmountInputFor = (item, value) => { item.amount = item.currency === 'IDR' ? String(value).replace(/[^0-9]/g, '') : String(value).replace(/[^0-9.]/g, '') }
const onAmountInputWithEstimate = (item, value) => { onAmountInputFor(item, value); item.eligible_estimate = item.amount === '' ? '' : item.amount }
const onAccommodationAmountInput = value => {
  const item = form.value.accommodation
  const max = hotelEligibleTotal.value
  const digits = String(value).replace(item.currency === 'IDR' ? /[^0-9]/g : /[^0-9.]/g, '')
  item.amount = max != null && Number(digits) > max ? String(max) : digits
  item.eligible_estimate = item.amount === '' ? '' : item.amount
}
const estimateDisplayFor = item => (isManualReview(item) ? 'Manual Review' : item.eligible_estimate === '' ? '' : new Intl.NumberFormat(item.currency === 'IDR' ? 'id-ID' : 'en-US', { maximumFractionDigits: 2 }).format(Number(item.eligible_estimate)))
const onEstimateInputFor = (item, value) => { item.eligible_estimate = String(value).replace(item.currency === 'IDR' ? /[^0-9]/g : /[^0-9.]/g, '') }

const sumItem = item => (claimed(item) ? Number(item.amount) || 0 : 0)
const sumList = list => (list ?? []).reduce((sum, item) => sum + sumItem(item), 0)
const mealAtCost = computed(() => props.entitlements?.meal_basis === 'at_cost' && mealPerMeal.value == null)
const categories = computed(() => [
  { key: 'breakfast', label: 'Breakfast', actual: sumItem(form.value.meals.breakfast), eligible: mealPerMeal.value, atCost: mealAtCost.value, currency: moneyCurrency.value, manualReview: isManualReview(form.value.meals.breakfast) },
  { key: 'lunch', label: 'Lunch', actual: sumItem(form.value.meals.lunch), eligible: mealPerMeal.value, atCost: mealAtCost.value, currency: moneyCurrency.value, manualReview: isManualReview(form.value.meals.lunch) },
  { key: 'dinner', label: 'Dinner', actual: sumItem(form.value.meals.dinner), eligible: mealPerMeal.value, atCost: mealAtCost.value, currency: moneyCurrency.value, manualReview: isManualReview(form.value.meals.dinner) },
  { key: 'pocket_money', label: 'Pocket Money', actual: sumItem(form.value.pocket_money), eligible: pocketNotEligible.value ? null : pocketBudget.value, currency: moneyCurrency.value, manualReview: isManualReview(form.value.pocket_money), notEligible: pocketNotEligible.value },
  { key: 'ticket', label: 'Ticket', actual: sumItem(form.value.ticket), eligible: Number(form.value.ticket.eligible_estimate) || null, hasData: touched(form.value.ticket), manualReview: isManualReview(form.value.ticket) },
  { key: 'accommodation', label: 'Accommodation', actual: sumItem(form.value.accommodation), eligible: Number(form.value.accommodation.eligible_estimate) || null, hasData: touched(form.value.accommodation), manualReview: isManualReview(form.value.accommodation) },
  ...(showLaundry.value ? [{ key: 'laundry', label: 'Laundry', actual: sumItem(form.value.laundry), eligible: Number(form.value.laundry.eligible_estimate) || null, availableFromDay: LAUNDRY_FROM_DAY, hasData: touched(form.value.laundry), manualReview: isManualReview(form.value.laundry) }] : []),
  { key: 'telephone', label: 'Telephone', actual: sumItem(form.value.telephone) + sumItem(form.value.office_telephone), eligible: TELEPHONE_BUDGET, hasData: touched(form.value.telephone) || touched(form.value.office_telephone), manualReview: isManualReview(form.value.telephone) || isManualReview(form.value.office_telephone) },
  { key: 'transports', label: 'Transport', actual: sumList(form.value.transports), eligible: null, hasData: form.value.transports.some(touched), manualReview: form.value.transports.some(isManualReview) },
  { key: 'others', label: 'Other', actual: sumList(form.value.others), eligible: null, hasData: form.value.others.some(touched), manualReview: form.value.others.some(isManualReview) },
])
const actionOf = c => (isNotClaimable(c) || (c.key === 'breakfast' && breakfastLocked.value) || (c.key === 'pocket_money' && pocketNotEligible.value) || (c.key === 'accommodation' && accommodationNotEligible.value) ? 'View' : c.availableFromDay && laundryLocked.value ? 'View' : c.hasData ? 'Edit' : 'Add')
const actualCurrencyOf = c => {
  if (['breakfast', 'lunch', 'dinner'].includes(c.key)) return form.value.meals[c.key].currency
  if (c.key === 'pocket_money') return form.value.pocket_money.currency
  if (c.key === 'ticket') return form.value.ticket.currency
  if (c.key === 'accommodation') return form.value.accommodation.currency
  if (c.key === 'laundry') return form.value.laundry.currency
  if (c.key === 'telephone') return form.value.telephone.currency === form.value.office_telephone.currency ? form.value.telephone.currency : null
  const list = c.key === 'transports' ? form.value.transports : form.value.others
  return list.length && list.every(item => item.currency === list[0].currency) ? list[0].currency : null
}
const formatActual = c => { const currency = actualCurrencyOf(c); return currency ? formatMoney(c.actual, currency) : 'Mixed' }

const expanded = ref(null)
watch(() => props.modelValue, open => { if (open) { expanded.value = null; submitAttempted.value = false } })
watch(() => props.day, () => { form.value = cloneForm(props.initial); expanded.value = null; submitAttempted.value = false })

const onAction = c => { expanded.value = expanded.value === c.key ? null : c.key }

const addTransport = () => form.value.transports.push({ ...createItem(), kind: 'perjalanan', eligible_estimate: '' })
const addOther = () => form.value.others.push({ ...createItem(), eligible_estimate: '' })
const removeAt = (list, index) => list.splice(index, 1)

const close = () => emit('update:modelValue', false)
const onSaveDraft = () => emit('draft', { day: dayNumber.value, ...form.value })
const onSave = () => {
  submitAttempted.value = true
  // emit hanya kalau semua validasi lolos - error deskripsi/justifikasi tampil di form setelah klik ini
  if (canSave.value && Object.keys(errors.value).length === 0 && !Object.keys(attachmentViolations.value).length) emit('save', { day: dayNumber.value, ...form.value })
}
const onSelectDayCard = target => { if (target !== dayNumber.value) emit('select-day', { target, payload: { day: dayNumber.value, ...form.value }, hasData: formHasData(form.value) }) }
</script>

<template>
  <VDialog :model-value="modelValue" max-width="1000" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <div class="d-flex align-center justify-space-between px-4 py-3"><div><span class="text-h6 font-weight-bold">Expense Entry</span><div class="text-caption text-medium-emphasis">{{ day ? `${day.title} · ${day.date}${day.effective ? ` · Effective ${day.effective}` : ''}` : '' }}</div></div><VBtn icon="ri-close-line" variant="text" size="small" @click="close" /></div>
      <VDivider />
      <VCardText class="pa-4 bg-grey-lighten-5">
        <div class="d-flex ga-2 mb-4 overflow-auto">
          <VCard v-for="i in totalDays" :key="i" link :variant="i === dayNumber ? 'tonal' : 'outlined'" :color="i === dayNumber ? 'primary' : undefined" class="px-3 py-2 bg-white flex-shrink-0" :style="i === dayNumber ? undefined : 'opacity: 0.6'" @click="onSelectDayCard(i)"><div class="text-caption font-weight-bold">Day {{ i }}</div><div v-if="i === dayNumber" class="text-body-2">{{ day?.date }}{{ day?.effective ? ` - ${day.effective}` : '' }}</div><div v-else class="text-body-2 text-medium-emphasis">&nbsp;</div></VCard>
        </div>

        <VCard variant="outlined" rounded="lg" class="mb-4 bg-white">
          <div class="d-flex justify-space-between align-center px-4 py-3 border-b"><div class="text-subtitle-1 font-weight-bold">Daily Travel Context</div><div class="text-caption text-medium-emphasis">Controls automatic allowance validation for this day.</div></div>
          <div class="d-flex justify-space-between align-center px-4 py-3"><div><div class="text-body-2 font-weight-bold">Different countries in one day</div><div class="text-caption text-primary">Adjusts allowance validation when crossing countries during the same day.</div></div><VSwitch v-model="form.different_countries" color="primary" density="compact" hide-details :label="form.different_countries ? 'Yes' : 'No'" /></div>
          <VAlert v-if="form.different_countries" type="warning" variant="tonal" density="compact" :icon="false" class="mx-4 mb-4">
            <div class="text-body-2"><span class="font-weight-bold">Manual OC Review</span><br>Currencies: IDR, SGD, MYR, USD, EUR, JPY. If an expense currency differs from the destination, Eligible Estimate and Settlement show Manual Review. No automatic FX conversion.</div>
          </VAlert>
        </VCard>

        <VCard variant="outlined" rounded="lg" class="bg-white">
          <div class="d-flex justify-space-between align-center px-4 py-3 border-b"><div><div class="text-subtitle-1 font-weight-bold">Daily Expenses</div><div class="text-caption text-medium-emphasis">Day {{ dayNumber }} of {{ totalDays }}{{ day?.date ? ` · ${day.date}` : '' }}</div></div><VChip size="small" variant="tonal" :color="initial?.status === 'saved' ? 'success' : 'warning'" class="font-weight-bold">{{ initial?.status === 'saved' ? 'Saved' : 'New' }}</VChip></div>

          <VTable class="text-body-2">
            <thead><tr><th class="text-caption font-weight-bold text-medium-emphasis">Category</th><th class="text-caption font-weight-bold text-medium-emphasis text-end">Actual</th><th class="text-caption font-weight-bold text-medium-emphasis text-end">Eligible</th><th class="text-caption font-weight-bold text-medium-emphasis text-end">Action</th></tr></thead>
            <tbody>
              <template v-for="c in categories" :key="c.key">
                <tr :class="expanded === c.key ? 'bg-blue-grey-lighten-5' : ''" style="cursor: pointer" @click="onAction(c)"><td class="font-weight-bold">{{ c.label }}</td><td class="text-end text-medium-emphasis">{{ c.actual ? formatActual(c) : '—' }}</td><td class="text-end text-primary"><VChip v-if="c.manualReview" size="x-small" color="warning" variant="tonal" class="font-weight-bold">Manual Review</VChip><VChip v-else-if="c.atCost" size="x-small" color="info" variant="tonal" class="font-weight-bold">At Cost</VChip><template v-else>{{ c.eligible != null ? formatMoney(c.eligible, c.currency) : '—' }}</template></td><td class="text-end"><VBtn variant="text" size="small" color="primary" class="text-none text-decoration-underline font-weight-bold px-1" @click.stop="onAction(c)">{{ actionOf(c) }}</VBtn></td></tr>
                <tr v-if="expanded === c.key" class="bg-blue-grey-lighten-5">
                  <td colspan="4" class="pa-4">
                    <template v-if="c.availableFromDay && laundryLocked">
                      <div class="text-caption text-medium-emphasis mb-2">Not Eligible · Available from Day {{ c.availableFromDay }}</div>
                      <div class="border rounded-lg pa-3 bg-blue-lighten-5 text-body-2"><span class="font-weight-bold text-primary">Not Eligible</span> {{ c.label }} is eligible starting from Day {{ c.availableFromDay }} of the business trip. No additional claim is allowed for this day.</div>
                    </template>
                    <template v-else-if="c.key === 'breakfast' && breakfastLocked">
                      <div class="text-caption text-medium-emphasis mb-2">Not Eligible · {{ day?.type === 'Stay' ? 'Stay Day' : 'Return Time' }}</div>
                      <div class="border rounded-lg pa-3 bg-blue-lighten-5 text-body-2"><span class="font-weight-bold text-primary">Not Eligible</span> {{ breakfastLockReason }}</div>
                    </template>
                    <template v-else>
                      <div v-if="c.availableFromDay" class="text-caption text-medium-emphasis mb-2">Eligible · Available from Day {{ c.availableFromDay }}</div>
                      <div class="d-flex flex-column ga-3">
                        <template v-if="c.key === 'transports'">
                          <VCard v-for="(transport, index) in form.transports" :key="`transport-${index}`" rounded="lg" elevation="0" border>
                            <div class="d-flex align-center justify-space-between px-4 py-3 bg-grey-lighten-5"><span class="text-subtitle-2 font-weight-bold">Transport {{ index + 1 }}</span><VBtn icon="ri-delete-bin-line" variant="text" size="small" color="error" @click="removeAt(form.transports, index)" /></div>
                            <VDivider />
                            <div class="pa-4">
                              <div class="text-caption font-weight-bold mb-1">Description *</div>
                              <VTextField v-model="transport.description" variant="outlined" density="comfortable" hide-details="auto" :error-messages="transportDescError(index)" />
                              <VRow dense class="mt-3">
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Currency</div><VSelect v-model="transport.currency" :items="currencyItems" :disabled="currencyLocked" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Actual Amount</div><VTextField :model-value="amountDisplayFor(transport)" type="text" inputmode="decimal" placeholder="0" variant="outlined" density="comfortable" hide-details="auto" class="text-right-input" @update:model-value="v => onAmountInputWithEstimate(transport, v)" /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Eligible Estimate</div><VTextField :model-value="estimateDisplayFor(transport)" type="text" inputmode="numeric" placeholder="0" variant="outlined" density="comfortable" hide-details :readonly="isManualReview(transport)" class="text-right-input" @update:model-value="v => onEstimateInputFor(transport, v)" /></VCol>
                              </VRow>
                              <div class="mt-3"><div class="text-caption font-weight-bold mb-1">Supporting Document *</div><div class="d-flex align-center ga-2"><input type="file" @change="e => { transport.attachment = Array.from(e.target.files) }"></div><div v-if="transportAttachError(index)" class="text-caption text-error mt-1">{{ transportAttachError(index) }}</div></div>
                            </div>
                          </VCard>
                          <div class="d-flex justify-end"><VBtn variant="outlined" size="small" class="text-none" @click="addTransport()">+ Add Transport</VBtn></div>
                        </template>
                        <template v-else-if="c.key === 'others'">
                          <VCard v-for="(other, index) in form.others" :key="`other-${index}`" rounded="lg" elevation="0" border>
                            <div class="d-flex align-center justify-space-between px-4 py-3 bg-grey-lighten-5"><span class="text-subtitle-2 font-weight-bold">Other {{ index + 1 }}</span><VBtn icon="ri-delete-bin-line" variant="text" size="small" color="error" @click="removeAt(form.others, index)" /></div>
                            <VDivider />
                            <div class="pa-4">
                              <div class="text-caption font-weight-bold mb-1">Description *</div>
                              <VTextField v-model="other.description" variant="outlined" density="comfortable" hide-details="auto" :error-messages="otherDescError(index)" />
                              <VRow dense class="mt-3">
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Currency</div><VSelect v-model="other.currency" :items="currencyItems" :disabled="currencyLocked" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Actual Amount</div><VTextField :model-value="amountDisplayFor(other)" type="text" inputmode="decimal" placeholder="0" variant="outlined" density="comfortable" hide-details="auto" class="text-right-input" @update:model-value="v => onAmountInputWithEstimate(other, v)" /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Eligible Estimate</div><VTextField :model-value="estimateDisplayFor(other)" type="text" inputmode="numeric" placeholder="0" variant="outlined" density="comfortable" hide-details :readonly="isManualReview(other)" class="text-right-input" @update:model-value="v => onEstimateInputFor(other, v)" /></VCol>
                              </VRow>
                              <div class="mt-3"><div class="text-caption font-weight-bold mb-1">Supporting Document *</div><div class="d-flex align-center ga-2"><input type="file" @change="e => { other.attachment = Array.from(e.target.files) }"></div><div v-if="otherAttachError(index)" class="text-caption text-error mt-1">{{ otherAttachError(index) }}</div></div>
                            </div>
                          </VCard>
                          <div class="d-flex justify-end"><VBtn variant="outlined" size="small" class="text-none" @click="addOther()">+ Add Other</VBtn></div>
                        </template>
                        <template v-else-if="c.key === 'accommodation'">
                          <template v-if="!accommodationClaimable">
                            <div class="text-caption text-medium-emphasis mb-2">Booked by Company · Not Reimbursable</div>
                            <div class="border rounded-lg pa-3 bg-blue-lighten-5 text-body-2"><span class="font-weight-bold text-primary">Booked by Company</span> Accommodation is arranged and paid by the company, so it is not reimbursable and cannot be claimed in this report.</div>
                          </template>
                          <template v-else-if="accommodationReturnLocked">
                            <div class="text-caption text-medium-emphasis mb-2">Not Eligible · Return Day</div>
                            <div class="border rounded-lg pa-3 bg-blue-lighten-5 text-body-2"><span class="font-weight-bold text-primary">Not Eligible</span> Accommodation is not eligible on the return day because there is no hotel night left on this day. No additional claim is allowed for this day.</div>
                          </template>
                          <template v-else-if="accommodationDayLocked">
                            <div class="text-caption text-medium-emphasis mb-2">Not Eligible · Claimed on Day 1</div>
                            <div class="border rounded-lg pa-3 bg-blue-lighten-5 text-body-2"><span class="font-weight-bold text-primary">Not Eligible</span> Accommodation is claimed once for the entire stay on Day 1. No additional claim is allowed for this day.</div>
                          </template>
                          <template v-else-if="accommodationNoOvernight">
                            <div class="text-caption text-medium-emphasis mb-2">Not Eligible · No Overnight Stay</div>
                            <div class="border rounded-lg pa-3 bg-blue-lighten-5 text-body-2"><span class="font-weight-bold text-primary">Not Eligible</span> Accommodation is not eligible because this trip has no overnight stay. No additional claim is allowed for this day.</div>
                          </template>
                          <VCard v-else rounded="lg" elevation="0" border>
                            <div class="px-4 py-3 bg-grey-lighten-5"><span class="text-subtitle-2 font-weight-bold">Accommodation</span><VChip size="x-small" color="info" variant="tonal" class="ml-2">Direct Payment</VChip></div>
                            <VDivider />
                            <div v-if="hotelRatePerNight != null" class="px-4 py-2 text-caption text-medium-emphasis border-b">Entitlement: {{ formatMoney(hotelRatePerNight, hotelRateCurrency) }} / night × {{ tripNights }} night(s) = <strong>{{ formatMoney(hotelEligibleTotal, hotelRateCurrency) }}</strong></div>
                            <VDivider v-if="hotelRatePerNight != null" />
                            <div class="pa-4">
                              <VRow dense>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Hotel Name</div><VTextField v-model="form.accommodation.hotel_name" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Room Type</div><VTextField v-model="form.accommodation.room_type" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Hotel Standard</div><VSelect :model-value="hotelStar ? `${hotelStar} Star` : ''" :items="hotelStar ? [`${hotelStar} Star`] : []" variant="outlined" density="comfortable" hide-details disabled /></VCol>
                              </VRow>
                              <div class="text-caption font-weight-bold mb-1 mt-3">Description *</div>
                              <VTextField v-model="form.accommodation.description" variant="outlined" density="comfortable" hide-details="auto" :error-messages="accDescError()" />
                              <VRow dense class="mt-3">
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Currency</div><VSelect v-model="form.accommodation.currency" :items="currencyItems" :disabled="currencyLocked" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Actual Amount</div><VTextField :model-value="amountDisplayFor(form.accommodation)" type="text" inputmode="decimal" placeholder="0" variant="outlined" density="comfortable" hide-details="auto" class="text-right-input" @keydown="e => blockAtMaxKeydown(e, form.accommodation.amount, hotelEligibleTotal)" @update:model-value="onAccommodationAmountInput" /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Eligible Estimate</div><VTextField :model-value="estimateDisplayFor(form.accommodation)" type="text" inputmode="numeric" placeholder="0" variant="outlined" density="comfortable" hide-details :readonly="isManualReview(form.accommodation)" class="text-right-input" @update:model-value="v => onEstimateInputFor(form.accommodation, v)" /></VCol>
                              </VRow>
                              <div class="mt-3"><div class="text-caption font-weight-bold mb-1">Supporting Document *</div><div class="d-flex align-center ga-2"><input type="file" @change="e => { form.accommodation.attachment = Array.from(e.target.files) }"></div><div v-if="accAttachError()" class="text-caption text-error mt-1">{{ accAttachError() }}</div></div>
                            </div>
                          </VCard>
                        </template>
                        <template v-else-if="c.key === 'ticket'">
                          <template v-if="!ticketClaimable">
                            <div class="text-caption text-medium-emphasis mb-2">Booked by Company · Not Reimbursable</div>
                            <div class="border rounded-lg pa-3 bg-blue-lighten-5 text-body-2"><span class="font-weight-bold text-primary">Booked by Company</span> Ticket is arranged and paid by the company, so it is not reimbursable and cannot be claimed in this report.</div>
                          </template>
                          <VCard v-else rounded="lg" elevation="0" border>
                            <div class="px-4 py-3 bg-grey-lighten-5"><span class="text-subtitle-2 font-weight-bold">Ticket</span><VChip size="x-small" color="info" variant="tonal" class="ml-2">Direct Payment</VChip></div>
                            <VDivider />
                            <div class="pa-4">
                              <div class="text-caption font-weight-bold mb-1">Ticket Type</div>
                              <VSelect v-model="form.ticket.ticket_type" :items="TICKET_TYPES" variant="outlined" density="comfortable" hide-details />
                              <div class="text-caption font-weight-bold mb-1 mt-3">Description *</div>
                              <VTextField v-model="form.ticket.description" variant="outlined" density="comfortable" hide-details="auto" :error-messages="ticketDescError()" />
                              <VRow dense class="mt-3">
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Currency</div><VSelect v-model="form.ticket.currency" :items="currencyItems" :disabled="currencyLocked" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Actual Amount</div><VTextField :model-value="amountDisplayFor(form.ticket)" type="text" inputmode="decimal" placeholder="0" variant="outlined" density="comfortable" hide-details="auto" class="text-right-input" @update:model-value="v => onAmountInputWithEstimate(form.ticket, v)" /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Eligible Estimate</div><VTextField :model-value="estimateDisplayFor(form.ticket)" type="text" inputmode="numeric" placeholder="0" variant="outlined" density="comfortable" hide-details :readonly="isManualReview(form.ticket)" class="text-right-input" @update:model-value="v => onEstimateInputFor(form.ticket, v)" /></VCol>
                              </VRow>
                              <div class="mt-3"><div class="text-caption font-weight-bold mb-1">Supporting Document *</div><div class="d-flex align-center ga-2"><input type="file" @change="e => { form.ticket.attachment = Array.from(e.target.files) }"></div><div v-if="ticketAttachError()" class="text-caption text-error mt-1">{{ ticketAttachError() }}</div></div>
                            </div>
                          </VCard>
                        </template>
                        <template v-else-if="c.key === 'pocket_money' && pocketNotEligible">
                          <div class="text-caption text-medium-emphasis mb-2">Not Eligible · No Daily Allowance</div>
                          <div class="border rounded-lg pa-3 bg-blue-lighten-5 text-body-2"><span class="font-weight-bold text-primary">Not Eligible</span> Pocket Money is not available for this destination. The destination does not receive a daily allowance, so there is nothing to claim for this category.</div>
                        </template>
                        <template v-else-if="isSimpleClaim(c)">
                          <VCard rounded="lg" elevation="0" border>
                            <div class="px-4 py-3 bg-grey-lighten-5"><span class="text-subtitle-2 font-weight-bold">{{ c.label }}</span></div>
                            <VDivider />
                            <div class="pa-4">
                              <div v-if="c.key === 'dinner'" class="mb-3"><div class="text-caption font-weight-bold mb-1">Dinner Type</div><VSelect v-model="itemOf(c).dinner_type" :items="DINNER_TYPES" variant="outlined" density="comfortable" hide-details /></div>
                              <div class="text-caption font-weight-bold mb-1">Description *</div>
                              <VTextField v-model="itemOf(c).description" variant="outlined" density="comfortable" hide-details="auto" :error-messages="descError(c)" />
                              <div v-if="c.key === 'dinner' && itemOf(c).dinner_type === DINNER_AT_HOTEL" class="mt-3"><div class="text-caption font-weight-bold mb-1">Reason / Justification *</div><VTextarea v-model="itemOf(c).justification" variant="outlined" density="comfortable" rows="3" hide-details="auto" :error-messages="dinnerJustificationError()" /></div>
                              <VRow dense class="mt-3">
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Currency</div><VSelect v-model="itemOf(c).currency" :items="currencyItems" :disabled="currencyLocked" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Actual Amount</div><VTextField :model-value="amountDisplayFor(itemOf(c))" type="text" inputmode="numeric" placeholder="0" variant="outlined" density="comfortable" hide-details="auto" :error-messages="amountError(c)" class="text-right-input" @keydown="e => blockAtMaxKeydown(e, itemOf(c).amount, simpleMaxOf(c))" @update:model-value="v => onAmountInput(c, v)" /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Eligible Entitlement</div><VTextField :model-value="isManualReview(itemOf(c)) ? 'Manual Review' : c.atCost ? 'At Cost' : formatMoney(c.eligible, c.currency)" variant="outlined" density="comfortable" hide-details readonly /></VCol>
                              </VRow>
                              <div class="mt-3"><div class="text-caption font-weight-bold mb-1">Supporting Document</div><div class="d-flex align-center ga-2"><input type="file" @change="e => onAttachment(c, e)"></div><div v-if="attachError(c)" class="text-caption text-error mt-1">{{ attachError(c) }}</div></div>
                            </div>
                          </VCard>
                        </template>
                        <template v-else-if="c.key === 'telephone'">
                          <VCard rounded="lg" elevation="0" border>
                            <div class="px-4 py-3 bg-grey-lighten-5"><span class="text-subtitle-2 font-weight-bold">Personal Telephone</span><VChip size="x-small" color="grey" variant="tonal" class="ml-2">Fixed Entitlement</VChip></div>
                            <VDivider />
                            <div class="pa-4">
                              <div class="text-caption font-weight-bold mb-1">Description *</div>
                              <VTextField v-model="form.telephone.description" variant="outlined" density="comfortable" hide-details="auto" :error-messages="telDescError()" />
                              <VRow dense class="mt-3">
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Currency</div><VSelect v-model="form.telephone.currency" :items="currencyItems" :disabled="currencyLocked" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Actual Amount</div><VTextField :model-value="amountDisplayFor(form.telephone)" type="text" inputmode="decimal" placeholder="0" variant="outlined" density="comfortable" hide-details="auto" :error-messages="telAmountError()" class="text-right-input" @keydown="e => blockAtMaxKeydown(e, form.telephone.amount, TELEPHONE_BUDGET)" @update:model-value="onTelephoneAmountInput" /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Eligible Entitlement</div><VTextField :model-value="isManualReview(form.telephone) ? 'Manual Review' : formatIDR(TELEPHONE_BUDGET)" variant="outlined" density="comfortable" hide-details readonly /></VCol>
                              </VRow>
                              <div class="mt-3"><div class="text-caption font-weight-bold mb-1">Supporting Document</div><div class="d-flex align-center ga-2"><input type="file" @change="e => { form.telephone.attachment = Array.from(e.target.files) }"></div><div v-if="telAttachError()" class="text-caption text-error mt-1">{{ telAttachError() }}</div></div>
                            </div>
                          </VCard>
                          <VCard rounded="lg" elevation="0" border>
                            <div class="px-4 py-3 bg-grey-lighten-5"><span class="text-subtitle-2 font-weight-bold">Office Telephone</span><VChip size="x-small" color="grey" variant="tonal" class="ml-2">Optional</VChip></div>
                            <VDivider />
                            <div class="pa-4">
                              <div class="text-caption font-weight-bold mb-1">Description *</div>
                              <VTextField v-model="form.office_telephone.description" variant="outlined" density="comfortable" hide-details="auto" :error-messages="otDescError()" />
                              <VRow dense class="mt-3">
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Currency</div><VSelect v-model="form.office_telephone.currency" :items="currencyItems" :disabled="currencyLocked" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Actual Amount</div><VTextField :model-value="amountDisplayFor(form.office_telephone)" type="text" inputmode="decimal" placeholder="0" variant="outlined" density="comfortable" hide-details="auto" class="text-right-input" @update:model-value="v => onAmountInputWithEstimate(form.office_telephone, v)" /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Eligible Estimate</div><VTextField :model-value="estimateDisplayFor(form.office_telephone)" type="text" inputmode="numeric" placeholder="0" variant="outlined" density="comfortable" hide-details :readonly="isManualReview(form.office_telephone)" class="text-right-input" @update:model-value="v => onEstimateInputFor(form.office_telephone, v)" /></VCol>
                              </VRow>
                              <div class="mt-3"><div class="text-caption font-weight-bold mb-1">Supporting Document</div><div class="d-flex align-center ga-2"><input type="file" @change="e => { form.office_telephone.attachment = Array.from(e.target.files) }"></div><div v-if="otAttachError()" class="text-caption text-error mt-1">{{ otAttachError() }}</div></div>
                            </div>
                          </VCard>
                        </template>
                        <template v-else-if="c.key === 'laundry'">
                          <VCard rounded="lg" elevation="0" border>
                            <div class="px-4 py-3 bg-grey-lighten-5"><span class="text-subtitle-2 font-weight-bold">Laundry</span></div>
                            <VDivider />
                            <div class="pa-4">
                              <div class="text-caption font-weight-bold mb-1">Description *</div>
                              <VTextField v-model="form.laundry.description" variant="outlined" density="comfortable" hide-details="auto" :error-messages="launDescError()" />
                              <VRow dense class="mt-3">
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Currency</div><VSelect v-model="form.laundry.currency" :items="currencyItems" :disabled="currencyLocked" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Actual Amount</div><VTextField :model-value="amountDisplayFor(form.laundry)" type="text" inputmode="decimal" placeholder="0" variant="outlined" density="comfortable" hide-details="auto" class="text-right-input" @update:model-value="v => onAmountInputWithEstimate(form.laundry, v)" /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Eligible Estimate</div><VTextField :model-value="estimateDisplayFor(form.laundry)" type="text" inputmode="numeric" placeholder="0" variant="outlined" density="comfortable" hide-details :readonly="isManualReview(form.laundry)" class="text-right-input" @update:model-value="v => onEstimateInputFor(form.laundry, v)" /></VCol>
                              </VRow>
                              <div class="mt-3"><div class="text-caption font-weight-bold mb-1">Supporting Document *</div><div class="d-flex align-center ga-2"><input type="file" @change="e => { form.laundry.attachment = Array.from(e.target.files) }"></div><div v-if="launAttachError()" class="text-caption text-error mt-1">{{ launAttachError() }}</div></div>
                            </div>
                          </VCard>
                        </template>
                      </div>
                    </template>
                  </td>
                </tr>
              </template>
            </tbody>
          </VTable>

          <div class="px-4 py-3 text-caption text-medium-emphasis border-t">Select Add or Edit to view expense details. Amounts are shown in the original expense currency.</div>
        </VCard>

        <VCard variant="outlined" rounded="lg" class="mt-4 bg-grey-lighten-4">
          <div class="d-flex justify-end ga-3 pa-4"><VBtn variant="outlined" class="text-none bg-white" @click="onSaveDraft">Draft</VBtn><VBtn color="primary" variant="flat" class="text-none" :disabled="!canSave" @click="onSave">{{ isLast ? 'Save & Finish' : 'Save & Next Day' }}</VBtn></div>
        </VCard>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style scoped>
.text-right-input :deep(input) { text-align: right; }
</style>