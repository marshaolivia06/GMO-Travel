<script setup>
import { ref, computed, watch } from 'vue'
import ExpenseCategoryCard from './ExpenseCategoryCard.vue'

const props = defineProps({ modelValue: { type: Boolean, default: false }, day: { type: Object, default: null }, totalDays: { type: Number, default: 0 }, initial: { type: Object, default: null }, isLast: { type: Boolean, default: false }, usedElsewhere: { type: Object, default: () => ({}) }, mealBudget: { type: Number, default: 35000 }, mealEntitlement: { type: Number, default: 245000 }, eligibleMeals: { type: Number, default: 7 }, travelAdvance: { type: Number, default: 170000 }, pocketBudget: { type: Number, default: 150000 }, pocketAdvance: { type: Number, default: 100000 }, telephoneBudget: { type: Number, default: 200000 }, employeePosition: { type: String, default: 'Supervisor' }, hotelStandard: { type: Number, default: 3 } })
const emit = defineEmits(['update:modelValue', 'save', 'draft'])

const meals = [{ key: 'breakfast', label: 'Breakfast', fixed: false }, { key: 'lunch', label: 'Lunch', fixed: true }, { key: 'dinner', label: 'Dinner', fixed: true }]
const hotelStars = Array.from({ length: 5 }, (_, index) => ({ title: `${'★'.repeat(index + 1)} ${index + 1} Star`, value: index + 1 }))

const num = value => Number(value) || 0
const formatRupiah = value => `IDR ${new Intl.NumberFormat('id-ID').format(value)}`
const cloneValue = value => Array.isArray(value) ? value.map(cloneValue) : value && typeof value === 'object' && Object.getPrototypeOf(value) === Object.prototype ? Object.fromEntries(Object.entries(value).map(([key, entry]) => [key, cloneValue(entry)])) : value
const usedBy = key => num(props.usedElsewhere?.[key])
const createItem = (amount = 0, description = '') => ({ currency: 'IDR', amount, description, paid_by_others: false, attachment: null })

const pocketCap = computed(() => Math.max(0, props.pocketBudget - props.pocketAdvance - usedBy('pocket_money')))
const createForm = () => ({ different_countries: false, meals: Object.fromEntries(meals.map(meal => [meal.key, meal.fixed ? createItem(props.mealBudget, `${meal.label} allowance`) : createItem()])), pocket_money: createItem(pocketCap.value, 'Pocket money reimbursement'), ticket: createItem(), accommodation: { ...createItem(), hotel_name: '', room_type: '', hotel_star: props.hotelStandard }, laundry: createItem(), telephone: createItem(props.telephoneBudget), transports: [], others: [] })
const form = ref(createForm())

const dayNumber = computed(() => props.day?.key ?? 0)
const isDeparture = computed(() => props.day?.type === 'Departure')
const breakfastExcluded = computed(() => props.day?.type === 'Stay')
const showLaundry = computed(() => props.totalDays > 6)
const laundryEligible = computed(() => dayNumber.value >= 6)

const itemOf = meal => form.value.meals[meal.key]
const overMax = (item, max) => num(item.amount) > max ? `Maximum ${formatRupiah(max)}` : ''
const mealsTotal = computed(() => meals.reduce((sum, meal) => sum + num(itemOf(meal).amount), 0))
const mealRemaining = computed(() => props.mealEntitlement - props.travelAdvance - usedBy('meals') - mealsTotal.value)
const mealMax = meal => Math.max(0, Math.min(props.mealBudget, mealRemaining.value + num(itemOf(meal).amount)))
const mealLimit = meal => ({ budgetLabel: 'BUDGET / MEAL', budget: props.mealBudget, max: mealMax(meal), remaining: mealRemaining.value, note: 'The Meals balance is shared by Breakfast, Lunch, and Dinner across the complete trip.' })
const mealStats = computed(() => [{ label: 'BUDGET / MEAL', value: props.mealBudget }, { label: 'TRAVEL ADVANCE', value: props.travelAdvance }, { label: 'REMAINING BUDGET', value: mealRemaining.value }])
const pocketRemaining = computed(() => pocketCap.value - num(form.value.pocket_money.amount))
const pocketLimit = computed(() => ({ budgetLabel: 'TRIP BUDGET', budget: props.pocketBudget, max: pocketCap.value, remaining: pocketRemaining.value, note: 'Limit is calculated for this category across the trip.' }))
const pocketStats = computed(() => [{ label: 'TRIP BUDGET', value: props.pocketBudget }, { label: 'TRAVEL ADVANCE', value: props.pocketAdvance }, { label: 'REMAINING BUDGET', value: pocketRemaining.value }])

