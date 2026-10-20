<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useConfirm } from '../../../../composables/useConfirm'

const emit = defineEmits(['cancel', 'submit'])
const props = defineProps({ loading: { type: Boolean, default: false }, initialData: { type: Object, default: null }, memberOptions: { type: Array, default: () => [] } })
const { confirm } = useConfirm()
const regionOptions = ['Domestic', 'Singapore', 'Overseas']
const requiredFields = ['tripName', 'travelRegion', 'travelFrom', 'travelTo', 'departureDate', 'returnDate']
const selectedMemberIds = ref([])
const generalError = ref('')
const errors = reactive({ tripName: '', travelRegion: '', travelFrom: '', travelTo: '', departureDate: '', returnDate: '', members: '' })
const form = reactive({ tripName: '', travelRegion: '', travelFrom: '', travelTo: '', departureDate: '', returnDate: '', purpose: '' })

const memberItems = computed(() => props.memberOptions.map(user => ({ ...user, id: user.id, name: user.name || user.full_name || '', departmentName: user.department?.name || user.department_name || 'No department' })))
const selectedMembers = computed(() => memberItems.value.filter(user => selectedMemberIds.value.some(id => String(id) === String(user.id))))
const canCreate = computed(() => requiredFields.every(key => !!String(form[key]).trim()) && form.returnDate >= form.departureDate && selectedMemberIds.value.length >= 2 && !props.loading)

function normalizeDate(value) {
    if (!value) return ''
    const text = String(value)
    if (/^\d{4}-\d{2}-\d{2}$/.test(text)) return text
    const date = new Date(text)
    if (isNaN(date.getTime())) return ''
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
}

function fillInitialData(data) {
    if (!data) return
    Object.assign(form, { tripName: data.trip_name || data.tripName || '', travelRegion: data.travel_region || data.travelRegion || '', travelFrom: data.travel_from || data.travelFrom || '', travelTo: data.travel_to || data.travelTo || '', departureDate: normalizeDate(data.departure_date || data.departureDate), returnDate: normalizeDate(data.return_date || data.returnDate || data.arrival_date || data.arrivalDate), purpose: data.purpose || '' })
    selectedMemberIds.value = (data.members || data.group_members || []).map(member => member.user_id ?? member.user?.id ?? member.id).filter(id => id != null)
}

function filterMember(_, query, item) {
    const search = String(query || '').trim().toLowerCase()
    return !search || String(item.raw.name || '').toLowerCase().includes(search) || String(item.raw.departmentName || '').toLowerCase().includes(search)
}

function updateMembers(ids) { selectedMemberIds.value = ids || []; errors.members = '' }
function removeMember(id) { selectedMemberIds.value = selectedMemberIds.value.filter(value => String(value) !== String(id)); errors.members = '' }
function clearErrors() { Object.keys(errors).forEach(key => { errors[key] = '' }); generalError.value = '' }

function validate() {
    clearErrors()
    let valid = true
    for (const key of requiredFields) if (!String(form[key]).trim()) { errors[key] = 'This field is required.'; valid = false }
    if (form.departureDate && form.returnDate && form.returnDate < form.departureDate) { errors.returnDate = 'Return date cannot be before departure date.'; valid = false }
    if (selectedMemberIds.value.length < 2) { errors.members = 'Select at least two group members.'; valid = false }
    return valid
}

function getPayload() {
    return { trip_name: form.tripName.trim(), travel_region: form.travelRegion, travel_from: form.travelFrom.trim(), travel_to: form.travelTo.trim(), departure_date: form.departureDate, return_date: form.returnDate, purpose: form.purpose.trim(), members: selectedMemberIds.value, request_type: 'business_trip', trip_type: 'group', status: 'open' }
}

async function createGroup() {
    if (props.loading || !validate()) return
    const confirmed = await confirm({ title: 'Create Group Trip?', text: 'Are you sure you want to create this Group Trip?', color: 'primary' })
    if (confirmed) emit('submit', getPayload())
}

function cancel() { if (!props.loading) emit('cancel') }

watch(() => props.initialData, data => { if (data) fillInitialData(data) }, { immediate: true })
</script>

