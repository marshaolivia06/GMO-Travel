<template>
  <div
    class="relative flex min-h-screen min-h-[100dvh] w-full items-center justify-center overflow-hidden bg-slate-400 font-sans text-white"
  >
    <div
      class="absolute inset-[-20px] bg-[url('/error-bg.jpg')] bg-cover bg-center blur-[8px]"
    />

    <div class="absolute inset-0 bg-slate-500/65" />

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
        422
      </div>

      <h1 class="text-[32px] font-bold max-[600px]:text-2xl">
        Validation Error
      </h1>

      <p
        class="mx-auto mt-4 max-w-[500px] text-base leading-[1.6] text-white/85 max-[600px]:text-sm"
      >
        The information you submitted could not be processed. Please check
        your input and try again.
      </p>

      <button
        type="button"
        class="mt-7 rounded-lg bg-white px-7 py-3 text-[15px] font-semibold text-slate-800 shadow-lg shadow-slate-900/10 transition duration-200 hover:-translate-y-0.5 hover:bg-slate-100 hover:shadow-xl active:translate-y-0"
        @click="$router.back()"
      >
        Back
      </button>
    </div>
  </div>
</template>
