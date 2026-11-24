<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({ modelValue: { type: Boolean, default: false }, day: { type: Object, default: null }, totalDays: { type: Number, default: 0 }, initial: { type: Object, default: null }, isLast: { type: Boolean, default: false }, usedElsewhere: { type: Object, default: () => ({ meals: 0, pocket_money: 0 }) } })
const emit = defineEmits(['update:modelValue', 'save', 'draft'])

const MEAL_BUDGETS = { breakfast: 35000, lunch: 65000, dinner: 80000 }
const POCKET_BUDGET = 125000
const TELEPHONE_BUDGET = 15000
const LAUNDRY_FROM_DAY = 6
const formatIDR = value => `IDR ${new Intl.NumberFormat('id-ID').format(value)}`

const createItem = (amount = '', description = '') => ({ currency: 'IDR', amount: String(amount), description, paid_by_others: false, attachment: null })
const cloneItem = item => ({ ...item, attachment: item?.attachment ?? null })
const cloneForm = value => ({
  different_countries: !!value?.different_countries,
  meals: { breakfast: cloneItem(value?.meals?.breakfast ?? createItem()), lunch: cloneItem(value?.meals?.lunch ?? createItem()), dinner: cloneItem(value?.meals?.dinner ?? createItem()) },
  pocket_money: value?.pocket_money ? cloneItem(value.pocket_money) : createItem(),
  ticket: value?.ticket ? { ...cloneItem(value.ticket), ticket_type: value.ticket.ticket_type ?? '', eligible_estimate: value.ticket.eligible_estimate ?? '' } : { ...createItem(), ticket_type: '', eligible_estimate: '' },
  accommodation: value?.accommodation ? { ...cloneItem(value.accommodation), hotel_name: value.accommodation.hotel_name ?? '', room_type: value.accommodation.room_type ?? '', hotel_standard: value.accommodation.hotel_standard ?? '', eligible_estimate: value.accommodation.eligible_estimate ?? '' } : { ...createItem(), hotel_name: '', room_type: '', hotel_standard: '', eligible_estimate: '' },
  laundry: value?.laundry ? { ...cloneItem(value.laundry), eligible_estimate: value.laundry.eligible_estimate ?? '' } : { ...createItem(), eligible_estimate: '' },
  telephone: value?.telephone ? cloneItem(value.telephone) : createItem(),
  office_telephone: value?.office_telephone ? cloneItem(value.office_telephone) : createItem(),
  transports: (value?.transports ?? []).map(transport => ({ ...cloneItem(transport), eligible_estimate: transport?.eligible_estimate ?? '' })),
  others: (value?.others ?? []).map(other => ({ ...cloneItem(other), eligible_estimate: other?.eligible_estimate ?? '' })),
})
const form = ref(cloneForm(null))
watch(() => props.initial, value => { form.value = cloneForm(value) }, { immediate: true })

const dayNumber = computed(() => props.day?.key ?? 1)
const showLaundry = computed(() => props.totalDays >= LAUNDRY_FROM_DAY)
const laundryLocked = computed(() => dayNumber.value < LAUNDRY_FROM_DAY)
const usedMeals = computed(() => Number(props.usedElsewhere?.meals) || 0)
const usedPocket = computed(() => Number(props.usedElsewhere?.pocket_money) || 0)

const mealLimit = key => { const budget = MEAL_BUDGETS[key]; const max = Math.max(0, budget - usedMeals.value); return { budgetLabel: 'BUDGET / MEAL', budget, max, remaining: max, note: `Meals claimed on other days: ${formatIDR(usedMeals.value)}. Maximum input for this day is adjusted automatically.` } }
const pocketLimit = computed(() => { const max = Math.max(0, POCKET_BUDGET - usedPocket.value); return { budgetLabel: 'TRIP BUDGET', budget: POCKET_BUDGET, max, remaining: max, note: `Pocket money claimed on other days: ${formatIDR(usedPocket.value)}.` } })

