<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  days: { type: Array, default: () => [] },
  advanceMeal: { type: Number, default: 0 },
  advancePocket: { type: Number, default: 0 },
})
const emit = defineEmits(['back', 'submit'])

const receiptConfirmed = ref(false)
const collapsed = ref({ settlement: [], advance: [] })
const isOpen = (kind, currency) => !collapsed.value[kind].includes(currency)
const toggleOpen = (kind, currency) => { const list = collapsed.value[kind]; const index = list.indexOf(currency); if (index >= 0) list.splice(index, 1); else list.push(currency) }
const expandedDays = ref([])
const expandedItems = ref([])

const num = value => Number(value) || 0
const formatMoney = (value, currency = 'IDR') => `${currency ?? 'IDR'} ${new Intl.NumberFormat('id-ID').format(num(value))}`
const MEALS = ['Breakfast', 'Lunch', 'Dinner']
const FIXED_CATEGORIES = ['Meals', 'Pocket Money', 'Personal Telephone', 'Telephone', 'Office Telephone']
const categoryName = title => { const name = title.replace(/\s\d+$/, ''); return MEALS.includes(name) ? 'Meals' : name }
const basisOf = item => (FIXED_CATEGORIES.includes(categoryName(item.title)) ? 'fixed' : 'receipt')
const eligibleOf = item => item.eligible ?? item.amount

const allItems = computed(() => props.days.flatMap(day => day.items))

const totalsByCurrency = computed(() => {
  const result = {}
  allItems.value.forEach(item => {
    const currency = item.currency ?? 'IDR'
    const group = result[currency] ??= { currency, total: 0, categories: {} }
    const name = categoryName(item.title)
    group.categories[name] = (group.categories[name] ?? 0) + item.amount
    group.total += item.amount
  })
  return Object.values(result).map(group => ({ currency: group.currency, total: group.total, rows: Object.entries(group.categories).map(([name, amount]) => ({ name, amount })).filter(row => row.amount > 0).sort((a, b) => b.amount - a.amount) }))
})

const groupDetailsFor = (items, basis) => {
  const groups = {}
  items.filter(item => basisOf(item) === basis).forEach(item => {
    const name = categoryName(item.title)
    groups[name] ??= { name, actual: 0, eligible: 0 }
    groups[name].actual += item.amount
    groups[name].eligible += eligibleOf(item)
  })
  return Object.values(groups)
}

const advanceTotal = computed(() => num(props.advanceMeal) + num(props.advancePocket))

const settlementOf = (advance, totalEligible) => {
  const balance = advance - totalEligible
  if (balance > 0) return { chip: 'Employee → Company', title: 'Return to Company', note: 'The employee must return the unused Travel Advance to the company.', amount: balance, tone: 'warning' }
  if (balance < 0) return { chip: 'Company → Employee', title: 'Reimburse to Employee', note: 'The company will reimburse the remaining eligible expense to the employee.', amount: Math.abs(balance), tone: 'success' }
  return { chip: 'Balanced', title: 'No Settlement Required', note: 'The Travel Advance received matches the total eligible expense.', amount: 0, tone: 'success' }
}

const settlementGroups = computed(() => {
  const groups = {}
  allItems.value.forEach(item => {
    const currency = item.currency ?? 'IDR'
    groups[currency] ??= { currency, items: [] }
    groups[currency].items.push(item)
  })
  return Object.values(groups)
    .sort((a, b) => (a.currency === 'IDR' ? -1 : b.currency === 'IDR' ? 1 : a.currency.localeCompare(b.currency)))
    .map(group => {
      const fixedRows = groupDetailsFor(group.items, 'fixed')
      const receiptRows = groupDetailsFor(group.items, 'receipt')
      const fixedTotal = fixedRows.reduce((sum, row) => sum + row.eligible, 0)
      const receiptTotal = receiptRows.reduce((sum, row) => sum + row.eligible, 0)
      const totalEligible = fixedTotal + receiptTotal
      const isIdr = group.currency === 'IDR'
      const advance = isIdr ? advanceTotal.value : 0
      return { currency: group.currency, fixedRows, receiptRows, fixedTotal, receiptTotal, totalEligible, isIdr, advanceMeal: isIdr ? num(props.advanceMeal) : 0, advancePocket: isIdr ? num(props.advancePocket) : 0, advance, settlement: settlementOf(advance, totalEligible) }
    })
})

