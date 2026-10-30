<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({
  travelers: { type: Array, required: true },
  legs: { type: Object, required: true },
  route: { type: Object, required: true },
  quotaYear: { type: Number, default: () => new Date().getFullYear() },
  submitting: { type: Boolean, default: false },
  initial: { type: Object, default: null },
  evidenceRequired: { type: Boolean, default: true },
  submitLabel: { type: String, default: 'Submit Annual Ticket Request' },
})

const emit = defineEmits(['back', 'submit'])

const arrangementOptions = [
  { title: 'Direct Payment', value: 'direct_payment' },
  { title: 'Booked by Company', value: 'booked_by_company' },
]

const arrangement = ref(props.initial?.arrangement ?? 'direct_payment')
const remarks = ref(props.initial?.remarks ?? '')
const evidenceFile = ref(null)

function setEvidenceFile(event) {
  evidenceFile.value = event.target.files?.[0] ?? null
}
const confirmInfo = ref(false)
const confirmSettle = ref(false)

const norm = value => (value || '').trim().toLowerCase()

const legOf = (direction, traveler) => (props.legs?.[direction] || []).find(item => item.type === traveler.type && item.id === traveler.id) || null

const isSameRoute = (direction, item) => {
  const expectedFrom = direction === 'departure' ? props.route.origin : props.route.destination
  const expectedTo = direction === 'departure' ? props.route.destination : props.route.origin

  return norm(item.from) === norm(expectedFrom) && norm(item.to) === norm(expectedTo)
}

const sections = computed(() => [{ key: 'departure', title: 'Departure' }, { key: 'return', title: 'Return' }].map(entry => ({
  ...entry,
  items: props.travelers.map(traveler => ({ traveler, leg: legOf(entry.key, traveler) })).filter(row => row.leg).map(row => ({ ...row, same: isSameRoute(entry.key, row.leg) })),
})))

const hasDifferentRoute = computed(() => sections.value.some(section => section.items.some(row => !row.same)))
const terSections = computed(() => sections.value
  .map(section => ({ ...section, different: section.items.filter(row => !row.same) })))
const routeLabel = computed(() => (hasDifferentRoute.value ? 'Different Route' : 'Same Route'))
const routeColor = computed(() => (hasDifferentRoute.value ? 'error' : 'success'))
const routeHint = computed(() => (hasDifferentRoute.value ? 'At least one travel leg uses a different route.' : 'All travel legs use the eligible route.'))
const arrangementStatus = computed(() => {
  if (arrangement.value !== 'booked_by_company')
    return { label: 'Reimbursable', color: 'success' }

  return hasDifferentRoute.value
    ? { label: 'Excess Potential', color: 'info' }
    : { label: 'Not Reimbursable', color: 'warning' }
})
const canSubmit = computed(() => confirmInfo.value && (!hasDifferentRoute.value || confirmSettle.value) && (!props.evidenceRequired || !!evidenceFile.value))
const travelerRole = traveler => (traveler.type === 'employee' ? 'Employee' : traveler.relationship)

watch(hasDifferentRoute, value => {
  if (!value)
    confirmSettle.value = false
})

const onSubmit = () => emit('submit', { arrangement: arrangement.value, remarks: remarks.value, evidenceFile: evidenceFile.value, routeCondition: routeLabel.value })
</script>

