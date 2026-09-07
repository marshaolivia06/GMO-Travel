<template>
  <div
    class="mx-auto max-w-[1180px] pb-8 pt-2 text-left font-['Segoe_UI','Helvetica_Neue',Arial,sans-serif] text-[#172033]"
  >

    <!-- WELCOME -->
    <section
      class="flex items-center justify-between gap-6 rounded-xl bg-gradient-to-br from-[#1E4F8A] via-[#2D659C] to-[#DCEBF7] px-[34px] py-[30px] text-white shadow-[0_10px_30px_rgba(30,79,138,0.14)] max-[600px]:flex-col max-[600px]:items-start max-[600px]:px-6 max-[600px]:py-6"
      aria-labelledby="welcome-title"
    >
      <div>
        <p
          class="mb-2 text-[11px] font-bold uppercase tracking-[0.14em] text-[#DCEBF7]"
        >
          GMO TRAVEL · ADMINISTRASI
        </p>

        <h1
          id="welcome-title"
          class="mb-2 text-[clamp(25px,3vw,34px)] font-bold tracking-[-0.02em]"
        >
          Selamat datang, Admin
        </h1>

        <p
          class="mb-0 max-w-[590px] text-sm leading-[1.6] text-[#e5f2ff]"
        >
          Kelola perjalanan dinas, cuti tahunan, dan aktivitas operasional
          dalam satu panel.
        </p>
      </div>

      <div
        class="inline-flex shrink-0 items-center gap-2 rounded-full border border-white/35 bg-white/[0.08] px-3 py-2 text-xs font-semibold text-[#f5faff]"
        aria-label="Status panel aktif"
      >
        <span
          class="h-[7px] w-[7px] rounded-full bg-[#DCEBF7] shadow-[0_0_0_3px_rgba(220,235,247,0.18)]"
          aria-hidden="true"
        ></span>

        <span>Panel aktif</span>
      </div>
    </section>


    <!-- OVERVIEW -->
    <section
      class="mt-8"
      aria-labelledby="overview-title"
    >
      <div
        class="mb-[14px] flex items-end justify-between gap-4 max-[600px]:flex-col max-[600px]:items-start"
      >
        <div>
          <p
            class="mb-[5px] text-[11px] font-bold uppercase tracking-[0.14em] text-[#1E4F8A]"
          >
            Ikhtisar
          </p>

          <h2
            id="overview-title"
            class="mb-0 text-xl font-semibold tracking-[-0.01em]"
          >
            Aktivitas saat ini
          </h2>
        </div>

        <span class="text-xs text-[#64748b]">
          Ringkasan administrasi
        </span>
      </div>


      <div
        class="grid grid-cols-4 gap-[14px] max-[900px]:grid-cols-2 max-[600px]:grid-cols-1"
      >

        <article
          class="flex min-h-[92px] items-center gap-[13px] rounded-[10px] border border-[#e2e8f0] bg-white p-[18px] transition duration-200 hover:border-[#9fc1df] hover:shadow-[0_6px_18px_rgba(30,79,138,0.07)]"
        >
          <span
            class="h-[34px] w-1 shrink-0 rounded bg-gradient-to-b from-[#1E4F8A] to-[#2D659C]"
            aria-hidden="true"
          ></span>

          <div class="grid gap-[3px]">
            <strong class="text-[25px] font-[650] leading-none">
              {{ stats.totalTravel }}
            </strong>

            <span class="text-xs text-[#64748b]">
              Total Travel Order
            </span>
          </div>
        </article>


        <article
          class="flex min-h-[92px] items-center gap-[13px] rounded-[10px] border border-[#e2e8f0] bg-white p-[18px] transition duration-200 hover:border-[#9fc1df] hover:shadow-[0_6px_18px_rgba(30,79,138,0.07)]"
        >
          <span
            class="h-[34px] w-1 shrink-0 rounded bg-gradient-to-b from-[#1E4F8A] to-[#2D659C]"
            aria-hidden="true"
          ></span>

          <div class="grid gap-[3px]">
            <strong class="text-[25px] font-[650] leading-none">
              {{ stats.totalLeave }}
            </strong>

            <span class="text-xs text-[#64748b]">
              Annual Leave
            </span>
          </div>
        </article>


        <article
          class="flex min-h-[92px] items-center gap-[13px] rounded-[10px] border border-[#e2e8f0] bg-white p-[18px] transition duration-200 hover:border-[#9fc1df] hover:shadow-[0_6px_18px_rgba(30,79,138,0.07)]"
        >
          <span
            class="h-[34px] w-1 shrink-0 rounded bg-gradient-to-b from-[#1E4F8A] to-[#2D659C]"
            aria-hidden="true"
          ></span>

          <div class="grid gap-[3px]">
            <strong class="text-[25px] font-[650] leading-none">
              {{ stats.pending }}
            </strong>

            <span class="text-xs text-[#64748b]">
              Menunggu persetujuan
            </span>
          </div>
        </article>


        <article
          class="flex min-h-[92px] items-center gap-[13px] rounded-[10px] border border-[#e2e8f0] bg-white p-[18px] transition duration-200 hover:border-[#9fc1df] hover:shadow-[0_6px_18px_rgba(30,79,138,0.07)]"
        >
          <span
            class="h-[34px] w-1 shrink-0 rounded bg-gradient-to-b from-[#1E4F8A] to-[#2D659C]"
            aria-hidden="true"
          ></span>

          <div class="grid gap-[3px]">
            <strong class="text-[25px] font-[650] leading-none">
              {{ stats.approved }}
            </strong>

            <span class="text-xs text-[#64748b]">
              Telah disetujui
            </span>
          </div>
        </article>

      </div>
    </section>


    <!-- QUICK ACCESS -->
    <section
      class="mt-8"
      aria-labelledby="menu-title"
    >
      <div
        class="mb-[14px] flex items-end justify-between gap-4 max-[600px]:flex-col max-[600px]:items-start"
      >
        <div>
          <p
            class="mb-[5px] text-[11px] font-bold uppercase tracking-[0.14em] text-[#1E4F8A]"
          >
            Akses cepat
          </p>

          <h2
            id="menu-title"
            class="mb-0 text-xl font-semibold tracking-[-0.01em]"
          >
            Kelola kebutuhan Anda
          </h2>
        </div>
      </div>


      <div
        class="grid grid-cols-3 gap-[14px] max-[900px]:grid-cols-1"
      >

        <!-- ANNUAL LEAVE -->
        <RouterLink
          to="/annual-leave"
          class="flex min-h-[194px] flex-col rounded-[10px] border border-[#e2e8f0] bg-white p-5 text-inherit no-underline transition duration-200 hover:-translate-y-0.5 hover:border-[#9fc1df] hover:shadow-[0_8px_22px_rgba(30,79,138,0.10)] focus-visible:outline-[3px] focus-visible:outline-[#1E4F8A]/[0.22] focus-visible:outline-offset-[3px]"
        >
          <div class="flex items-center justify-between text-[#1E4F8A]">
            <span class="text-[11px] font-bold tracking-[0.1em]">
              01
            </span>

            <span class="text-xl leading-none" aria-hidden="true">
              ↗
            </span>
          </div>

          <h3
            class="mb-[7px] mt-[22px] text-lg font-semibold text-[#1E4F8A]"
          >
            Annual Leave
          </h3>

          <p class="mb-[18px] text-[13px] leading-[1.55] text-[#64748b]">
            Kelola pengajuan dan informasi cuti tahunan
            secara terpusat.
          </p>

          <span class="mt-auto text-xs font-bold text-[#1E4F8A]">
            Buka Annual Leave
            <span aria-hidden="true">→</span>
          </span>
        </RouterLink>


        <!-- TRAVEL ORDER -->
        <RouterLink
          to="/travel-order"
          class="flex min-h-[194px] flex-col rounded-[10px] border border-[#e2e8f0] bg-white p-5 text-inherit no-underline transition duration-200 hover:-translate-y-0.5 hover:border-[#9fc1df] hover:shadow-[0_8px_22px_rgba(30,79,138,0.10)] focus-visible:outline-[3px] focus-visible:outline-[#1E4F8A]/[0.22] focus-visible:outline-offset-[3px]"
        >
          <div class="flex items-center justify-between text-[#1E4F8A]">
            <span class="text-[11px] font-bold tracking-[0.1em]">
              02
            </span>

            <span class="text-xl leading-none" aria-hidden="true">
              ↗
            </span>
          </div>

          <h3
            class="mb-[7px] mt-[22px] text-lg font-semibold text-[#1E4F8A]"
          >
            Travel Order
          </h3>

          <p class="mb-[18px] text-[13px] leading-[1.55] text-[#64748b]">
            Kelola pengajuan perjalanan dinas
            dan informasi perjalanan.
          </p>

          <span class="mt-auto text-xs font-bold text-[#1E4F8A]">
            Buka Travel Order
            <span aria-hidden="true">→</span>
          </span>
        </RouterLink>


        <!-- RINGKASAN -->
        <article
          class="flex min-h-[194px] flex-col rounded-[10px] border border-[#e2e8f0] bg-white p-5 text-inherit"
        >
          <div class="flex items-center justify-between text-[#98a2b3]">
            <span class="text-[11px] font-bold tracking-[0.1em]">
              03
            </span>

            <span class="text-xl leading-none" aria-hidden="true">
              —
            </span>
          </div>

          <h3
            class="mb-[7px] mt-[22px] text-lg font-semibold text-[#475569]"
          >
            Ringkasan
          </h3>

          <p class="mb-[18px] text-[13px] leading-[1.55] text-[#64748b]">
            Lihat ringkasan aktivitas perjalanan
            dan pengajuan terbaru.
          </p>

          <span class="mt-auto text-xs font-bold text-[#98a2b3]">
            Ringkasan aktivitas
          </span>
        </article>

      </div>
    </section>

  </div>
</template>


<script setup>
import { reactive } from 'vue'

const stats = reactive({
  totalTravel: 0,
  totalLeave: 0,
  pending: 0,
  approved: 0
})
</script>