const dayTotal = day => day.items.reduce((sum, item) => sum + eligibleOf(item), 0)
const dayStatus = day => (day.items.some(item => item.manualReview) ? 'Manual Review*' : 'Eligible')
const dayStatusColor = day => (day.items.some(item => item.manualReview) ? 'text-warning' : 'text-primary')
const toggleItem = key => { const index = expandedItems.value.indexOf(key); if (index >= 0) expandedItems.value.splice(index, 1); else expandedItems.value.push(key) }
const toggleDay = key => { const index = expandedDays.value.indexOf(key); if (index >= 0) expandedDays.value.splice(index, 1); else expandedDays.value.push(key) }

const previewOpen = ref(false)
const previewName = ref('')
const previewUrl = ref('')
const previewKind = ref('')
const openPreview = item => {
  const file = item.file
  const type = String(file?.type ?? '')
  const name = String(file?.name ?? '').toLowerCase()
  previewName.value = item.attachment ?? ''
  if (type.startsWith('image/')) { previewKind.value = 'image'; previewUrl.value = URL.createObjectURL(file) } else if (type === 'application/pdf' || name.endsWith('.pdf')) { previewKind.value = 'pdf'; previewUrl.value = URL.createObjectURL(file) } else { previewKind.value = ''; previewUrl.value = '' }
  previewOpen.value = true
}
const closePreview = () => { if (previewUrl.value) URL.revokeObjectURL(previewUrl.value); previewUrl.value = ''; previewName.value = ''; previewKind.value = ''; previewOpen.value = false }
</script>

