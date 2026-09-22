<script setup>
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useConfirm } from '../composables/useConfirm'
import { navItems } from '../utils/navItems'
import logoFull from '../assets/logo_ecogreen.png'
import logoIcon from '../assets/logo.png'

const route = useRoute()
const authStore = useAuthStore()
const { confirm } = useConfirm()

const rail = ref(false)
const pageTitle = computed(() => route.meta.title || 'Dashboard')
const userName = computed(() => authStore.user?.name || 'User')
const userRole = computed(() => authStore.user?.roles?.[0]?.name || 'User')
const userInitial = computed(() => userName.value.charAt(0).toUpperCase())

async function logout() {
  const confirmed = await confirm({
    title: 'Logout',
    text: 'Are you sure you want to logout?',
    color: 'error',
  })

  if (!confirmed) return

  await authStore.logout()
}
</script>

<template>
  <VLayout class="min-h-screen">
    <VNavigationDrawer :rail="rail" permanent class="d-flex flex-column">
  <VListItem class="py-4">
    <VImg
      :src="rail ? logoIcon : logoFull"
      height="44"
      :width="rail ? 44 : undefined"
      contain
    />
  </VListItem>

  <VDivider />

  <VList nav density="comfortable" class="flex-grow-1">
    <VListItem
      v-for="item in navItems"
      :key="item.to"
      :to="{ name: item.to }"
      :prepend-icon="item.icon"
      :title="item.label"
      color="primary"
      rounded="lg"
      class="font-weight-bold"
    />
  </VList>

  <template #append>
    <VDivider />

    <VListItem :title="userName" :subtitle="userRole" class="py-3">
      <template #prepend>
        <VAvatar color="primary" size="40">
          <span class="text-white text-caption font-weight-bold">
            {{ userInitial }}
          </span>
        </VAvatar>
      </template>
    </VListItem>
  </template>
</VNavigationDrawer>
<VAppBar color="primary" height="76">
      <template #prepend>
        <VBtn icon="ri-menu-line" variant="text" color="white" @click="rail = !rail" />
      </template>

      <VAppBarTitle>{{ pageTitle }}</VAppBarTitle>

      <template #append>
        <VMenu>
          <template #activator="{ props }">
            <VBtn icon variant="text" color="white" v-bind="props">
              <VAvatar color="white" size="36">
                <span class="text-primary text-caption font-weight-bold">{{ userInitial }}</span>
              </VAvatar>
            </VBtn>
          </template>

          <VList>
            <VListItem title="Logout" prepend-icon="ri-logout-box-line" @click="logout" />
          </VList>
        </VMenu>
      </template>
    </VAppBar>

    <VMain style="min-width: 0">
      <VContainer fluid>
        <RouterView />
      </VContainer>
    </VMain>

    <VFooter app class="justify-center text-caption text-medium-emphasis">
      © 2026 GMO Travel
    </VFooter>
  </VLayout>
</template>
<style scoped>
:deep(.v-list-item--active) {
  background: rgb(var(--v-theme-primary)) !important;
  color: white !important;
}
</style>