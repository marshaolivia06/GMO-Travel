<template>
    <div
      class="relative flex min-h-screen min-h-[100dvh] w-full items-center justify-center overflow-hidden bg-slate-400 font-sans text-white"
    >
      <div
        class="absolute inset-[-20px] bg-cover bg-center blur-[8px]"
        style="background-image: url('/error-bg.jpg')"
      ></div>
  
      <div class="absolute inset-0 bg-slate-500/65"></div>
  
      <div
        class="relative z-10 -translate-y-8 w-[90%] max-w-[600px] text-center"
      >
        <img
          src="/logo ecog.png"
          alt="Company Logo"
          class="mx-auto mb-6 w-[180px] object-contain max-[600px]:w-[140px]"
        />
  
        <div
          class="mb-3 text-[120px] font-extrabold leading-none max-[600px]:text-[90px]"
        >
          403
        </div>
  
        <h1 class="text-[32px] font-bold max-[600px]:text-2xl">
          Access Denied
        </h1>
  
        <p
          class="mx-auto mt-4 max-w-[500px] text-base leading-[1.6] text-white/85 max-[600px]:text-sm"
        >
          Sorry, you don't have permission to access this page.
        </p>
  
        <button
          type="button"
          class="mt-7 rounded-lg bg-white px-7 py-3 text-[15px] font-semibold text-slate-800 transition hover:-translate-y-0.5 hover:bg-slate-100"
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