const addTransport = kind => form.value.transports.push({ ...createItem(), kind })
const removeTransport = index => form.value.transports.splice(index, 1)
const transportTitle = (item, index) => `Transport ${item.kind === 'perjalanan' ? 'Travel' : 'Others'} ${form.value.transports.slice(0, index + 1).filter(entry => entry.kind === item.kind).length}`
const addOther = () => form.value.others.push(createItem())
const removeOther = index => form.value.others.splice(index, 1)

const total = computed(() => mealsTotal.value + ['pocket_money', 'ticket', 'accommodation', 'telephone'].reduce((sum, key) => sum + num(form.value[key].amount), 0) + (showLaundry.value && laundryEligible.value ? num(form.value.laundry.amount) : 0) + [...form.value.transports, ...form.value.others].reduce((sum, item) => sum + num(item.amount), 0))

const hasFile = item => Array.isArray(item.attachment) ? item.attachment.length > 0 : !!item.attachment
const checks = computed(() => [
  ...meals.map(meal => ({ item: itemOf(meal), fixed: meal.fixed, locked: meal.key === 'breakfast' && breakfastExcluded.value, error: overMax(itemOf(meal), mealMax(meal)) })),
  { item: form.value.pocket_money, fixed: true, locked: false, error: overMax(form.value.pocket_money, pocketCap.value) },
  { item: form.value.ticket, fixed: false, locked: false, error: '' },
  { item: form.value.accommodation, fixed: false, locked: false, error: '' },
  ...(showLaundry.value ? [{ item: form.value.laundry, fixed: false, locked: !laundryEligible.value, error: '' }] : []),
  { item: form.value.telephone, fixed: true, locked: false, error: overMax(form.value.telephone, props.telephoneBudget) },
  ...form.value.transports.map(item => ({ item, fixed: false, locked: false, error: '' })),
  ...form.value.others.map(item => ({ item, fixed: false, locked: false, error: '', descriptionRequired: true })),
])
const canSave = computed(() => checks.value.every(check => !check.error && (check.fixed || check.locked || check.item.paid_by_others || num(check.item.amount) <= 0 || hasFile(check.item)) && (!check.descriptionRequired || check.item.paid_by_others || !!String(check.item.description ?? '').trim())))

const close = () => emit('update:modelValue', false)
const buildPayload = () => cloneValue({ ...form.value, day: props.day?.key, laundry: showLaundry.value && laundryEligible.value ? form.value.laundry : null, total: total.value })
const save = () => emit('save', buildPayload())
const saveDraft = () => emit('draft', buildPayload())

watch(() => [props.modelValue, props.day?.key], ([open]) => { if (open) form.value = { ...createForm(), ...cloneValue(props.initial ?? {}) } })
</script>

<template>
  <VDialog :model-value="modelValue" max-width="1400" width="95%" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <div class="d-flex align-center justify-space-between px-4 py-3">
        <div>
          <div class="text-h6 font-weight-bold">Expense Entry</div>
          <div class="text-caption text-medium-emphasis">{{ day?.title }} · {{ day?.subtitle }}</div>
        </div>
        <VBtn icon="ri-close-line" variant="text" size="small" @click="close" />
      </div>
      <VDivider />
      <VCardText class="pa-4">
        <div class="d-flex flex-column ga-4">
          <VCard rounded="lg" elevation="0" border>
            <div class="d-flex align-center justify-space-between px-4 py-3">
              <span class="text-subtitle-1 font-weight-bold">Daily Travel Context</span>
              <span class="text-caption text-medium-emphasis">Controls automatic allowance validation for this day.</span>
            </div>
            <VDivider />
            <div class="d-flex align-center justify-space-between px-4 py-3">
              <div>
                <div class="text-body-2 font-weight-bold">Different countries in one day</div>
                <div class="text-caption text-medium-emphasis">Turn on when the employee crosses a country border during this expense day.</div>
              </div>
              <VSwitch v-model="form.different_countries" :label="form.different_countries ? 'Yes' : 'No'" color="primary" density="compact" hide-details class="flex-grow-0" />
            </div>
          </VCard>
          <VCard rounded="lg" elevation="0" border>
            <div class="d-flex align-center ga-2 px-4 py-3">
              <span class="text-subtitle-1 font-weight-bold">Meals</span>
              <span class="text-caption text-medium-emphasis">Breakfast + Lunch + Dinner</span>
            </div>
            <VDivider />
            <div class="pa-4">
              <VRow class="mb-1">
                <VCol v-for="stat in mealStats" :key="stat.label" cols="12" sm="4"><VCard rounded="lg" elevation="0" border class="pa-3 bg-grey-lighten-5"><div class="text-caption text-medium-emphasis">{{ stat.label }}</div><div class="text-subtitle-2 font-weight-bold">{{ formatRupiah(stat.value) }}</div></VCard></VCol>
              </VRow>
              <VAlert color="info" variant="tonal" density="compact" class="mb-4 text-caption">
  Trip entitlement: <strong>{{ formatRupiah(mealEntitlement) }}</strong>.
  Based on <strong>{{ eligibleMeals }} eligible meals</strong>.
  Stay-day breakfast is excluded because it is provided by accommodation.
