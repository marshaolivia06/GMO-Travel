<script setup>
import { computed } from 'vue'
import { useConfirm } from '../composables/useConfirm'

const { state, respond } = useConfirm()

const dialogIcon = computed(() => {
  const color = state.color || 'primary'

  const icons = {
    error: 'ri-delete-bin-line',
    warning: 'ri-alert-line',
    success: 'ri-check-line',
    info: 'ri-information-line',
    primary: 'ri-question-line',
  }

  return icons[color] || 'ri-question-line'
})

const dialogColor = computed(() => {
  return state.color || 'primary'
})

function onCancel() {
  respond(false)
}

function onConfirm() {
  respond(true)
}
</script>

<template>
  <VDialog
    v-model="state.show"
    max-width="440"
    persistent
  >
    <VCard
      rounded="xl"
      elevation="8"
      class="confirm-dialog"
    >
      <!-- Icon -->
      <div class="d-flex justify-center pt-6">
        <div
          class="confirm-icon"
          :class="`bg-${dialogColor}`"
        >
          <VIcon
            :icon="dialogIcon"
            size="28"
            color="white"
          />
        </div>
      </div>

      <!-- Content -->
      <VCardText class="text-center px-6 pt-5 pb-2">
        <h3 class="text-h5 font-weight-bold mb-2">
          {{ state.title || 'Are you sure?' }}
        </h3>

        <p
          v-if="state.text"
          class="text-body-1 text-medium-emphasis mb-0"
        >
          {{ state.text }}
        </p>

        <p
          v-else
          class="text-body-2 text-medium-emphasis mb-0"
        >
          Please confirm this action before continuing.
        </p>
      </VCardText>

      <!-- Actions -->
      <VCardActions class="pa-6 pt-4">
        <VRow
          no-gutters
          class="ga-3"
        >
          <VCol>
            <VBtn
              block
              size="large"
              variant="tonal"
              color="secondary"
              rounded="lg"
              @click="onCancel"
            >
              Cancel
            </VBtn>
          </VCol>

          <VCol>
            <VBtn
              block
              size="large"
              :color="dialogColor"
              rounded="lg"
              @click="onConfirm"
            >
              <VIcon
                :icon="dialogIcon"
                start
              />

              Confirm
            </VBtn>
          </VCol>
        </VRow>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.confirm-dialog {
  overflow: hidden;
}

.confirm-icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
}
</style>