<template>
  <VCard rounded="lg" elevation="1">
    <VCardTitle class="d-flex align-center justify-space-between pa-5">
      <div class="d-flex align-center ga-3">
        <VBtn icon="ri-arrow-left-line" variant="tonal" color="primary" size="small" :disabled="loading" @click="cancel" />
        <div class="text-h6 font-weight-bold">{{ initialData ? 'Edit Group Trip' : 'Create Group Trip' }}</div>
      </div>
      <VBtn icon="ri-close-line" variant="text" :disabled="loading" @click="cancel" />
    </VCardTitle>
    <VDivider />
    <VForm @submit.prevent="createGroup">
      <VCardText class="pa-6">
        <div class="text-subtitle-2 font-weight-bold mb-4">Group Trip Information</div>
        <VAlert v-if="generalError" type="error" variant="tonal" density="compact" class="mb-4">{{ generalError }}</VAlert>
        <VRow>
          <VCol cols="12" md="6"><div class="text-caption mb-1">1. Trip Name</div><VTextField v-model="form.tripName" placeholder="Enter trip name" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.tripName ? [errors.tripName] : []" /></VCol>
          <VCol cols="12" md="6"><div class="text-caption mb-1">2. Travel Region</div><VSelect v-model="form.travelRegion" :items="regionOptions" placeholder="Select travel region" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.travelRegion ? [errors.travelRegion] : []" /></VCol>
          <VCol cols="12" md="6"><div class="text-caption mb-1">3. Travel From</div><VTextField v-model="form.travelFrom" placeholder="Enter travel from" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.travelFrom ? [errors.travelFrom] : []" /></VCol>
          <VCol cols="12" md="6"><div class="text-caption mb-1">4. Travel To</div><VTextField v-model="form.travelTo" placeholder="Enter travel to" variant="outlined" density="comfortable" hide-details="auto" :disabled="loading" :error-messages="errors.travelTo ? [errors.travelTo] : []" /></VCol>
          <VCol cols="12" md="6"><div class="text-caption mb-1">5. Departure Date</div><VTextField v-model="form.departureDate" type="date" variant="outlined" density="comfortable" prepend-inner-icon="mdi-calendar" hide-details="auto" :disabled="loading" :error-messages="errors.departureDate ? [errors.departureDate] : []" /></VCol>
          <VCol cols="12" md="6"><div class="text-caption mb-1">6. Return Date</div><VTextField v-model="form.returnDate" type="date" variant="outlined" density="comfortable" prepend-inner-icon="mdi-calendar" hide-details="auto" :disabled="loading" :error-messages="errors.returnDate ? [errors.returnDate] : []" /></VCol>
          <VCol cols="12"><div class="text-caption mb-1">7. Purpose (Optional)</div><VTextarea v-model="form.purpose" placeholder="Enter purpose" variant="outlined" density="comfortable" rows="1" auto-grow hide-details="auto" :disabled="loading" /></VCol>
          <VCol cols="12">
            <div class="text-caption mb-1">8. Group Member</div>
            <VAutocomplete :model-value="selectedMemberIds" :items="memberItems" item-title="name" item-value="id" multiple chips closable-chips :custom-filter="filterMember" placeholder="Search by employee name or department" variant="outlined" density="comfortable" hide-details="auto" no-data-text="No employee found" :disabled="loading" :error-messages="errors.members ? [errors.members] : []" @update:model-value="updateMembers">
              <template #item="{ props: itemProps, item }"><VListItem v-bind="itemProps" :title="item.raw.name" :subtitle="item.raw.departmentName" /></template>
            </VAutocomplete>
          </VCol>
          <VCol v-if="selectedMembers.length" cols="12">
            <div class="text-caption font-weight-bold mb-2">Selected Members ({{ selectedMembers.length }})</div>
            <VRow dense>
              <VCol v-for="member in selectedMembers" :key="member.id" cols="12" sm="6" md="4">
                <VCard rounded="lg" elevation="0" border class="bg-grey-lighten-4 pa-3 d-flex align-center ga-3">
                  <div class="flex-grow-1" style="min-width: 0"><div class="text-body-2 font-weight-bold text-truncate">{{ member.name }}</div><div class="text-caption text-medium-emphasis text-truncate">{{ member.departmentName }}</div></div>
                  <VBtn icon="mdi-close" size="x-small" variant="text" :disabled="loading" @click="removeMember(member.id)" />
                </VCard>
              </VCol>
            </VRow>
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />
      <VCardActions class="pa-4 justify-end">
        <VBtn variant="text" :disabled="loading" @click="cancel">Cancel</VBtn>
        <VBtn type="submit" color="primary" variant="flat" :loading="loading" :disabled="!canCreate">{{ initialData ? 'Save Changes' : 'Create Group' }}</VBtn>
      </VCardActions>
    </VForm>
  </VCard>
</template>
