<template>
  <div class="bg-blue-grey-lighten-5 fill-height d-flex align-center justify-center pa-5">
    <v-card width="350" rounded="xl" elevation="4" color="grey-lighten-5" class="overflow-hidden">
      <v-progress-linear model-value="100" height="4" color="primary" />

      <v-card-text class="pa-5">
        <div class="text-center mb-4">
          <v-img :src="logo" width="56" height="56" class="mx-auto mb-1" contain />
          <div class="text-h6 font-weight-bold text-grey-darken-4">GMO Travel</div>
          <div class="text-caption text-uppercase text-blue-grey">General Management Office</div>
        </div>

        <v-card rounded="lg" elevation="1" color="white" class="pa-4">
          <div class="text-subtitle-1 font-weight-medium text-grey-darken-4">Welcome back</div>
          <div class="text-caption text-grey mb-3">Sign in to continue to GMO Travel</div>

          <v-alert v-if="errorMessage" type="error" variant="tonal" density="compact" rounded="lg" class="mb-3">{{ errorMessage }}</v-alert>

          <v-form autocomplete="off" @submit.prevent="handleLogin">
            <div class="text-caption mb-1">Email</div>
            <v-text-field v-model="email" placeholder="Enter your username" type="email" autocomplete="off" variant="outlined" density="compact" color="primary" rounded="lg" class="mb-2" hide-details single-line required />

            <div class="text-caption mb-1">Password</div>
            <v-text-field v-model="password" placeholder="Enter your password" :type="showPassword ? 'text' : 'password'" :append-inner-icon="showPassword ? 'ri-eye-off-line' : 'ri-eye-line'" autocomplete="current-password" variant="outlined" density="compact" color="primary" rounded="lg" hide-details single-line required @click:append-inner="showPassword = !showPassword" />

            <v-btn type="submit" color="primary" block rounded="lg" class="mt-4" :loading="loading" :disabled="loading">{{ loading ? 'Signing in...' : 'Sign in' }}</v-btn>
          </v-form>
        </v-card>

        <div class="text-center text-caption text-grey-lighten-1 mt-3">© 2026 GMO Travel</div>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import logo from '../assets/logo.png'

const auth = useAuthStore()
const router = useRouter()
const email = ref('')
const password = ref('')
const errorMessage = ref('')
const loading = ref(false)
const showPassword = ref(false)

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
    if (!result.success) errorMessage.value = result.message || 'Invalid email or password.'
    else router.push({ name: 'user-management' })
  } catch (error) {
    errorMessage.value = 'An error occurred while signing in.'
  } finally {
    loading.value = false
  }
}
</script>