<script setup>
import { computed, reactive, ref, watch } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  paymentLabel: { type: String, default: 'Direct Payment · Reimbursable' },
  bookedByCompany: { type: Boolean, default: false },
  showFareReference: { type: Boolean, default: true },
  departureLegs: { type: Array, default: () => [] },
  returnLegs: { type: Array, default: () => [] },
  processing: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'back', 'process'])

const departureFareInput = ref('')
const departureOperator = ref('')
const returnFareInput = ref('')
const returnOperator = ref('')
const attachments = ref([])
const fileInput = ref(null)
const bookingCurrency = ref('IDR')
const bookingAmountInput = ref('')
const eTicketFile = ref(null)
const eTicketInput = ref(null)
const emailForm = reactive({ to: '', cc: '', subject: '', message: '' })

const departureFare = computed(() => Number(departureFareInput.value.replace(/[^\d]/g, '') || 0))
const returnFare = computed(() => Number(returnFareInput.value.replace(/[^\d]/g, '') || 0))
const bookingAmount = computed(() => Number(bookingAmountInput.value.replace(/[^\d]/g, '') || 0))

const legHeaders = [
  { title: 'TRAVELER', key: 'name', sortable: false },
  { title: 'DATE', key: 'date', sortable: false },
  { title: 'ROUTE', key: 'route', sortable: false },
  { title: 'ROUTE STATUS', key: 'routeStatus', sortable: false },
]

const departureMax = computed(() => departureFare.value * props.departureLegs.length)
const returnMax = computed(() => returnFare.value * props.returnLegs.length)
const totalMax = computed(() => departureMax.value + returnMax.value)

const fareValid = computed(() => !props.showFareReference || (departureFare.value > 0 && returnFare.value > 0 && !!departureOperator.value.trim() && !!returnOperator.value.trim() && attachments.value.length > 0))
const bookingValid = computed(() => !props.bookedByCompany || (bookingAmount.value > 0 && !!eTicketFile.value))
const emailValid = computed(() => !props.bookedByCompany || (!!emailForm.to.trim() && !!emailForm.subject.trim() && !!emailForm.message.trim()))
const isValid = computed(() => fareValid.value && bookingValid.value && emailValid.value)

const routeStatusColor = status => (status === 'Same Route' ? 'success' : 'warning')
const formatIdr = value => 'IDR ' + new Intl.NumberFormat('id-ID').format(Number(value || 0))

function onFilesChange(event) { attachments.value = Array.from(event.target.files ?? []) }

function handleFareInput(key, value) {
  const digits = String(value ?? '').replace(/[^\d]/g, '').replace(/^0+(?=\d)/, '')
  const formatted = digits ? new Intl.NumberFormat('id-ID').format(Number(digits)) : ''
  if (key === 'departure') departureFareInput.value = formatted
  else returnFareInput.value = formatted
}

function handleBookingAmountInput(value) {
  const digits = String(value ?? '').replace(/[^\d]/g, '').replace(/^0+(?=\d)/, '')
  bookingAmountInput.value = digits ? new Intl.NumberFormat('id-ID').format(Number(digits)) : ''
}

watch(() => props.modelValue, open => {
  if (!open) return
  departureFareInput.value = ''
  departureOperator.value = ''
  returnFareInput.value = ''
  returnOperator.value = ''
  attachments.value = []
  bookingCurrency.value = 'IDR'
  bookingAmountInput.value = ''
  eTicketFile.value = null
  emailForm.to = ''
  emailForm.cc = ''
  emailForm.subject = ''
  emailForm.message = ''
  if (fileInput.value) fileInput.value.value = ''
  if (eTicketInput.value) eTicketInput.value.value = ''
})

const close = () => emit('update:modelValue', false)
const onBack = () => { emit('back'); close() }

