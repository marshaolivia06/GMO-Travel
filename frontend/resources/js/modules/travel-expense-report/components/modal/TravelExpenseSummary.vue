<script setup>
import { ref, computed } from 'vue'

// Langkah 1 (UI saja): data contoh. Nanti `days` diganti data hari dari Expense Overview,
// jadi jumlah hari otomatis mengikuti perjalanan (tidak hardcode 3 hari).
const props = defineProps({
  days: {
    type: Array,
    default: () => [
      { key: 1, title: 'Day 1', date: '15 July 2026', effective: '08:15', items: [
        { title: 'Breakfast', amount: 10000, currency: 'IDR', metaLabel: 'Budget / Meal', metaValue: 30000, note: 'Departure-day breakfast', attachment: 'breakfast-receipt.pdf' },
        { title: 'Lunch', amount: 10000, currency: 'IDR', metaLabel: 'Budget / Meal', metaValue: 30000, note: 'Lunch allowance' },
        { title: 'Dinner', amount: 10000, currency: 'IDR', metaLabel: 'Budget / Meal', metaValue: 30000, note: 'Dinner allowance' },
        { title: 'Telephone', amount: 200000, currency: 'IDR', metaLabel: 'Policy', metaValue: 'Fixed Entitlement', note: 'Telephone fixed entitlement' },
        { title: 'Pocket Money', amount: 50000, currency: 'IDR', metaLabel: 'Trip Budget', metaValue: 150000, note: 'Pocket money reimbursement' },
      ] },
      { key: 2, title: 'Day 2', date: '16 July 2026', items: [
        { title: 'Accommodation', amount: 1250000, currency: 'IDR', metaLabel: 'Policy', metaValue: '3 Star Hotel Standard', note: 'Hotel stay', attachment: 'hotel-invoice.pdf' },
      ] },
      { key: 3, title: 'Day 3', date: '17 July 2026', effective: '18:40', items: [
        { title: 'Transport Others 1', amount: 145000, currency: 'IDR', note: 'Transport to restaurant', attachment: 'local-transport.jpg' },
      ] },
    ],
  },
  mealEntitlement: { type: Number, default: 210000 },
  mealAdvance: { type: Number, default: 0 },
  eligibleMeals: { type: Number, default: 7 },
  pocketEntitlement: { type: Number, default: 150000 },
  pocketAdvance: { type: Number, default: 0 },
})
const emit = defineEmits(['back', 'submit', 'view-attachment'])

const hardcopyConfirmed = ref(false)
const formatMoney = (value, currency = 'IDR') => `${currency} ${new Intl.NumberFormat('id-ID').format(value)}`
const categoryName = title => title.replace(/\s\d+$/, '')

const allItems = computed(() => props.days.flatMap(day => day.items))

const totalsByCurrency = computed(() => {
  const result = {}
  allItems.value.forEach(item => {
    const group = result[item.currency] ??= { currency: item.currency, total: 0, categories: {} }
    const name = categoryName(item.title)
    group.categories[name] = (group.categories[name] ?? 0) + item.amount
    group.total += item.amount
  })
  return Object.values(result).map(group => ({
    currency: group.currency,
    total: group.total,
    rows: Object.entries(group.categories).map(([name, amount]) => ({ name, amount })).sort((a, b) => b.amount - a.amount),
  }))
})

const categoryCards = computed(() => {
  const names = [...new Set(allItems.value.map(item => categoryName(item.title)))]
  return names.map(name => ({
    name,
    rows: totalsByCurrency.value.map(group => ({ currency: group.currency, amount: group.rows.find(row => row.name === name)?.amount ?? 0 })).filter(row => row.amount > 0),
  }))
})

