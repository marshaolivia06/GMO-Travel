<template>
  <div
    class="flex items-center justify-center"
    :class="containerClass"
  >
    <div class="flex flex-col items-center">
      <div
        class="relative flex h-[50px] w-[50px] items-center justify-center"
      >
        <!-- Spinner -->
        <div
          class="absolute inset-0 rounded-full border-[3px] border-[#DCEBF7] border-t-[#1E4F8A] animate-spin"
        ></div>

        <!-- Logo -->
        <img
          src="/logo.png"
          alt="Loading"
          class="h-[24px] w-[24px] object-contain animate-pulse"
        />
      </div>

      <p class="mt-3 text-[12px] font-medium text-[#1E4F8A]">
        {{ text }}
      </p>
    </div>
  </div>
</template>

<script setup>
defineProps({
  containerClass: {
    type: String,
    default: 'min-h-[220px]',
  },

  text: {
    type: String,
    default: 'Loading...',
  },
})
</script>
