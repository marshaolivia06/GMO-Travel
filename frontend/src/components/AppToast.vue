<script setup>
import { useToastStore } from '../stores/toast'

const toast = useToastStore()

const closeToast = () => {
  toast.show = false
}
</script>

<template>
  <Transition
    enter-active-class="transition duration-250 ease-out"
    enter-from-class="scale-85 translate-y-3 opacity-0"
    enter-to-class="scale-100 translate-y-0 opacity-100"
    leave-active-class="transition duration-250 ease-in"
    leave-from-class="scale-100 translate-y-0 opacity-100"
    leave-to-class="scale-85 translate-y-3 opacity-0"
  >
    <div
      v-if="toast.show"
      class="pointer-events-none fixed inset-0 z-[99999] flex items-center justify-center p-5"
    >
      <button
        type="button"
        class="pointer-events-auto relative box-border min-h-[260px] w-[400px] cursor-pointer rounded-[18px] border border-slate-200 bg-white px-[35px] pb-[34px] pt-[38px] text-center shadow-[0_20px_50px_rgba(15,23,42,.16),0_5px_18px_rgba(15,23,42,.06)] outline-none transition hover:scale-[1.01] max-[500px]:min-h-[240px] max-[500px]:w-full max-[500px]:max-w-[380px] max-[500px]:px-[25px] max-[500px]:py-8"
        @click="closeToast"
      >
        <div
          class="mx-auto mb-5 flex h-[72px] w-[72px] items-center justify-center rounded-full text-[36px] font-bold leading-none max-[500px]:h-16 max-[500px]:w-16 max-[500px]:text-[32px]"
          :class="{
            'bg-green-100 text-green-600': toast.type === 'success',
            'bg-red-100 text-red-600': toast.type === 'error',
            'bg-amber-100 text-amber-600': toast.type === 'warning',
            'bg-[#DCEBF7] text-[#2D659C]':
              toast.type !== 'success' &&
              toast.type !== 'error' &&
              toast.type !== 'warning'
          }"
        >
          <span v-if="toast.type === 'success'">✓</span>
          <span v-else-if="toast.type === 'error'">!</span>
          <span v-else-if="toast.type === 'warning'">!</span>
          <span v-else>i</span>
        </div>

        <h2
          class="mb-2.5 text-2xl font-bold leading-[1.3] text-[#172033] max-[500px]:text-[21px]"
        >
          {{ toast.title }}
        </h2>

        <p
          class="mx-auto max-w-[330px] text-[15px] font-medium leading-[1.6] text-slate-500 max-[500px]:text-sm"
        >
          {{ toast.message }}
        </p>
      </button>
    </div>
  </Transition>
</template>
