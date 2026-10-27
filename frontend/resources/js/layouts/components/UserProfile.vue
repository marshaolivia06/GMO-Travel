<script setup>
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

async function handleLogout() {
  await auth.logout()
}
</script>

<template>
  <div class="d-flex align-center ga-2">
    <div class="text-right d-none d-sm-block">
      <div class="text-sm font-weight-semibold text-body-2">
        {{ auth.user?.name ?? '—' }}
      </div>
      <VChip
        color="primary"
        size="x-small"
        variant="tonal"
        class="mt-1"
      >
        {{ auth.roles[0] ?? 'User' }}
      </VChip>
    </div>

    <VAvatar
      class="cursor-pointer"
      color="primary"
      variant="tonal"
    >
      <span class="text-sm font-semibold">
        {{ auth.user?.name?.charAt(0)?.toUpperCase() ?? 'U' }}
      </span>

      <VMenu
        activator="parent"
        width="230"
        location="bottom end"
        offset="14px"
      >
        <VList>
          <VListItem>
            <VListItemTitle class="font-weight-semibold">
              {{ auth.user?.name ?? '—' }}
            </VListItemTitle>
            <VListItemSubtitle>{{ auth.user?.email }}</VListItemSubtitle>
          </VListItem>

          <VDivider class="my-2" />

          <VListItem
            link
            @click="handleLogout"
          >
            <template #prepend>
              <VIcon
                class="me-2"
                icon="ri-logout-box-line"
                size="22"
              />
            </template>
            <VListItemTitle>Logout</VListItemTitle>
          </VListItem>
        </VList>
      </VMenu>
    </VAvatar>
  </div>
</template>
