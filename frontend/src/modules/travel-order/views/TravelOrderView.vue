<script setup>
import { onMounted, ref } from 'vue'
import GroupTripForm from '../components/modal/GroupTripForm.vue'
import IndividualTripForm from '../components/modal/IndividualTripForm.vue'
import { createTravelOrder, getTravelOrders } from '../services/travelOrderService'

const view = ref('root')
const dialog = ref(false)
const dialogType = ref(null)
const reviewDialog = ref(false)
const reviewData = ref(null)
const confirmInfo = ref(false)
const confirmTer = ref(false)
const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')
const submitting = ref(false)
const travelOrders = ref([])
const loadingOrders = ref(false)
const submittedTravelOrder = ref(null)

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

function handleIndividualReview(data) {
  dialog.value = false
  dialogType.value = null
  reviewData.value = data
  confirmInfo.value = false
  confirmTer.value = false
  reviewDialog.value = true
}

function backToEdit() {
  reviewDialog.value = false
  dialogType.value = 'individual'
  dialog.value = true
}

async function handleFinalSubmit() {
  submitting.value = true

  try {
    const response = await createTravelOrder(reviewData.value)
    submittedTravelOrder.value = response.data
    reviewDialog.value = false
    showMessage(response.message || 'Travel order submitted successfully.')
  } catch (error) {
    showMessage(error?.response?.data?.message || 'Failed to submit travel order.', 'error')
  } finally {
    submitting.value = false
  }
}

async function handleIndividualSubmit(payload) {
  submitting.value = true

  try {
    const response = await createTravelOrder(payload)
    submittedTravelOrder.value = response.data
    closeDialog()
    showMessage(response.message || 'Travel order submitted successfully.')
  } catch (error) {
    const errors = error?.response?.data?.errors
    const firstError = errors ? Object.values(errors)[0][0] : null

    showMessage(firstError || error?.response?.data?.message || 'Failed to submit travel order.', 'error')
  } finally {
    submitting.value = false
  }
}

async function handleGroupSubmit(payload) {
  submitting.value = true

  try {
    const response = await createTravelOrder(payload)
    submittedTravelOrder.value = response.data
    closeDialog()
    showMessage(response.message || 'Travel order submitted successfully.')
  } catch (error) {
    const errors = error?.response?.data?.errors
    const firstError = errors ? Object.values(errors)[0][0] : null

    showMessage(firstError || error?.response?.data?.message || 'Failed to submit travel order.', 'error')
  } finally {
    submitting.value = false
  }
}

async function loadTravelOrders() {
  loadingOrders.value = true

  try {
    const response = await getTravelOrders()
    travelOrders.value = response.data ?? response
  } catch (error) {
    showMessage(error?.response?.data?.message || 'Failed to load travel orders.', 'error')
  } finally {
    loadingOrders.value = false
  }
}

onMounted(() => {
  loadTravelOrders()
})
</script>

<template>
  <VCard v-if="view === 'root' && !submittedTravelOrder" rounded="lg" elevation="2">
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

      <div class="text-body-2 text-medium-emphasis mb-6">
        Select a request category to continue.
      </div>

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

  <VCard v-else-if="view === 'business' && !submittedTravelOrder" rounded="lg" elevation="2">
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

      <div class="text-body-2 text-medium-emphasis mb-6">
        Select the appropriate business trip format.
      </div>

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

  <VCard v-else-if="submittedTravelOrder" rounded="lg" elevation="2">
    <VCardText class="pa-6">
      <div class="d-flex align-center ga-3 mb-6">
        <VAvatar color="primary" variant="tonal" size="44">
          <VIcon icon="ri-file-list-3-line" size="24" />
        </VAvatar>

        <div>
          <div class="text-primary text-body-2 font-weight-bold">TRAVEL ORDER</div>
          <div class="text-h5 font-weight-bold">Travel Order Summary</div>
        </div>
      </div>

      <VAlert type="success" variant="tonal" class="mb-6">
        Travel Order has been submitted successfully.
      </VAlert>

      <div class="d-flex align-center justify-space-between flex-wrap ga-4 mb-6">
        <div>
          <div class="text-body-2 text-medium-emphasis">Order Number</div>
          <div class="text-h6 font-weight-bold">
            {{ submittedTravelOrder.order_number || '-' }}
          </div>
        </div>

        <div class="text-end">
          <div class="text-body-2 text-medium-emphasis mb-1">Status</div>
          <VChip color="warning" variant="tonal">
            Awaiting Approval Manager
          </VChip>
        </div>
      </div>
    </VCardText>
  </VCard>

  <VDialog
  v-if="!reviewDialog"
  v-model="dialog"
  max-width="1200"
  scrollable