const claimOf = names => allItems.value.filter(item => names.includes(categoryName(item.title))).reduce((sum, item) => sum + item.amount, 0)
const reconciliation = computed(() => [
  { type: 'Meals', note: `Breakfast + Lunch + Dinner · ${props.eligibleMeals} eligible meals`, budget: props.mealEntitlement, advance: props.mealAdvance, claim: claimOf(['Breakfast', 'Lunch', 'Dinner']) },
  { type: 'Pocket Money', note: 'Trip-level entitlement', budget: props.pocketEntitlement, advance: props.pocketAdvance, claim: claimOf(['Pocket Money']) },
].map(row => ({ ...row, remaining: row.budget - row.advance - row.claim })))
const reconciliationHeaders = [
  { title: 'ADVANCE TYPE', key: 'type', sortable: false },
  { title: 'CURRENCY', key: 'currency', sortable: false },
  { title: 'BUDGET ENTITLEMENT', key: 'budget', align: 'end', sortable: false },
  { title: 'ADVANCE RECEIVED', key: 'advance', align: 'end', sortable: false },
  { title: 'ADDITIONAL CLAIM', key: 'claim', align: 'end', sortable: false },
  { title: 'REMAINING BALANCE', key: 'remaining', align: 'end', sortable: false },
]
</script>

