<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: Boolean,
  group: { type: Object, default: null },
  loading: Boolean,
  memberOptions: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue', 'close'])
const group = computed(() => props.group || {})
const members = computed(() => group.value.members || [])
const statusColor = computed(() => ({ open: 'warning', closed: 'error', draft: 'success' }[group.value.status] || 'grey'))
const createdOrders = computed(() => members.value.filter(member => travelOrder(member)).length)
const progress = computed(() => members.value.length ? Math.round((createdOrders.value / members.value.length) * 100) : 0)
const departmentById = computed(() => Object.fromEntries((group.value.departments || []).filter(item => item && typeof item === 'object').map(item => [item.id, item.name])))
const userDepartmentById = computed(() => Object.fromEntries(props.memberOptions.map(user => [String(user.id), user.department?.name || user.department_name || ''])))
const participantDepartments = computed(() => {
  const fromGroup = (group.value.departments || []).map(department => departmentName(department)).filter(name => name && name !== '-')
  const fromMembers = members.value.map(member => memberDepartment(member)).filter(name => name && name !== '-')
  return [...new Set(fromGroup.length ? fromGroup : fromMembers)]
})

function regionColor(region) {
  const value = String(region || '').trim().toLowerCase()
  if (value.includes('non singapore') || value.includes('overseas')) return 'purple'
  if (value.includes('singapore')) return 'success'
  if (value.includes('domestic') || value === 'indonesia') return 'info'
  return 'secondary'
}

function close() {
  emit('close')
  emit('update:modelValue', false)
}

function valueOrDash(value) {
  return value === null || value === undefined || value === '' ? '-' : value
}

function formatDate(value) {
  if (!value) return '-'
  const date = new Date(value)
  return Number.isNaN(date.getTime())
    ? String(value)
    : date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'Asia/Jakarta' })
}

function memberUser(member) {
  return member.user || member.employee || member
}

function memberName(member) {
  const user = memberUser(member)
  return user.name || member.employee_name || '-'
}

function memberUserId(member) {
  return member.user_id ?? member.user?.id ?? member.employee?.id ?? member.employee_id ?? member.id
}

function initials(name) {
  return String(name || '?').split(' ').filter(Boolean).slice(0, 2).map(part => part[0]).join('').toUpperCase()
}

function memberDepartment(member) {
  const user = memberUser(member)
  const direct = user.department?.name || user.department_name || member.department?.name || member.department_name || member.departmentName
  return direct || userDepartmentById.value[String(memberUserId(member))] || departmentById.value[user.department_id ?? member.department_id] || '-'
}

function travelOrder(member) {
  const order = member.travel_order || member.travelOrder || member.travel_orders?.[0] || null

  return order && order.status !== 'draft' ? order : null
}

function departmentName(department) {
  return typeof department === 'object' ? department?.name || '-' : department
}

function travelOrderNumber(member) {
  const order = travelOrder(member)
  return order?.order_number || order?.travel_order_number || ''
}

function travelOrderStatus(member) {
  const order = travelOrder(member)

  if (!order) return ''

  if (order.status === 'awaiting_approval') {
    const pending = order.approvals?.find(step => step.status === 'pending')

    return pending?.role_name ? `Awaiting Approval - ${pending.role_name}` : 'Awaiting Approval'
  }

  const status = order.status_label || order.display_status || order.status || 'Created'
  const labels = {
    awaiting_approval_manager: 'Awaiting Approval - Manager',
    awaiting_approval_gmo: 'Awaiting Approval - GMO',
    awaiting_gmo_processing: 'Awaiting GMO Processing',
    awaiting_gmo_booking_document_issuance: 'Awaiting GMO Booking & Document Issuance',
    processed_by_gmo: 'Processed by GMO',
    revision_required: 'Revision Required',
    cancelled: 'Cancelled',
    draft: 'Draft',
  }

  return labels[status] || status.replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase())
}
</script>

<template>
  <VDialog :model-value="modelValue" width="1100" scrollable @update:model-value="emit('update:modelValue', $event)">
    <VCard rounded="lg">
      <VCardItem class="px-4 py-3">
        <VCardTitle class="pa-0 text-subtitle-1 font-weight-bold">Group Trip Details</VCardTitle>
        <template #append>
          <VBtn icon="ri-close-line" variant="text" size="small" @click="close" />
        </template>
      </VCardItem>

      <VDivider />
      <VProgressLinear v-if="loading" indeterminate color="primary" />

      <VCardText v-else class="bg-grey-lighten-5 pa-4">
        <VCard rounded="lg" border elevation="0" class="mb-4">
          <div class="bg-grey-lighten-4 px-4 py-3 d-flex align-center justify-space-between flex-wrap ga-2">
            <div class="text-subtitle-2 font-weight-bold">Trip Information</div>
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
                  <VChip size="small" variant="tonal" :color="regionColor(group.travel_region)">{{ valueOrDash(group.travel_region) }}</VChip>
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
                    <VChip v-for="name in participantDepartments" :key="name" size="small" variant="tonal" color="secondary">{{ name }}</VChip>
                    <span v-if="!participantDepartments.length" class="text-body-2">-</span>
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

          <VTable density="comfortable" class="w-100">
            <colgroup>
              <col style="width: 25%" />
              <col style="width: 25%" />
              <col style="width: 50%" />
            </colgroup>
            <thead>
              <tr>
                <th class="text-no-wrap font-weight-bold text-black">Employee</th>
                <th class="text-no-wrap font-weight-bold text-black">Department</th>
                <th class="text-no-wrap font-weight-bold text-black">Travel Order</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(member, index) in members" :key="member.id || member.user_id || index">
                <td class="text-body-2 font-weight-medium py-3">{{ memberName(member) }}</td>
                <td class="text-body-2 py-3">{{ memberDepartment(member) }}</td>
                <td class="py-3">
                  <template v-if="travelOrder(member)">
                    <VChip size="small" variant="tonal" color="primary">Created</VChip>
                    <div class="text-caption text-medium-emphasis mt-1">
                      <span v-if="travelOrderNumber(member)">{{ travelOrderNumber(member) }} · </span>{{ travelOrderStatus(member) }}
                    </div>
                  </template>
                  <template v-else>
                    <VChip size="small" variant="tonal" color="secondary">Not Created</VChip>
                    <div class="text-caption text-medium-emphasis mt-1">Employee has not created a Travel Order.</div>
                  </template>
                </td>
              </tr>
              <tr v-if="!members.length">
                <td colspan="3" class="text-center text-medium-emphasis py-6">No group members found.</td>
              </tr>
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
