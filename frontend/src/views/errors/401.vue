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
      class="relative z-10 -translate-y-10 w-[90%] max-w-[600px] text-center"
    >
      <!-- Logo -->
      <img
        src="/logo ecog.png"
        alt="Company Logo"
        class="mx-auto mb-7 w-[180px] object-contain drop-shadow-[0_8px_20px_rgba(0,0,0,.25)] max-[600px]:w-[140px]"
      />

      <!-- Error Code -->
      <div
        class="mb-3 text-[120px] font-extrabold leading-none drop-shadow-[0_8px_25px_rgba(0,0,0,.2)] max-[600px]:text-[90px]"
      >
        401
      </div>

      <h1 class="m-0 text-[32px] font-bold max-[600px]:text-2xl">
        Unauthorized
      </h1>

      <p
        class="mx-auto mt-4 max-w-[500px] text-base leading-[1.6] text-white/85 max-[600px]:text-sm"
      >
        You need to be logged in to access this page.
      </p>

      <!-- Back to Login Button -->
      <button
        type="button"
        class="group relative mt-8 inline-flex items-center justify-center overflow-hidden rounded-xl border-0 bg-white px-8 py-3.5 text-[15px] font-bold text-slate-800 shadow-[0_0_0_0_rgba(255,255,255,.45),0_8px_25px_rgba(0,0,0,.2)] transition-all duration-300 hover:-translate-y-1 hover:bg-white hover:shadow-[0_0_25px_8px_rgba(255,255,255,.25),0_12px_30px_rgba(0,0,0,.25)] active:translate-y-0"
        @click="$router.push('/login')"
      >
        <!-- Shine Effect -->
        <span
          class="absolute inset-y-0 -left-10 w-8 -skew-x-[20deg] bg-white/70 blur-sm transition-all duration-700 group-hover:left-[110%]"
        ></span>

        <span class="relative">
          Back
        </span>
      </button>
    </div>
  </div>
</template>