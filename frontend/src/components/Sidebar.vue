<template>
  <aside
    class="relative h-screen flex-shrink-0 flex flex-col overflow-visible bg-white text-[#172033] border-r border-slate-200 shadow-[3px_0_15px_rgba(30,79,138,0.04)] transition-[width] duration-250 ease-in-out"
    :class="isCollapsed ? 'w-[70px]' : 'w-[240px]'"
  >
    <div
      class="h-[78px] flex-shrink-0 flex items-center justify-center bg-white border-b border-[#eef2f6]"
      :class="isCollapsed ? 'p-0' : 'px-5 py-3'"
    >
      <img
        :src="currentLogo"
        alt="JMO Travel"
        class="object-contain transition duration-250 ease-in-out"
        :class="isCollapsed ? 'w-[42px] h-[42px]' : 'w-[175px] max-h-[52px]'"
      />
    </div>

    <div
      class="relative flex-1 min-h-0 flex flex-col text-white bg-gradient-to-b from-[#1E4F8A] via-[#2D659C] to-[#245985]"
    >
      <button
        type="button"
        class="absolute top-6 -right-[15px] w-[30px] h-[30px] flex items-center justify-center border border-[#dbe3ec] rounded-full bg-white text-[#1E4F8A] cursor-pointer shadow-[0_3px_10px_rgba(15,23,42,0.1)] z-10 hover:bg-[#DCEBF7] hover:text-[#173F70]"
        @click="$emit('toggle-collapse')"
        :aria-label="isCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
      >
        <ChevronRight v-if="isCollapsed" :size="18" />
        <ChevronLeft v-else :size="18" />
      </button>

      <nav
        class="flex flex-col gap-[5px]"
        :class="isCollapsed ? 'px-2.5 py-[22px]' : 'px-3 py-[22px]'"
      >
        <router-link
          v-for="item in menuItems"
          :key="item.to"
          :to="item.to"
          v-slot="{ isActive, href, navigate }"
        >
          <a
            :href="href"
            @click="navigate"
            class="w-full min-h-[44px] flex items-center rounded-lg no-underline font-medium text-sm transition duration-200"
            :class="[
              isCollapsed ? 'justify-center p-2.5' : 'px-[13px] py-2.5',
              isActive
                ? 'bg-white text-[#1E4F8A] font-semibold shadow-[0_3px_10px_rgba(15,23,42,0.08)]'
                : 'bg-transparent text-white/[.88] hover:bg-white/[.12] hover:text-white hover:translate-x-[2px]'
            ]"
          >
            <span
              class="flex items-center min-w-0"
              :class="isCollapsed ? 'w-full justify-center gap-0' : 'gap-3'"
            >
              <component
                :is="item.icon"
                class="flex-shrink-0 block"
                :size="19"
              />

              <span
                v-show="!isCollapsed"
                class="whitespace-nowrap leading-tight"
              >
                {{ item.label }}
              </span>
            </span>
          </a>
        </router-link>
      </nav>

      <div
        class="mt-auto flex-shrink-0 flex items-center gap-2.5 border-t border-white/[.16]"
        :class="isCollapsed ? 'justify-center px-2.5 py-[15px]' : 'p-[15px]'"
      >
        <div
          class="w-[38px] h-[38px] flex-shrink-0 flex items-center justify-center rounded-full bg-white/[.16] border border-white/[.14] text-white text-[13px] font-bold"
        >
          {{ userInitial }}
        </div>

        <div
          v-show="!isCollapsed"
          class="min-w-0 flex flex-col items-start gap-0.5"
        >
          <strong
            class="max-w-[145px] overflow-hidden text-ellipsis whitespace-nowrap text-white text-[13px] leading-tight"
          >
            {{ currentUser.name }}
          </strong>

          <span class="text-white/[.68] text-[11px] leading-tight">
            {{ currentUser.role }}
          </span>
        </div>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'

import {
  ChevronLeft,
  ChevronRight,
  Users,
  LayoutDashboard,
  CalendarDays,
  Plane,
  FileText
} from 'lucide-vue-next'

import logoFull from '../assets/logo_ecogreen.png'
import logoIcon from '../assets/logo.png'

defineProps({
  isCollapsed: {
    type: Boolean,
    default: false
  }
})

defineEmits(['toggle-collapse'])

const isCollapsed = defineModel('isCollapsed', {
  type: Boolean,
  default: false
})

const menuItems = [
  {
    to: '/user-management',
    label: 'User Management',
    icon: Users
  },
  {
    to: '/dashboard',
    label: 'Dashboard',
    icon: LayoutDashboard
  },
  {
    to: '/annual-leave',
    label: 'Annual Leave',
    icon: CalendarDays
  },
  {
    to: '/travel-order',
    label: 'Travel Order',
    icon: Plane
  },
  {
    to: '/ter',
    label: 'TER',
    icon: FileText
  }
]

const currentUser = ref({
  name: 'User',
  role: 'User'
})

const userInitial = computed(() => {
  const name = currentUser.value.name

  if (!name) {
    return 'U'
  }

  return name.trim().charAt(0).toUpperCase()
})

const currentLogo = computed(() => {
  return isCollapsed.value ? logoIcon : logoFull
})

const formatRole = role => {
  if (!role) {
    return 'User'
  }

  const normalized = String(role).trim().toLowerCase()

  if (
    normalized === 'admin' ||
    normalized === 'administrator'
  ) {
    return 'Administrator'
  }

  if (normalized === 'user') {
    return 'User'
  }

  if (normalized === 'manager') {
    return 'Manager'
  }

  if (
    normalized === 'dept head' ||
    normalized === 'department head'
  ) {
    return 'Dept Head'
  }

  return String(role)
    .trim()
    .replace(/^./, char => char.toUpperCase())
}

const loadCurrentUser = () => {
  try {
    const storedUser = localStorage.getItem('user')

    if (!storedUser) {
      return
    }

    const parsed = JSON.parse(storedUser)
    const userData =
      parsed?.user ||
      parsed?.data ||
      parsed

    const name =
      userData?.name ||
      userData?.full_name ||
      userData?.fullname ||
      userData?.username ||
      parsed?.name ||
      parsed?.username ||
      'User'

    let role = null

    if (userData?.role) {
      role =
        typeof userData.role === 'object'
          ? userData.role?.name
          : userData.role
    } else if (
      Array.isArray(userData?.roles) &&
      userData.roles.length > 0
    ) {
      role = userData.roles[0]
    }

    if (
      !role &&
      Array.isArray(userData?.roles) &&
      userData.roles.length > 0
    ) {
      role =
        userData.roles[0]?.name ||
        userData.roles[0]?.role
    }

    if (!role && parsed?.role) {
      role =
        typeof parsed.role === 'object'
          ? parsed.role?.name
          : parsed.role
    }

    if (
      !role &&
      Array.isArray(parsed?.roles) &&
      parsed.roles.length > 0
    ) {
      role =
        parsed.roles[0]?.name ||
        parsed.roles[0]?.role
    }

    currentUser.value = {
      name: String(name).trim(),
      role: formatRole(role)
    }
  } catch (error) {
    console.error('Gagal membaca data user:', error)
  }
}

onMounted(() => {
  loadCurrentUser()
})
</script>