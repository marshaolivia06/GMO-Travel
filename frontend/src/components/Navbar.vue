<template>
  <header
    class="flex h-[72px] shrink-0 items-center justify-between border-b border-slate-200 bg-white px-[30px] shadow-[0_2px_8px_rgba(30,79,138,0.03)]"
  >
    <div class="text-2xl font-semibold tracking-[-0.02em] text-[#172033]">
      {{ pageTitle }}
    </div>

    <!-- PROFILE -->
    <div class="relative flex items-center justify-center">

      <!-- AVATAR -->
      <button
        type="button"
        class="flex h-[38px] w-[38px] items-center justify-center rounded-full bg-gradient-to-br from-[#1E4F8A] to-[#2D659C] text-[13px] font-bold text-white shadow-[0_3px_10px_rgba(30,79,138,0.18)] transition duration-200 hover:-translate-y-px hover:shadow-[0_5px_14px_rgba(30,79,138,0.25)]"
        @click="toggleProfileMenu"
      >
        {{ userInitial }}
      </button>

      <!-- LOGOUT MENU -->
      <div
        v-if="showProfileMenu"
        class="absolute right-0 top-[calc(100%+10px)] z-[1000] min-w-[130px] rounded-lg border border-slate-200 bg-white p-1.5 shadow-[0_10px_25px_rgba(15,23,42,0.12)]"
      >
        <button
          type="button"
          class="w-full rounded-md bg-transparent px-3 py-[9px] text-left text-[13px] font-semibold text-red-600 hover:bg-red-50"
          @click="openLogoutConfirmation"
        >
          Logout
        </button>
      </div>

    </div>
  </header>

  <!-- LOGOUT CONFIRMATION -->
  <div
    v-if="showLogoutModal"
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/45 p-5 backdrop-blur-[2px]"
    @click.self="cancelLogout"
  >
    <div
      class="w-[400px] max-w-full rounded-xl bg-white p-[30px_25px] text-center shadow-[0_20px_50px_rgba(15,23,42,0.20)]"
    >
      <div
        class="mx-auto mb-[18px] flex h-[54px] w-[54px] items-center justify-center rounded-full bg-[#eaf2f9] text-[27px] font-bold text-[#1E4F8A]"
      >
        ?
      </div>

      <p class="m-0 text-base font-medium leading-[1.6] text-[#172033]">
        Are you sure you want to logout?
      </p>

      <div class="mt-[25px] flex items-center justify-center gap-2.5">

        <button
          type="button"
          class="min-w-[100px] rounded-[7px] border border-slate-300 bg-white px-4 py-2.5 text-[13px] font-semibold text-slate-600 transition duration-200 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="loggingOut"
          @click="cancelLogout"
        >
          Cancel
        </button>

        <button
          type="button"
          class="min-w-[100px] rounded-[7px] bg-red-600 px-4 py-2.5 text-[13px] font-semibold text-white transition duration-200 hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="loggingOut"
          @click="confirmLogout"
        >
          {{ loggingOut ? 'Logging out...' : 'Yes i want to logout' }}
        </button>

      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToastStore } from '../stores/toast'

const route = useRoute()
const router = useRouter()
const toast = useToastStore()

const pageTitle = computed(() => {
  return route.meta.title || 'Dashboard'
})

const currentUser = computed(() => {
  try {
    const storedUser = localStorage.getItem('user')

    if (!storedUser) return null

    return JSON.parse(storedUser)
  } catch (error) {
    console.error('Gagal membaca data user:', error)
    return null
  }
})

const userInitial = computed(() => {
  const name = currentUser.value?.name

  if (!name) return 'U'

  return name.charAt(0).toUpperCase()
})

const showProfileMenu = ref(false)

const toggleProfileMenu = () => {
  showProfileMenu.value = !showProfileMenu.value
}

const showLogoutModal = ref(false)
const loggingOut = ref(false)

const openLogoutConfirmation = () => {
  showProfileMenu.value = false
  showLogoutModal.value = true
}

const cancelLogout = () => {
  if (loggingOut.value) return

  showLogoutModal.value = false
}

const confirmLogout = async () => {
  if (loggingOut.value) return

  loggingOut.value = true

  try {
    localStorage.removeItem('token')
    localStorage.removeItem('user')

    showLogoutModal.value = false

    await router.push('/login')
  } catch (error) {
    console.error('Gagal logout:', error)
    toast.error('Gagal logout.')
  } finally {
    loggingOut.value = false
  }
}
</script>
