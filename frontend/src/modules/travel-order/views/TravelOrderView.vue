<script setup>
import { ref } from 'vue'
import GroupTripForm from '../components/modal/GroupTripForm.vue'
import IndividualTripForm from '../components/modal/IndividualTripForm.vue'
import { createTravelOrder } from '../services/travelOrderService'

const view = ref('root')
const dialog = ref(false)
const dialogType = ref(null)
const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')
const submitting = ref(false)

function showMessage(text, color = 'success') {
  snackbarText.value = text
  snackbarColor.value = color
  snackbar.value = true
}

const rootCards = [
  { key: 'annual', title: 'Annual Leave', desc: 'Submit your annual leave request.', icon: 'ri-calendar-check-line' },
  { key: 'business', title: 'Business Trip', desc: 'Submit an individual or group business trip request.', icon: 'ri-briefcase-line' },
]

const businessCards = [
  { key: 'individual', title: 'Individual Trip', desc: 'Business trip for one person.', icon: 'ri-user-line' },
  { key: 'group', title: 'Group Trip', desc: 'Business trip for multiple people.', icon: 'ri-group-line' },
]

function handleClick(item) {
  if (item.key === 'business') view.value = 'business'

  if (item.key === 'individual') {
    dialogType.value = 'individual'
    dialog.value = true
  }

  if (item.key === 'group') {
    dialogType.value = 'group'
    dialog.value = true
  }
}

function closeDialog() {
  dialog.value = false
  dialogType.value = null
}

async function handleIndividualSubmit(payload) {
  submitting.value = true

  try {
    const response = await createTravelOrder(payload)

    showMessage(response.message || 'Travel order submitted successfully.')
    closeDialog()
  } catch (error) {
    const errors = error?.response?.data?.errors
    const firstError = errors ? Object.values(errors)[0][0] : null

    showMessage(
      firstError || error?.response?.data?.message || 'Failed to submit travel order.',
      'error'
    )
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <VCard v-if="view === 'root'" rounded="lg" elevation="2">
    <VCardText class="pa-6">
      <div class="d-flex align-center ga-3 mb-2">
        <VAvatar color="primary" variant="tonal" size="44">
          <VIcon icon="ri-file-list-3-line" size="24" />
        </VAvatar>

        <div>
          <div class="text-primary text-body-2 font-weight-bold">REQUEST</div>
          <div class="text-h5 font-weight-bold">What would you like to request today?</div>
        </div>
      </div>

      <div class="text-body-2 text-medium-emphasis mb-6">Select a request category to continue.</div>

      <VRow>
        <VCol v-for="item in rootCards" :key="item.key" cols="12" md="6">
          <VCard rounded="lg" color="primary" elevation="2" hover class="h-100" @click="handleClick(item)">
            <VCardText class="pa-6">
              <div class="d-flex align-center ga-4">
                <VAvatar color="white" variant="flat" size="56">
                  <VIcon :icon="item.icon" color="primary" size="30" />
                </VAvatar>

                <div class="flex-grow-1">
                  <div class="text-h6 font-weight-bold text-white">{{ item.title }}</div>
                  <div class="text-body-2 text-white">{{ item.desc }}</div>
                </div>

                <VIcon icon="ri-arrow-right-s-line" color="white" size="26" />
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>

  <VCard v-else-if="view === 'business'" rounded="lg" elevation="2">
    <VCardText class="pa-6">
      <div class="d-flex align-center mb-6">
        <VBtn variant="text" icon="ri-arrow-left-line" class="me-3" @click="view = 'root'" />

        <VAvatar color="primary" variant="tonal" size="44" class="me-3">
          <VIcon icon="ri-briefcase-line" size="24" />
        </VAvatar>

        <div>
          <div class="text-primary text-body-2 font-weight-bold">BUSINESS TRIP</div>
          <div class="text-h5 font-weight-bold">How many people are traveling?</div>
        </div>
      </div>

      <div class="text-body-2 text-medium-emphasis mb-6">Select the appropriate business trip format.</div>

      <VRow>
        <VCol v-for="item in businessCards" :key="item.key" cols="12" md="6">
          <VCard rounded="lg" color="primary" elevation="2" hover class="h-100" @click="handleClick(item)">
            <VCardText class="pa-6">
              <div class="d-flex align-center ga-4">
                <VAvatar color="white" size="56">
                  <VIcon :icon="item.icon" color="primary" size="30" />
                </VAvatar>

                <div class="flex-grow-1">
                  <div class="text-h6 font-weight-bold text-white">{{ item.title }}</div>
                  <div class="text-body-2 text-white">{{ item.desc }}</div>
                </div>

                <VIcon icon="ri-arrow-right-s-line" color="white" size="26" />
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>

  <VDialog v-model="dialog" max-width="1200" scrollable>
    <VCard rounded="lg" class="overflow-hidden">
      <VCardTitle class="d-flex align-center justify-space-between pa-5">
        <div class="d-flex align-center ga-3">
          <VAvatar color="primary" variant="tonal" size="42">
            <VIcon :icon="dialogType === 'individual' ? 'ri-user-line' : 'ri-group-line'" size="22" />
          </VAvatar>

          <div>
            <div class="text-subtitle-1 font-weight-bold">{{ dialogType === 'individual' ? 'Individual Trip' : 'Group Trip' }}</div>
            <div class="text-caption text-medium-emphasis">{{ dialogType === 'individual' ? 'Create an individual business trip request.' : 'Create a group business trip request.' }}</div>
          </div>
        </div>

        <VBtn icon="ri-close-line" variant="text" @click="closeDialog" />
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-6">
        <IndividualTripForm v-if="dialogType === 'individual'" :loading="submitting" @cancel="closeDialog" @submit="handleIndividualSubmit" />

        <GroupTripForm v-else-if="dialogType === 'group'" @cancel="closeDialog" @submit="handleGroupSubmit" />
      </VCardText>
    </VCard>
  </VDialog>

  <VSnackbar v-model="snackbar" :color="snackbarColor" :timeout="4000" location="top end">
  {{ snackbarText }}
</VSnackbar>
</template>
