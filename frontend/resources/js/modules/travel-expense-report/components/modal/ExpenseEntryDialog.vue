<script setup>
import { computed, ref, watch } from 'vue'
import { useAuthStore } from '../../../../stores/auth.js'
import { useConfirm } from '../../../../composables/useConfirm'

const props = defineProps({ modelValue: { type: Boolean, default: false }, day: { type: Object, default: null }, totalDays: { type: Number, default: 0 }, initial: { type: Object, default: null }, isLast: { type: Boolean, default: false }, usedElsewhere: { type: Object, default: () => ({ meals: 0, pocket_money: 0 }) }, entitlements: { type: Object, default: null }, ticketArrangement: { type: String, default: '' }, accommodationArrangement: { type: String, default: '' }, groupMembers: { type: Array, default: () => [] }, isGroup: { type: Boolean, default: false } })
const emit = defineEmits(['update:modelValue', 'save', 'draft', 'select-day'])
const authStore = useAuthStore()
const { confirm } = useConfirm()

const DINNER_AT_HOTEL = 'Dinner at Hotel'
const DINNER_TYPES = ['Regular Dinner', DINNER_AT_HOTEL]
const TELEPHONE_BUDGET = 15000
const LAUNDRY_FROM_DAY = 6
const formatIDR = value => `IDR ${new Intl.NumberFormat('id-ID').format(value)}`
const formatMoney = (value, currency = 'IDR') => (value == null ? '—' : `${currency ?? 'IDR'} ${new Intl.NumberFormat('id-ID').format(Number(value))}`)

const createItem = (amount = '', description = '') => ({ currency: 'IDR', amount: String(amount), description, paid_by_others: false, attachment: null, payment_mode: 'myself', paid_by: null, paid_by_name: '', pay_for: [] })
const cloneItem = item => ({ payment_mode: 'myself', paid_by: null, paid_by_name: '', pay_for: [], ...item, attachment: item?.attachment ?? null })
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