const claimed = item => !!item && !item.paid_by_others && Number(item.amount) > 0
const hasAttachment = item => !!(Array.isArray(item.attachment) ? item.attachment.length : item.attachment)
const touched = item => !!item && (item.paid_by_others || Number(item.amount) > 0 || !!String(item.description ?? '').trim())
const errors = computed(() => {
  const map = {}
  const check = (key, item, { max = null, fixed = false } = {}) => { if (!claimed(item)) return; if (max !== null && Number(item.amount) > max) map[key] ??= `Amount exceeds the maximum allowed (${formatIDR(max)}).`; if (!String(item.description ?? '').trim()) map[key] ??= 'Description is required.'; if (!fixed && !hasAttachment(item)) map[key] ??= 'Attachment is required.' }
  Object.entries(form.value.meals).forEach(([key, item]) => check(`meals.${key}`, item, { max: mealLimit(key).max }))
  check('pocket_money', form.value.pocket_money, { max: pocketLimit.value.max })
  check('ticket', form.value.ticket)
  check('accommodation', form.value.accommodation)
  if (showLaundry.value && !laundryLocked.value) check('laundry', form.value.laundry)
  check('telephone', form.value.telephone, { max: TELEPHONE_BUDGET })
  check('office_telephone', form.value.office_telephone)
  form.value.transports.forEach((item, index) => check(`transports.${index}`, item))
  form.value.others.forEach((item, index) => check(`others.${index}`, item))
  return map
})
const errorOf = key => errors.value[key] ?? ''
const canSave = computed(() => Object.keys(errors.value).length === 0)

const SIMPLE_CLAIM_KEYS = ['breakfast', 'lunch', 'dinner', 'pocket_money']
const isSimpleClaim = c => SIMPLE_CLAIM_KEYS.includes(c.key)
const itemOf = c => (c.key === 'pocket_money' ? form.value.pocket_money : form.value.meals[c.key])
const errorKeyOf = c => (c.key === 'pocket_money' ? 'pocket_money' : `meals.${c.key}`)
const errOf = c => errorOf(errorKeyOf(c))
const descError = c => (errOf(c) === 'Description is required.' ? errOf(c) : '')
const amountError = c => (errOf(c).startsWith('Amount exceeds') ? errOf(c) : '')
const attachError = c => (errOf(c) === 'Attachment is required.' ? errOf(c) : '')
const amountDisplay = item => (item.amount === '' || item.amount == null ? '' : new Intl.NumberFormat('id-ID').format(Number(item.amount)))
const onAmountInput = (c, value) => { itemOf(c).amount = String(value).replace(/[^0-9]/g, '') }
const fileNames = attachment => (Array.isArray(attachment) ? attachment : attachment ? [attachment] : []).map(file => file?.name ?? file).join(', ')
const onAttachment = (c, event) => { itemOf(c).attachment = Array.from(event.target.files) }

const TICKET_TYPES = ['Airplane', 'Train', 'Ferry', 'Bus', 'Other']
const TICKET_CURRENCIES = ['IDR', 'USD', 'SGD', 'MYR', 'EUR']
const HOTEL_STANDARDS = ['1 Star', '2 Star', '3 Star', '4 Star', '5 Star']
const ticketErr = () => errorOf('ticket')
const ticketDescError = () => (ticketErr() === 'Description is required.' ? ticketErr() : '')
const ticketAttachError = () => (ticketErr() === 'Attachment is required.' ? ticketErr() : '')
const accErr = () => errorOf('accommodation')
const accDescError = () => (accErr() === 'Description is required.' ? accErr() : '')
const accAttachError = () => (accErr() === 'Attachment is required.' ? accErr() : '')
const launErr = () => errorOf('laundry')
const launDescError = () => (launErr() === 'Description is required.' ? launErr() : '')
const launAttachError = () => (launErr() === 'Attachment is required.' ? launErr() : '')
const telErr = () => errorOf('telephone')
const telDescError = () => (telErr() === 'Description is required.' ? telErr() : '')
const telAmountError = () => (telErr().startsWith('Amount exceeds') ? telErr() : '')
const telAttachError = () => (telErr() === 'Attachment is required.' ? telErr() : '')
const otErr = () => errorOf('office_telephone')
const otDescError = () => (otErr() === 'Description is required.' ? otErr() : '')
const otAttachError = () => (otErr() === 'Attachment is required.' ? otErr() : '')
const transportErr = index => errorOf(`transports.${index}`)
const transportDescError = index => (transportErr(index) === 'Description is required.' ? transportErr(index) : '')
const transportAttachError = index => (transportErr(index) === 'Attachment is required.' ? transportErr(index) : '')
const otherErr = index => errorOf(`others.${index}`)
const otherDescError = index => (otherErr(index) === 'Description is required.' ? otherErr(index) : '')
const otherAttachError = index => (otherErr(index) === 'Attachment is required.' ? otherErr(index) : '')
const amountDisplayFor = item => { const value = item.amount; if (value === '' || value == null || Number.isNaN(Number(value))) return ''; return new Intl.NumberFormat(item.currency === 'IDR' ? 'id-ID' : 'en-US', { maximumFractionDigits: 2 }).format(Number(value)) }
const onAmountInputFor = (item, value) => { item.amount = item.currency === 'IDR' ? String(value).replace(/[^0-9]/g, '') : String(value).replace(/[^0-9.]/g, '') }
const estimateDisplayFor = item => (item.eligible_estimate === '' ? '' : new Intl.NumberFormat('id-ID').format(Number(item.eligible_estimate)))
const onEstimateInputFor = (item, value) => { item.eligible_estimate = String(value).replace(/[^0-9]/g, '') }

