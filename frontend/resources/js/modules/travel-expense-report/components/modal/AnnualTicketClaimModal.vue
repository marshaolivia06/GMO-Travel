<script setup>
import { computed, ref, watch } from 'vue'
import { useAuthStore } from '../../../../stores/auth.js'
import { useConfirm } from '../../../../composables/useConfirm'
import { legRouteStatus } from '../../../annual-ticket/utils/routeStatus.js'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  ticket: { type: Object, default: null },
  initial: { type: Object, default: null },
  processing: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'close', 'submit', 'draft'])

const { confirm } = useConfirm()

const t = computed(() => props.ticket || {})
const legs = computed(() => t.value.legs ?? [])
const isDirectPayment = computed(() => t.value.ticket_arrangement === 'Direct Payment')
const claimType = computed(() => isDirectPayment.value ? 'Ticket Reimbursement' : 'Employee Excess Settlement')
const arrangementLabel = computed(() => isDirectPayment.value ? 'Direct Payment' : `${t.value.ticket_arrangement || 'Booked by Company'} - Excess`)
const claimTitle = computed(() => isDirectPayment.value ? 'Direct Payment - Ticket Reimbursement' : 'Booked by Company - Excess Settlement')

// Reference route: rute leg pertama pada direction tersebut
const directionRoute = direction => {
  const leg = directionLegs(direction)[0]
  return leg ? `${leg.travel_from} - ${leg.travel_to}` : '-'
}

const routeStatus = leg => legRouteStatus(t.value, leg)