// ===== GROUP PAYMENT MODE (Pay Myself / Paid by Other / Pay for Others) =====
const ELEVATED_ROLES = ['Dept Head', 'Div Head', 'President Director']
const isElevated = computed(() => ELEVATED_ROLES.some(role => authStore.hasRole(role)))
const paymentModeItems = computed(() => [
  ...(isElevated.value ? [{ value: 'pay_for_others', title: 'Pay for Others' }] : []),
  { value: 'paid_by_other', title: paidByOtherUnavailable.value ? 'Paid by Other - unavailable' : 'Paid by Other', unavailable: paidByOtherUnavailable.value, props: { disabled: paidByOtherUnavailable.value } },
  { value: 'myself', title: 'Pay Myself' },
])
const payerOptions = computed(() => props.groupMembers.filter(member => (member.roles ?? []).some(role => ELEVATED_ROLES.includes(role)) && Number(member.id) !== Number(authStore.user?.id)).map(member => ({ ...member, label: `${member.name}${(member.roles ?? []).length ? ` - ${member.roles.join(', ')}` : ''}` })))
const payForOptions = computed(() => props.groupMembers.filter(member => Number(member.id) !== Number(authStore.user?.id)).map(member => ({ ...member, label: `${member.name}${(member.roles ?? []).length ? ` - ${member.roles.join(', ')}` : ''}` })))
const paidByOtherUnavailable = computed(() => !payerOptions.value.length)
const paidByOtherUnavailableReason = 'Paid by Other is unavailable: no other Manager, Director, or President Director is listed in this Group Trip.'
const payForCountOf = item => new Set(item?.pay_for ?? []).size
const onPaymentMode = (item, mode) => {
  if (mode === 'paid_by_other' && paidByOtherUnavailable.value) return
  item.payment_mode = mode
  item.paid_by_others = mode === 'paid_by_other'
  if (mode !== 'paid_by_other') { item.paid_by = null; item.paid_by_name = '' }
  if (mode !== 'pay_for_others') item.pay_for = []
}
const onSelectPayer = (item, id) => { item.paid_by = id; item.paid_by_name = payerOptions.value.find(member => member.id === id)?.name ?? '' }
const onTogglePayFor = (item, member, checked) => {
  const list = Array.isArray(item.pay_for) ? item.pay_for : (item.pay_for = [])
  if (checked && !list.includes(member.id)) list.push(member.id)
  if (!checked) { const index = list.indexOf(member.id); if (index >= 0) list.splice(index, 1) }
}
const paymentErrorOf = item => {
  if (!item) return ''
  if (item.payment_mode === 'paid_by_other' && !item.paid_by) return 'Payer is required.'
  if (item.payment_mode === 'pay_for_others' && !payForCountOf(item)) return 'Select at least one employee covered.'
  return ''
}
const lockedOf = item => item?.payment_mode === 'paid_by_other'
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
const hasAttachment = item => !!(Array.isArray(item.attachment) ? item.attachment.length : item.attachment) || !!item?.existing_attachment_path
const touched = item => !!item && (item.paid_by_others || Number(item.amount) > 0 || !!String(item.description ?? '').trim() || (!!item.payment_mode && item.payment_mode !== 'myself'))
const errors = computed(() => {
  const map = {}
  const check = (key, item, { max = null } = {}) => { if (!claimed(item)) return; if (max !== null && Number(item.amount) > max) map[key] ??= `Amount exceeds the maximum allowed (${formatIDR(max)}).`; if (!String(item.description ?? '').trim()) map[key] ??= 'Description is required.' }
  const payCheck = (key, item) => { if (!props.isGroup) return; const err = paymentErrorOf(item); if (err) map[key] ??= err }
  Object.entries(form.value.meals).forEach(([key, item]) => { if (key === 'breakfast' && breakfastLocked.value) return; check(`meals.${key}`, item, { max: mealLimit().max, fixed: true }); payCheck(`meals.${key}`, item) })
  if (claimed(form.value.meals.dinner) && form.value.meals.dinner.dinner_type === DINNER_AT_HOTEL && !String(form.value.meals.dinner.justification ?? '').trim()) map['meals.dinner'] ??= 'Reason / Justification is required.'
  check('pocket_money', form.value.pocket_money, { max: pocketLimit.value.max, fixed: true })
  if (ticketClaimable.value) { check('ticket', form.value.ticket); payCheck('ticket', form.value.ticket) }
  if (accommodationClaimable.value && !accommodationNotEligible.value) { check('accommodation', form.value.accommodation); payCheck('accommodation', form.value.accommodation) }
  if (showLaundry.value && !laundryLocked.value) { check('laundry', form.value.laundry); payCheck('laundry', form.value.laundry) }
  check('telephone', form.value.telephone, { max: TELEPHONE_BUDGET })
  check('office_telephone', form.value.office_telephone); payCheck('office_telephone', form.value.office_telephone)
  form.value.transports.forEach((item, index) => { check(`transports.${index}`, item); payCheck(`transports.${index}`, item) })
  form.value.others.forEach((item, index) => { check(`others.${index}`, item); payCheck(`others.${index}`, item) })
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
const payErrorOf = key => (submitAttempted.value ? errorOf(key) : '')
const amountDisplay = item => (item.amount === '' || item.amount == null ? '' : new Intl.NumberFormat('id-ID').format(Number(item.amount)))
const simpleMaxOf = c => (itemOf(c)?.payment_mode === 'pay_for_others' ? null : c.key === 'pocket_money' ? pocketLimit.value.max : mealLimit().max)
const setClampedAmount = (item, value, max) => { const digits = String(value).replace(item.currency === 'IDR' ? /[^0-9]/g : /[^0-9.]/g, ''); item.amount = max != null && Number(digits) > max ? String(max) : digits }
const blockAtMaxKeydown = (event, current, max) => { if (max == null || !/^[0-9]$/.test(event.key)) return; const el = event.target; if (el && el.selectionStart !== el.selectionEnd) return; if (Number(current || '0') >= max) event.preventDefault() }
const onAmountInput = (c, value) => setClampedAmount(itemOf(c), value, simpleMaxOf(c))
const onTelephoneAmountInput = value => setClampedAmount(form.value.telephone, value, TELEPHONE_BUDGET)
const firstFileName = attachment => (Array.isArray(attachment) ? attachment : attachment ? [attachment] : [])[0]?.name ?? ''
// Tampilan di area nama: file baru > file lama dari draft > no file chosen
const displayNameOf = item => firstFileName(item?.attachment) || item?.existing_attachment_name || 'no file chosen'
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
  // Warning accommodation fresh per klik Save - edit amount menghapus warning lama sampai klik Save lagi
  submitAttempted.value = false
}
const estimateDisplayFor = item => (isManualReview(item) ? 'Manual Review' : item.eligible_estimate === '' ? '' : new Intl.NumberFormat(item.currency === 'IDR' ? 'id-ID' : 'en-US', { maximumFractionDigits: 2 }).format(Number(item.eligible_estimate)))
const onEstimateInputFor = (item, value) => { item.eligible_estimate = String(value).replace(item.currency === 'IDR' ? /[^0-9]/g : /[^0-9.]/g, '') }

const sumItem = item => (claimed(item) ? Number(item.amount) || 0 : 0)
const sumList = list => (list ?? []).reduce((sum, item) => sum + sumItem(item), 0)
const mealAtCost = computed(() => props.entitlements?.meal_basis === 'at_cost' && mealPerMeal.value == null)
const paymentFlagsOf = item => ({ paidByOther: item?.payment_mode === 'paid_by_other', payForCount: payForCountOf(item) })
const listPaymentFlagsOf = list => {
  const entries = list ?? []
  const claimedEntries = entries.filter(entry => entry.payment_mode !== 'paid_by_other')
  return { paidByOther: entries.length > 0 && entries.every(entry => entry.payment_mode === 'paid_by_other'), payForCount: claimedEntries.length ? Math.max(...claimedEntries.map(entry => payForCountOf(entry))) : 0 }
}
const categories = computed(() => [
  { key: 'breakfast', label: 'Breakfast', actual: sumItem(form.value.meals.breakfast), eligible: mealPerMeal.value, atCost: mealAtCost.value, currency: moneyCurrency.value, manualReview: isManualReview(form.value.meals.breakfast), ...paymentFlagsOf(form.value.meals.breakfast) },
  { key: 'lunch', label: 'Lunch', actual: sumItem(form.value.meals.lunch), eligible: mealPerMeal.value, atCost: mealAtCost.value, currency: moneyCurrency.value, manualReview: isManualReview(form.value.meals.lunch), ...paymentFlagsOf(form.value.meals.lunch) },
  { key: 'dinner', label: 'Dinner', actual: sumItem(form.value.meals.dinner), eligible: mealPerMeal.value, atCost: mealAtCost.value, currency: moneyCurrency.value, manualReview: isManualReview(form.value.meals.dinner), ...paymentFlagsOf(form.value.meals.dinner) },
  { key: 'pocket_money', label: 'Pocket Money', actual: sumItem(form.value.pocket_money), eligible: pocketNotEligible.value ? null : pocketBudget.value, currency: moneyCurrency.value, manualReview: isManualReview(form.value.pocket_money), notEligible: pocketNotEligible.value },
  { key: 'ticket', label: 'Ticket', actual: sumItem(form.value.ticket), eligible: Number(form.value.ticket.eligible_estimate) || null, hasData: touched(form.value.ticket), manualReview: isManualReview(form.value.ticket), ...paymentFlagsOf(form.value.ticket) },
  { key: 'accommodation', label: 'Accommodation', actual: sumItem(form.value.accommodation), eligible: Number(form.value.accommodation.eligible_estimate) || null, hasData: touched(form.value.accommodation), manualReview: isManualReview(form.value.accommodation), ...paymentFlagsOf(form.value.accommodation) },
  ...(showLaundry.value ? [{ key: 'laundry', label: 'Laundry', actual: sumItem(form.value.laundry), eligible: Number(form.value.laundry.eligible_estimate) || null, availableFromDay: LAUNDRY_FROM_DAY, hasData: touched(form.value.laundry), manualReview: isManualReview(form.value.laundry), ...paymentFlagsOf(form.value.laundry) }] : []),
  { key: 'telephone', label: 'Telephone', actual: sumItem(form.value.telephone) + sumItem(form.value.office_telephone), eligible: TELEPHONE_BUDGET, hasData: touched(form.value.telephone) || touched(form.value.office_telephone), manualReview: isManualReview(form.value.telephone) || isManualReview(form.value.office_telephone), ...paymentFlagsOf(form.value.office_telephone) },
  { key: 'transports', label: 'Transport', actual: sumList(form.value.transports), eligible: null, hasData: form.value.transports.some(touched), manualReview: form.value.transports.some(isManualReview), ...listPaymentFlagsOf(form.value.transports) },
  { key: 'others', label: 'Other', actual: sumList(form.value.others), eligible: null, hasData: form.value.others.some(touched), manualReview: form.value.others.some(isManualReview), ...listPaymentFlagsOf(form.value.others) },
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

const expanded = ref([])
watch(() => props.modelValue, open => { if (open) { expanded.value = []; submitAttempted.value = false } })
watch(() => props.day, () => { form.value = cloneForm(props.initial); expanded.value = []; submitAttempted.value = false })

const onAction = c => { const index = expanded.value.indexOf(c.key); if (index >= 0) expanded.value.splice(index, 1); else expanded.value.push(c.key) }

const addTransport = () => form.value.transports.push({ ...createItem(), kind: 'perjalanan', eligible_estimate: '' })
const addOther = () => form.value.others.push({ ...createItem(), eligible_estimate: '' })
const removeAt = (list, index) => list.splice(index, 1)

const close = () => emit('update:modelValue', false)
const onSaveDraft = async () => {
  const confirmed = await confirm({ title: 'Save as Draft', text: "Are you sure you want to save this day's expense as a draft?", color: 'primary' })
  if (!confirmed) return
  emit('draft', { day: dayNumber.value, ...form.value })
}
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
            <div class="d-flex justify-space-between align-center px-4 py-3 border-b"><div><div class="text-subtitle-1 font-weight-bold">Daily Expenses</div><div class="text-caption text-medium-emphasis">Day {{ dayNumber }} of {{ totalDays }}{{ day?.date ? ` · ${day.date}` : '' }}</div></div></div>

          <VTable class="text-body-2">
            <thead><tr><th class="text-caption font-weight-bold text-medium-emphasis">Category</th><th class="text-caption font-weight-bold text-medium-emphasis text-end">Actual</th><th class="text-caption font-weight-bold text-medium-emphasis text-end">Eligible</th><th class="text-caption font-weight-bold text-medium-emphasis text-end">Action</th></tr></thead>
            <tbody>
              <template v-for="c in categories" :key="c.key">
                <tr :class="expanded.includes(c.key) ? 'bg-blue-grey-lighten-5' : ''" style="cursor: pointer" @click="onAction(c)"><td class="font-weight-bold">{{ c.label }}</td><td class="text-end text-medium-emphasis">{{ c.actual ? formatActual(c) : '—' }}</td><td class="text-end text-primary"><VChip v-if="c.manualReview" size="x-small" color="warning" variant="tonal" class="font-weight-bold">Manual Review</VChip><VChip v-else-if="c.atCost" size="x-small" color="info" variant="tonal" class="font-weight-bold">At Cost</VChip><template v-else>{{ !c.paidByOther && c.eligible != null ? formatMoney(c.eligible, c.currency) : '—' }}</template></td><td class="text-end"><VBtn variant="text" size="small" color="primary" class="text-none text-decoration-underline font-weight-bold px-1" @click.stop="onAction(c)">{{ actionOf(c) }}</VBtn></td></tr>
                <tr v-if="expanded.includes(c.key)" class="bg-blue-grey-lighten-5">
                  <td colspan="4" class="pa-4">
                    <template v-if="c.availableFromDay && laundryLocked">
                      <div class="text-caption text-medium-emphasis mb-2">Not Eligible · Available from Day {{ c.availableFromDay }}</div>
                      <div class="border rounded-lg pa-3 bg-blue-lighten-5 text-body-2 d-flex align-center ga-2"><span class="font-weight-bold text-primary">Not Eligible</span><VTooltip :text="`${c.label} is eligible starting from Day ${c.availableFromDay} of the business trip. No additional claim is allowed for this day.`"><template #activator="{ props }"><VIcon v-bind="props" icon="ri-information-line" size="16" class="text-medium-emphasis" /></template></VTooltip></div>
                    </template>
                    <template v-else-if="c.key === 'breakfast' && breakfastLocked">
                      <div class="text-caption text-medium-emphasis mb-2">Not Eligible · {{ day?.type === 'Stay' ? 'Stay Day' : 'Return Time' }}</div>
                      <div class="border rounded-lg pa-3 bg-blue-lighten-5 text-body-2 d-flex align-center ga-2"><span class="font-weight-bold text-primary">Not Eligible</span><VTooltip :text="breakfastLockReason"><template #activator="{ props }"><VIcon v-bind="props" icon="ri-information-line" size="16" class="text-medium-emphasis" /></template></VTooltip></div>
                    </template>
                    <template v-else>
                      <div v-if="c.availableFromDay" class="text-caption text-medium-emphasis mb-2">Eligible · Available from Day {{ c.availableFromDay }}</div>
                      <div class="d-flex flex-column ga-3">
                        <template v-if="c.key === 'transports'">
                          <VCard v-for="(transport, index) in form.transports" :key="`transport-${index}`" rounded="lg" elevation="0" border>
                            <div class="d-flex align-center justify-space-between px-4 py-3 bg-grey-lighten-5"><span class="text-subtitle-2 font-weight-bold">Transport {{ index + 1 }}</span><VBtn icon="ri-delete-bin-line" variant="text" size="small" color="error" @click="removeAt(form.transports, index)" /></div>
                            <VDivider />
                            <div v-if="isGroup" class="px-4 pt-3 pb-3">
                              <div class="d-flex align-start ga-3 flex-wrap">
                                <div style="width: 280px">
                                  <div class="text-caption font-weight-bold mb-1 d-flex align-center ga-1">Payment Arrangement<VTooltip v-if="paidByOtherUnavailable" :text="paidByOtherUnavailableReason"><template #activator="{ props: tooltipProps }"><VIcon v-bind="tooltipProps" icon="ri-information-line" size="14" class="text-medium-emphasis" /></template></VTooltip></div>
                                  <VSelect :model-value="transport.payment_mode" :items="paymentModeItems" item-title="title" item-value="value" placeholder="Select payment" variant="outlined" density="comfortable" hide-details @update:model-value="mode => onPaymentMode(transport, mode)" />
                                </div>
                                <div v-if="transport.payment_mode === 'paid_by_other'" style="width: 280px">
                                  <div class="text-caption font-weight-bold mb-1">Paid by *</div>
                                  <VSelect :model-value="transport.paid_by" :items="payerOptions" item-title="label" item-value="id" placeholder="Select person who paid the bill" variant="outlined" density="comfortable" hide-details="auto" :error-messages="payErrorOf(`transports.${index}`)" @update:model-value="id => onSelectPayer(transport, id)" />
                                </div>
                              </div>
                              <div v-if="transport.payment_mode === 'paid_by_other'" class="border rounded-lg pa-3 bg-blue-lighten-5 text-body-2 d-flex align-center ga-2 mt-3"><span class="font-weight-bold text-primary">Not Claimed by Me</span><VTooltip :text="`This expense was paid by ${transport.paid_by_name || 'another employee'}, so your claim for this expense is 0.`"><template #activator="{ props }"><VIcon v-bind="props" icon="ri-information-line" size="16" class="text-medium-emphasis" /></template></VTooltip></div>
                            </div>
                            <VDivider />
                            <div class="pa-4">
                              <template v-if="isGroup && transport.payment_mode === 'pay_for_others'">
                                <div class="text-caption font-weight-bold mb-1">Paid For (select multiple) *</div>
                                <VCard variant="outlined" rounded="lg" class="pa-3 mb-3">
                                  <div class="d-flex flex-wrap">
                                    <VCheckboxBtn v-for="member in payForOptions" :key="member.id" :model-value="(transport.pay_for ?? []).includes(member.id)" :label="member.label" density="compact" hide-details style="width: 33.333%" @update:model-value="checked => onTogglePayFor(transport, member, checked)" />
                                  </div>
                                  <div v-if="!payForOptions.length" class="text-caption text-medium-emphasis">No other employee in this group.</div>
                                </VCard>
                                <div v-if="payErrorOf(`transports.${index}`)" class="text-caption text-error mt-1 mb-3">{{ payErrorOf(`transports.${index}`) }}</div>
                              </template>
                              <div class="text-caption font-weight-bold mb-1">Description *</div>
                              <VTextField v-model="transport.description" :disabled="lockedOf(transport)" variant="outlined" density="comfortable" hide-details="auto" :error-messages="transportDescError(index)" />
                              <VRow dense class="mt-3">
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Currency</div><VSelect v-model="transport.currency" :items="currencyItems" :disabled="currencyLocked || lockedOf(transport)" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Actual Amount</div><VTextField :model-value="amountDisplayFor(transport)" type="text" inputmode="decimal" placeholder="0" :disabled="lockedOf(transport)" variant="outlined" density="comfortable" hide-details="auto" class="text-right-input" @update:model-value="v => onAmountInputWithEstimate(transport, v)" /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Eligible Estimate</div><VTextField :model-value="estimateDisplayFor(transport)" type="text" inputmode="numeric" placeholder="0" variant="outlined" density="comfortable" hide-details :readonly="lockedOf(transport) || isManualReview(transport)" class="text-right-input" @update:model-value="v => onEstimateInputFor(transport, v)" /></VCol>
                              </VRow>
                              <div class="mt-3"><div class="text-caption font-weight-bold mb-1">Supporting Document *</div><label class="d-inline-flex align-center ga-2 cursor-pointer" :style="lockedOf(transport) ? 'pointer-events: none; opacity: 0.6' : ''"><input type="file" class="d-none" @change="e => { transport.attachment = Array.from(e.target.files) }"><span class="file-btn">Choose File</span><span class="file-name">{{ displayNameOf(transport) }}</span></label><div v-if="transportAttachError(index)" class="text-caption text-error mt-1">{{ transportAttachError(index) }}</div></div>
                            </div>
                          </VCard>
                          <div class="d-flex justify-end"><VBtn variant="outlined" size="small" class="text-none" @click="addTransport()">+ Add Transport</VBtn></div>
                        </template>
                        <template v-else-if="c.key === 'others'">
                          <VCard v-for="(other, index) in form.others" :key="`other-${index}`" rounded="lg" elevation="0" border>
                            <div class="d-flex align-center justify-space-between px-4 py-3 bg-grey-lighten-5"><span class="text-subtitle-2 font-weight-bold">Other {{ index + 1 }}</span><VBtn icon="ri-delete-bin-line" variant="text" size="small" color="error" @click="removeAt(form.others, index)" /></div>
                            <VDivider />
                            <div v-if="isGroup" class="px-4 pt-3 pb-3">
                              <div class="d-flex align-start ga-3 flex-wrap">
                                <div style="width: 280px">
                                  <div class="text-caption font-weight-bold mb-1 d-flex align-center ga-1">Payment Arrangement<VTooltip v-if="paidByOtherUnavailable" :text="paidByOtherUnavailableReason"><template #activator="{ props: tooltipProps }"><VIcon v-bind="tooltipProps" icon="ri-information-line" size="14" class="text-medium-emphasis" /></template></VTooltip></div>
                                  <VSelect :model-value="other.payment_mode" :items="paymentModeItems" item-title="title" item-value="value" placeholder="Select payment" variant="outlined" density="comfortable" hide-details @update:model-value="mode => onPaymentMode(other, mode)" />
                                </div>
                                <div v-if="other.payment_mode === 'paid_by_other'" style="width: 280px">
                                  <div class="text-caption font-weight-bold mb-1">Paid by *</div>
                                  <VSelect :model-value="other.paid_by" :items="payerOptions" item-title="label" item-value="id" placeholder="Select person who paid the bill" variant="outlined" density="comfortable" hide-details="auto" :error-messages="payErrorOf(`others.${index}`)" @update:model-value="id => onSelectPayer(other, id)" />
                                </div>
                              </div>
                              <div v-if="other.payment_mode === 'paid_by_other'" class="border rounded-lg pa-3 bg-blue-lighten-5 text-body-2 d-flex align-center ga-2 mt-3"><span class="font-weight-bold text-primary">Not Claimed by Me</span><VTooltip :text="`This expense was paid by ${other.paid_by_name || 'another employee'}, so your claim for this expense is 0.`"><template #activator="{ props }"><VIcon v-bind="props" icon="ri-information-line" size="16" class="text-medium-emphasis" /></template></VTooltip></div>
                            </div>
                            <VDivider />
                            <div class="pa-4">
                              <template v-if="isGroup && other.payment_mode === 'pay_for_others'">
                                <div class="text-caption font-weight-bold mb-1">Paid For (select multiple) *</div>
                                <VCard variant="outlined" rounded="lg" class="pa-3 mb-3">
                                  <div class="d-flex flex-wrap">
                                    <VCheckboxBtn v-for="member in payForOptions" :key="member.id" :model-value="(other.pay_for ?? []).includes(member.id)" :label="member.label" density="compact" hide-details style="width: 33.333%" @update:model-value="checked => onTogglePayFor(other, member, checked)" />
                                  </div>
                                  <div v-if="!payForOptions.length" class="text-caption text-medium-emphasis">No other employee in this group.</div>
                                </VCard>
                                <div v-if="payErrorOf(`others.${index}`)" class="text-caption text-error mt-1 mb-3">{{ payErrorOf(`others.${index}`) }}</div>
                              </template>
                              <div class="text-caption font-weight-bold mb-1">Description *</div>
                              <VTextField v-model="other.description" :disabled="lockedOf(other)" variant="outlined" density="comfortable" hide-details="auto" :error-messages="otherDescError(index)" />
                              <VRow dense class="mt-3">
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Currency</div><VSelect v-model="other.currency" :items="currencyItems" :disabled="currencyLocked || lockedOf(other)" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Actual Amount</div><VTextField :model-value="amountDisplayFor(other)" type="text" inputmode="decimal" placeholder="0" :disabled="lockedOf(other)" variant="outlined" density="comfortable" hide-details="auto" class="text-right-input" @update:model-value="v => onAmountInputWithEstimate(other, v)" /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Eligible Estimate</div><VTextField :model-value="estimateDisplayFor(other)" type="text" inputmode="numeric" placeholder="0" variant="outlined" density="comfortable" hide-details :readonly="lockedOf(other) || isManualReview(other)" class="text-right-input" @update:model-value="v => onEstimateInputFor(other, v)" /></VCol>
                              </VRow>
                              <div class="mt-3"><div class="text-caption font-weight-bold mb-1">Supporting Document *</div><label class="d-inline-flex align-center ga-2 cursor-pointer" :style="lockedOf(other) ? 'pointer-events: none; opacity: 0.6' : ''"><input type="file" class="d-none" @change="e => { other.attachment = Array.from(e.target.files) }"><span class="file-btn">Choose File</span><span class="file-name">{{ displayNameOf(other) }}</span></label><div v-if="otherAttachError(index)" class="text-caption text-error mt-1">{{ otherAttachError(index) }}</div></div>
                            </div>
                          </VCard>
                          <div class="d-flex justify-end"><VBtn variant="outlined" size="small" class="text-none" @click="addOther()">+ Add Other</VBtn></div>
                        </template>
                        <template v-else-if="c.key === 'accommodation'">
                          <template v-if="!accommodationClaimable">
                            <div class="text-caption text-medium-emphasis mb-2">Booked by Company · Not Reimbursable</div>
                            <div class="border rounded-lg pa-3 bg-blue-lighten-5 text-body-2 d-flex align-center ga-2"><span class="font-weight-bold text-primary">Booked by Company</span><VTooltip text="Accommodation is arranged and paid by the company, so it is not reimbursable and cannot be claimed in this report."><template #activator="{ props }"><VIcon v-bind="props" icon="ri-information-line" size="16" class="text-medium-emphasis" /></template></VTooltip></div>
                          </template>
                          <template v-else-if="accommodationReturnLocked">
                            <div class="text-caption text-medium-emphasis mb-2">Not Eligible · Return Day</div>
                            <div class="border rounded-lg pa-3 bg-blue-lighten-5 text-body-2 d-flex align-center ga-2"><span class="font-weight-bold text-primary">Not Eligible</span><VTooltip text="Accommodation is not eligible on the return day because there is no hotel night left on this day. No additional claim is allowed for this day."><template #activator="{ props }"><VIcon v-bind="props" icon="ri-information-line" size="16" class="text-medium-emphasis" /></template></VTooltip></div>
                          </template>
                          <template v-else-if="accommodationDayLocked">
                            <div class="text-caption text-medium-emphasis mb-2">Not Eligible · Claimed on Day 1</div>
                            <div class="border rounded-lg pa-3 bg-blue-lighten-5 text-body-2 d-flex align-center ga-2"><span class="font-weight-bold text-primary">Not Eligible</span><VTooltip text="Accommodation is claimed once for the entire stay on Day 1. No additional claim is allowed for this day."><template #activator="{ props }"><VIcon v-bind="props" icon="ri-information-line" size="16" class="text-medium-emphasis" /></template></VTooltip></div>
                          </template>
                          <template v-else-if="accommodationNoOvernight">
                            <div class="text-caption text-medium-emphasis mb-2">Not Eligible · No Overnight Stay</div>
                            <div class="border rounded-lg pa-3 bg-blue-lighten-5 text-body-2 d-flex align-center ga-2"><span class="font-weight-bold text-primary">Not Eligible</span><VTooltip text="Accommodation is not eligible because this trip has no overnight stay. No additional claim is allowed for this day."><template #activator="{ props }"><VIcon v-bind="props" icon="ri-information-line" size="16" class="text-medium-emphasis" /></template></VTooltip></div>
                          </template>
                          <VCard v-else rounded="lg" elevation="0" border>
                            <div class="px-4 py-3 bg-grey-lighten-5"><span class="text-subtitle-2 font-weight-bold">Accommodation</span><VChip size="x-small" color="info" variant="tonal" class="ml-2">Direct Payment</VChip></div>
                            <VDivider />
                            <div v-if="hotelRatePerNight != null" class="px-4 py-2 text-caption text-medium-emphasis border-b">Entitlement: {{ formatMoney(hotelRatePerNight, hotelRateCurrency) }} / night × {{ tripNights }} night(s) = <strong>{{ formatMoney(hotelEligibleTotal, hotelRateCurrency) }}</strong></div>
                            <VDivider v-if="hotelRatePerNight != null" />
                            <div v-if="isGroup" class="px-4 pt-3 pb-3">
                              <div class="d-flex align-start ga-3 flex-wrap">
                                <div style="width: 280px">
                                  <div class="text-caption font-weight-bold mb-1 d-flex align-center ga-1">Payment Arrangement<VTooltip v-if="paidByOtherUnavailable" :text="paidByOtherUnavailableReason"><template #activator="{ props: tooltipProps }"><VIcon v-bind="tooltipProps" icon="ri-information-line" size="14" class="text-medium-emphasis" /></template></VTooltip></div>
                                  <VSelect :model-value="form.accommodation.payment_mode" :items="paymentModeItems" item-title="title" item-value="value" placeholder="Select payment" variant="outlined" density="comfortable" hide-details @update:model-value="mode => onPaymentMode(form.accommodation, mode)" />
                                </div>
                                <div v-if="form.accommodation.payment_mode === 'paid_by_other'" style="width: 280px">
                                  <div class="text-caption font-weight-bold mb-1">Paid by *</div>
                                  <VSelect :model-value="form.accommodation.paid_by" :items="payerOptions" item-title="label" item-value="id" placeholder="Select person who paid the bill" variant="outlined" density="comfortable" hide-details="auto" :error-messages="payErrorOf('accommodation')" @update:model-value="id => onSelectPayer(form.accommodation, id)" />
                                </div>
                              </div>
                              <div v-if="form.accommodation.payment_mode === 'paid_by_other'" class="border rounded-lg pa-3 bg-blue-lighten-5 text-body-2 d-flex align-center ga-2 mt-3"><span class="font-weight-bold text-primary">Not Claimed by Me</span><VTooltip :text="`This expense was paid by ${form.accommodation.paid_by_name || 'another employee'}, so your claim for this expense is 0.`"><template #activator="{ props }"><VIcon v-bind="props" icon="ri-information-line" size="16" class="text-medium-emphasis" /></template></VTooltip></div>
                            </div>
                            <VDivider />
                            <div class="pa-4">
                              <template v-if="isGroup && form.accommodation.payment_mode === 'pay_for_others'">
                                <div class="text-caption font-weight-bold mb-1">Paid For (select multiple) *</div>
                                <VCard variant="outlined" rounded="lg" class="pa-3 mb-3">
                                  <div class="d-flex flex-wrap">
                                    <VCheckboxBtn v-for="member in payForOptions" :key="member.id" :model-value="(form.accommodation.pay_for ?? []).includes(member.id)" :label="member.label" density="compact" hide-details style="width: 33.333%" @update:model-value="checked => onTogglePayFor(form.accommodation, member, checked)" />
                                  </div>
                                  <div v-if="!payForOptions.length" class="text-caption text-medium-emphasis">No other employee in this group.</div>
                                </VCard>
                                <div v-if="payErrorOf('accommodation')" class="text-caption text-error mt-1 mb-3">{{ payErrorOf('accommodation') }}</div>
                              </template>
                              <VRow dense>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Hotel Name</div><VTextField v-model="form.accommodation.hotel_name" :disabled="lockedOf(form.accommodation)" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Room Type</div><VTextField v-model="form.accommodation.room_type" :disabled="lockedOf(form.accommodation)" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Hotel Standard</div><VSelect :model-value="hotelStar ? `${hotelStar} Star` : ''" :items="hotelStar ? [`${hotelStar} Star`] : []" variant="outlined" density="comfortable" hide-details disabled /></VCol>
                              </VRow>
                              <div class="text-caption font-weight-bold mb-1 mt-3">Description *</div>
                              <VTextField v-model="form.accommodation.description" :disabled="lockedOf(form.accommodation)" variant="outlined" density="comfortable" hide-details="auto" :error-messages="accDescError()" />
                              <VRow dense class="mt-3">
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Currency</div><VSelect v-model="form.accommodation.currency" :items="currencyItems" :disabled="currencyLocked || lockedOf(form.accommodation)" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Actual Amount</div><VTextField :model-value="amountDisplayFor(form.accommodation)" type="text" inputmode="decimal" placeholder="0" :disabled="lockedOf(form.accommodation)" variant="outlined" density="comfortable" hide-details="auto" class="text-right-input" @keydown="e => blockAtMaxKeydown(e, form.accommodation.amount, hotelEligibleTotal)" @update:model-value="onAccommodationAmountInput" /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Eligible Estimate</div><VTextField :model-value="estimateDisplayFor(form.accommodation)" type="text" inputmode="numeric" placeholder="0" variant="outlined" density="comfortable" hide-details :readonly="lockedOf(form.accommodation) || isManualReview(form.accommodation)" class="text-right-input" @update:model-value="v => onEstimateInputFor(form.accommodation, v)" /></VCol>
                              </VRow>
                              <div class="mt-3"><div class="text-caption font-weight-bold mb-1">Supporting Document *</div><label class="d-inline-flex align-center ga-2 cursor-pointer" :style="lockedOf(form.accommodation) ? 'pointer-events: none; opacity: 0.6' : ''"><input type="file" class="d-none" @change="e => { form.accommodation.attachment = Array.from(e.target.files) }"><span class="file-btn">Choose File</span><span class="file-name">{{ displayNameOf(form.accommodation) }}</span></label><div v-if="accAttachError()" class="text-caption text-error mt-1">{{ accAttachError() }}</div></div>
                            </div>
                          </VCard>
                        </template>
                        <template v-else-if="c.key === 'ticket'">
                          <template v-if="!ticketClaimable">
                            <div class="text-caption text-medium-emphasis mb-2">Booked by Company · Not Reimbursable</div>
                            <div class="border rounded-lg pa-3 bg-blue-lighten-5 text-body-2 d-flex align-center ga-2"><span class="font-weight-bold text-primary">Booked by Company</span><VTooltip text="Ticket is arranged and paid by the company, so it is not reimbursable and cannot be claimed in this report."><template #activator="{ props }"><VIcon v-bind="props" icon="ri-information-line" size="16" class="text-medium-emphasis" /></template></VTooltip></div>
                          </template>
                          <VCard v-else rounded="lg" elevation="0" border>
                            <div class="px-4 py-3 bg-grey-lighten-5"><span class="text-subtitle-2 font-weight-bold">Ticket</span><VChip size="x-small" color="info" variant="tonal" class="ml-2">Direct Payment</VChip></div>
                            <VDivider />
                            <div v-if="isGroup" class="px-4 pt-3 pb-3">
                              <div class="d-flex align-start ga-3 flex-wrap">
                                <div style="width: 280px">
                                  <div class="text-caption font-weight-bold mb-1 d-flex align-center ga-1">Payment Arrangement<VTooltip v-if="paidByOtherUnavailable" :text="paidByOtherUnavailableReason"><template #activator="{ props: tooltipProps }"><VIcon v-bind="tooltipProps" icon="ri-information-line" size="14" class="text-medium-emphasis" /></template></VTooltip></div>
                                  <VSelect :model-value="form.ticket.payment_mode" :items="paymentModeItems" item-title="title" item-value="value" placeholder="Select payment" variant="outlined" density="comfortable" hide-details @update:model-value="mode => onPaymentMode(form.ticket, mode)" />
                                </div>
                                <div v-if="form.ticket.payment_mode === 'paid_by_other'" style="width: 280px">
                                  <div class="text-caption font-weight-bold mb-1">Paid by *</div>
                                  <VSelect :model-value="form.ticket.paid_by" :items="payerOptions" item-title="label" item-value="id" placeholder="Select person who paid the bill" variant="outlined" density="comfortable" hide-details="auto" :error-messages="payErrorOf('ticket')" @update:model-value="id => onSelectPayer(form.ticket, id)" />
                                </div>
                              </div>
                              <div v-if="form.ticket.payment_mode === 'paid_by_other'" class="border rounded-lg pa-3 bg-blue-lighten-5 text-body-2 d-flex align-center ga-2 mt-3"><span class="font-weight-bold text-primary">Not Claimed by Me</span><VTooltip :text="`This expense was paid by ${form.ticket.paid_by_name || 'another employee'}, so your claim for this expense is 0.`"><template #activator="{ props }"><VIcon v-bind="props" icon="ri-information-line" size="16" class="text-medium-emphasis" /></template></VTooltip></div>
                            </div>
                            <VDivider />
                            <div class="pa-4">
                              <template v-if="isGroup && form.ticket.payment_mode === 'pay_for_others'">
                                <div class="text-caption font-weight-bold mb-1">Paid For (select multiple) *</div>
                                <VCard variant="outlined" rounded="lg" class="pa-3 mb-3">
                                  <div class="d-flex flex-wrap">
                                    <VCheckboxBtn v-for="member in payForOptions" :key="member.id" :model-value="(form.ticket.pay_for ?? []).includes(member.id)" :label="member.label" density="compact" hide-details style="width: 33.333%" @update:model-value="checked => onTogglePayFor(form.ticket, member, checked)" />
                                  </div>
                                  <div v-if="!payForOptions.length" class="text-caption text-medium-emphasis">No other employee in this group.</div>
                                </VCard>
                                <div v-if="payErrorOf('ticket')" class="text-caption text-error mt-1 mb-3">{{ payErrorOf('ticket') }}</div>
                              </template>
                              <div class="text-caption font-weight-bold mb-1">Ticket Type</div>
                              <VSelect v-model="form.ticket.ticket_type" :items="TICKET_TYPES" :disabled="lockedOf(form.ticket)" variant="outlined" density="comfortable" hide-details />
                              <div class="text-caption font-weight-bold mb-1 mt-3">Description *</div>
                              <VTextField v-model="form.ticket.description" :disabled="lockedOf(form.ticket)" variant="outlined" density="comfortable" hide-details="auto" :error-messages="ticketDescError()" />
                              <VRow dense class="mt-3">
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Currency</div><VSelect v-model="form.ticket.currency" :items="currencyItems" :disabled="currencyLocked || lockedOf(form.ticket)" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Actual Amount</div><VTextField :model-value="amountDisplayFor(form.ticket)" type="text" inputmode="decimal" placeholder="0" :disabled="lockedOf(form.ticket)" variant="outlined" density="comfortable" hide-details="auto" class="text-right-input" @update:model-value="v => onAmountInputWithEstimate(form.ticket, v)" /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Eligible Estimate</div><VTextField :model-value="estimateDisplayFor(form.ticket)" type="text" inputmode="numeric" placeholder="0" variant="outlined" density="comfortable" hide-details :readonly="lockedOf(form.ticket) || isManualReview(form.ticket)" class="text-right-input" @update:model-value="v => onEstimateInputFor(form.ticket, v)" /></VCol>
                              </VRow>
                              <div class="mt-3"><div class="text-caption font-weight-bold mb-1">Supporting Document *</div><label class="d-inline-flex align-center ga-2 cursor-pointer" :style="lockedOf(form.ticket) ? 'pointer-events: none; opacity: 0.6' : ''"><input type="file" class="d-none" @change="e => { form.ticket.attachment = Array.from(e.target.files) }"><span class="file-btn">Choose File</span><span class="file-name">{{ displayNameOf(form.ticket) }}</span></label><div v-if="ticketAttachError()" class="text-caption text-error mt-1">{{ ticketAttachError() }}</div></div>
                            </div>
                          </VCard>
                        </template>
                        <template v-else-if="c.key === 'pocket_money' && pocketNotEligible">
                          <div class="text-caption text-medium-emphasis mb-2">Not Eligible · No Daily Allowance</div>
                          <div class="border rounded-lg pa-3 bg-blue-lighten-5 text-body-2 d-flex align-center ga-2"><span class="font-weight-bold text-primary">Not Eligible</span><VTooltip text="Pocket Money is not available for this destination. The destination does not receive a daily allowance, so there is nothing to claim for this category."><template #activator="{ props }"><VIcon v-bind="props" icon="ri-information-line" size="16" class="text-medium-emphasis" /></template></VTooltip></div>
                        </template>
                        <template v-else-if="isSimpleClaim(c)">
                          <VCard rounded="lg" elevation="0" border>
                            <div class="px-4 py-3 bg-grey-lighten-5"><span class="text-subtitle-2 font-weight-bold">{{ c.label }}</span></div>
                            <VDivider />
                            <div v-if="isGroup && c.key !== 'pocket_money'" class="px-4 pt-3 pb-3">
                              <div class="d-flex align-start ga-3 flex-wrap">
                                <div style="width: 280px">
                                  <div class="text-caption font-weight-bold mb-1 d-flex align-center ga-1">Payment Arrangement<VTooltip v-if="paidByOtherUnavailable" :text="paidByOtherUnavailableReason"><template #activator="{ props: tooltipProps }"><VIcon v-bind="tooltipProps" icon="ri-information-line" size="14" class="text-medium-emphasis" /></template></VTooltip></div>
                                  <VSelect :model-value="itemOf(c).payment_mode" :items="paymentModeItems" item-title="title" item-value="value" placeholder="Select payment" variant="outlined" density="comfortable" hide-details @update:model-value="mode => onPaymentMode(itemOf(c), mode)" />
                                </div>
                                <div v-if="itemOf(c).payment_mode === 'paid_by_other'" style="width: 280px">
                                  <div class="text-caption font-weight-bold mb-1">Paid by *</div>
                                  <VSelect :model-value="itemOf(c).paid_by" :items="payerOptions" item-title="label" item-value="id" placeholder="Select person who paid the bill" variant="outlined" density="comfortable" hide-details="auto" :error-messages="payErrorOf(errorKeyOf(c))" @update:model-value="id => onSelectPayer(itemOf(c), id)" />
                                </div>
                              </div>
                              <div v-if="itemOf(c).payment_mode === 'paid_by_other'" class="border rounded-lg pa-3 bg-blue-lighten-5 text-body-2 d-flex align-center ga-2 mt-3"><span class="font-weight-bold text-primary">Not Claimed by Me</span><VTooltip :text="`This expense was paid by ${itemOf(c).paid_by_name || 'another employee'}, so your claim for this expense is 0.`"><template #activator="{ props }"><VIcon v-bind="props" icon="ri-information-line" size="16" class="text-medium-emphasis" /></template></VTooltip></div>
                            </div>
                            <VDivider />
                            <div class="pa-4">
                              <template v-if="isGroup && itemOf(c).payment_mode === 'pay_for_others'">
                                <div class="text-caption font-weight-bold mb-1">Paid For (select multiple) *</div>
                                <VCard variant="outlined" rounded="lg" class="pa-3 mb-3">
                                  <div class="d-flex flex-wrap">
                                    <VCheckboxBtn v-for="member in payForOptions" :key="member.id" :model-value="(itemOf(c).pay_for ?? []).includes(member.id)" :label="member.label" density="compact" hide-details style="width: 33.333%" @update:model-value="checked => onTogglePayFor(itemOf(c), member, checked)" />
                                  </div>
                                  <div v-if="!payForOptions.length" class="text-caption text-medium-emphasis">No other employee in this group.</div>
                                </VCard>
                                <div v-if="payErrorOf(errorKeyOf(c))" class="text-caption text-error mt-1 mb-3">{{ payErrorOf(errorKeyOf(c)) }}</div>
                              </template>
                              <div v-if="c.key === 'dinner'" class="mb-3"><div class="text-caption font-weight-bold mb-1">Dinner Type</div><VSelect v-model="itemOf(c).dinner_type" :items="DINNER_TYPES" :disabled="lockedOf(itemOf(c))" variant="outlined" density="comfortable" hide-details /></div>
                              <div class="text-caption font-weight-bold mb-1">Description *</div>
                              <VTextField v-model="itemOf(c).description" :disabled="lockedOf(itemOf(c))" variant="outlined" density="comfortable" hide-details="auto" :error-messages="descError(c)" />
                              <div v-if="c.key === 'dinner' && itemOf(c).dinner_type === DINNER_AT_HOTEL" class="mt-3"><div class="text-caption font-weight-bold mb-1">Reason / Justification *</div><VTextarea v-model="itemOf(c).justification" :disabled="lockedOf(itemOf(c))" variant="outlined" density="comfortable" rows="3" hide-details="auto" :error-messages="dinnerJustificationError()" /></div>
                              <VRow dense class="mt-3">
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Currency</div><VSelect v-model="itemOf(c).currency" :items="currencyItems" :disabled="currencyLocked || lockedOf(itemOf(c))" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Actual Amount</div><VTextField :model-value="amountDisplayFor(itemOf(c))" type="text" inputmode="numeric" placeholder="0" :disabled="lockedOf(itemOf(c))" variant="outlined" density="comfortable" hide-details="auto" :error-messages="amountError(c)" class="text-right-input" @keydown="e => blockAtMaxKeydown(e, itemOf(c).amount, simpleMaxOf(c))" @update:model-value="v => onAmountInput(c, v)" /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Eligible Entitlement</div><VTextField :model-value="lockedOf(itemOf(c)) ? '—' : itemOf(c).payment_mode === 'pay_for_others' ? 'Manual Review' : isManualReview(itemOf(c)) ? 'Manual Review' : c.atCost ? 'At Cost' : formatMoney(c.eligible, c.currency)" variant="outlined" density="comfortable" hide-details readonly /></VCol>
                              </VRow>
                              <div class="mt-3"><div class="text-caption font-weight-bold mb-1">Supporting Document</div><label class="d-inline-flex align-center ga-2 cursor-pointer" :style="lockedOf(itemOf(c)) ? 'pointer-events: none; opacity: 0.6' : ''"><input type="file" class="d-none" @change="e => onAttachment(c, e)"><span class="file-btn">Choose File</span><span class="file-name">{{ displayNameOf(itemOf(c)) }}</span></label><div v-if="attachError(c)" class="text-caption text-error mt-1">{{ attachError(c) }}</div></div>
                            </div>
                          </VCard>
                        </template>
                        <template v-else-if="c.key === 'telephone'">
                          <VCard rounded="lg" elevation="0" border>
                            <div class="px-4 py-3 bg-grey-lighten-5"><span class="text-subtitle-2 font-weight-bold">Personal Telephone</span><VChip size="x-small" color="grey" variant="tonal" class="ml-2">Fixed Entitlement</VChip></div>
                            <VDivider />
                            <VDivider />
                            <div class="pa-4">
                              <div class="text-caption font-weight-bold mb-1">Description *</div>
                              <VTextField v-model="form.telephone.description" variant="outlined" density="comfortable" hide-details="auto" :error-messages="telDescError()" />
                              <VRow dense class="mt-3">
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Currency</div><VSelect v-model="form.telephone.currency" :items="currencyItems" :disabled="currencyLocked" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Actual Amount</div><VTextField :model-value="amountDisplayFor(form.telephone)" type="text" inputmode="decimal" placeholder="0" variant="outlined" density="comfortable" hide-details="auto" :error-messages="telAmountError()" class="text-right-input" @keydown="e => blockAtMaxKeydown(e, form.telephone.amount, TELEPHONE_BUDGET)" @update:model-value="onTelephoneAmountInput" /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Eligible Entitlement</div><VTextField :model-value="isManualReview(form.telephone) ? 'Manual Review' : formatIDR(TELEPHONE_BUDGET)" variant="outlined" density="comfortable" hide-details readonly /></VCol>
                              </VRow>
                              <div class="mt-3"><div class="text-caption font-weight-bold mb-1">Supporting Document</div><label class="d-inline-flex align-center ga-2 cursor-pointer"><input type="file" class="d-none" @change="e => { form.telephone.attachment = Array.from(e.target.files) }"><span class="file-btn">Choose File</span><span class="file-name">{{ displayNameOf(form.telephone) }}</span></label><div v-if="telAttachError()" class="text-caption text-error mt-1">{{ telAttachError() }}</div></div>
                            </div>
                          </VCard>
                          <VCard rounded="lg" elevation="0" border>
                            <div class="px-4 py-3 bg-grey-lighten-5"><span class="text-subtitle-2 font-weight-bold">Office Telephone</span><VChip size="x-small" color="grey" variant="tonal" class="ml-2">Optional</VChip></div>
                            <VDivider />
                            <div v-if="isGroup" class="px-4 pt-3 pb-3">
                              <div class="d-flex align-start ga-3 flex-wrap">
                                <div style="width: 280px">
                                  <div class="text-caption font-weight-bold mb-1 d-flex align-center ga-1">Payment Arrangement<VTooltip v-if="paidByOtherUnavailable" :text="paidByOtherUnavailableReason"><template #activator="{ props: tooltipProps }"><VIcon v-bind="tooltipProps" icon="ri-information-line" size="14" class="text-medium-emphasis" /></template></VTooltip></div>
                                  <VSelect :model-value="form.office_telephone.payment_mode" :items="paymentModeItems" item-title="title" item-value="value" placeholder="Select payment" variant="outlined" density="comfortable" hide-details @update:model-value="mode => onPaymentMode(form.office_telephone, mode)" />
                                </div>
                                <div v-if="form.office_telephone.payment_mode === 'paid_by_other'" style="width: 280px">
                                  <div class="text-caption font-weight-bold mb-1">Paid by *</div>
                                  <VSelect :model-value="form.office_telephone.paid_by" :items="payerOptions" item-title="label" item-value="id" placeholder="Select person who paid the bill" variant="outlined" density="comfortable" hide-details="auto" :error-messages="payErrorOf('office_telephone')" @update:model-value="id => onSelectPayer(form.office_telephone, id)" />
                                </div>
                              </div>
                              <div v-if="form.office_telephone.payment_mode === 'paid_by_other'" class="border rounded-lg pa-3 bg-blue-lighten-5 text-body-2 d-flex align-center ga-2 mt-3"><span class="font-weight-bold text-primary">Not Claimed by Me</span><VTooltip :text="`This expense was paid by ${form.office_telephone.paid_by_name || 'another employee'}, so your claim for this expense is 0.`"><template #activator="{ props }"><VIcon v-bind="props" icon="ri-information-line" size="16" class="text-medium-emphasis" /></template></VTooltip></div>
                            </div>
                            <VDivider />
                            <div class="pa-4">
                              <template v-if="isGroup && form.office_telephone.payment_mode === 'pay_for_others'">
                                <div class="text-caption font-weight-bold mb-1">Paid For (select multiple) *</div>
                                <VCard variant="outlined" rounded="lg" class="pa-3 mb-3">
                                  <div class="d-flex flex-wrap">
                                    <VCheckboxBtn v-for="member in payForOptions" :key="member.id" :model-value="(form.office_telephone.pay_for ?? []).includes(member.id)" :label="member.label" density="compact" hide-details style="width: 33.333%" @update:model-value="checked => onTogglePayFor(form.office_telephone, member, checked)" />
                                  </div>
                                  <div v-if="!payForOptions.length" class="text-caption text-medium-emphasis">No other employee in this group.</div>
                                </VCard>
                                <div v-if="payErrorOf('office_telephone')" class="text-caption text-error mt-1 mb-3">{{ payErrorOf('office_telephone') }}</div>
                              </template>
                              <div class="text-caption font-weight-bold mb-1">Description *</div>
                              <VTextField v-model="form.office_telephone.description" :disabled="lockedOf(form.office_telephone)" variant="outlined" density="comfortable" hide-details="auto" :error-messages="otDescError()" />
                              <VRow dense class="mt-3">
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Currency</div><VSelect v-model="form.office_telephone.currency" :items="currencyItems" :disabled="currencyLocked || lockedOf(form.office_telephone)" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Actual Amount</div><VTextField :model-value="amountDisplayFor(form.office_telephone)" type="text" inputmode="decimal" placeholder="0" :disabled="lockedOf(form.office_telephone)" variant="outlined" density="comfortable" hide-details="auto" class="text-right-input" @update:model-value="v => onAmountInputWithEstimate(form.office_telephone, v)" /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Eligible Estimate</div><VTextField :model-value="estimateDisplayFor(form.office_telephone)" type="text" inputmode="numeric" placeholder="0" variant="outlined" density="comfortable" hide-details :readonly="lockedOf(form.office_telephone) || isManualReview(form.office_telephone)" class="text-right-input" @update:model-value="v => onEstimateInputFor(form.office_telephone, v)" /></VCol>
                              </VRow>
                              <div class="mt-3"><div class="text-caption font-weight-bold mb-1">Supporting Document</div><label class="d-inline-flex align-center ga-2 cursor-pointer" :style="lockedOf(form.office_telephone) ? 'pointer-events: none; opacity: 0.6' : ''"><input type="file" class="d-none" @change="e => { form.office_telephone.attachment = Array.from(e.target.files) }"><span class="file-btn">Choose File</span><span class="file-name">{{ displayNameOf(form.office_telephone) }}</span></label><div v-if="otAttachError()" class="text-caption text-error mt-1">{{ otAttachError() }}</div></div>
                            </div>
                          </VCard>
                        </template>
                        <template v-else-if="c.key === 'laundry'">
                          <VCard rounded="lg" elevation="0" border>
                            <div class="px-4 py-3 bg-grey-lighten-5"><span class="text-subtitle-2 font-weight-bold">Laundry</span></div>
                            <VDivider />
                            <div v-if="isGroup" class="px-4 pt-3 pb-3">
                              <div class="d-flex align-start ga-3 flex-wrap">
                                <div style="width: 280px">
                                  <div class="text-caption font-weight-bold mb-1 d-flex align-center ga-1">Payment Arrangement<VTooltip v-if="paidByOtherUnavailable" :text="paidByOtherUnavailableReason"><template #activator="{ props: tooltipProps }"><VIcon v-bind="tooltipProps" icon="ri-information-line" size="14" class="text-medium-emphasis" /></template></VTooltip></div>
                                  <VSelect :model-value="form.laundry.payment_mode" :items="paymentModeItems" item-title="title" item-value="value" placeholder="Select payment" variant="outlined" density="comfortable" hide-details @update:model-value="mode => onPaymentMode(form.laundry, mode)" />
                                </div>
                                <div v-if="form.laundry.payment_mode === 'paid_by_other'" style="width: 280px">
                                  <div class="text-caption font-weight-bold mb-1">Paid by *</div>
                                  <VSelect :model-value="form.laundry.paid_by" :items="payerOptions" item-title="label" item-value="id" placeholder="Select person who paid the bill" variant="outlined" density="comfortable" hide-details="auto" :error-messages="payErrorOf('laundry')" @update:model-value="id => onSelectPayer(form.laundry, id)" />
                                </div>
                              </div>
                              <div v-if="form.laundry.payment_mode === 'paid_by_other'" class="border rounded-lg pa-3 bg-blue-lighten-5 text-body-2 d-flex align-center ga-2 mt-3"><span class="font-weight-bold text-primary">Not Claimed by Me</span><VTooltip :text="`This expense was paid by ${form.laundry.paid_by_name || 'another employee'}, so your claim for this expense is 0.`"><template #activator="{ props }"><VIcon v-bind="props" icon="ri-information-line" size="16" class="text-medium-emphasis" /></template></VTooltip></div>
                            </div>
                            <VDivider />
                            <div class="pa-4">
                              <template v-if="isGroup && form.laundry.payment_mode === 'pay_for_others'">
                                <div class="text-caption font-weight-bold mb-1">Paid For (select multiple) *</div>
                                <VCard variant="outlined" rounded="lg" class="pa-3 mb-3">
                                  <div class="d-flex flex-wrap">
                                    <VCheckboxBtn v-for="member in payForOptions" :key="member.id" :model-value="(form.laundry.pay_for ?? []).includes(member.id)" :label="member.label" density="compact" hide-details style="width: 33.333%" @update:model-value="checked => onTogglePayFor(form.laundry, member, checked)" />
                                  </div>
                                  <div v-if="!payForOptions.length" class="text-caption text-medium-emphasis">No other employee in this group.</div>
                                </VCard>
                                <div v-if="payErrorOf('laundry')" class="text-caption text-error mt-1 mb-3">{{ payErrorOf('laundry') }}</div>
                              </template>
                              <div class="text-caption font-weight-bold mb-1">Description *</div>
                              <VTextField v-model="form.laundry.description" :disabled="lockedOf(form.laundry)" variant="outlined" density="comfortable" hide-details="auto" :error-messages="launDescError()" />
                              <VRow dense class="mt-3">
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Currency</div><VSelect v-model="form.laundry.currency" :items="currencyItems" :disabled="currencyLocked || lockedOf(form.laundry)" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Actual Amount</div><VTextField :model-value="amountDisplayFor(form.laundry)" type="text" inputmode="decimal" placeholder="0" :disabled="lockedOf(form.laundry)" variant="outlined" density="comfortable" hide-details="auto" class="text-right-input" @update:model-value="v => onAmountInputWithEstimate(form.laundry, v)" /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Eligible Estimate</div><VTextField :model-value="estimateDisplayFor(form.laundry)" type="text" inputmode="numeric" placeholder="0" variant="outlined" density="comfortable" hide-details :readonly="lockedOf(form.laundry) || isManualReview(form.laundry)" class="text-right-input" @update:model-value="v => onEstimateInputFor(form.laundry, v)" /></VCol>
                              </VRow>
                              <div class="mt-3"><div class="text-caption font-weight-bold mb-1">Supporting Document *</div><label class="d-inline-flex align-center ga-2 cursor-pointer" :style="lockedOf(form.laundry) ? 'pointer-events: none; opacity: 0.6' : ''"><input type="file" class="d-none" @change="e => { form.laundry.attachment = Array.from(e.target.files) }"><span class="file-btn">Choose File</span><span class="file-name">{{ displayNameOf(form.laundry) }}</span></label><div v-if="launAttachError()" class="text-caption text-error mt-1">{{ launAttachError() }}</div></div>
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
input[type="file"] { display: none; }
.file-btn { display: inline-block; padding: 1px 10px; border: thin solid rgba(var(--v-border-color), 0.3); border-radius: 4px; background: rgba(var(--v-theme-on-surface), 0.06); font-size: 13px; font-weight: 500; color: rgb(var(--v-theme-on-surface)); }
.file-name { font-size: 13px; color: rgba(var(--v-theme-on-surface), 0.6); }
.text-right-input :deep(input) { text-align: right; }
</style>