const sumItem = item => (claimed(item) ? Number(item.amount) || 0 : 0)
const sumList = list => (list ?? []).reduce((sum, item) => sum + sumItem(item), 0)
const categories = computed(() => [
  { key: 'breakfast', label: 'Breakfast', actual: sumItem(form.value.meals.breakfast), eligible: MEAL_BUDGETS.breakfast },
  { key: 'lunch', label: 'Lunch', actual: sumItem(form.value.meals.lunch), eligible: MEAL_BUDGETS.lunch },
  { key: 'dinner', label: 'Dinner', actual: sumItem(form.value.meals.dinner), eligible: MEAL_BUDGETS.dinner },
  { key: 'pocket_money', label: 'Pocket Money', actual: sumItem(form.value.pocket_money), eligible: POCKET_BUDGET },
  { key: 'ticket', label: 'Ticket', actual: sumItem(form.value.ticket), eligible: Number(form.value.ticket.eligible_estimate) || null, hasData: touched(form.value.ticket) },
  { key: 'accommodation', label: 'Accommodation', actual: sumItem(form.value.accommodation), eligible: Number(form.value.accommodation.eligible_estimate) || null, hasData: touched(form.value.accommodation) },
  ...(showLaundry.value ? [{ key: 'laundry', label: 'Laundry', actual: sumItem(form.value.laundry), eligible: Number(form.value.laundry.eligible_estimate) || null, availableFromDay: LAUNDRY_FROM_DAY, hasData: touched(form.value.laundry) }] : []),
  { key: 'telephone', label: 'Telephone', actual: sumItem(form.value.telephone) + sumItem(form.value.office_telephone), eligible: TELEPHONE_BUDGET, hasData: touched(form.value.telephone) || touched(form.value.office_telephone) },
  { key: 'transports', label: 'Transport', actual: sumList(form.value.transports), eligible: null, hasData: form.value.transports.some(touched) },
  { key: 'others', label: 'Other', actual: sumList(form.value.others), eligible: null, hasData: form.value.others.some(touched) },
])
const actionOf = c => (c.availableFromDay && laundryLocked.value ? 'View' : c.hasData ? 'Edit' : 'Add')

const expanded = ref(null)
watch(() => props.modelValue, open => { if (open) expanded.value = null })

const onAction = c => { expanded.value = expanded.value === c.key ? null : c.key }

const addTransport = () => form.value.transports.push({ ...createItem(), kind: 'perjalanan', eligible_estimate: '' })
const addOther = () => form.value.others.push({ ...createItem(), eligible_estimate: '' })
const removeAt = (list, index) => list.splice(index, 1)

const close = () => emit('update:modelValue', false)
const onSaveDraft = () => emit('draft', { day: dayNumber.value, ...form.value })
const onSave = () => { if (canSave.value) emit('save', { day: dayNumber.value, ...form.value }) }
</script>

