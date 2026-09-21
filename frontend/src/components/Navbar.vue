<template>
  <header
  class="flex h-[78px] w-full shrink-0 items-center justify-between border-b border-[#DCE3EA] bg-[#1E4F8A] px-[30px] text-white shadow-[0_2px_8px_rgba(30,79,138,0.12)]"
>
    <div
      class="text-2xl font-semibold tracking-[-0.02em] text-white"
    >
      {{ pageTitle }}
    </div>

    <div class="relative flex items-center justify-center">
      <button
        type="button"
        class="flex h-[38px] w-[38px] items-center justify-center rounded-full bg-white text-[13px] font-bold text-[#1E4F8A] shadow-[0_3px_10px_rgba(15,23,42,0.15)] transition duration-200 hover:-translate-y-px hover:bg-[#f1f6fb] hover:shadow-[0_5px_14px_rgba(15,23,42,0.20)]"
        @click="toggleProfileMenu"
      >
        {{ userInitial }}
      </button>

      <div
        v-if="showProfileMenu"
        class="absolute right-0 top-[calc(100%+10px)] z-[1000] w-[140px] rounded-xl border border-slate-200 bg-white p-1.5 shadow-[0_12px_30px_rgba(15,23,42,0.14)]"
      >
        <button
          type="button"
          class="w-full rounded-lg px-3 py-2.5 text-left text-[13px] font-semibold text-slate-600 transition duration-200 hover:bg-red-50 hover:text-red-600"
          @click="openLogoutConfirmation"
        >
          Logout
        </button>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import swal from '../plugins/swal'

const route = useRoute()
const router = useRouter()

const pageTitle = computed(() => route.meta.title || 'Dashboard')

const currentUser = computed(() => {
  try {
    const user = localStorage.getItem('user')
    return user ? JSON.parse(user) : null
  } catch {
    return null
  }
})

const userInitial = computed(() => {
  return currentUser.value?.name?.charAt(0).toUpperCase() || 'U'
})

const showProfileMenu = ref(false)

const toggleProfileMenu = () => {
  showProfileMenu.value = !showProfileMenu.value
}

const openLogoutConfirmation = async () => {
  showProfileMenu.value = false

  const result = await swal.confirm(
    'Are you sure you want to logout?',
    'delete'
  )

  if (!result.isConfirmed) return

  localStorage.removeItem('token')
  localStorage.removeItem('user')

  await router.push('/login')
}
</script>
