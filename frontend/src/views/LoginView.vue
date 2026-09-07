<template>
  <div
    class="min-h-screen bg-white p-[30px] flex items-center justify-center max-[800px]:p-5"
  >

    <div
      class="w-full max-w-[900px] min-h-[540px] grid grid-cols-1 md:grid-cols-2 overflow-hidden border border-slate-200 rounded-[20px] bg-white shadow-[0_15px_45px_rgba(37,99,168,0.12)]"
    >

      <div
        class="min-h-[280px] md:min-h-0 p-[30px] md:p-10 flex flex-col justify-between text-white bg-gradient-to-br from-[#1E4F8A] via-[#2D659C] to-[#DCEBF7]"
      >

        <div>

          <div
            class="flex items-center justify-center gap-3 translate-y-5"
          >

            <img
              :src="logo"
              alt="GMO Travel"
              class="w-[52px] h-[52px] object-contain"
            />

            <div class="flex flex-col gap-[3px]">

              <span
                class="text-[21px] font-bold text-white"
              >
                GMO Travel
              </span>

              <small
                class="text-[#DCEBF7] text-[11px] font-normal tracking-[0.5px]"
              >
                General Management Office
              </small>

            </div>

          </div>

          <div
            class="max-w-[380px] mt-[60px] md:mt-[90px]"
          >

            <h1
              class="m-0 mb-[15px] text-[28px] md:text-[34px] leading-[1.2] font-normal text-white"
            >
              Every journey begins with a single step.
            </h1>

            <p
              class="m-0 text-[#e0efff] text-sm leading-[1.6]"
            >
              Manage bookings, itineraries, and customers
              in one place with GMO Travel.
            </p>

          </div>

        </div>

        <small class="text-[#d0e5fa]">
          © 2026 GMO Travel
        </small>

      </div>

      <div
        class="p-[25px] px-5 md:p-[35px] flex items-center justify-center bg-slate-50"
      >

        <div
          class="w-full max-w-[350px] p-8 border border-slate-200 rounded-[14px] bg-white shadow-[0_8px_25px_rgba(15,23,42,0.06)]"
        >

          <h2
            class="m-0 mb-[7px] text-[#1E4F8A] text-[26px] font-semibold"
          >
            Welcome back
          </h2>

          <p
            class="m-0 mb-[25px] text-slate-500 text-sm"
          >
            Sign in to access your account
          </p>

          <form
            @submit.prevent="handleLogin"
            autocomplete="off"
          >

            <p
              v-if="errorMessage"
              class="m-0 mb-[15px] px-3 py-2.5 rounded-[7px] text-[#b42318] bg-[#fef3f2] text-[13px]"
            >
              {{ errorMessage }}
            </p>

            <div class="mb-4">

              <label
                for="email"
                class="block mb-1.5 text-[#1E4F8A] text-[13px] font-semibold"
              >
                Email
              </label>

              <input
                id="email"
                v-model="email"
                type="email"
                placeholder="Enter your email"
                autocomplete="off"
                required
                class="w-full h-[46px] px-[13px] border border-slate-300 rounded-[7px] outline-none bg-white text-[#172033] text-sm placeholder:text-[#94a3b8] transition focus:border-[#1E4F8A] focus:shadow-[0_0_0_3px_rgba(30,79,138,0.12)]"
              />

            </div>

            <div class="mb-4">

              <label
                for="password"
                class="block mb-1.5 text-[#1E4F8A] text-[13px] font-semibold"
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
                class="w-full h-[46px] px-[13px] border border-slate-300 rounded-[7px] outline-none bg-white text-[#172033] text-sm placeholder:text-[#94a3b8] transition focus:border-[#1E4F8A] focus:shadow-[0_0_0_3px_rgba(30,79,138,0.12)]"
              />

            </div>

            <label
              class="flex items-center gap-2 my-1 mb-5 text-slate-500 text-[13px] cursor-pointer"
            >

              <input
                type="checkbox"
                class="w-4 h-4 accent-[#1E4F8A]"
              />

              <span>
                Remember me
              </span>

            </label>

            <button
              type="submit"
              :disabled="loading"
              class="w-full h-[46px] border-0 rounded-[7px] bg-[#1E4F8A] text-white text-sm font-semibold cursor-pointer transition hover:bg-[#173F70] disabled:opacity-60 disabled:cursor-not-allowed"
            >
              {{ loading ? 'Signing in...' : 'Sign in' }}
            </button>

          </form>

        </div>

      </div>

    </div>

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

    const result = await auth.login(
      email.value,
      password.value
    )

    if (!result.success) {

      errorMessage.value =
        result.message ||
        'Email atau password salah.'

    }

  } catch (error) {

    console.error(
      'Login error:',
      error
    )

    errorMessage.value =
      'Terjadi kesalahan saat login.'

  } finally {

    loading.value = false

  }

}

</script>