<template>
  <div
    class="min-h-screen flex items-center justify-center p-5 font-['Inter'] bg-[#F1F4F8]"
  >
    <!-- LOGIN CARD -->
    <div
  class="relative w-[350px] rounded-[20px] bg-[#F8FAFC] border border-white shadow-[0_12px_35px_rgba(15,23,42,0.08)] overflow-hidden"
>
      <!-- TOP ACCENT -->
      <div
        class="absolute top-0 left-0 right-0 h-[4px] bg-[#1E4F8A]"
      ></div>

      <div class="px-6 py-6">

        <!-- BRANDING -->
        <div class="flex flex-col items-center text-center mb-7">

          <!-- LOGO -->
          <img
  :src="logo"
  alt="GMO Travel"
  class="w-[62px] h-[62px] object-contain mb-2"
/>

<h1
  class="m-0 text-[24px] font-bold tracking-[-0.5px] text-[#172033]"
>
  GMO Travel
</h1>

          <!-- COMPANY NAME -->
          <p
            class="m-0 mt-1.5 text-[10px] font-medium tracking-[1.5px] uppercase text-[#6B8299]"
          >
            General Management Office
          </p>
        </div>

        <!-- LOGIN FORM -->
        <div
  class="bg-white rounded-[12px] border border-slate-200 p-5 shadow-[0_4px_15px_rgba(15,23,42,0.04)]"
>
          <!-- TITLE -->
          <div class="mb-5">
            <h2
              class="m-0 text-[20px] font-semibold text-[#172033]"
            >
              Welcome back
            </h2>

            <p
              class="m-0 mt-1 text-[12px] text-slate-500"
            >
              Sign in to continue to GMO Travel
            </p>
          </div>

          <form
            @submit.prevent="handleLogin"
            autocomplete="off"
          >
            <!-- ERROR -->
            <p
              v-if="errorMessage"
              class="mb-4 px-3 py-2 rounded-[7px] text-[#b42318] bg-[#fef3f2] border border-[#fecdca] text-[12px]"
            >
              {{ errorMessage }}
            </p>

            <!-- USERNAME -->
            <div class="mb-4">
              <label
                for="email"
                class="block mb-1.5 text-[#294D70] text-[12px] font-semibold"
              >
                Email
              </label>

              <input
                id="email"
                v-model="email"
                type="email"
                placeholder="Enter your username"
                autocomplete="off"
                required
                class="w-full h-[44px] px-3.5 border border-slate-300 rounded-[8px] outline-none bg-[#F8FAFC] text-[#172033] text-[13px] placeholder:text-slate-400 transition focus:bg-white focus:border-[#1E4F8A] focus:ring-2 focus:ring-[#1E4F8A]/10"
              />
            </div>

            <!-- PASSWORD -->
            <div class="mb-3.5">
              <label
                for="password"
                class="block mb-1.5 text-[#294D70] text-[12px] font-semibold"
              >
                Password
              </label>

              <input
                id="password"
                v-model="password"
                type="password"
                placeholder="Enter your password"
                autocomplete="current-password"
                required
                class="w-full h-[44px] px-3.5 border border-slate-300 rounded-[8px] outline-none bg-[#F8FAFC] text-[#172033] text-[13px] placeholder:text-slate-400 transition focus:bg-white focus:border-[#1E4F8A] focus:ring-2 focus:ring-[#1E4F8A]/10"
              />
            </div>

            <!-- REMEMBER -->
            <label
              class="flex items-center gap-2 mb-5 text-slate-500 text-[12px] cursor-pointer select-none"
            >
              <input
                type="checkbox"
                class="w-[15px] h-[15px] accent-[#1E4F8A]"
              />

              <span>Remember me</span>
            </label>

            <!-- BUTTON -->
            <button
              type="submit"
              :disabled="loading"
              class="w-full h-[44px] border-0 rounded-[8px] bg-[#1E4F8A] text-white text-[13px] font-semibold cursor-pointer transition hover:bg-[#173F70] hover:shadow-[0_5px_15px_rgba(30,79,138,0.18)] active:scale-[0.99] disabled:opacity-60 disabled:cursor-not-allowed"
            >
              {{ loading ? 'Signing in...' : 'Sign in' }}
            </button>
          </form>
        </div>

        <!-- FOOTER -->
        <p
          class="m-0 mt-5 text-center text-[10px] text-slate-400"
        >
          © 2026 GMO Travel
        </p>

      </div>
    </div>
    <AppLoading
  v-if="loading"
  container-class="fixed inset-0 z-[9999] bg-[#F5F7FA]/95 backdrop-blur-[2px]"
/>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import logo from '../assets/logo.png'
import AppLoading from '../components/AppLoading.vue'

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
    const result = await auth.login(
      email.value,
      password.value
    )

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