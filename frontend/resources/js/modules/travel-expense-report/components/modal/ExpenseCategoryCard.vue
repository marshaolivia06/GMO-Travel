<script setup>
import { computed, onMounted, ref, watch } from 'vue'

const props = defineProps({ title: { type: String, default: '' }, item: { type: Object, required: true }, fixed: { type: Boolean, default: false }, locked: { type: Boolean, default: false }, defaultAmount: { type: Number, default: 0 }, error: { type: String, default: '' }, limit: { type: Object, default: null }, showDescription: { type: Boolean, default: true }, descriptionRequired: { type: Boolean, default: false }, compact: { type: Boolean, default: false } })

const currencies = ['IDR', 'USD', 'SGD', 'MYR', 'EUR']
const formatRupiah = value => `IDR ${new Intl.NumberFormat('id-ID').format(value)}`
const formatNumberByCurrency = (value, currency) => {
  if (value === '' || value == null) return ''
  const number = Number(value)
  if (isNaN(number)) return ''
  if (currency === 'IDR') return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(number)
  if (currency === 'EUR') return new Intl.NumberFormat('de-DE', { maximumFractionDigits: 2 }).format(number)
  if (currency === 'JPY') return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(number)
  return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(number)
}
const parseNumberInput = (value, currency) => {
  if (!value) return ''
  let result = String(value)
  if (currency === 'IDR') return result.replace(/[^0-9]/g, '')
  result = currency === 'EUR' ? result.replace(/\./g, '').replace(',', '.') : result.replace(/,/g, '')
  return result.replace(/[^0-9.]/g, '')
}
const amountDisplay = computed({
  get: () => formatNumberByCurrency(props.item.amount, props.item.currency),
  set: value => { props.item.amount = parseNumberInput(value, props.item.currency) },
})
const disabled = computed(() => props.locked || props.item.paid_by_others)
const cols = computed(() => props.compact ? 6 : 12)
const onPaidByOthers = value => {
  props.item.paid_by_others = !!value
  props.item.amount = value ? 0 : props.defaultAmount
  if (value) props.item.attachment = null
}
const fileInput = ref(null)
const onFileChange = event => { props.item.attachment = Array.from(event.target.files) }
const syncInput = () => {
  if (!fileInput.value) return
  const transfer = new DataTransfer()
  const files = Array.isArray(props.item.attachment) ? props.item.attachment : []
  files.forEach(file => transfer.items.add(file))
  fileInput.value.files = transfer.files
}
onMounted(syncInput)
watch(() => props.item.attachment, syncInput, { flush: 'post' })
</script>

<template>
  <VCard rounded="lg" elevation="0" border>
    <div class="d-flex align-center justify-space-between px-4 py-3 bg-grey-lighten-5">
      <div class="d-flex align-center ga-2">
        <span class="text-subtitle-2 font-weight-bold">{{ title }}</span>
        <VChip v-if="fixed" size="x-small" color="grey" variant="tonal">Fixed</VChip>
      </div>
      <div class="d-flex align-center ga-3">
        <VSwitch :model-value="item.paid_by_others" label="Paid by Others" color="primary" density="compact" hide-details :disabled="locked" class="flex-grow-0" @update:model-value="onPaidByOthers" />
        <slot name="actions" />
      </div>
    </div>
    <VDivider />
    <div class="pa-4">
      <slot />
      <VAlert v-if="limit" color="warning" variant="tonal" density="compact" :icon="false" class="mb-3">
        <div class="d-flex align-start justify-space-between ga-2">
          <VRow no-gutters class="text-caption flex-grow-1">
            <VCol cols="4"><div class="text-medium-emphasis">{{ limit.budgetLabel }}</div><div class="font-weight-bold">{{ formatRupiah(limit.budget) }}</div></VCol>
            <VCol cols="4"><div class="text-medium-emphasis">MAXIMUM INPUT NOW</div><div class="font-weight-bold">{{ formatRupiah(limit.max) }}</div></VCol>
            <VCol cols="4"><div class="text-medium-emphasis">REMAINING TRIP BALANCE</div><div class="font-weight-bold">{{ formatRupiah(limit.remaining) }}</div></VCol>
          </VRow>
          <span class="d-inline-flex cursor-pointer"><VIcon icon="ri-information-line" size="small" color="medium-emphasis" /><VTooltip activator="parent" location="top" max-width="300">{{ limit.note }}</VTooltip></span>
        </div>
      </VAlert>
      <VRow dense>
        <VCol :cols="cols"><div class="text-caption font-weight-bold mb-1">Currency</div><VSelect v-model="item.currency" :items="currencies" variant="outlined" density="comfortable" :hint="compact ? undefined : 'Currency can be selected manually.'" :persistent-hint="!compact" :disabled="disabled" /></VCol>
        <VCol :cols="cols"><div class="text-caption font-weight-bold mb-1">Amount</div><VTextField v-model="amountDisplay" type="text" inputmode="decimal" placeholder="0" variant="outlined" density="comfortable" hide-details="auto" :error-messages="error" :disabled="disabled" class="text-right-input" /></VCol>
        <VCol v-if="showDescription" :cols="cols"><div class="text-caption font-weight-bold mb-1">{{ descriptionRequired ? 'Description *' : 'Description' }}</div><VTextField v-model="item.description" variant="outlined" density="comfortable" hide-details :disabled="disabled" /></VCol>
        <VCol :cols="cols">
          <template v-if="fixed">
            <div class="d-flex align-center ga-1 mb-1">
              <span class="text-caption font-weight-bold">Attachment</span>
              <span class="d-inline-flex cursor-pointer"><VIcon icon="ri-information-line" size="small" color="medium-emphasis" /><VTooltip activator="parent" location="top">Not Required - Fixed Category</VTooltip></span>
            </div>
          </template>
          <template v-else>
            <div class="text-caption font-weight-bold mb-1">Attachment *</div><input ref="fileInput" type="file" class="d-block" :disabled="disabled" @change="onFileChange">
          </template>
        </VCol>
      </VRow>
    </div>
  </VCard>
</template>
<style scoped>
.text-right-input :deep(input) { text-align: right; }
</style>