</VAlert>
              <VRow>
                <VCol v-for="meal in meals" :key="meal.key" cols="12" md="4">
                  <ExpenseCategoryCard :title="meal.label" :fixed="meal.fixed" :item="itemOf(meal)" :default-amount="meal.fixed ? mealBudget : 0" :locked="meal.key === 'breakfast' && breakfastExcluded" :error="overMax(itemOf(meal), mealMax(meal))" :limit="mealLimit(meal)">
                    <VAlert v-if="meal.key === 'breakfast' && isDeparture" color="info" variant="tonal" density="compact" class="mb-3"><div class="d-flex align-center ga-1"><span class="text-body-2 font-weight-bold">Departure Day Breakfast</span><span class="d-inline-flex cursor-pointer"><VIcon icon="ri-information-line" size="small" color="medium-emphasis" /><VTooltip activator="parent" location="top" max-width="300">Breakfast is eligible on the departure day under the current travel schedule rule.</VTooltip></span></div></VAlert>
                    <VAlert v-if="meal.key === 'breakfast' && breakfastExcluded" color="secondary" variant="tonal" density="compact" class="mb-3"><div class="text-body-2 font-weight-bold">Breakfast Excluded</div><div class="text-caption">Stay-day breakfast is provided by accommodation.</div></VAlert>
                  </ExpenseCategoryCard>
                </VCol>
              </VRow>
            </div>
          </VCard>
          <ExpenseCategoryCard title="Pocket Money" fixed :item="form.pocket_money" :default-amount="pocketCap" :error="overMax(form.pocket_money, pocketCap)" :limit="pocketLimit">
            <VRow class="mb-1">
              <VCol v-for="stat in pocketStats" :key="stat.label" cols="12" sm="4"><VCard rounded="lg" elevation="0" border class="pa-3 bg-grey-lighten-5"><div class="text-caption text-medium-emphasis">{{ stat.label }}</div><div class="text-subtitle-2 font-weight-bold">{{ formatRupiah(stat.value) }}</div></VCard></VCol>
            </VRow>
          </ExpenseCategoryCard>
          <VRow>
            <VCol cols="12" md="6"><ExpenseCategoryCard title="Ticket" :item="form.ticket" /></VCol>
            <VCol cols="12" md="6">
              <ExpenseCategoryCard title="Accommodation" :item="form.accommodation" :show-description="false">
                <VAlert color="info" variant="tonal" density="compact" class="mb-3 text-caption"><div class="font-weight-bold">Employee Position: {{ employeePosition }}</div><div class="mt-1"><span class="text-warning">{{ '★'.repeat(hotelStandard) }}</span> <strong>{{ hotelStandard }} Star Hotel Standard.</strong> Information only.</div></VAlert>
                <div class="mb-2"><div class="text-caption font-weight-bold mb-1">Hotel Name</div><VTextField v-model="form.accommodation.hotel_name" variant="outlined" density="comfortable" hide-details :disabled="form.accommodation.paid_by_others" /></div>
                <div class="mb-2"><div class="text-caption font-weight-bold mb-1">Room Type</div><VTextField v-model="form.accommodation.room_type" variant="outlined" density="comfortable" hide-details :disabled="form.accommodation.paid_by_others" /></div>
                <div class="mb-2"><div class="text-caption font-weight-bold mb-1">Actual Hotel Star</div><VSelect v-model="form.accommodation.hotel_star" :items="hotelStars" variant="outlined" density="comfortable" hide-details :disabled="form.accommodation.paid_by_others" /></div>
              </ExpenseCategoryCard>
            </VCol>
          </VRow>
            <VRow>
  <VCol v-if="showLaundry" cols="12" md="6">
    <ExpenseCategoryCard title="Laundry" :item="form.laundry" :locked="!laundryEligible">
      <VAlert v-if="!laundryEligible" color="warning" variant="tonal" density="compact" class="mb-3">
        <div class="text-body-2 font-weight-bold">Laundry Eligibility</div>
        <div class="text-caption">Available starting Day 6. Current day is Day {{ dayNumber }}.</div>
      </VAlert>
    </ExpenseCategoryCard>
  </VCol>
  <VCol cols="12" md="6">
    <ExpenseCategoryCard title="Telephone" fixed :item="form.telephone" :default-amount="telephoneBudget" :error="overMax(form.telephone, telephoneBudget)" :show-description="false">
      <div class="mb-2">
        <div class="text-caption font-weight-bold mb-1">Telephone Type</div>
        <VTextField model-value="Fixed Entitlement" variant="outlined" density="comfortable" hide-details disabled />
      </div>
    </ExpenseCategoryCard>
  </VCol>