<template>
  <VCard rounded="lg">
    <VCardText class="pa-4">
      <div class="d-flex flex-column ga-4">
        <div>
          <div class="text-h5 font-weight-bold">Travel Expense Summary</div>
          <div class="text-caption text-medium-emphasis">Review the settlement summary before submitting your travel expense report.</div>
        </div>

        <VCard rounded="lg" elevation="0" border>
          <div class="d-flex align-center justify-space-between px-4 py-3">
            <span class="text-subtitle-1 font-weight-bold">Actual Expense Category Distribution</span>
            <span class="text-caption text-medium-emphasis">One bar chart for each currency.</span>
          </div>
          <VDivider />
          <VRow class="pa-4">
            <VCol v-for="group in totalsByCurrency" :key="group.currency" cols="12" md="4">
              <VCard rounded="lg" elevation="0" border class="pa-4">
                <div class="d-flex align-center justify-space-between mb-3">
                  <span class="text-subtitle-2 font-weight-bold">{{ group.currency }} Expense Distribution</span>
                  <VChip color="success" variant="outlined" size="small" class="font-weight-bold flex-shrink-0" style="white-space: nowrap">Total&nbsp;{{ formatMoney(group.total, group.currency).replace(' ', '\u00A0') }}</VChip>
                </div>
                <VDivider class="mb-3" />
                <VRow v-for="row in group.rows" :key="row.name" align="center" no-gutters class="mb-2">
                  <VCol cols="4" class="text-caption">{{ row.name }}</VCol>
                  <VCol cols="4" class="px-2"><VProgressLinear :model-value="(row.amount / group.rows[0].amount) * 100" color="primary" bg-color="grey-lighten-3" height="12" rounded /></VCol>
                  <VCol cols="4" class="text-caption font-weight-bold text-end" style="white-space: nowrap">{{ formatMoney(row.amount, group.currency).replace(' ', '\u00A0') }}</VCol>
                </VRow>
              </VCard>
            </VCol>
          </VRow>
        </VCard>

        <VCard rounded="lg" elevation="0" border>
          <div class="d-flex align-center justify-space-between px-4 py-3">
            <span class="text-subtitle-1 font-weight-bold">Travel Expense Settlement</span>
          </div>
          <VDivider />
          <VRow class="pa-4">
            <VCol v-for="group in settlementGroups" :key="group.currency" cols="12">
              <VCard rounded="lg" elevation="0" border>
                <div class="px-4 py-3">
                  <div class="text-subtitle-1 font-weight-bold">Travel Expense Settlement</div>
                  <div class="text-caption text-medium-emphasis">{{ group.currency }}</div>
                </div>
                <div class="d-flex align-center justify-space-between px-4 py-3 bg-blue-lighten-5">
                  <span class="text-body-2 font-weight-bold">Total Eligible Expense (Estimated)</span>
                  <span class="text-h5 font-weight-bold">{{ formatMoney(group.totalEligible, group.currency) }}</span>
                </div>
                <div class="d-flex justify-end px-4 py-1"><VBtn variant="text" size="small" color="primary" class="text-none font-weight-bold" :append-icon="isOpen('settlement', group.currency) ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'" @click="toggleOpen('settlement', group.currency)">{{ isOpen('settlement', group.currency) ? 'Hide detail' : 'View detail' }}</VBtn></div>
                <template v-if="isOpen('settlement', group.currency)">
                  <VDivider />
                  <div class="px-4 pt-3 pb-1 text-body-2 font-weight-bold">A. Fixed Entitlement</div>
                  <VTable density="compact" class="text-body-2 px-2">
                    <thead><tr><th class="text-caption">Category</th><th class="text-caption text-end">Actual</th><th class="text-caption text-end">Eligible</th></tr></thead>
                    <tbody>
                      <tr v-for="row in group.fixedRows" :key="row.name"><td>{{ row.name }}</td><td class="text-end">{{ formatMoney(row.actual, group.currency) }}</td><td class="text-end">{{ formatMoney(row.eligible, group.currency) }}</td></tr>
                      <tr v-if="!group.fixedRows.length"><td colspan="3" class="text-caption text-medium-emphasis">No fixed entitlement claimed.</td></tr>
                      <tr class="bg-blue-lighten-5 font-weight-bold text-primary"><td>Eligible Fixed Entitlement</td><td></td><td class="text-end">{{ formatMoney(group.fixedTotal, group.currency) }}</td></tr>
                    </tbody>
                  </VTable>
                  <VDivider class="mt-3" />
                  <div class="px-4 pt-3 pb-1 text-body-2 font-weight-bold">B. Receipt-Based Expenses</div>
                  <VTable density="compact" class="text-body-2 px-2">
                    <thead><tr><th class="text-caption">Category</th><th class="text-caption text-end">Actual</th><th class="text-caption text-end">Eligible</th></tr></thead>
                    <tbody>
                      <tr v-for="row in group.receiptRows" :key="row.name"><td>{{ row.name }}</td><td class="text-end">{{ formatMoney(row.actual, group.currency) }}</td><td class="text-end">{{ formatMoney(row.eligible, group.currency) }}</td></tr>
                      <tr v-if="!group.receiptRows.length"><td colspan="3" class="text-caption text-medium-emphasis">No receipt-based expense claimed.</td></tr>
                      <tr class="bg-blue-lighten-5 font-weight-bold text-primary"><td>Eligible Receipt-Based Expenses</td><td></td><td class="text-end">{{ formatMoney(group.receiptTotal, group.currency) }}</td></tr>
                    </tbody>
                  </VTable>
                  <div class="px-4 py-2 text-caption bg-grey-lighten-5">Eligible Fixed Entitlement ({{ formatMoney(group.fixedTotal, group.currency) }}) + Eligible Receipt-Based Expenses ({{ formatMoney(group.receiptTotal, group.currency) }}) = <span class="font-weight-bold text-primary">{{ formatMoney(group.totalEligible, group.currency) }}</span></div>
                </template>
                <VDivider />
                <div class="d-flex align-center justify-space-between px-4 py-3">
                  <div><div class="text-body-2 font-weight-bold">Travel Advance Received</div><div class="text-caption text-medium-emphasis">{{ group.isIdr ? 'Previously paid by the company via Travel Order' : 'No travel advance in this currency' }}</div></div>
                  <span class="text-h5 font-weight-bold">{{ formatMoney(group.advance, group.currency) }}</span>
                </div>
                <div v-if="group.isIdr" class="d-flex justify-end px-4 py-1"><VBtn variant="text" size="small" color="primary" class="text-none font-weight-bold" :append-icon="isOpen('advance', group.currency) ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'" @click="toggleOpen('advance', group.currency)">{{ isOpen('advance', group.currency) ? 'Hide detail' : 'View detail' }}</VBtn></div>
                <div v-if="group.isIdr && isOpen('advance', group.currency)" class="border-s ms-4 mb-3 ps-3 me-4">
                  <div class="d-flex justify-space-between text-body-2 py-1"><span>Meals Advance</span><span class="font-weight-bold">{{ formatMoney(group.advanceMeal, group.currency) }}</span></div>
                  <div class="d-flex justify-space-between text-body-2 py-1"><span>Pocket Money Advance</span><span class="font-weight-bold">{{ formatMoney(group.advancePocket, group.currency) }}</span></div>
                </div>
                <VDivider />
                <div class="d-flex align-center justify-space-between pa-4" :class="group.settlement.tone === 'warning' ? 'bg-amber-lighten-5' : 'bg-green-lighten-5'">
                  <div>
                    <div class="text-subtitle-1 font-weight-bold">{{ group.settlement.title }}</div>
                    <div class="text-caption font-weight-bold" :class="group.settlement.tone === 'warning' ? 'text-warning' : 'text-success'">{{ group.settlement.chip }}</div>
                    <div class="text-caption text-medium-emphasis mt-1">{{ group.settlement.note }}</div>
                  </div>
                  <div class="text-end">
                    <div class="text-caption text-medium-emphasis">{{ group.currency }}</div>
                    <div class="text-h5 font-weight-bold" :class="group.settlement.tone === 'warning' ? 'text-warning' : 'text-success'">{{ group.settlement.amount > 0 ? formatMoney(group.settlement.amount, group.currency) : '—' }}</div>
                  </div>
                </div>
              </VCard>
            </VCol>
          </VRow>
        </VCard>

        <VCard rounded="lg" elevation="0" border>
          <div class="d-flex align-center justify-space-between px-4 py-3">
            <span class="text-subtitle-1 font-weight-bold">Expense by Day</span>
          </div>
          <VDivider />
          <div class="d-flex flex-column ga-3 pa-4">
            <VCard v-for="day in days" :key="day.key" rounded="lg" elevation="0" border>
              <div class="d-flex align-center justify-space-between px-4 py-3 cursor-pointer" :class="expandedDays.includes(day.key) ? 'bg-grey-lighten-5' : ''" @click="toggleDay(day.key)">
                <div>
                  <div class="text-subtitle-2 font-weight-bold">{{ day.title }}</div>
                  <div class="text-caption text-medium-emphasis">{{ day.date }} · {{ day.items.length }} expense entries<span v-if="day.effective"> · Effective {{ day.effective }}</span></div>
                </div>
                <div class="d-flex align-center ga-3">
                  <div class="text-end"><div class="text-body-1 font-weight-bold text-primary">{{ formatMoney(dayTotal(day)) }}</div><div class="text-caption" :class="dayStatusColor(day)">{{ dayStatus(day) }}</div></div>
                  <VIcon :icon="expandedDays.includes(day.key) ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'" />
                </div>
              </div>
              <template v-if="expandedDays.includes(day.key)">
                <VDivider />
                <VTable density="comfortable" class="text-body-2 px-2">
                  <thead><tr><th class="text-caption font-weight-bold text-uppercase">Category</th><th class="text-caption font-weight-bold text-uppercase text-end">Actual</th><th class="text-caption font-weight-bold text-uppercase text-end">Eligible</th></tr></thead>
                  <tbody>
                    <template v-for="(item, index) in day.items" :key="index">
                      <tr class="cursor-pointer" @click="toggleItem(`${day.key}-${index}`)">
                        <td class="font-weight-bold"><VIcon :icon="expandedItems.includes(`${day.key}-${index}`) ? 'ri-arrow-down-s-line' : 'ri-arrow-right-s-line'" size="small" class="me-1" />{{ item.title }}</td>
                        <td class="text-end text-medium-emphasis">{{ item.amount ? formatMoney(item.amount, item.currency) : '—' }}</td>
                        <td class="text-end font-weight-bold" :class="item.manualReview ? 'text-warning' : 'text-primary'">{{ item.manualReview ? 'Manual Review' : formatMoney(eligibleOf(item), item.currency) }}</td>
                      </tr>
                      <tr v-if="expandedItems.includes(`${day.key}-${index}`)">
                        <td colspan="3" class="bg-grey-lighten-5">
                          <div class="py-1">
                            <span v-if="item.attachment" class="d-inline-flex align-center ga-2 text-caption text-primary text-decoration-underline cursor-pointer" @click.stop="openPreview(item)"><VIcon icon="ri-file-line" size="small" />{{ item.attachment }}</span>
                            <span v-else class="text-caption text-medium-emphasis">No attachment.</span>
                          </div>
                        </td>
                      </tr>
                    </template>
                  </tbody>
                </VTable>
              </template>
            </VCard>
            <div v-if="!days.length" class="text-body-2 text-medium-emphasis">No expense claimed yet.</div>
          </div>
          <VDivider />
          <VCard color="amber-lighten-5" rounded="lg" elevation="0" border class="mx-4 my-4 border-warning">
            <VCardText class="pa-2">
              <VCheckbox v-model="receiptConfirmed" hide-details density="compact"><template #label><span class="text-body-2">I confirm any required original receipts will be submitted to GMO (if applicable).</span></template></VCheckbox>
            </VCardText>
          </VCard>
          <VDivider />
          <div class="d-flex align-center justify-end ga-2 pa-4">
            <VBtn variant="outlined" color="secondary" class="text-none font-weight-semibold" @click="emit('back')">Back to Edit</VBtn>
            <VBtn color="primary" variant="flat" class="text-none font-weight-semibold" :disabled="!receiptConfirmed" @click="emit('submit')">Submit Expense Report</VBtn>
          </div>
        </VCard>
      </div>
    </VCardText>

    <VDialog v-model="previewOpen" max-width="720" @update:model-value="value => { if (!value) closePreview() }">
      <VCard rounded="lg">
        <div class="d-flex align-center justify-space-between px-4 py-3">
          <span class="text-subtitle-2 font-weight-bold">{{ previewName }}</span>
          <VBtn icon="ri-close-line" variant="text" size="small" @click="closePreview" />
        </div>
        <VDivider />
        <div class="pa-4 d-flex justify-center bg-grey-lighten-4">
          <img v-if="previewKind === 'image'" :src="previewUrl" alt="attachment preview" style="max-width: 100%; max-height: 65vh; object-fit: contain">
          <iframe v-else-if="previewKind === 'pdf'" :src="previewUrl" title="attachment preview" style="width: 100%; height: 70vh; border: none"></iframe>
          <div v-else class="d-flex flex-column align-center text-medium-emphasis py-8">
            <VIcon icon="ri-file-line" size="48" />
            <div class="text-caption mt-2">Preview not available for this file type.</div>
          </div>
        </div>
      </VCard>
    </VDialog>
  </VCard>
</template>