>
  <VCard rounded="lg" class="overflow-hidden">
    <VCardTitle class="d-flex align-center justify-space-between pa-5">
      <div class="d-flex align-center ga-3">
        <VAvatar color="primary" variant="tonal" size="42">
          <VIcon :icon="dialogType === 'individual' ? 'ri-user-line' : 'ri-group-line'" size="22" />
        </VAvatar>

        <div>
          <div class="text-subtitle-1 font-weight-bold">
            {{ dialogType === 'individual' ? 'Individual Trip' : 'Group Trip' }}
          </div>

          <div class="text-caption text-medium-emphasis">
            {{ dialogType === 'individual'
              ? 'Create an individual business trip request.'
              : 'Create a group business trip request.' }}
          </div>
        </div>
      </div>

      <VBtn icon="ri-close-line" variant="text" @click="closeDialog" />
    </VCardTitle>

    <VDivider />

    <VCardText class="pa-6">
      <IndividualTripForm
        v-if="dialogType === 'individual'"
        :loading="submitting"
        @cancel="closeDialog"
        @review="handleIndividualReview"
      />

      <GroupTripForm
        v-else-if="dialogType === 'group'"
        :loading="submitting"
        @cancel="closeDialog"
        @review="handleGroupSubmit"
      />
    </VCardText>
  </VCard>
</VDialog>

  <!-- REVIEW -->
  <VDialog v-model="reviewDialog" max-width="1200" scrollable>
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between">
        <div>
          <div class="text-caption text-primary font-weight-bold">FINAL REVIEW</div>
          <div class="text-h5 font-weight-bold">Travel Order Summary</div>
        </div>

        <VChip color="warning" variant="outlined">Not Submitted</VChip>
      </VCardTitle>

      <VDivider />

      <VCardText>
        <VCard variant="outlined" class="mb-4">
          <VCardTitle>Request Summary</VCardTitle>
          <VDivider />

          <VCardText>
            <VRow>
              <VCol cols="12" md="3">
                <div class="text-caption">Request Type</div>
                <b>Business Trip - Individual</b>
              </VCol>

              <VCol cols="12" md="3">
                <div class="text-caption">Travel Region</div>
                <b>{{ reviewData?.travelRegion || '-' }}</b>
              </VCol>

              <VCol cols="12" md="3">
                <div class="text-caption">Departure</div>
                <b>{{ reviewData?.departureDate || '-' }} {{ reviewData?.departureTime || '' }}</b>
              </VCol>

              <VCol cols="12" md="3">
                <div class="text-caption">Return</div>
                <b>{{ reviewData?.returnDate || '-' }} {{ reviewData?.returnTime || '' }}</b>
              </VCol>

              <VCol cols="12" md="6">
                <div class="text-caption">Route</div>
                <b>{{ reviewData?.travelFrom || '-' }} → {{ reviewData?.travelTo || '-' }}</b>
              </VCol>

              <VCol cols="12">
                <div class="text-caption">Purpose</div>
                <b>{{ reviewData?.purpose || '-' }}</b>
              </VCol>

              <VCol cols="12">
                <div class="text-caption">Remarks</div>
                <b>{{ reviewData?.remarks || '—' }}</b>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <VCard variant="outlined" class="mb-4">
          <VCardTitle>Ticket, Accommodation & Travel Advance</VCardTitle>
          <VDivider />

          <VCardText>
            <VRow>
              <VCol cols="12" md="4">
                <div class="text-caption">Airplane Ticket</div>
                <b>{{ reviewData?.ferryTicketType || '-' }}</b>
              </VCol>

              <VCol cols="12" md="4">
                <div class="text-caption">Accommodation</div>
                <b>{{ reviewData?.accommodationArrangement || '-' }}</b>
              </VCol>

              <VCol cols="12" md="4">
                <div class="text-caption">Travel Advance</div>
                <b>{{ reviewData?.mealAllowance || reviewData?.pocketMoney ? 'Applicable' : 'Not Applicable' }}</b>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <VCard variant="outlined">
          <VCardTitle>Submission Declaration</VCardTitle>
          <VDivider />

          <VCardText>
            <VCheckbox
              v-model="confirmInfo"
              label="I confirm that the Travel Order information is correct and ready to enter the approval process."
            />

            <VCheckbox
              v-model="confirmTer"
              label="I acknowledge and commit to submit the TER no later than 7 calendar days after the Return Date."
            />
          </VCardText>
        </VCard>
      </VCardText>

      <VDivider />

      <VCardActions class="justify-end">
        <VBtn variant="outlined" @click="backToEdit">Back to Edit</VBtn>

        <VBtn
          color="primary"
          :loading="submitting"
          :disabled="!confirmInfo || !confirmTer"
          @click="handleFinalSubmit"
        >
          Submit Travel Order
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <VSnackbar v-model="snackbar" :color="snackbarColor" :timeout="4000" location="top end">
    {{ snackbarText }}
  </VSnackbar>
</template>