const directionLegs = direction => legs.value.filter(leg => leg.direction === direction)
const departureLegs = computed(() => directionLegs('departure'))
const returnLegs = computed(() => directionLegs('return'))
const legDate = leg => {
  const value = leg.reschedule_date ?? leg.travel_date
  if (!value) return '-'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

const fareRows = computed(() => (t.value.fare_references ?? []).map(reference => {
  const legCount = directionLegs(reference.direction).length
  return {
    direction: reference.direction === 'departure' ? 'Departure' : 'Return',
    route: directionRoute(reference.direction),
    fare: reference.fare_amount,
    currency: reference.currency ?? 'IDR',
    legs: legCount,
    subtotal: Number(reference.fare_amount || 0) * legCount,
    operator: reference.operator_name,
  }
}))
const totalMaxFare = computed(() => fareRows.value.reduce((sum, row) => sum + row.subtotal, 0))
const claimCurrency = computed(() => t.value.fare_references?.[0]?.currency ?? 'IDR')

const formatMoney = value => new Intl.NumberFormat('id-ID').format(Number(value || 0))

const totalActual = ref(null)
const attachments = ref([])
const actualError = ref('')

// Excess settlement (Booked by Company)
const bookingAmount = computed(() => Number(t.value.booking?.amount || 0))
const bookingCurrency = computed(() => t.value.booking?.amount_currency ?? claimCurrency.value)
const excessAmount = computed(() => Math.max(0, bookingAmount.value - totalMaxFare.value))

async function downloadETicket() {
  const url = t.value.booking?.e_ticket_url
  if (!url) return
  try {
    const response = await fetch(url)
    if (!response.ok) throw new Error('fetch failed')
    const blob = await response.blob()
    const link = document.createElement('a')
    link.href = URL.createObjectURL(blob)
    link.download = decodeURIComponent(url.split('/').pop().split('?')[0]) || 'e-ticket'
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(link.href)
  } catch {
    window.open(url, '_blank')
  }
}
// Finance Payment Account - read-only, dari data finance account
const authStore = useAuthStore()
const financeAccount = computed(() => t.value.finance_account ?? {})
const bankName = computed(() => financeAccount.value.bank_name || '-')
const accountNumber = computed(() => financeAccount.value.account_number || '-')
const accountName = computed(() => financeAccount.value.account_name || '-')
const paymentNote = computed(() => financeAccount.value.payment_note || [t.value.document_number, authStore.user?.name].filter(Boolean).join(' / ') || '-')
const proofFile = ref(null)

watch(() => props.modelValue, open => {
  if (open) {
    // Prefill dari draft TER yang sudah ada - file lampiran tidak bisa diprefill (keamanan browser)
    totalActual.value = props.initial?.total_actual_amount ?? null
    attachments.value = []
    clearTimeout(actualErrorTimer)
    actualError.value = ''
    proofFile.value = null
  }
})

const reimbursableAmount = computed(() => Math.min(Number(totalActual.value) || 0, totalMaxFare.value))

const totalActualDisplay = computed(() => totalActual.value ? new Intl.NumberFormat('id-ID').format(Number(totalActual.value)) : '')

let actualErrorTimer = null

// Digit yang melebihi fare reference diblokir di keydown - benar-benar tidak masuk ke input
function onAmountKeydown(event) {
  const allowedKeys = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Home', 'End']
  if (allowedKeys.includes(event.key) || event.ctrlKey || event.metaKey || event.altKey) return

  if (!/^\d$/.test(event.key)) {
    event.preventDefault()
    return
  }

  const input = event.target
  const start = input.selectionStart ?? input.value.length

  // Posisi cursor dipetakan ke index digit (pemisah titik tidak dihitung)
  let digitIndex = 0
  for (let i = 0; i < start; i++) {
    if (/\d/.test(input.value[i])) digitIndex++
  }

  const digits = input.value.replace(/\D/g, '')
  const nextDigits = digits.slice(0, digitIndex) + event.key + digits.slice(digitIndex)
  const normalized = nextDigits.replace(/^0+(?=\d)/, '')
  const nextAmount = Number(normalized || 0)

  if (normalized.length > 10 || (totalMaxFare.value > 0 && nextAmount > totalMaxFare.value)) {
    event.preventDefault()
    showActualError()
  }
}

// Warning hanya tampil sekali per 2.5 detik walau user spam digit
function showActualError() {
  if (actualError.value) return
  actualError.value = 'Amount cannot exceed Total Fare Reference.'
  actualErrorTimer = setTimeout(() => { actualError.value = '' }, 2500)
}

function setTotalActual(value) {
  const digits = String(value ?? '').replace(/[^\d]/g, '').replace(/^0+(?=\d)/, '')
  const amount = digits ? Number(digits) : null

  clearTimeout(actualErrorTimer)

  // Safety net (misal paste): cap tetap ke fare reference
  if (amount !== null && totalMaxFare.value > 0 && amount > totalMaxFare.value) {
    totalActual.value = totalMaxFare.value
    return
  }

  totalActual.value = amount
  actualError.value = ''
}

function setAttachments(event) {
  const files = Array.from(event.target?.files ?? [])
  // File baru ditambahkan ke daftar, bukan menggantikan yang lama
  attachments.value = [...attachments.value, ...files]
  // Reset nilai input agar file yang sama bisa dipilih lagi
  event.target.value = ''
  actualError.value = ''
}

const removeAttachment = index => attachments.value.splice(index, 1)

function setProofFile(event) {
  proofFile.value = event.target?.files?.[0] ?? null
}

const canSubmit = computed(() => {
  if (isDirectPayment.value) return attachments.value.length > 0 || storedAttachments.value.length > 0
  // Draft excess yang sudah punya proof tersimpan boleh submit tanpa upload ulang
  return !!proofFile.value || !!props.initial?.has_proof
})

// File yang sudah tersimpan di draft TER (dari backend) - tetap tampil saat dibuka ulang
const storedAttachments = computed(() => props.initial?.attachments ?? [])

const isPdfFile = name => String(name ?? '').toLowerCase().endsWith('.pdf')

const openStoredFile = url => url && window.open(url, '_blank')

// Payload sesuai kontrak backend: nominal settlement/excess dihitung server, kirim hanya input user + file asli
function payload(action) {
  const data = {
    action,
    annual_ticket_id: t.value.id,
  }

  if (isDirectPayment.value) {
    data.total_actual_amount = Number(totalActual.value) || 0
    return {
      data,
      attachments: [...attachments.value],
      proof_of_payment: null,
    }
  }

  return {
    data,
    attachments: [],
    proof_of_payment: proofFile.value,
  }
}

const submitPayload = async () => {
  const ok = await confirm({ title: 'Submit Claim?', text: 'Are you sure you want to submit this claim?', confirmText: 'Yes, Submit', color: 'primary' })
  if (!ok) return
  emit('submit', payload('submit'))
}

const draftPayload = async () => {
  const ok = await confirm({ title: 'Save Draft?', text: 'Are you sure you want to save this claim as a draft?', confirmText: 'Save Draft', color: 'secondary' })
  if (!ok) return
  emit('draft', payload('draft'))
}

function close() {
  emit('close')
  emit('update:modelValue', false)
}
</script>

<template>
  <VDialog :model-value="modelValue" max-width="1400" width="95%" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <div class="d-flex align-center justify-space-between px-4 py-3">
        <span class="text-h6 font-weight-bold">Annual Ticket - Air Ticket Only</span>
        <VBtn icon="ri-close-line" variant="text" size="small" @click="close" />
      </div>
      <VDivider />

      <VCardText class="bg-grey-lighten-5 pa-4">
        <VCard rounded="lg" border elevation="0" class="mb-4">
          <div class="bg-grey-lighten-4 px-4 py-3">
            <span class="text-subtitle-2 font-weight-bold">Linked Travel Information</span>
          </div>
          <VDivider />
          <VCardText class="pa-4">
            <VRow no-gutters class="border rounded-lg overflow-hidden">
              <VCol cols="6" class="px-4 py-3 border-b border-e">
                <div class="text-caption text-medium-emphasis">Annual Ticket Number</div>
                <div class="text-body-2 font-weight-bold">{{ t.document_number || '-' }}</div>
              </VCol>
              <VCol cols="6" class="px-4 py-3 border-b">
                <div class="text-caption text-medium-emphasis">Route Condition</div>
                <VChip :color="t.route_condition === 'Same Route' ? 'success' : 'warning'" variant="tonal" size="small" class="mt-1">{{ t.route_condition || '-' }}</VChip>
              </VCol>
              <VCol cols="6" class="px-4 py-3 border-b border-e">
                <div class="text-caption text-medium-emphasis">Employee</div>
                <div class="text-body-2 font-weight-bold">{{ t.employee?.name || '-' }}</div>
              </VCol>
              <VCol cols="6" class="px-4 py-3 border-b">
                <div class="text-caption text-medium-emphasis">Ticket Arrangement</div>
                <div class="text-body-2 font-weight-bold">{{ arrangementLabel }}</div>
              </VCol>
              <VCol cols="12" class="px-4 py-3">
                <div class="text-caption text-medium-emphasis">Claim / Settlement Type</div>
                <div class="text-body-2 font-weight-bold">{{ claimType }}</div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <VCard v-for="direction in ['departure', 'return']" :key="direction" rounded="lg" border elevation="0" class="mb-4">
          <div class="bg-grey-lighten-4 px-4 py-3">
            <span class="text-subtitle-2 font-weight-bold text-capitalize">{{ direction }} Travel Legs</span>
          </div>
          <VDivider />
          <VTable density="comfortable">
            <thead>
              <tr>
                <th>Traveler</th>
                <th>Relationship</th>
                <th>Date</th>
                <th>Travel From</th>
                <th>Travel To</th>
                <th>Route Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="leg in directionLegs(direction)" :key="leg.id">
                <td class="text-body-2">{{ leg.name }}</td>
                <td class="text-body-2">{{ leg.relationship || 'Employee' }}</td>
                <td class="text-body-2">{{ legDate(leg) }}</td>
                <td class="text-body-2">{{ leg.travel_from }}</td>
                <td class="text-body-2">{{ leg.travel_to }}</td>
                <td class="text-body-2">
                  <VChip :color="routeStatus(leg) === 'Same Route' ? 'success' : 'warning'" variant="tonal" size="x-small">{{ routeStatus(leg) }}</VChip>
                </td>
              </tr>
              <tr v-if="!directionLegs(direction).length">
                <td colspan="6" class="text-center text-medium-emphasis py-3">No {{ direction }} legs.</td>
              </tr>
            </tbody>
          </VTable>
        </VCard>

        <VCard rounded="lg" border elevation="0" class="mb-4">
          <div class="bg-grey-lighten-4 px-4 py-3">
            <span class="text-subtitle-2 font-weight-bold">Fare Reference</span>
          </div>
          <VDivider />
          <VTable density="comfortable">
            <thead>
              <tr>
                <th>Direction</th>
                <th>Reference Route</th>
                <th>Fare per Traveler</th>
                <th>Travel Legs</th>
                <th>Subtotal</th>
                <th>Operator Reference</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in fareRows" :key="row.direction">
                <td class="text-body-2">{{ row.direction }}</td>
                <td class="text-body-2">{{ row.route }}</td>
                <td class="text-body-2">{{ row.currency }} {{ formatMoney(row.fare) }}</td>
                <td class="text-body-2">{{ row.legs }}</td>
                <td class="text-body-2 font-weight-bold">{{ row.currency }} {{ formatMoney(row.subtotal) }}</td>
                <td class="text-body-2">{{ row.operator }}</td>
              </tr>
              <tr v-if="!fareRows.length">
                <td colspan="6" class="text-center text-medium-emphasis py-3">No fare reference.</td>
              </tr>
            </tbody>
            <tfoot v-if="fareRows.length">
              <tr class="bg-grey-lighten-4">
                <td colspan="4" class="text-subtitle-2 font-weight-bold">Total Maximum Ticket Fare Reference</td>
                <td class="text-subtitle-2 font-weight-bold">{{ claimCurrency }} {{ formatMoney(totalMaxFare) }}</td>
                <td />
              </tr>
            </tfoot>
          </VTable>
        </VCard>

        <VCard rounded="lg" border elevation="0">
          <div class="bg-grey-lighten-4 px-4 py-3">
            <span class="text-subtitle-2 font-weight-bold">{{ claimTitle }}</span>
          </div>
          <VDivider />
          <VCardText class="pa-4">
            <VRow v-if="isDirectPayment">
              <VCol cols="12" md="4">
                <div class="text-caption mb-1">Currency</div>
                <VTextField :model-value="claimCurrency" variant="outlined" density="comfortable" readonly bg-color="grey-lighten-1" />
              </VCol>
              <VCol cols="12" md="4">
                <div class="text-caption text-black mb-1">Total Actual Ticket Amount <span class="text-error"></span></div>
                <VTextField
                  :model-value="totalActualDisplay"
                  type="text"
                  inputmode="numeric"
                  placeholder="Input actual ticket amount"
                  variant="outlined"
                  density="comfortable"
                  hide-details="auto"
                  class="text-right-input"
                  :error-messages="actualError ? [actualError] : []"
                  @keydown="onAmountKeydown"
                  @paste.prevent
                  @update:model-value="setTotalActual"
                />
              </VCol>
              <VCol cols="12" md="4">
                <div class="text-caption mb-1">Total Fare Reference</div>
                <VTextField :model-value="`${claimCurrency} ${formatMoney(totalMaxFare)}`" variant="outlined" density="comfortable" readonly bg-color="grey-lighten-2" />
              </VCol>
              <VCol cols="12" md="6">
                <div class="d-flex flex-column h-100">
                  <div class="text-caption text-black mb-1">E-Ticket + Boarding Pass + Invoice <span class="text-error"></span></div>
                  <div class="d-flex align-center flex-grow-1 w-100" style="background:#fff;border:1px solid #bdbdbd;border-radius:8px;padding:8px 10px;min-height:48px">
                    <input type="file" multiple accept=".jpg,.jpeg,.png" class="w-100" style="font-size:13px;border:0;background:transparent;outline:none" @change="setAttachments">
                  </div>
                  <div v-if="attachments.length" class="d-flex flex-wrap ga-1 mt-2">
                    <VChip v-for="(file, index) in attachments" :key="`${file.name}-${index}`" size="x-small" variant="tonal" closable @click:close="removeAttachment(index)">
                      {{ file.name }}
                    </VChip>
                  </div>
                  <div v-if="storedAttachments.length" class="d-flex flex-wrap ga-1 mt-1">
                    <VChip v-for="file in storedAttachments" :key="file.url" size="x-small" color="success" variant="tonal" class="cursor-pointer" @click="openStoredFile(file.url)">
                      <VIcon v-if="!isPdfFile(file.name)" icon="ri-check-line" size="x-small" start />{{ file.name }}
                    </VChip>
                  </div>
                  <div class="text-caption text-medium-emphasis mt-1">
                    <template v-if="attachments.length">{{ attachments.length }} file(s) selected</template>
                    <template v-else>&nbsp;</template>
                  </div>
                </div>
              </VCol>
              <VCol cols="12" md="6">
                <div class="d-flex flex-column h-100">
                  <div class="text-caption mb-1">Reimbursable Amount</div>
                  <div class="flex-grow-1">
                    <VTextField
                      :model-value="`${claimCurrency} ${formatMoney(reimbursableAmount)}`"
                      variant="outlined"
                      density="comfortable"
                      readonly
                      bg-color="grey-lighten-1"
                      class="h-100"
                      style="height: 100%"
                    />
                  </div>
                  <div class="text-caption mt-1">&nbsp;</div>
                </div>
              </VCol>
            </VRow>

            <VRow v-else>
              <VCol cols="12" md="4">
                <div class="text-caption mb-1">Company Booking Amount</div>
                <VTextField :model-value="`${bookingCurrency} ${formatMoney(bookingAmount)}`" variant="outlined" density="comfortable" readonly bg-color="grey-lighten-1" />
              </VCol>
              <VCol cols="12" md="4">
                <div class="text-caption mb-1">Total Fare Reference</div>
                <VTextField :model-value="`${claimCurrency} ${formatMoney(totalMaxFare)}`" variant="outlined" density="comfortable" readonly bg-color="grey-lighten-2" />
              </VCol>
              <VCol cols="12" md="4">
                <div class="text-caption text-medium-emphasis mb-1">E-Ticket from GMO</div>
                <VTextField variant="outlined" density="comfortable" readonly bg-color="grey-lighten-1" style="cursor: pointer" @click="downloadETicket">
                  <template #prepend-inner>
                    <span class="text-primary text-body-2 font-weight-medium text-decoration-underline">Download</span>
                  </template>
                </VTextField>
              </VCol>
              <VCol cols="12">
                <VCard color="warning" variant="tonal" border rounded="lg" class="border-warning px-4 py-3">
    <div class="text-caption text-uppercase">Employee Excess</div>
    <div class="text-h5 font-weight-bold">{{ claimCurrency }} {{ formatMoney(excessAmount) }}</div>
    <div class="text-caption mt-1">{{ excessAmount > 0 ? 'Pay this amount to Finance before completing the settlement.' : 'No excess - nothing to settle.' }}</div>
  </VCard>
</VCol>

              <VCol cols="12" md="6">
                <div class="border rounded-lg bg-grey-lighten-5 pa-4 h-100">
                  <div class="text-subtitle-2 font-weight-bold mb-3">Finance Payment Account</div>
                  <div class="d-flex justify-space-between py-2 border-b text-body-2"><span class="text-medium-emphasis">Bank</span><span class="font-weight-bold">{{ bankName }}</span></div>
                  <div class="d-flex justify-space-between py-2 border-b text-body-2"><span class="text-medium-emphasis">Account Number</span><span class="font-weight-bold">{{ accountNumber }}</span></div>
                  <div class="d-flex justify-space-between py-2 border-b text-body-2"><span class="text-medium-emphasis">Account Name</span><span class="font-weight-bold">{{ accountName }}</span></div>
                  <div class="d-flex justify-space-between py-2 text-body-2"><span class="text-medium-emphasis">Payment Note</span><span class="font-weight-bold">{{ paymentNote }}</span></div>
                </div>
              </VCol>
              <VCol cols="12" md="6">
                <div class="text-caption text-black mb-1">Proof of Excess Payment to Finance <span class="text-error"></span></div>
                <div class="d-flex align-center w-100" style="background:#fff;border:1px solid #bdbdbd;border-radius:8px;padding:8px 10px;min-height:48px">
                  <input type="file" accept=".jpg,.jpeg,.png" class="w-100" style="font-size:13px;border:0;background:transparent;outline:none" @change="setProofFile">
                </div>
                <div v-if="!proofFile && initial?.has_proof" class="text-caption mt-1">
                  <VChip size="x-small" color="success" variant="tonal"><VIcon icon="ri-check-line" size="x-small" start />Proof of payment already uploaded</VChip>
                </div>
                <div v-else-if="!proofFile" class="text-caption text-medium-emphasis mt-1">&nbsp;</div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCardText>

      <VDivider />
      <VCardActions class="justify-end px-4 py-3 ga-2">
        <VBtn variant="text" :disabled="!canSubmit || processing" :loading="processing" @click="draftPayload">Draft</VBtn>
        <VBtn color="primary" variant="flat" :disabled="!canSubmit || processing" @click="submitPayload">Submit</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.text-right-input :deep(input) {
  text-align: right;
}
.text-right-input :deep(input::placeholder) {
  text-align: left;
}
</style>