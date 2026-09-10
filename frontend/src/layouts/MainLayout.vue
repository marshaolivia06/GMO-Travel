<template>
  <div class="min-h-screen w-full bg-[#f5f7fb]">

    <div
      class="fixed inset-y-0 left-0 z-50 transition-[width] duration-250 ease-in-out"
      :class="isCollapsed ? 'w-[70px]' : 'w-[240px]'"
    >
      <Sidebar
        :is-collapsed="isCollapsed"
        @toggle-collapse="isCollapsed = !isCollapsed"
      />
    </div>

    <main
      class="min-h-screen transition-[margin] duration-250 ease-in-out"
      :class="isCollapsed ? 'ml-[70px]' : 'ml-[240px]'"
    >
    <div
  class="fixed right-0 top-0 z-[60] transition-[left] duration-250 ease-in-out"
  :class="isCollapsed ? 'left-[70px]' : 'left-[240px]'"
>
  <Navbar />
</div>

      <div class="h-screen overflow-y-auto pt-[70px]">
        <div class="p-[25px]">
          <router-view />
        </div>

        <Footer />
      </div>
    </main>

  </div>
</template>

<script setup>
import { ref } from 'vue'

import Sidebar from '../components/Sidebar.vue'
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'

const isCollapsed = ref(false)
</script>

<style>
html,
body,
#app {
  width: 100%;
  min-height: 100%;
  margin: 0;
  padding: 0;
}

body {
  margin: 0;
  overflow: hidden;
}
</style>
