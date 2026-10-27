<script setup>
import VerticalNavLayout from '@layouts/components/VerticalNavLayout.vue'
import { useLayoutConfigStore } from '@layouts/stores/config'
import { useRoute } from 'vue-router'
import Footer from '@/layouts/components/Footer.vue'
import NavbarThemeSwitcher from '@/layouts/components/NavbarThemeSwitcher.vue'
import UserProfile from '@/layouts/components/UserProfile.vue'
import { useNavItems } from '@/utils/navItems'

const route = useRoute()
const layoutConfigStore = useLayoutConfigStore()
const navItems = useNavItems()
</script>

<template>
  <VerticalNavLayout :nav-items="navItems">
    <template #navbar="{ toggleVerticalOverlayNavActive }">
      <div class="d-flex h-100 align-center">
        <IconBtn
          class="ms-n3 d-none d-lg-flex"
          @click="layoutConfigStore.isVerticalNavCollapsed = !layoutConfigStore.isVerticalNavCollapsed"
        >
          <VIcon :icon="layoutConfigStore.isVerticalNavCollapsed ? 'ri-menu-unfold-line' : 'ri-menu-fold-line'" />
        </IconBtn>

        <IconBtn
          class="ms-n3 d-lg-none"
          @click="toggleVerticalOverlayNavActive(true)"
        >
          <VIcon icon="bx-menu" />
        </IconBtn>

        <VSpacer />

        <NavbarThemeSwitcher class="me-2" />
        <UserProfile />
      </div>
    </template>

    <template #vertical-nav-header>
      <div class="d-flex align-center w-100">
        <RouterLink
          to="/"
          class="app-logo app-title-wrapper"
        >
          <img
            src="/img/logo-eco.png"
            alt="Ecogreen"
            class="app-logo-icon"
          >
          <div class="app-brand-text">
            <span class="app-brand-divider" />
            <span class="app-brand-title">GMO - Travel</span>
          </div>
        </RouterLink>
      </div>
    </template>

    <RouterView
      v-if="route.path"
      :key="route.path"
    />

    <template #footer>
      <Footer />
    </template>
  </VerticalNavLayout>
</template>

<style lang="scss" scoped>
.app-logo {
  display: flex;
  align-items: center;
  column-gap: 0.75rem;
  overflow: hidden;

  .app-logo-icon {
    inline-size: 34px;
    block-size: 34px;
    flex-shrink: 0;
    object-fit: contain;
  }

  .app-brand-text {
    display: flex;
    align-items: center;
    column-gap: 0.75rem;
    white-space: nowrap;
  }

  .app-brand-divider {
    inline-size: 1px;
    block-size: 26px;
    background: rgba(var(--v-border-color), var(--v-border-opacity));
  }

  .app-brand-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
  }
}
</style>

<style lang="scss">
// Collapsed & not hovered: show the logo only
.layout-vertical-nav-collapsed .layout-vertical-nav:not(:hover) .app-brand-text {
  display: none;
}
</style>