<template>
  <VCardText class="px-6 pt-8 pb-6">
    <VCard variant="outlined" class="px-5 py-3 mb-4">
      <div class="text-caption text-medium-emphasis">Annual Ticket Quota Year</div>
      <div class="text-body-2 font-weight-bold">{{ props.quotaYear }}</div>
    </VCard>

    <VCard variant="outlined" class="px-5 py-3 mb-4 d-flex align-center justify-space-between">
      <div class="d-flex align-center ga-2"><span class="text-caption text-medium-emphasis">Route Condition</span><span class="text-h6 font-weight-bold">{{ routeLabel }}</span></div>
      <span class="text-caption text-medium-emphasis">{{ routeHint }}</span>
    </VCard>

    <VCard variant="outlined" class="mb-4">
      <VCardTitle class="text-subtitle-1 font-weight-bold">Travel Leg Review</VCardTitle>
      <VDivider />
      <VCardText>
        <VRow>
          <VCol v-for="section in sections" :key="section.key" cols="12" md="6">
            <div class="text-subtitle-2 font-weight-bold mb-3">{{ section.title }}</div>
            <VTable density="comfortable" class="border rounded">
              <thead>
                <tr>
                  <th class="text-caption font-weight-bold">Traveler</th>
                  <th class="text-caption font-weight-bold">Date</th>
                  <th class="text-caption font-weight-bold">Route</th>
                  <th class="text-caption font-weight-bold">Route Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in section.items" :key="`${row.traveler.type}-${row.traveler.id}`">
                  <td><div class="text-body-2 font-weight-bold">{{ row.traveler.name }}</div><div class="text-caption text-primary">{{ travelerRole(row.traveler) }}</div></td>
                  <td class="text-body-2">{{ row.leg.date || '-' }}</td>
                  <td><div class="text-body-2 font-weight-bold">{{ row.leg.from }} - {{ row.leg.to }}</div><div class="text-caption text-medium-emphasis"></div></td>
                  <td><VChip size="small" variant="outlined" :color="row.same ? 'success' : 'error'" class="font-weight-bold">{{ row.same ? 'Same Route' : 'Different Route' }}</VChip></td>
                </tr>
              </tbody>
            </VTable>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VCard v-if="hasDifferentRoute" variant="outlined" class="mb-4">
      <VCardTitle class="text-subtitle-1 font-weight-bold">Potential TER Eligibility</VCardTitle>
      <VDivider />
      <VCardText>
        <VRow>
          <VCol v-for="section in terSections" :key="section.key" cols="12" md="6">
            <template v-if="section.different.length">
              <div class="d-flex align-center justify-space-between mb-3">
                <span class="text-subtitle-2 font-weight-bold">{{ section.title }}</span>
                <VChip size="small" variant="outlined" :color="routeColor" class="font-weight-bold">{{ routeLabel }}</VChip>
              </div>
              <VSheet v-for="row in section.different" :key="`${row.traveler.type}-${row.traveler.id}`" class="d-flex align-center justify-space-between border rounded pa-3 mb-2">
                <span class="text-body-2 font-weight-bold">{{ row.traveler.name }}</span>
                <span class="text-caption text-medium-emphasis">{{ row.leg.from }} - {{ row.leg.to }}</span>
              </VSheet>
            </template>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VCard variant="outlined" class="mb-4">
      <VCardTitle class="text-subtitle-1 font-weight-bold">Ticket Arrangement</VCardTitle>
      <VDivider />
      <VCardText>
        <VRow>
          <VCol cols="12" md="6">
            <VCard variant="outlined" class="pa-4">
              <div class="d-flex align-center justify-space-between"><span class="text-h6 font-weight-bold text-high-emphasis">Airplane Ticket</span><VChip size="small" variant="outlined" :color="arrangementStatus.color" class="font-weight-bold">{{ arrangementStatus.label }}</VChip></div>
              <div class="text-caption text-medium-emphasis mb-3">Select transport type, arranger, and payer.</div>
              <div class="text-caption font-weight-bold text-high-emphasis mb-1">Arrangement <span class="text-error">*</span></div>
              <VSelect v-model="arrangement" :items="arrangementOptions" item-title="title" item-value="value" variant="outlined" density="comfortable" />
            </VCard>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VCard v-if="hasDifferentRoute" variant="outlined" class="mb-4">
      <VCardTitle class="text-subtitle-1 font-weight-bold">Different Route Remarks</VCardTitle>
      <VDivider />
      <VCardText>
        <div class="text-caption font-weight-bold text-high-emphasis mb-1">Remarks <span class="text-error">*</span></div>
        <VTextarea v-model="remarks" placeholder="Explain the reason for the different-route trip(s)." variant="outlined" rows="3" hide-details />
      </VCardText>
    </VCard>

    <VCard variant="outlined" class="mb-4">
      <VCardTitle class="text-subtitle-1 font-weight-bold">Haermes Annual Ticket Approval Evidence</VCardTitle>
      <VDivider />
      <VCardText>
        <VRow>
          <VCol cols="12" md="6">
            <div class="text-caption font-weight-medium mb-1 d-flex align-center ga-1">
              <span>Upload Approved Annual Ticket Screenshot</span>
              <span v-if="props.evidenceRequired" class="text-error">*</span>
              <VTooltip v-else location="top" max-width="260">
                <template #activator="{ props: tooltipProps }">
                  <VIcon v-bind="tooltipProps" icon="ri-information-line" size="14" class="text-medium-emphasis" />
                </template>
                <span>Optional — keep the current evidence if not replaced.</span>
              </VTooltip>
            </div>
            <div class="d-flex align-center w-100" style="background:#fff;border:1px solid #bdbdbd;border-radius:8px;padding:8px 10px;min-height:48px">
              <input type="file" accept=".jpg,.jpeg,.png" class="w-100" style="font-size:13px;border:0;background:transparent;outline:none" @change="setEvidenceFile" />
            </div>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VCard variant="outlined">
      <VCardTitle class="text-subtitle-1 font-weight-bold">Submission Declaration</VCardTitle>
      <VDivider />
      <VCardText>
        <VSheet class="border rounded px-3 py-1 mb-3"><VCheckbox v-model="confirmInfo" density="compact" hide-details label="I confirm that the Annual Ticket information is correct and ready to enter the approval process." /></VSheet>
        <VAlert v-if="hasDifferentRoute" type="warning" variant="tonal" density="compact" :icon="false">
          <VCheckbox v-model="confirmSettle" density="compact" hide-details class="py-0">
            <template #label><span class="text-body-2">I will settle the employee "EXCESS" / "REIMBURSEMENT" through the Travel Expense Report <strong>within 7 calendar days after the latest Departure or Return Date.</strong></span></template>
          </VCheckbox>
        </VAlert>
      </VCardText>
    </VCard>
  </VCardText>

  <VDivider />

  <VCardActions class="px-6 py-4 ga-2">
    <VSpacer />
    <VBtn variant="flat" color="primary" :disabled="!canSubmit || props.submitting" :loading="props.submitting" @click="onSubmit">{{ props.submitLabel }}</VBtn>
  </VCardActions>
</template>