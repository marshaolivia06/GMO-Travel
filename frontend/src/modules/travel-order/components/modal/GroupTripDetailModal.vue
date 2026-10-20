<script setup>
import { computed } from 'vue'

const props = defineProps({ modelValue: Boolean, group: { type: Object, default: null }, loading: Boolean })
const emit = defineEmits(['update:modelValue', 'close'])
const group = computed(() => props.group || {})
const members = computed(() => group.value.members || [])
const statusColor = computed(() => ({ open: 'warning', closed: 'error', draft: 'success' }[group.value.status] || 'grey'))
const createdOrders = computed(() => members.value.filter(member => travelOrder(member)).length)

function close() { emit('close'); emit('update:modelValue', false) }
function valueOrDash(value) { return value === null || value === undefined || value === '' ? '-' : value }
function formatDate(value) {
  if (!value) return '-'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'Asia/Jakarta' })
}
function memberUser(member) { return member.user || member.employee || member }
function memberName(member) { const user = memberUser(member); return user.name || member.employee_name || '-' }
function memberDepartment(member) {
  const user = member.user || member.employee || member
  return user.department?.name
    || user.department_name
    || member.department?.name
    || member.department_name
    || member.departmentName
    || '-'
}
function travelOrder(member) { return member.travel_order || member.travelOrder || member.travel_orders?.[0] || null }
function departmentName(department) { return typeof department === 'object' ? department?.name || '-' : department }
</script>

<template>
  <VDialog :model-value="modelValue" max-width="1100" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <VCardItem class="px-4 py-3">
        <VCardTitle class="pa-0 text-subtitle-1 font-weight-bold">Group Trip Details</VCardTitle>
        <template #append><VBtn icon="ri-close-line" variant="text" size="small" @click="close" /></template>
      </VCardItem>
      <VDivider />
      <VProgressLinear v-if="loading" indeterminate color="primary" />
      <VCardText v-else class="bg-grey-lighten-5 pa-4">
        <VCard rounded="lg" border elevation="0" class="mb-4">
          <div class="bg-grey-lighten-4 px-4 py-3 d-flex align-center justify-space-between flex-wrap ga-2">
            <div class="text-subtitle-2 font-weight-bold">Trip Information</div>
            <div class="text-caption text-medium-emphasis">
  {{ valueOrDash(group.status) }} · {{ group.travel_orders_count || 0 }} Travel Orders
</div>
          </div>
          <VDivider />
          <VCardText class="pa-4">
            <div class="rounded-lg border overflow-hidden">
              <VRow no-gutters>
                <VCol cols="12" sm="6" class="pa-3 border-b border-e-sm">
                  <div class="text-caption text-medium-emphasis mb-1">Trip ID</div>
                  <div class="text-body-2 font-weight-bold">{{ valueOrDash(group.group_number || group.trip_number || group.trip_id || group.id) }}</div>
                </VCol>
                <VCol cols="12" sm="6" class="pa-3 border-b">
                  <div class="text-caption text-medium-emphasis mb-1">Group Trip Name</div>
                  <div class="text-body-2 font-weight-bold">{{ valueOrDash(group.trip_name) }}</div>
                </VCol>
                <VCol cols="12" sm="6" class="pa-3 border-b border-e-sm">
                  <div class="text-caption text-medium-emphasis mb-1">Travel From</div>
                  <div class="text-body-2 font-weight-bold">{{ valueOrDash(group.travel_from) }}</div>
                </VCol>
                <VCol cols="12" sm="6" class="pa-3 border-b">
                  <div class="text-caption text-medium-emphasis mb-1">Travel To</div>
                  <div class="text-body-2 font-weight-bold">{{ valueOrDash(group.travel_to) }}</div>
                </VCol>
                <VCol cols="12" sm="6" class="pa-3 border-b border-e-sm">
                  <div class="text-caption text-medium-emphasis mb-1">Travel Region</div>
                  <VChip size="small" variant="tonal" color="primary">{{ valueOrDash(group.travel_region) }}</VChip>
                </VCol>
                <VCol cols="12" sm="6" class="pa-3 border-b">
                  <div class="text-caption text-medium-emphasis mb-1">Group Trip Status</div>
                  <VChip size="small" variant="tonal" :color="statusColor" class="text-capitalize">{{ valueOrDash(group.status) }}</VChip>
                </VCol>
                <VCol cols="12" sm="6" class="pa-3 border-b border-e-sm">
                  <div class="text-caption text-medium-emphasis mb-1">Departure Date</div>
                  <div class="text-body-2 font-weight-bold">{{ formatDate(group.departure_date) }}</div>
                </VCol>
                <VCol cols="12" sm="6" class="pa-3 border-b">
                  <div class="text-caption text-medium-emphasis mb-1">Return Date</div>
                  <div class="text-body-2 font-weight-bold">{{ formatDate(group.return_date) }}</div>
                </VCol>
                <VCol cols="12" class="pa-3 border-b">
                  <div class="text-caption text-medium-emphasis mb-2">Department Participants</div>
                  <div class="d-flex flex-wrap ga-2">
                    <VChip v-for="department in group.departments || []" :key="typeof department === 'object' ? department.id || department.name : department" size="small" variant="tonal" color="secondary">{{ departmentName(department) }}</VChip>
                    <span v-if="!group.departments?.length" class="text-body-2">-</span>
                  </div>
                </VCol>
                <VCol cols="12" class="pa-3">
                  <div class="text-caption text-medium-emphasis mb-1">Trip Purpose</div>
                  <div class="text-body-2 font-weight-bold" style="white-space: pre-wrap; overflow-wrap: anywhere">{{ valueOrDash(group.purpose) }}</div>
                </VCol>
              </VRow>
            </div>
          </VCardText>
        </VCard>

        <VCard rounded="lg" border elevation="0">
          <div class="bg-grey-lighten-4 px-4 py-3 d-flex align-center justify-space-between flex-wrap ga-2">
            <div class="text-subtitle-2 font-weight-bold">Trip Members</div>
            <div class="text-caption text-medium-emphasis">{{ createdOrders }} of {{ members.length }} members have created a Travel Order</div>
          </div>
          <VDivider />
          <VTable density="comfortable">
            <thead>
              <tr><th class="text-no-wrap">Employee</th><th class="text-no-wrap">Department</th><th class="text-no-wrap">Travel Order</th></tr>
            </thead>
            <tbody>
              <tr v-for="(member, index) in members" :key="member.id || member.user_id || index">
                <td class="text-body-2 font-weight-medium">{{ memberName(member) }}</td>
                <td>{{ memberDepartment(member) }}</td>
                <td>
                  <template v-if="travelOrder(member)">
                    <VChip size="small" variant="tonal" color="success">{{ travelOrder(member).status || 'Created' }}</VChip>
                    <div v-if="travelOrder(member).order_number || travelOrder(member).travel_order_number" class="text-caption text-medium-emphasis mt-1">{{ travelOrder(member).order_number || travelOrder(member).travel_order_number }}</div>
                  </template>
                  <VChip v-else size="small" variant="tonal" color="secondary">Not Created</VChip>
                </td>
              </tr>
              <tr v-if="!members.length"><td colspan="3" class="text-center text-medium-emphasis py-6">No group members found.</td></tr>
            </tbody>
          </VTable>
        </VCard>
      </VCardText>
      <VDivider />
      <VCardActions class="justify-end px-4 py-3">
        <VBtn variant="text" @click="close">Close</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
