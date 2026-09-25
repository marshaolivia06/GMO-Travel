<script setup>
import { computed, reactive, ref } from 'vue'

const emit = defineEmits(['cancel', 'submit'])

const loading = ref(false)
const generalError = ref('')
const step = ref(1)

const form = reactive({
  purpose: '', departureDate: '', arrivalDate: '', department: '', members: [],
  memberDepartments: {}, employee: '', travelFrom: '', travelTo: '',
  departureTime: '', returnDate: '', returnTime: '', remarks: '',
  travelRegion: '', ferryTicketType: 'Ferry', ferryArrangement: 'Direct Payment',
  accommodationArrangement: 'Direct Payment', mealAllowance: '', pocketMoney: '',
})

const errors = reactive({
  purpose: '', departureDate: '', arrivalDate: '', department: '', members: '',
  memberDepartments: '', employee: '', travelFrom: '', travelTo: '',
  departureTime: '', returnDate: '', returnTime: '', remarks: '', travelRegion: '',
  mealAllowance: '', pocketMoney: '', ferryTicketType: '', ferryArrangement: '',
  accommodationArrangement: '',
})

const ldapEmployees = [
  { id: 1, name: 'Marsha Olivia', employeeId: 'EMP001', department: 'Information Technology' },
  { id: 2, name: 'Sabrina Rosa Salsyabilla', employeeId: 'EMP002', department: 'Information Technology' },
  { id: 3, name: 'Andi Pratama', employeeId: 'EMP003', department: 'Engineering' },
  { id: 4, name: 'Budi Santoso', employeeId: 'EMP004', department: 'Maintenance' },
  { id: 5, name: 'Rina Amelia', employeeId: 'EMP005', department: 'HSE' },
  { id: 6, name: 'Dedi Kurniawan', employeeId: 'EMP006', department: 'HSE' },
  { id: 7, name: 'Fajar Ramadhan', employeeId: 'EMP007', department: 'Production' },
]

const departments = ['Information Technology', 'Engineering', 'Maintenance', 'HSE', 'Production', 'Finance']
const travelRegions = ['Indonesia', 'Singapore', 'Non Singapore']
const ferryTicketTypes = ['Ferry', 'Airplane']
const ferryArrangements = ['Direct Payment', 'Booked by Company']
const accommodationArrangements = ['Direct Payment', 'Booked by Company', 'Not Required']
const nonSingaporeCurrencies = ['USD', 'EUR', 'MYR', 'JPY']

const mealCurrency = ref('')
const pocketCurrency = ref('')

const isOverseas = computed(() => ['Singapore', 'Non Singapore'].includes(form.travelRegion))
const isFerryReimbursable = computed(() => form.ferryArrangement === 'Direct Payment')
const isAccommodationReimbursable = computed(() => form.accommodationArrangement === 'Direct Payment')

function getMember(id) {
  return ldapEmployees.find(member => member.id === id)
}

function clearErrors() {
  Object.keys(errors).forEach(key => errors[key] = '')
  generalError.value = ''
}

function validateCreateGroup() {
  clearErrors()
  let valid = true

  if (!form.purpose.trim()) {
    errors.purpose = 'Purpose is required.'
    valid = false
  }

  if (!form.departureDate) {
    errors.departureDate = 'Departure date is required.'
    valid = false
  }

  if (!form.arrivalDate) {
    errors.arrivalDate = 'Arrival date is required.'
    valid = false
  }

  if (!form.department) {
    errors.department = 'Department is required.'
    valid = false
  }

  if (!form.members.length) {
    errors.members = 'Select at least one group member.'
    valid = false
  }

  return valid
}