<template>
  <VDialog :model-value="modelValue" max-width="900" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <div class="d-flex align-center justify-space-between px-4 py-3"><div><span class="text-h6 font-weight-bold">Expense Entry</span><div class="text-caption text-medium-emphasis">{{ day ? `${day.title} · ${day.date}${day.effective ? ` · Effective ${day.effective}` : ''}` : '' }}</div></div><VBtn icon="ri-close-line" variant="text" size="small" @click="close" /></div>
      <VDivider />
      <VCardText class="pa-4 bg-grey-lighten-5">
        <div class="d-flex ga-2 mb-4 overflow-auto">
          <VCard v-for="i in totalDays" :key="i" :variant="i === dayNumber ? 'tonal' : 'outlined'" :color="i === dayNumber ? 'primary' : undefined" class="px-3 py-2 bg-white flex-shrink-0" :style="i === dayNumber ? undefined : 'opacity: 0.6'"><div class="text-caption font-weight-bold">Day {{ i }}</div><div v-if="i === dayNumber" class="text-body-2">{{ day?.date }}{{ day?.effective ? ` - ${day.effective}` : '' }}</div><div v-else class="text-body-2 text-medium-emphasis">&nbsp;</div></VCard>
        </div>

        <VCard variant="outlined" rounded="lg" class="mb-4 bg-white">
          <div class="d-flex justify-space-between align-center px-4 py-3 border-b"><div class="text-subtitle-1 font-weight-bold">Daily Travel Context</div><div class="text-caption text-medium-emphasis">Controls automatic allowance validation for this day.</div></div>
          <div class="d-flex justify-space-between align-center px-4 py-3"><div><div class="text-body-2 font-weight-bold">Different countries in one day</div><div class="text-caption text-primary">Adjusts allowance validation when crossing countries during the same day.</div></div><VSwitch v-model="form.different_countries" color="primary" density="compact" hide-details :label="form.different_countries ? 'Yes' : 'No'" /></div>
        </VCard>

        <VCard variant="outlined" rounded="lg" class="bg-white">
          <div class="d-flex justify-space-between align-center px-4 py-3 border-b"><div><div class="text-subtitle-1 font-weight-bold">Daily Expenses</div><div class="text-caption text-medium-emphasis">Day {{ dayNumber }} of {{ totalDays }}{{ day?.date ? ` · ${day.date}` : '' }}</div></div><VChip size="small" variant="tonal" color="warning" class="font-weight-bold">New</VChip></div>

          <VTable class="text-body-2">
            <thead><tr><th class="text-caption font-weight-bold text-medium-emphasis">Category</th><th class="text-caption font-weight-bold text-medium-emphasis text-end">Actual</th><th class="text-caption font-weight-bold text-medium-emphasis text-end">Eligible</th><th class="text-caption font-weight-bold text-medium-emphasis text-end">Action</th></tr></thead>
            <tbody>
              <template v-for="c in categories" :key="c.key">
                <tr :class="expanded === c.key ? 'bg-blue-grey-lighten-5' : ''"><td class="font-weight-bold">{{ c.label }}</td><td class="text-end text-medium-emphasis">{{ c.actual ? formatIDR(c.actual) : '—' }}</td><td class="text-end text-primary">{{ c.eligible ? formatIDR(c.eligible) : '—' }}</td><td class="text-end"><VBtn variant="text" size="small" color="primary" class="text-none text-decoration-underline font-weight-bold px-1" @click="onAction(c)">{{ actionOf(c) }}</VBtn></td></tr>
                <tr v-if="expanded === c.key" class="bg-blue-grey-lighten-5">
                  <td colspan="4" class="pa-4">
                    <template v-if="c.availableFromDay && laundryLocked">
                      <div class="text-caption text-medium-emphasis mb-2">Not Eligible · Available from Day {{ c.availableFromDay }}</div>
                      <div class="border rounded-lg pa-3 bg-blue-lighten-5 text-body-2"><span class="font-weight-bold text-primary">Not Eligible</span> {{ c.label }} is eligible starting from Day {{ c.availableFromDay }} of the business trip. No additional claim is allowed for this day.</div>
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
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Currency</div><VSelect v-model="transport.currency" :items="TICKET_CURRENCIES" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Actual Amount</div><VTextField :model-value="amountDisplayFor(transport)" type="text" inputmode="decimal" placeholder="0" variant="outlined" density="comfortable" hide-details="auto" class="text-right-input" @update:model-value="v => onAmountInputFor(transport, v)" /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Eligible Estimate</div><VTextField :model-value="estimateDisplayFor(transport)" type="text" inputmode="numeric" placeholder="0" variant="outlined" density="comfortable" hide-details class="text-right-input" @update:model-value="v => onEstimateInputFor(transport, v)" /></VCol>
                              </VRow>
                              <div class="mt-3"><div class="text-caption font-weight-bold mb-1">Supporting Document *</div><input type="file" @change="e => { transport.attachment = Array.from(e.target.files) }"><div v-if="fileNames(transport.attachment)" class="text-caption text-medium-emphasis mt-1">{{ fileNames(transport.attachment) }}</div><div v-if="transportAttachError(index)" class="text-caption text-error mt-1">{{ transportAttachError(index) }}</div></div>
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
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Currency</div><VSelect v-model="other.currency" :items="TICKET_CURRENCIES" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Actual Amount</div><VTextField :model-value="amountDisplayFor(other)" type="text" inputmode="decimal" placeholder="0" variant="outlined" density="comfortable" hide-details="auto" class="text-right-input" @update:model-value="v => onAmountInputFor(other, v)" /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Eligible Estimate</div><VTextField :model-value="estimateDisplayFor(other)" type="text" inputmode="numeric" placeholder="0" variant="outlined" density="comfortable" hide-details class="text-right-input" @update:model-value="v => onEstimateInputFor(other, v)" /></VCol>
                              </VRow>
                              <div class="mt-3"><div class="text-caption font-weight-bold mb-1">Supporting Document *</div><input type="file" @change="e => { other.attachment = Array.from(e.target.files) }"><div v-if="fileNames(other.attachment)" class="text-caption text-medium-emphasis mt-1">{{ fileNames(other.attachment) }}</div><div v-if="otherAttachError(index)" class="text-caption text-error mt-1">{{ otherAttachError(index) }}</div></div>
                            </div>
                          </VCard>
                          <div class="d-flex justify-end"><VBtn variant="outlined" size="small" class="text-none" @click="addOther()">+ Add Other</VBtn></div>
                        </template>
                        <template v-else-if="c.key === 'accommodation'">
                          <VCard rounded="lg" elevation="0" border>
                            <div class="px-4 py-3 bg-grey-lighten-5"><span class="text-subtitle-2 font-weight-bold">Accommodation</span></div>
                            <VDivider />
                            <div class="pa-4">
                              <VRow dense>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Hotel Name</div><VTextField v-model="form.accommodation.hotel_name" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Room Type</div><VTextField v-model="form.accommodation.room_type" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Hotel Standard</div><VSelect v-model="form.accommodation.hotel_standard" :items="HOTEL_STANDARDS" variant="outlined" density="comfortable" hide-details /></VCol>
                              </VRow>
                              <div class="text-caption font-weight-bold mb-1 mt-3">Description *</div>
                              <VTextField v-model="form.accommodation.description" variant="outlined" density="comfortable" hide-details="auto" :error-messages="accDescError()" />
                              <VRow dense class="mt-3">
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Currency</div><VSelect v-model="form.accommodation.currency" :items="TICKET_CURRENCIES" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Actual Amount</div><VTextField :model-value="amountDisplayFor(form.accommodation)" type="text" inputmode="decimal" placeholder="0" variant="outlined" density="comfortable" hide-details="auto" class="text-right-input" @update:model-value="v => onAmountInputFor(form.accommodation, v)" /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Eligible Estimate</div><VTextField :model-value="estimateDisplayFor(form.accommodation)" type="text" inputmode="numeric" placeholder="0" variant="outlined" density="comfortable" hide-details class="text-right-input" @update:model-value="v => onEstimateInputFor(form.accommodation, v)" /></VCol>
                              </VRow>
                              <div class="mt-3"><div class="text-caption font-weight-bold mb-1">Supporting Document *</div><input type="file" @change="e => { form.accommodation.attachment = Array.from(e.target.files) }"><div v-if="fileNames(form.accommodation.attachment)" class="text-caption text-medium-emphasis mt-1">{{ fileNames(form.accommodation.attachment) }}</div><div v-if="accAttachError()" class="text-caption text-error mt-1">{{ accAttachError() }}</div></div>
                            </div>
                          </VCard>
                        </template>
                        <template v-else-if="c.key === 'ticket'">
                          <VCard rounded="lg" elevation="0" border>
                            <div class="px-4 py-3 bg-grey-lighten-5"><span class="text-subtitle-2 font-weight-bold">Ticket</span></div>
                            <VDivider />
                            <div class="pa-4">
                              <div class="text-caption font-weight-bold mb-1">Ticket Type</div>
                              <VSelect v-model="form.ticket.ticket_type" :items="TICKET_TYPES" variant="outlined" density="comfortable" hide-details />
                              <div class="text-caption font-weight-bold mb-1 mt-3">Description *</div>
                              <VTextField v-model="form.ticket.description" variant="outlined" density="comfortable" hide-details="auto" :error-messages="ticketDescError()" />
                              <VRow dense class="mt-3">
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Currency</div><VSelect v-model="form.ticket.currency" :items="TICKET_CURRENCIES" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Actual Amount</div><VTextField :model-value="amountDisplayFor(form.ticket)" type="text" inputmode="decimal" placeholder="0" variant="outlined" density="comfortable" hide-details="auto" class="text-right-input" @update:model-value="v => onAmountInputFor(form.ticket, v)" /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Eligible Estimate</div><VTextField :model-value="estimateDisplayFor(form.ticket)" type="text" inputmode="numeric" placeholder="0" variant="outlined" density="comfortable" hide-details class="text-right-input" @update:model-value="v => onEstimateInputFor(form.ticket, v)" /></VCol>
                              </VRow>
                              <div class="mt-3"><div class="text-caption font-weight-bold mb-1">Supporting Document *</div><input type="file" @change="e => { form.ticket.attachment = Array.from(e.target.files) }"><div v-if="fileNames(form.ticket.attachment)" class="text-caption text-medium-emphasis mt-1">{{ fileNames(form.ticket.attachment) }}</div><div v-if="ticketAttachError()" class="text-caption text-error mt-1">{{ ticketAttachError() }}</div></div>
                            </div>
                          </VCard>
                        </template>
                        <template v-else-if="isSimpleClaim(c)">
                          <VCard rounded="lg" elevation="0" border>
                            <div class="px-4 py-3 bg-grey-lighten-5"><span class="text-subtitle-2 font-weight-bold">{{ c.label }}</span></div>
                            <VDivider />
                            <div class="pa-4">
                              <div class="text-caption font-weight-bold mb-1">Description *</div>
                              <VTextField v-model="itemOf(c).description" variant="outlined" density="comfortable" hide-details="auto" :error-messages="descError(c)" />
                              <VRow dense class="mt-3">
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Currency</div><VSelect :model-value="itemOf(c).currency" :items="['IDR']" variant="outlined" density="comfortable" hide-details disabled /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Actual Amount</div><VTextField :model-value="amountDisplay(itemOf(c))" type="text" inputmode="numeric" placeholder="0" variant="outlined" density="comfortable" hide-details="auto" :error-messages="amountError(c)" class="text-right-input" @update:model-value="v => onAmountInput(c, v)" /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Eligible Entitlement</div><VTextField :model-value="formatIDR(c.eligible)" variant="outlined" density="comfortable" hide-details readonly /></VCol>
                              </VRow>
                              <div class="mt-3"><div class="text-caption font-weight-bold mb-1">Supporting Document *</div><input type="file" @change="e => onAttachment(c, e)"><div v-if="fileNames(itemOf(c).attachment)" class="text-caption text-medium-emphasis mt-1">{{ fileNames(itemOf(c).attachment) }}</div><div v-if="attachError(c)" class="text-caption text-error mt-1">{{ attachError(c) }}</div></div>
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
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Currency</div><VSelect v-model="form.telephone.currency" :items="TICKET_CURRENCIES" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Actual Amount</div><VTextField :model-value="amountDisplayFor(form.telephone)" type="text" inputmode="decimal" placeholder="0" variant="outlined" density="comfortable" hide-details="auto" :error-messages="telAmountError()" class="text-right-input" @update:model-value="v => onAmountInputFor(form.telephone, v)" /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Eligible Entitlement</div><VTextField :model-value="formatIDR(TELEPHONE_BUDGET)" variant="outlined" density="comfortable" hide-details readonly /></VCol>
                              </VRow>
                              <div class="mt-3"><div class="text-caption font-weight-bold mb-1">Supporting Document *</div><input type="file" @change="e => { form.telephone.attachment = Array.from(e.target.files) }"><div v-if="fileNames(form.telephone.attachment)" class="text-caption text-medium-emphasis mt-1">{{ fileNames(form.telephone.attachment) }}</div><div v-if="telAttachError()" class="text-caption text-error mt-1">{{ telAttachError() }}</div></div>
                            </div>
                          </VCard>
                          <VCard rounded="lg" elevation="0" border>
                            <div class="px-4 py-3 bg-grey-lighten-5"><span class="text-subtitle-2 font-weight-bold">Office Telephone</span><VChip size="x-small" color="grey" variant="tonal" class="ml-2">Optional</VChip></div>
                            <VDivider />
                            <div class="pa-4">
                              <div class="text-caption font-weight-bold mb-1">Description *</div>
                              <VTextField v-model="form.office_telephone.description" variant="outlined" density="comfortable" hide-details="auto" :error-messages="otDescError()" />
                              <VRow dense class="mt-3">
                                <VCol cols="12" md="6"><div class="text-caption font-weight-bold mb-1">Currency</div><VSelect v-model="form.office_telephone.currency" :items="TICKET_CURRENCIES" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="6"><div class="text-caption font-weight-bold mb-1">Actual Amount</div><VTextField :model-value="amountDisplayFor(form.office_telephone)" type="text" inputmode="decimal" placeholder="0" variant="outlined" density="comfortable" hide-details="auto" class="text-right-input" @update:model-value="v => onAmountInputFor(form.office_telephone, v)" /></VCol>
                              </VRow>
                              <div class="mt-3"><div class="text-caption font-weight-bold mb-1">Supporting Document *</div><input type="file" @change="e => { form.office_telephone.attachment = Array.from(e.target.files) }"><div v-if="fileNames(form.office_telephone.attachment)" class="text-caption text-medium-emphasis mt-1">{{ fileNames(form.office_telephone.attachment) }}</div><div v-if="otAttachError()" class="text-caption text-error mt-1">{{ otAttachError() }}</div></div>
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
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Currency</div><VSelect v-model="form.laundry.currency" :items="TICKET_CURRENCIES" variant="outlined" density="comfortable" hide-details /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Actual Amount</div><VTextField :model-value="amountDisplayFor(form.laundry)" type="text" inputmode="decimal" placeholder="0" variant="outlined" density="comfortable" hide-details="auto" class="text-right-input" @update:model-value="v => onAmountInputFor(form.laundry, v)" /></VCol>
                                <VCol cols="12" md="4"><div class="text-caption font-weight-bold mb-1">Eligible Estimate</div><VTextField :model-value="estimateDisplayFor(form.laundry)" type="text" inputmode="numeric" placeholder="0" variant="outlined" density="comfortable" hide-details class="text-right-input" @update:model-value="v => onEstimateInputFor(form.laundry, v)" /></VCol>
                              </VRow>
                              <div class="mt-3"><div class="text-caption font-weight-bold mb-1">Supporting Document *</div><input type="file" @change="e => { form.laundry.attachment = Array.from(e.target.files) }"><div v-if="fileNames(form.laundry.attachment)" class="text-caption text-medium-emphasis mt-1">{{ fileNames(form.laundry.attachment) }}</div><div v-if="launAttachError()" class="text-caption text-error mt-1">{{ launAttachError() }}</div></div>
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
          <div class="d-flex justify-end ga-3 pa-4"><VBtn variant="outlined" class="text-none bg-white" @click="onSaveDraft">Save Draft</VBtn><VBtn color="primary" variant="flat" class="text-none" :disabled="!canSave" @click="onSave">{{ isLast ? 'Save & Finish' : 'Save & Next Day' }}</VBtn></div>
        </VCard>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style scoped>
.text-right-input :deep(input) { text-align: right; }
</style>