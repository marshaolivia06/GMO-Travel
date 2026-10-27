<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)
const isPasswordVisible = ref(false)

const themes = [
  { name: 'light', icon: 'ri-sun-line' },
  { name: 'dark', icon: 'ri-moon-line' },
]

async function handleSubmit() {
  error.value = ''
  loading.value = true

  try {
    await auth.login(email.value, password.value)
    router.push('/')
  } catch (e) {
    error.value = e.response?.data?.message
      || (e.response ? e.message : 'Unable to connect to the server.')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="auth-theme-switcher">
    <ThemeSwitcher :themes="themes" />
  </div>

  <VRow
    no-gutters
    class="auth-wrapper bg-background"
  >
    <VCol
      cols="12"
      class="d-flex align-center justify-center"
    >
      <VCard
        elevation="8"
        rounded="lg"
        width="100%"
        :max-width="460"
        class="pa-8 auth-card"
      >
        <VCardText class="text-center">
          <div class="auth-brand mb-5">
            <img
              src="/img/logo-eco.png"
              alt="Ecogreen"
              class="auth-brand-icon"
            >
            <span class="auth-brand-divider" />
            <span class="auth-brand-title">GMO - Travel</span>
          </div>
          <h4 class="text-h4 mb-1">
            Welcome to GMO Travel
          </h4>
        </VCardText>

        <VCardText class="px-8">
          <VAlert
            v-if="error"
            type="error"
            variant="tonal"
            density="compact"
            class="mb-4"
            closable
            @click:close="error = ''"
          >
            {{ error }}
          </VAlert>

          <VForm @submit.prevent="handleSubmit">
            <VRow>
              <VCol cols="12">
                <VTextField
                  v-model="email"
                  type="email"
                  label="Email"
                  placeholder="Enter your email"
                  autocomplete="username"
                  density="comfortable"
                  autofocus
                  required
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="password"
                  label="Password"
                  placeholder="············"
                  autocomplete="current-password"
                  density="comfortable"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                  required
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                />

                <VBtn
                  block
                  size="large"
                  type="submit"
                  class="mt-6"
                  :loading="loading"
                  :disabled="loading"
                >
                  Sign In
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core-scss/template/pages/page-auth";

.auth-theme-switcher {
  position: absolute;
  z-index: 2;
  inset-block-start: 1.5rem;
  inset-inline-end: 1.5rem;
}

.auth-card {
  border-block-start: 4px solid;
  border-image: linear-gradient(135deg, #0b2340 0%, #14304f 45%, #0f9488 100%) 1;
}

.auth-brand {
  display: flex;
  align-items: center;
  justify-content: center;
  column-gap: 1rem;
}

.auth-brand-icon {
  inline-size: 56px;
  block-size: 56px;
  object-fit: contain;
}

.auth-brand-divider {
  inline-size: 1px;
  block-size: 40px;
  background: rgba(var(--v-border-color), var(--v-border-opacity));
}

.auth-brand-title {
  font-size: 1.75rem;
  font-weight: 700;
  white-space: nowrap;
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
}
</style>