function validateForm() {
  clearErrors()
  let valid = true

  form.members.forEach(id => {
    if (!form.memberDepartments[id]) valid = false
  })

  if (!valid) errors.memberDepartments = 'Please select department for every member.'

  const required = [
    'travelFrom', 'travelTo', 'departureDate', 'departureTime', 'returnDate',
    'returnTime', 'purpose', 'remarks', 'travelRegion', 'ferryTicketType',
    'ferryArrangement', 'accommodationArrangement',
  ]

  required.forEach(field => {
    if (!String(form[field] || '').trim()) {
      errors[field] = 'This field is required.'
      valid = false
    }
  })

  if (isOverseas.value) {
    if (!String(form.mealAllowance || '').trim()) {
      errors.mealAllowance = 'This field is required.'
      valid = false
    }

    if (!String(form.pocketMoney || '').trim()) {
      errors.pocketMoney = 'This field is required.'
      valid = false
    }
  }

  return valid
}

function continueToForm() {
  if (!validateCreateGroup()) return

  form.members.forEach(id => {
    if (!form.memberDepartments[id]) form.memberDepartments[id] = getMember(id)?.department || ''
  })

  form.employee = form.members.map(id => getMember(id)?.name).filter(Boolean).join(', ')
  step.value = 2
  clearErrors()
}

function backToCreateGroup() {
  if (loading.value) return
  step.value = 1
  clearErrors()
}

function selectRegion(region) {
  form.travelRegion = region

  if (region === 'Indonesia') {
    mealCurrency.value = ''
    pocketCurrency.value = ''
    form.mealAllowance = ''
    form.pocketMoney = ''
  }

  if (region === 'Singapore') {
    mealCurrency.value = 'SGD'
    pocketCurrency.value = 'SGD'
  }

  if (region === 'Non Singapore') {
    mealCurrency.value = 'USD'
    pocketCurrency.value = 'USD'
  }

  errors.travelRegion = ''
}

function cancel() {
  if (!loading.value) emit('cancel')
}