<template>
  <VCard rounded="lg">
    <VCardText class="pa-4">
    <div class="d-flex flex-column ga-4">
    <div>
      <div class="text-h5 font-weight-bold">Travel Expense Summary</div>
      <div class="text-caption text-medium-emphasis">The distribution chart is split per currency and shows each expense amount and the currency total.</div>
    </div>

    <VCard rounded="lg" elevation="0" border>
      <div class="d-flex align-center justify-space-between px-4 py-3">
        <span class="text-subtitle-1 font-weight-bold">Expense Category Distribution</span>
        <span class="text-caption text-medium-emphasis">One bar chart for each currency.</span>
      </div>
      <VDivider />
      <VRow class="pa-4">
        <VCol v-for="group in totalsByCurrency" :key="group.currency" cols="12" md="5">
          <VCard rounded="lg" elevation="0" border class="pa-4">
            <div class="d-flex align-center justify-space-between mb-3">
              <span class="text-subtitle-2 font-weight-bold">{{ group.currency }} Expense Distribution</span>
              <VChip color="success" variant="tonal" size="small" label>Total {{ formatMoney(group.total, group.currency) }}</VChip>
            </div>
            <VDivider class="mb-3" />
            <VRow v-for="row in group.rows" :key="row.name" align="center" no-gutters class="mb-2">
              <VCol cols="3" class="text-caption">{{ row.name }}</VCol>
              <VCol cols="6" class="px-2"><VProgressLinear :model-value="(row.amount / group.rows[0].amount) * 100" color="primary" bg-color="grey-lighten-3" height="14" rounded /></VCol>
              <VCol cols="3" class="text-caption font-weight-bold text-end">{{ formatMoney(row.amount, group.currency) }}</VCol>
            </VRow>
          </VCard>
        </VCol>
      </VRow>
    </VCard>

    <VCard rounded="lg" elevation="0" border>
      <div class="d-flex align-center justify-space-between px-4 py-3">
        <span class="text-subtitle-1 font-weight-bold">Expense by Category</span>
        <span class="text-caption text-medium-emphasis">Two-column responsive layout.</span>
      </div>
      <VDivider />
      <VRow class="pa-4">
        <VCol v-for="category in categoryCards" :key="category.name" cols="12" md="6">
          <VCard rounded="lg" elevation="0" border class="pa-3">
            <div class="text-body-2 font-weight-bold mb-2">{{ category.name }}</div>
            <VDivider class="mb-2" />
            <div v-for="row in category.rows" :key="row.currency" class="d-flex justify-space-between text-caption">
              <span>{{ row.currency }}</span>
              <span class="font-weight-bold">{{ formatMoney(row.amount, row.currency) }}</span>
            </div>
          </VCard>
        </VCol>
      </VRow>
    </VCard>

    <VCard rounded="lg" elevation="0" border>
      <div class="d-flex align-center justify-space-between px-4 py-3">
        <span class="text-subtitle-1 font-weight-bold">Travel Advance Reconciliation</span>
        <span class="text-caption text-medium-emphasis">Travel Advance only consists of Meals and Pocket Money.</span>
      </div>
      <VDivider />
      <VDataTable :headers="reconciliationHeaders" :items="reconciliation" hide-default-footer class="text-body-2">
        <template #headers="{ columns }">
          <tr>
            <th v-for="column in columns" :key="column.key" :class="['bg-grey-lighten-3 py-3', column.align === 'end' ? 'text-end' : 'text-start']">{{ column.title }}</th>
          </tr>
        </template>
        <template #item.type="{ item }"><div class="py-4"><div class="font-weight-bold">{{ item.type }}</div><div class="text-caption text-medium-emphasis">{{ item.note }}</div></div></template>
        <template #item.currency><div class="py-4">IDR</div></template>
        <template #item.budget="{ item }"><div class="py-4">{{ formatMoney(item.budget) }}</div></template>
        <template #item.advance="{ item }"><div class="py-4">{{ formatMoney(item.advance) }}</div></template>
        <template #item.claim="{ item }"><div class="py-4">{{ formatMoney(item.claim) }}</div></template>
        <template #item.remaining="{ item }"><div class="py-4 font-weight-bold text-success">{{ formatMoney(item.remaining) }}</div></template>
      </VDataTable>
    </VCard>

    <VCard rounded="lg" elevation="0" border>
      <div class="d-flex align-center justify-space-between px-4 py-3">
        <span class="text-subtitle-1 font-weight-bold">Expense by Day</span>
        <span class="text-caption text-medium-emphasis">Attachment can be opened using View.</span>
      </div>
      <VDivider />
      <div class="d-flex flex-column ga-4 pa-4">
        <VCard v-for="day in days" :key="day.key" rounded="lg" elevation="0" border>
          <div class="d-flex align-center justify-space-between px-4 py-3 bg-grey-lighten-5">
            <span class="text-subtitle-2 font-weight-bold">{{ day.title }} · {{ day.date }}</span>
            <span v-if="day.effective" class="text-body-2">Effective {{ day.effective }}</span>
          </div>
          <VDivider />
          <VRow class="pa-4">
            <VCol v-for="(item, index) in day.items" :key="index" cols="12" md="6">
              <VCard rounded="lg" elevation="0" border class="pa-3 h-100">
                <div class="text-body-2 font-weight-bold">{{ item.title }}</div>
                <div class="text-subtitle-1 font-weight-bold">{{ formatMoney(item.amount, item.currency) }}</div>
                <div v-if="item.metaLabel" class="text-caption text-medium-emphasis">{{ item.metaLabel }}: <strong>{{ typeof item.metaValue === 'number' ? formatMoney(item.metaValue, item.currency) : item.metaValue }}</strong></div>
                <div class="text-caption text-medium-emphasis">{{ item.note }}</div>
                <template v-if="item.attachment">
                  <VDivider class="my-2" />
                  <div class="d-flex align-center justify-space-between">
                    <span class="d-flex align-center ga-2 text-caption"><VIcon icon="ri-file-line" size="small" />{{ item.attachment }}</span>
                    <VBtn variant="outlined" size="small" class="text-none" @click="emit('view-attachment', item)">View</VBtn>
                  </div>
                </template>
              </VCard>
            </VCol>
          </VRow>
        </VCard>
      </div>
      <VDivider />
      <VCard color="amber-lighten-5" rounded="lg" elevation="0" class="mx-4 my-4"><VCardText class="d-flex align-center ga-2 pa-3"><input v-model="hardcopyConfirmed" type="checkbox" class="mr-2"><div class="text-body-2">Hardcopy receipts will be collected directly by the <strong>GMO team</strong>.</div></VCardText></VCard>
      <div class="d-flex align-center justify-end ga-2 pa-4 bg-grey-lighten-5">
        <VBtn variant="flat" class="bg-grey-lighten-1 text-none font-weight-semibold" @click="emit('back')">Back to Edit</VBtn>
        <VBtn color="primary" variant="flat" class="text-none font-weight-semibold" :disabled="!hardcopyConfirmed" @click="emit('submit')">Submit Expense Report</VBtn>
      </div>
    </VCard>
    </div>
    </VCardText>
  </VCard>
</template>