const onProcess = () => {
  const payload = {}
  if (props.showFareReference) { payload.departureFare = departureFare.value; payload.departureOperator = departureOperator.value.trim(); payload.returnFare = returnFare.value; payload.returnOperator = returnOperator.value.trim(); payload.attachments = attachments.value; payload.totalMax = totalMax.value }
  if (props.bookedByCompany) { payload.bookingAmount = bookingAmount.value; payload.bookingCurrency = bookingCurrency.value; payload.eTicket = eTicketFile.value; payload.email = { ...emailForm } }
  emit('process', payload)
}
</script>

<template>
  <VDialog :model-value="modelValue" max-width="1100" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <VCardItem class="px-6 py-4">
        <VCardTitle class="pa-0 text-subtitle-1 font-weight-bold">{{ showFareReference ? 'Travel Legs & Fare Reference' : 'Travel Legs & Booking' }}</VCardTitle>
        <template #append><VBtn icon="ri-close-line" size="small" variant="text" @click="close" /></template>
      </VCardItem>
      <VDivider />
      <VCardText class="px-6 py-5">
        <VSheet color="grey-lighten-4" border rounded="lg" class="d-flex align-center justify-space-between flex-wrap ga-2 pa-4 mb-5">
          <div class="d-flex align-center flex-wrap ga-2"><span class="text-subtitle-2 text-medium-emphasis">Ticket Arrangement</span><VChip color="success" variant="tonal" size="small" class="font-weight-bold">{{ paymentLabel }}</VChip></div>
          <div class="d-flex align-center flex-wrap ga-2"><VChip color="primary" variant="tonal" size="small" class="font-weight-bold">{{ departureLegs.length }} Departure</VChip><VChip color="primary" variant="tonal" size="small" class="font-weight-bold">{{ returnLegs.length }} Return</VChip></div>
        </VSheet>

        <VSheet color="grey-lighten-5" border rounded="lg" class="pa-4">
          <VRow>
            <VCol cols="12" md="6">
              <div class="text-subtitle-1 font-weight-bold mb-2">Departure Legs</div>
              <VDataTable :headers="legHeaders" :items="departureLegs" hide-default-footer density="comfortable" no-data-text="No departure legs" class="bg-white rounded-lg">
                <template #item.name="{ item }"><div class="text-body-2 font-weight-bold">{{ item.name }}</div><div class="text-caption text-medium-emphasis">{{ item.role }}</div></template>
                <template #item.date="{ item }"><span class="text-caption text-medium-emphasis text-no-wrap">{{ item.date }}</span></template>
                <template #item.route="{ item }"><div class="text-body-2 font-weight-medium text-no-wrap">{{ item.route }}</div></template>
                <template #item.routeStatus="{ item }"><VChip :color="routeStatusColor(item.routeStatus)" variant="tonal" size="small" class="font-weight-bold">{{ item.routeStatus }}</VChip></template>
              </VDataTable>
            </VCol>
            <VCol cols="12" md="6">
              <div class="text-subtitle-1 font-weight-bold mb-2">Return Legs</div>
              <VDataTable :headers="legHeaders" :items="returnLegs" hide-default-footer density="comfortable" no-data-text="No return legs" class="bg-white rounded-lg">
                <template #item.name="{ item }"><div class="text-body-2 font-weight-bold">{{ item.name }}</div><div class="text-caption text-medium-emphasis">{{ item.role }}</div></template>
                <template #item.date="{ item }"><span class="text-caption text-medium-emphasis text-no-wrap">{{ item.date }}</span></template>
                <template #item.route="{ item }"><div class="text-body-2 font-weight-medium text-no-wrap">{{ item.route }}</div></template>
                <template #item.routeStatus="{ item }"><VChip :color="routeStatusColor(item.routeStatus)" variant="tonal" size="small" class="font-weight-bold">{{ item.routeStatus }}</VChip></template>
              </VDataTable>
            </VCol>
          </VRow>
        </VSheet>

        <template v-if="showFareReference">
          <div class="text-subtitle-1 font-weight-bold mt-5 mb-3">Fare Reference</div>
          <VRow>
            <VCol cols="12" md="6">
              <VSheet color="teal-lighten-5" border rounded="lg" class="pa-4 h-100">
                <div class="text-subtitle-2 font-weight-bold mb-3">Departure Fare Reference</div>
                <div class="text-caption text-medium-emphasis mb-1">Fare Reference / Traveler </div>
                <VTextField :model-value="departureFareInput" type="text" inputmode="numeric" prefix="IDR" placeholder="0" variant="outlined" density="compact" bg-color="white" hide-details class="fare-input" @update:model-value="value => handleFareInput('departure', value)" />
                <div class="text-caption text-medium-emphasis mb-1 mt-3">Operator Reference </div>
                <VTextField v-model="departureOperator" placeholder="Airline / Ferry Operator" variant="outlined" density="compact" bg-color="white" hide-details />
              </VSheet>
            </VCol>
            <VCol cols="12" md="6">
              <VSheet color="teal-lighten-5" border rounded="lg" class="pa-4 h-100">
                <div class="text-subtitle-2 font-weight-bold mb-3">Return Fare Reference</div>
                <div class="text-caption text-medium-emphasis mb-1">Fare Reference / Traveler </div>
                <VTextField :model-value="returnFareInput" type="text" inputmode="numeric" prefix="IDR" placeholder="0" variant="outlined" density="compact" bg-color="white" hide-details class="fare-input" @update:model-value="value => handleFareInput('return', value)" />
                <div class="text-caption text-medium-emphasis mb-1 mt-3">Operator Reference </div>
                <VTextField v-model="returnOperator" placeholder="Airline / Ferry Operator" variant="outlined" density="compact" bg-color="white" hide-details />
              </VSheet>
            </VCol>
          </VRow>

          <div class="mt-4">
            <div class="text-caption font-weight-medium mb-1">Fare Reference Attachment <span class="text-error"></span></div>
            <div class="d-flex align-center w-100" style="background:#fff;border:1px solid #bdbdbd;border-radius:8px;padding:8px 10px;min-height:48px"><input ref="fileInput" type="file" accept=".pdf,.jpg,.jpeg,.png" multiple class="w-100" style="font-size:13px;border:0;background:transparent;outline:none" @change="onFilesChange" /></div>
          </div>

          <div class="text-subtitle-1 font-weight-bold mt-6 mb-3">Maximum Ticket Entitlement</div>
          <VRow>
            <VCol cols="12" md="6">
              <VSheet color="grey-lighten-5" border rounded="lg" class="pa-4 h-100">
                <div class="text-subtitle-2 font-weight-bold mb-3">Departure</div>
                <div class="d-flex justify-space-between py-2 text-body-2"><span class="text-medium-emphasis">Fare Reference</span><span class="font-weight-bold">{{ formatIdr(departureFare) }}</span></div>
                <VDivider />
                <div class="d-flex justify-space-between py-2 text-body-2"><span class="text-medium-emphasis">Departure Legs</span><span class="font-weight-bold">{{ departureLegs.length }}</span></div>
                <VDivider />
                <div class="d-flex justify-space-between py-2 text-body-2"><span class="text-medium-emphasis">Departure Maximum</span><span class="font-weight-bold">{{ formatIdr(departureMax) }}</span></div>
              </VSheet>
            </VCol>
            <VCol cols="12" md="6">
              <VSheet color="grey-lighten-5" border rounded="lg" class="pa-4 h-100">
                <div class="text-subtitle-2 font-weight-bold mb-3">Return</div>
                <div class="d-flex justify-space-between py-2 text-body-2"><span class="text-medium-emphasis">Fare Reference</span><span class="font-weight-bold">{{ formatIdr(returnFare) }}</span></div>
                <VDivider />
                <div class="d-flex justify-space-between py-2 text-body-2"><span class="text-medium-emphasis">Return Legs</span><span class="font-weight-bold">{{ returnLegs.length }}</span></div>
                <VDivider />
                <div class="d-flex justify-space-between py-2 text-body-2"><span class="text-medium-emphasis">Return Maximum</span><span class="font-weight-bold">{{ formatIdr(returnMax) }}</span></div>
              </VSheet>
            </VCol>
          </VRow>
          <VSheet color="green-lighten-5" border rounded="lg" class="d-flex align-center justify-space-between pa-4 mt-2"><span class="text-subtitle-1 font-weight-medium">Total Maximum Covered Budget</span><span class="text-h6 font-weight-bold text-success">{{ formatIdr(totalMax) }}</span></VSheet>
        </template>

        <template v-if="bookedByCompany">
          <div class="text-subtitle-1 font-weight-bold mt-6 mb-3">Booking Item</div>
          <VSheet color="grey-lighten-5" border rounded="lg" class="pa-4">
            <div class="text-subtitle-2 font-weight-bold mb-3">Ticket</div>
            <div class="text-caption text-medium-emphasis mb-1">Booking Amount <span class="text-error"></span></div>
            <div class="d-flex ga-2 mb-4">
              <VSelect v-model="bookingCurrency" :items="['IDR']" variant="outlined" density="compact" bg-color="white" hide-details disabled style="max-width: 110px" />
              <VTextField :model-value="bookingAmountInput" type="text" inputmode="numeric" placeholder="0" variant="outlined" density="compact" bg-color="white" hide-details class="fare-input" @update:model-value="handleBookingAmountInput" />
            </div>
            <div class="text-caption text-medium-emphasis mb-1">E-Ticket / Travel Document <span class="text-error"></span></div>
            <div class="d-flex align-center w-100" style="background:#fff;border:1px solid #bdbdbd;border-radius:8px;padding:8px 10px;min-height:48px"><input ref="eTicketInput" type="file" accept=".pdf,.jpg,.jpeg,.png" class="w-100" style="font-size:13px;border:0;background:transparent;outline:none" @change="eTicketFile = $event.target.files?.[0] || null" /></div>
          </VSheet>

          <div class="text-subtitle-1 font-weight-bold mt-6 mb-3">Send Email</div>
          <VSheet color="grey-lighten-5" border rounded="lg" class="pa-4">
            <VRow>
              <VCol cols="12" md="6"><div class="text-caption text-medium-emphasis mb-1">To <span class="text-error"></span></div><VTextField v-model="emailForm.to" type="email" placeholder="Enter recipient email" variant="outlined" density="compact" bg-color="white" hide-details /></VCol>
              <VCol cols="12" md="6"><div class="text-caption text-medium-emphasis mb-1">CC</div><VTextField v-model="emailForm.cc" placeholder="Enter CC email" variant="outlined" density="compact" bg-color="white" hide-details /></VCol>
              <VCol cols="12"><div class="text-caption text-medium-emphasis mb-1">Subject <span class="text-error"></span></div><VTextField v-model="emailForm.subject" placeholder="Enter email subject" variant="outlined" density="compact" bg-color="white" hide-details /></VCol>
              <VCol cols="12"><div class="text-caption text-medium-emphasis mb-1">Message <span class="text-error"></span></div><VTextarea v-model="emailForm.message" placeholder="Enter email message" rows="4" variant="outlined" density="compact" bg-color="white" hide-details /></VCol>
            </VRow>
          </VSheet>
        </template>
      </VCardText>
      <VDivider />
      <VCardActions class="px-6 py-4 bg-grey-lighten-5">
        <VSpacer />
        <VBtn variant="outlined" color="secondary" @click="onBack">Back</VBtn>
        <VBtn color="primary" variant="flat" :disabled="!isValid || processing" :loading="processing" @click="onProcess">Processed</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.fare-input :deep(.v-field__input) { text-align: right; }
</style>