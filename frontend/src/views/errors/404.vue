<template>
    <div class="relative flex min-h-screen min-h-[100dvh] items-center justify-center overflow-hidden bg-gray-400 font-sans text-white">
      <div
        class="absolute inset-0 scale-105 bg-cover bg-center blur-[8px]"
        style="background-image: url('/error-bg.jpg')"
      ></div>
  
      <div class="absolute inset-0 bg-gray-500/65"></div>
  
      <div class="relative z-10 w-[90%] max-w-[600px] -translate-y-6 text-center">
        <img
          src="/logo ecog.png"
          alt="Company Logo"
          class="mx-auto mb-7 w-[180px] object-contain"
        />
  
        <div class="mb-3 text-[120px] font-extrabold leading-none">
          404
        </div>
  
        <h1 class="text-[32px] font-bold">
          Page Not Found
        </h1>
  
        <p class="mx-auto mt-4 max-w-[500px] text-base leading-7 text-white/85">
          Sorry, the page you are looking for could not be found or may have been moved.
        </p>
  
        <button
          type="button"
          class="mt-7 rounded-lg bg-white px-7 py-3 text-[15px] font-semibold text-gray-800 transition hover:-translate-y-0.5 hover:bg-gray-100"
          @click="goBack"
        >
          Go Back
        </button>
      </div>
    </div>
  </template>
  
  <script setup>
  const goBack = () => {
    window.history.back()
  }
  </script>