</VRow>
          <VRow>
            <VCol cols="12" md="6">
              <VCard rounded="lg" elevation="0" border>
                <div class="d-flex align-center justify-space-between px-4 py-3 bg-grey-lighten-5">
                  <span class="text-subtitle-2 font-weight-bold">Transport</span>
                  <div class="d-flex ga-2">
                    <VBtn color="primary" variant="flat" size="small" class="text-none" @click="addTransport('perjalanan')">+ Add Travel Transport</VBtn>
                    <VBtn color="primary" variant="outlined" size="small" class="text-none" @click="addTransport('others')">+ Add Transport Others</VBtn>
                  </div>
                </div>
                <VDivider />
                <div class="pa-4">
                  <VAlert color="info" variant="tonal" density="compact" class="mb-3 text-caption"><div><strong>Travel Transport</strong> = from home, the airport, or the office.</div><div><strong>Transport Others</strong> = transport to dining venues or for other local needs.</div></VAlert>
                  <ExpenseCategoryCard v-for="(item, index) in form.transports" :key="index" :title="transportTitle(item, index)" :item="item" compact class="mb-3">
                    <template #actions><VBtn icon="ri-delete-bin-line" color="error" variant="flat" rounded="circle" size="small" @click="removeTransport(index)" /></template>
                  </ExpenseCategoryCard>
                </div>
              </VCard>
            </VCol>
            <VCol cols="12" md="6">
              <VCard rounded="lg" elevation="0" border>
                <div class="d-flex align-center justify-space-between px-4 py-3 bg-grey-lighten-5">
                  <span class="text-subtitle-2 font-weight-bold">Other</span>
                  <VBtn color="primary" variant="flat" size="small" class="text-none" @click="addOther">+ Add Other</VBtn>
                </div>
                <template v-if="form.others.length">
                  <VDivider />
                  <div class="pa-4">
                    <ExpenseCategoryCard v-for="(item, index) in form.others" :key="index" :title="`Other ${index + 1}`" :item="item" compact description-required class="mb-3">
                        <template #actions><VBtn icon="ri-delete-bin-line" color="error" variant="flat" rounded="circle" size="small" @click="removeOther(index)" /></template>
                    </ExpenseCategoryCard>
                  </div>
                </template>
              </VCard>
            </VCol>
          </VRow>
        </div>
      </VCardText>
      <VDivider />
      <div class="d-flex align-center justify-end pa-4 bg-grey-lighten-5">
        <div class="d-flex ga-2">
          <VBtn variant="flat" class="bg-grey-lighten-1 text-none font-weight-semibold" @click="saveDraft">Draft</VBtn>
          <VBtn color="primary" variant="flat" class="text-none font-weight-semibold" :disabled="!canSave" @click="save">{{ isLast ? 'Save & Finish' : 'Save & Next Day' }}</VBtn>
        </div>
      </div>
    </VCard>
  </VDialog>
</template>