async function submitForm() {
  if (loading.value || !validateForm()) return

  loading.value = true

  try {
    emit('submit', {
      groupTrip: {
        purpose: form.purpose,
        departureDate: form.departureDate,
        arrivalDate: form.arrivalDate,
        department: form.department,
        members: form.members,
        memberDepartments: form.memberDepartments,
      },
      travelOrder: {
        employee: form.employee,
        travelFrom: form.travelFrom,
        travelTo: form.travelTo,
        departureDate: form.departureDate,
        departureTime: form.departureTime,
        returnDate: form.returnDate,
        returnTime: form.returnTime,
        purpose: form.purpose,
        remarks: form.remarks,
        travelRegion: form.travelRegion,
        ferryTicketType: form.ferryTicketType,
        ferryArrangement: form.ferryArrangement,
        accommodationArrangement: form.accommodationArrangement,
        mealAllowance: form.mealAllowance,
        pocketMoney: form.pocketMoney,
        mealCurrency: mealCurrency.value,
        pocketCurrency: pocketCurrency.value,
      },
    })
  } catch (err) {
    generalError.value = err?.response?.data?.message || err?.message || 'Failed to submit group trip.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <div class="d-flex align-start justify-space-between mb-6">
      <div>
        <div class="text-primary text-body-2 font-weight-bold mb-1">BUSINESS TRIP - GROUP</div>
        <div class="text-h5 font-weight-bold mb-1">{{ step === 1 ? 'Create Group Trip' : 'Travel Order Form' }}</div>
        <div class="text-body-2 text-medium-emphasis">{{ step === 1 ? 'Create a new Group Trip and select the employees who will participate.' : 'Complete the Travel Order for the selected Group Trip.' }}</div>
      </div>
      <VChip variant="tonal" color="primary" size="small">{{ step === 1 ? 'Group Trip Setup' : 'Group Request' }}</VChip>
    </div>

    <VCard rounded="lg" elevation="2" color="primary" class="mb-6">
      <VCardText class="d-flex align-center ga-4">
        <VAvatar color="white" size="42"><span class="text-primary font-weight-bold">{{ step === 1 ? '01' : '02' }}</span></VAvatar>
        <div>
          <div class="text-caption text-white font-weight-bold">{{ step === 1 ? 'CREATE GROUP TRIP' : 'GROUP TRAVEL ORDER' }}</div>
          <div class="text-subtitle-1 text-white font-weight-bold">{{ step === 1 ? 'Group Trip Information' : 'Complete Group Travel Order' }}</div>
          <div class="text-body-2 text-white">{{ step === 1 ? 'Fill the main information and select group members.' : 'Review the Group Trip and complete the travel information.' }}</div>
        </div>
      </VCardText>
    </VCard>

    <VAlert v-if="generalError" type="error" variant="tonal" class="mb-6">{{ generalError }}</VAlert>

    <template v-if="step === 1">
      <div class="text-subtitle-1 font-weight-bold mb-1">Create Group Trip</div>
      <div class="text-body-2 text-medium-emphasis mb-6">Fill the basic information for the Group Trip.</div>

      <VRow>
        <VCol cols="12">
          <VTextField v-model="form.purpose" label="Purpose *" placeholder="Contoh: Annual IT Conference" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.purpose ? [errors.purpose] : []" />
        </VCol>

        <VCol cols="12" md="6">
          <VTextField v-model="form.departureDate" label="Departure Date *" type="date" variant="outlined" density="comfortable" prepend-inner-icon="mdi-calendar" hide-details="auto" :disabled="loading" :error-messages="errors.departureDate ? [errors.departureDate] : []" />
        </VCol>

        <VCol cols="12" md="6">
          <VTextField v-model="form.arrivalDate" label="Arrival Date *" type="date" variant="outlined" density="comfortable" prepend-inner-icon="mdi-calendar" hide-details="auto" :disabled="loading" :error-messages="errors.arrivalDate ? [errors.arrivalDate] : []" />
        </VCol>

        <VCol cols="12">
          <VSelect v-model="form.department" label="Department *" :items="departments" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.department ? [errors.department] : []" />
        </VCol>

        <VCol cols="12">
          <VAutocomplete v-model="form.members" label="Group Members *" :items="ldapEmployees" item-title="name" item-value="id" multiple chips closable-chips variant="outlined" density="comfortable" placeholder="Search employee from LDAP" hide-details="auto" :disabled="loading" :error-messages="errors.members ? [errors.members] : []">
            <template #item="{ props, item }">
              <VListItem v-bind="props">
                <template #prepend>
                  <VAvatar color="primary" variant="tonal" size="36">{{ item.raw.name.charAt(0).toUpperCase() }}</VAvatar>
                </template>
                <VListItemTitle>{{ item.raw.name }}</VListItemTitle>
                <VListItemSubtitle>{{ item.raw.employeeId }} · {{ item.raw.department }}</VListItemSubtitle>
              </VListItem>
            </template>
          </VAutocomplete>
        </VCol>
      </VRow>

      <VCard v-if="form.members.length" color="primary" variant="tonal" rounded="lg" class="mt-6">
        <VCardText>
          <div class="text-subtitle-2 font-weight-bold mb-3">Selected Members ({{ form.members.length }})</div>
          <div class="d-flex flex-wrap ga-2">
            <VChip v-for="memberId in form.members" :key="memberId" color="primary" variant="flat">{{ getMember(memberId)?.name }}</VChip>
          </div>
        </VCardText>
      </VCard>
    </template>

    <template v-else>
      <VCard rounded="lg" color="primary" variant="tonal" class="mb-6">
        <VCardItem>
          <VCardTitle class="text-subtitle-1 font-weight-bold">Saved Group Trip</VCardTitle>
          <VCardSubtitle>Information from the first form.</VCardSubtitle>
        </VCardItem>

        <VCardText>
          <VRow>
            <VCol cols="12" md="6">
              <div class="text-caption text-medium-emphasis">Purpose</div>
              <div class="text-body-1 font-weight-bold">{{ form.purpose }}</div>
            </VCol>
            <VCol cols="12" md="6">
              <div class="text-caption text-medium-emphasis">Departure Date</div>
              <div class="text-body-1 font-weight-bold">{{ form.departureDate }}</div>
            </VCol>
            <VCol cols="12" md="6">
              <div class="text-caption text-medium-emphasis">Arrival Date</div>
              <div class="text-body-1 font-weight-bold">{{ form.arrivalDate }}</div>
            </VCol>
            <VCol cols="12" md="6">
              <div class="text-caption text-medium-emphasis">Department</div>
              <div class="text-body-1 font-weight-bold">{{ form.department }}</div>
            </VCol>
            <VCol cols="12">
              <div class="text-caption text-medium-emphasis mb-2">Group Members</div>
              <div class="d-flex flex-wrap ga-2">
                <VChip v-for="memberId in form.members" :key="memberId" color="primary" variant="flat">{{ getMember(memberId)?.name }}</VChip>
              </div>
            </VCol>
          </VRow>
        </VCardText>
      </VCard>

      <div class="text-subtitle-1 font-weight-bold mb-1">Mandatory Information</div>
      <div class="text-body-2 text-medium-emphasis mb-6">Select the department for each employee who will participate.</div>

      <VRow>
        <VCol v-for="memberId in form.members" :key="memberId" cols="12" md="6">
          <VCard rounded="lg" elevation="1">
            <VCardText class="d-flex align-center ga-3">
              <VAvatar color="primary" variant="tonal" size="42">{{ getMember(memberId)?.name.charAt(0).toUpperCase() }}</VAvatar>
              <div class="flex-grow-1">
                <div class="text-body-2 font-weight-bold">{{ getMember(memberId)?.name }}</div>
                <div class="text-caption text-medium-emphasis">{{ getMember(memberId)?.employeeId }}</div>
              </div>
              <VSelect v-model="form.memberDepartments[memberId]" label="Department *" :items="departments" variant="outlined" density="comfortable" hide-details="auto" style="max-width: 240px" :disabled="loading" />
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <div v-if="errors.memberDepartments" class="text-error text-caption mt-2">{{ errors.memberDepartments }}</div>

      <VCard rounded="lg" elevation="1" class="mt-6">
        <VCardItem>
          <VCardTitle class="text-subtitle-1 font-weight-bold">Travel Order Information</VCardTitle>
          <VCardSubtitle>Employee and Group Trip information are inherited from the previous form.</VCardSubtitle>
        </VCardItem>

        <VDivider />

        <VCardText class="pa-6">
          <div class="text-subtitle-2 font-weight-bold mb-4">Employee Information</div>

          <VRow>
            <VCol cols="12" md="6">
              <VTextField v-model="form.employee" label="Employee / Group *" variant="outlined" density="comfortable" hide-details="auto" readonly :disabled="loading" :error-messages="errors.employee ? [errors.employee] : []" />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField v-model="form.department" label="Department *" variant="outlined" density="comfortable" hide-details="auto" readonly :disabled="loading" :error-messages="errors.department ? [errors.department] : []" />
            </VCol>
          </VRow>

          <div class="text-subtitle-2 font-weight-bold mb-4 mt-4">Trip Information</div>

          <VRow>
            <VCol cols="12" md="6">
              <VTextField v-model="form.travelFrom" label="Travel From *" placeholder="Contoh: Batam" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.travelFrom ? [errors.travelFrom] : []" />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField v-model="form.travelTo" label="Travel To *" placeholder="Contoh: Jakarta" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.travelTo ? [errors.travelTo] : []" />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField v-model="form.departureDate" label="Departure Date *" type="date" variant="outlined" density="comfortable" prepend-inner-icon="mdi-calendar" hide-details="auto" :disabled="loading" :error-messages="errors.departureDate ? [errors.departureDate] : []" />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField v-model="form.departureTime" label="Departure Time *" type="time" variant="outlined" density="comfortable" prepend-inner-icon="mdi-clock-outline" hide-details="auto" :disabled="loading" :error-messages="errors.departureTime ? [errors.departureTime] : []" />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField v-model="form.returnDate" label="Return Date *" type="date" variant="outlined" density="comfortable" prepend-inner-icon="mdi-calendar" hide-details="auto" :disabled="loading" :error-messages="errors.returnDate ? [errors.returnDate] : []" />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField v-model="form.returnTime" label="Return Time *" type="time" variant="outlined" density="comfortable" prepend-inner-icon="mdi-clock-outline" hide-details="auto" :disabled="loading" :error-messages="errors.returnTime ? [errors.returnTime] : []" />
            </VCol>
            <VCol cols="12" md="6">
              <VTextarea v-model="form.purpose" label="Travelling Purpose *" placeholder="Jelaskan tujuan perjalanan dinas ini" variant="outlined" density="comfortable" rows="3" hide-details="auto" :disabled="loading" :error-messages="errors.purpose ? [errors.purpose] : []" />
            </VCol>
            <VCol cols="12" md="6">
              <VTextarea v-model="form.remarks" label="Remarks *" placeholder="Tambahkan keterangan" variant="outlined" density="comfortable" rows="3" hide-details="auto" :disabled="loading" :error-messages="errors.remarks ? [errors.remarks] : []" />
            </VCol>
          </VRow>

          <div class="text-subtitle-2 font-weight-bold mb-4 mt-4">Travel Region</div>

          <VRow>
            <VCol v-for="region in travelRegions" :key="region" cols="12" md="4">
              <VCard :variant="form.travelRegion === region ? 'tonal' : 'flat'" :color="form.travelRegion === region ? 'primary' : undefined" rounded="lg" hover class="h-100" @click="selectRegion(region)">
                <VCardText>
                  <div class="text-subtitle-2 font-weight-bold" :class="form.travelRegion === region ? 'text-primary' : ''">{{ region }}</div>
                  <div class="text-body-2 mt-1" :class="form.travelRegion === region ? 'text-primary' : 'text-medium-emphasis'">{{ region === 'Indonesia' ? 'Domestic travel' : 'International travel' }}</div>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>

          <div v-if="errors.travelRegion" class="text-error text-caption mt-2">{{ errors.travelRegion }}</div>

          <div class="text-subtitle-2 font-weight-bold mb-4 mt-6">Ticket, Accommodation & Travel Advance</div>

          <VRow>
            <VCol cols="12" md="4">
              <VCard rounded="lg" elevation="2" color="grey-lighten-5" class="h-100">
                <VCardItem>
                  <template #append>
                    <VChip variant="tonal" :color="isFerryReimbursable ? 'success' : 'error'" size="small">{{ isFerryReimbursable ? 'Reimbursable' : 'Not Reimbursable' }}</VChip>
                  </template>
                  <VCardTitle class="text-subtitle-2 font-weight-bold">Ferry Ticket</VCardTitle>
                  <VCardSubtitle>Select transport type, arranger, and payer.</VCardSubtitle>
                </VCardItem>

                <VCardText>
                  <VRow>
                    <VCol cols="6">
                      <VSelect v-model="form.ferryTicketType" label="Ticket Type *" :items="ferryTicketTypes" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.ferryTicketType ? [errors.ferryTicketType] : []" />
                    </VCol>
                    <VCol cols="6">
                      <VSelect v-model="form.ferryArrangement" label="Arrangement *" :items="ferryArrangements" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.ferryArrangement ? [errors.ferryArrangement] : []" />
                    </VCol>
                  </VRow>
                  <div class="text-caption text-medium-emphasis mt-3">Employee pays first and claims eligible expense through TER.</div>
                </VCardText>
              </VCard>
            </VCol>

            <VCol cols="12" md="4">
              <VCard rounded="lg" elevation="2" color="grey-lighten-5" class="h-100">
                <VCardItem>
                  <template #append>
                    <VChip variant="tonal" :color="isAccommodationReimbursable ? 'success' : 'error'" size="small">{{ isAccommodationReimbursable ? 'Reimbursable' : 'Not Reimbursable' }}</VChip>
                  </template>
                  <VCardTitle class="text-subtitle-2 font-weight-bold">Accommodation</VCardTitle>
                  <VCardSubtitle>Not Required remains available when applicable.</VCardSubtitle>
                </VCardItem>

                <VCardText>
                  <VSelect v-model="form.accommodationArrangement" label="Arrangement *" :items="accommodationArrangements" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.accommodationArrangement ? [errors.accommodationArrangement] : []" />
                  <div class="text-caption text-medium-emphasis mt-3">Employee pays first and claims eligible expense through TER.</div>
                </VCardText>
              </VCard>
            </VCol>

            <VCol cols="12" md="4">
              <VCard rounded="lg" elevation="2" color="grey-lighten-5" class="h-100">
                <VCardItem>
                  <VCardTitle class="text-subtitle-2 font-weight-bold">Travel Advance</VCardTitle>
                  <VCardSubtitle>Optional and determined by Travel Region.</VCardSubtitle>
                </VCardItem>

                <VCardText>
                  <VAlert v-if="form.travelRegion === 'Indonesia'" type="info" variant="tonal" density="compact">
                    Travel Advance is not applicable for Indonesia domestic travel.
                  </VAlert>

                  <template v-else>
                    <VAlert type="info" variant="tonal" density="compact" class="mb-4">
                      <template v-if="form.travelRegion === 'Singapore'">
                        <strong>Singapore policy:</strong> Travel Advance is requested in SGD.
                      </template>
                      <template v-else>
                        <strong>Non-Singapore policy:</strong> Select USD, EUR, MYR, or JPY for each advance component.
                      </template>
                    </VAlert>

                    <div class="text-body-2 font-weight-medium mb-2">Meal Allowance</div>

                    <VRow>
                      <VCol cols="4">
                        <VSelect v-model="mealCurrency" :items="form.travelRegion === 'Singapore' ? ['SGD'] : nonSingaporeCurrencies" variant="outlined" density="comfortable" hide-details :disabled="form.travelRegion === 'Singapore'" />
                      </VCol>
                      <VCol cols="8">
                        <VTextField v-model="form.mealAllowance" type="number" placeholder="0" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.mealAllowance ? [errors.mealAllowance] : []" />
                      </VCol>
                    </VRow>

                    <div class="text-body-2 font-weight-medium mb-2 mt-4">Pocket Money</div>

                    <VRow>
                      <VCol cols="4">
                        <VSelect v-model="pocketCurrency" :items="form.travelRegion === 'Singapore' ? ['SGD'] : nonSingaporeCurrencies" variant="outlined" density="comfortable" hide-details :disabled="form.travelRegion === 'Singapore'" />
                      </VCol>
                      <VCol cols="8">
                        <VTextField v-model="form.pocketMoney" type="number" placeholder="0" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.pocketMoney ? [errors.pocketMoney] : []" />
                      </VCol>
                    </VRow>
                  </template>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>
        </VCardText>
      </VCard>
    </template>

    <VDivider class="mt-6" />

    <VCardActions class="pa-4">
      <VBtn variant="text" :disabled="loading" @click="step === 1 ? cancel() : backToCreateGroup()">{{ step === 1 ? 'Cancel' : 'Cancel' }}</VBtn>
      <VSpacer />
      <VBtn v-if="step === 1" color="primary" variant="flat" :loading="loading" @click="continueToForm">Continue</VBtn>
      <VBtn v-else color="primary" variant="flat" :loading="loading" @click="submitForm">Submit</VBtn>
    </VCardActions>
  </div>
</template>
