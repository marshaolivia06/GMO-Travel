<template>
    <div
      class="relative flex min-h-screen min-h-[100dvh] w-full items-center justify-center overflow-hidden bg-slate-400 font-sans text-white"
    >
      <div
        class="absolute inset-0 scale-[1.05] bg-[url('/error-bg.jpg')] bg-cover bg-center blur-[8px]"
      />
  
      <div class="absolute inset-0 bg-slate-500/65" />
  
      <div class="relative z-10 w-[90%] max-w-[600px] -translate-y-8 text-center">
        <img
          src="/logo ecog.png"
          alt="Company Logo"
          class="mx-auto mb-6 w-[180px] object-contain"
        />
  
        <div class="mb-3 text-[120px] font-extrabold leading-none max-[600px]:text-[90px]">
          500
        </div>
  
        <h1 class="m-0 text-[32px] font-bold max-[600px]:text-2xl">
          Internal Server Error
        </h1>
  
        <p
          class="mx-auto mt-4 max-w-[500px] text-base leading-[1.6] text-white/85 max-[600px]:text-sm"
        >
          Something went wrong on our server. Please try again later.
        </p>
  
        <button
          type="button"
          class="mt-7 rounded-lg border-0 bg-white px-7 py-3 text-[15px] font-semibold text-slate-800 transition hover:-translate-y-0.5 hover:bg-slate-100"
          @click="$router.back()"
        >
          Go Back
        </button>
      </div>
    </div>
  </template>