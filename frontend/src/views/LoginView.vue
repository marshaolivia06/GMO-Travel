<template>
  <div class="bg-blue-grey-lighten-5 fill-height d-flex align-center justify-center pa-5">
    <v-card
      width="350"
      rounded="xl"
      elevation="4"
      color="grey-lighten-5"
      class="overflow-hidden"
    >
      <div class="bg-primary" style="height: 4px" />

      <v-card-text class="pa-6">
        <div class="text-center mb-7">
          <v-img
            :src="logo"
            width="62"
            height="62"
            class="mx-auto mb-2"
            contain
          />

          <div class="text-h5 font-weight-bold text-grey-darken-4">
            GMO Travel
          </div>

          <div class="text-caption text-uppercase text-blue-grey mt-1">
            General Management Office
          </div>
        </div>

        <v-card
          rounded="lg"
          elevation="1"
          color="white"
          class="pa-1"
        >
          <v-card-text class="pa-4">
            <div class="mb-5">
              <div class="text-h6 font-weight-medium text-grey-darken-4">
                Welcome back
              </div>

              <div class="text-caption text-grey mt-1">
                Sign in to continue to GMO Travel
              </div>
            </div>

            <v-alert
              v-if="errorMessage"
              type="error"
              variant="tonal"
              density="compact"
              rounded="lg"
              class="mb-4"
            >
              {{ errorMessage }}
            </v-alert>

            <v-form
              autocomplete="off"
              @submit.prevent="handleLogin"
            >
              <v-text-field
                v-model="email"
                label="Email"
                placeholder="Enter your username"
                type="email"
                autocomplete="off"
                variant="outlined"
                density="comfortable"
                color="primary"
                rounded="lg"
                class="mb-2"
                hide-details
                required
              />

              <v-text-field
                v-model="password"
                label="Password"
                placeholder="Enter your password"
                type="password"
                autocomplete="current-password"
                variant="outlined"
                density="comfortable"
                color="primary"
                rounded="lg"
                class="mb-1"
                hide-details
                required
              />

              <v-checkbox
                label="Remember me"
                color="primary"
                density="compact"
                hide-details
                class="mb-3"
              />

              <v-btn
                type="submit"
                color="primary"
                block
                size="large"
                rounded="lg"
                :loading="loading"
                :disabled="loading"
              >
                {{ loading ? 'Signing in...' : 'Sign in' }}
              </v-btn>
            </v-form>
          </v-card-text>
        </v-card>

        <div class="text-center text-caption text-grey-lighten-1 mt-5">
          © 2026 GMO Travel
        </div>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import logo from '../assets/logo.png'

const auth = useAuthStore()

const email = ref('')
const password = ref('')
const errorMessage = ref('')
const loading = ref(false)

onMounted(() => {
  email.value = ''
  password.value = ''
  errorMessage.value = ''
})

const handleLogin = async () => {
  if (loading.value) return

  errorMessage.value = ''
  loading.value = true

  try {
    const result = await auth.login(email.value, password.value)

    if (!result.success) {
      errorMessage.value =
        result.message || 'Invalid email or password.'
    }
  } catch (error) {
    errorMessage.value =
      'An error occurred while signing in.'
  } finally {
    loading.value = false
  }
}
</script>
