<template>
  <div class="max-w-[1180px] mx-auto px-0 pt-2 pb-8 text-[#172033] font-sans">

    <!-- HEADER -->
    <section
      class="flex items-center justify-between gap-6 px-[34px] py-[30px] text-white rounded-xl shadow-[0_10px_30px_rgba(30,79,138,0.14)] bg-gradient-to-br from-[#1E4F8A] via-[#2D659C] to-[#DCEBF7]"
    >
      <div>
        <p class="mb-2 text-[#DCEBF7] text-[11px] font-bold tracking-[0.14em] uppercase">
          GMO TRAVEL · ADMINISTRASI
        </p>

        <h1 class="mb-2 text-[clamp(25px,3vw,34px)] font-bold tracking-[-0.02em]">
          Annual Leave
        </h1>

        <p class="mb-0 text-[#e5f2ff] text-sm leading-[1.6]">
          Kelola pengajuan cuti tahunan karyawan secara terpusat
          dalam satu panel administrasi.
        </p>
      </div>

      <button
        class="shrink-0 px-[15px] py-2.5 text-[#1E4F8A] bg-white border-0 rounded-[7px] text-xs font-bold cursor-pointer hover:bg-slate-50"
        @click="showForm = true"
      >
        + Ajukan Cuti
      </button>
    </section>

    <!-- OVERVIEW -->
    <section class="mt-8" aria-labelledby="overview-title">

      <div class="flex items-end justify-between gap-4 mb-3.5">
        <div>
          <p class="mb-1.5 text-[#1E4F8A] text-[11px] font-bold tracking-[0.14em] uppercase">
            Ikhtisar
          </p>

          <h2 id="overview-title" class="mb-0 text-xl font-semibold">
            Aktivitas cuti
          </h2>
        </div>

        <span class="text-slate-500 text-xs">
          Ringkasan pengajuan annual leave
        </span>
      </div>

      <div class="grid grid-cols-4 gap-3.5 max-[900px]:grid-cols-2 max-[600px]:grid-cols-1">

        <article
          v-for="item in [
            { value: leaves.length, label: 'Total Pengajuan' },
            { value: pending, label: 'Menunggu' },
            { value: approved, label: 'Disetujui' },
            { value: rejected, label: 'Ditolak' }
          ]"
          :key="item.label"
          class="flex items-center gap-[13px] min-h-[92px] p-[18px] bg-white border border-slate-200 rounded-[10px] transition duration-200 hover:border-[#9fc1df] hover:shadow-[0_6px_18px_rgba(30,79,138,0.07)]"
        >
          <span class="w-1 h-[34px] shrink-0 rounded bg-gradient-to-b from-[#1E4F8A] to-[#2D659C]"></span>

          <div class="grid gap-[3px]">
            <strong class="text-[25px] font-semibold leading-none">
              {{ item.value }}
            </strong>

            <span class="text-slate-500 text-xs">
              {{ item.label }}
            </span>
          </div>
        </article>

      </div>
    </section>

    <!-- TABLE -->
    <section class="mt-8" aria-labelledby="table-title">

      <div class="overflow-hidden bg-white border border-slate-200 rounded-[10px]">

        <div class="flex items-center justify-between gap-4 p-5 border-b border-[#eef0f4] max-[600px]:items-start max-[600px]:flex-col">

          <div>
            <p class="mb-1.5 text-[#1E4F8A] text-[11px] font-bold tracking-[0.14em] uppercase">
              Data Cuti
            </p>

            <h2 id="table-title" class="m-0 text-xl font-semibold">
              Pengajuan Annual Leave
            </h2>
          </div>

          <input
            v-model="search"
            type="search"
            placeholder="Cari nama..."
            class="w-[220px] px-3 py-[9px] border border-[#d9dee7] rounded-[7px] text-xs outline-none focus:border-[#1E4F8A] max-[600px]:w-full"
          />

        </div>

        <div class="overflow-x-auto">

          <table class="w-full border-collapse">

            <thead>
              <tr>
                <th class="px-[18px] py-[13px] bg-slate-50 border-b border-[#eef0f4] text-left text-[#667085] text-[10px]">
                  Nama
                </th>

                <th class="px-[18px] py-[13px] bg-slate-50 border-b border-[#eef0f4] text-left text-[#667085] text-[10px]">
                  Periode
                </th>

                <th class="px-[18px] py-[13px] bg-slate-50 border-b border-[#eef0f4] text-left text-[#667085] text-[10px]">
                  Durasi
                </th>

                <th class="px-[18px] py-[13px] bg-slate-50 border-b border-[#eef0f4] text-left text-[#667085] text-[10px]">
                  Alasan
                </th>

                <th class="px-[18px] py-[13px] bg-slate-50 border-b border-[#eef0f4] text-left text-[#667085] text-[10px]">
                  Status
                </th>
              </tr>
            </thead>

            <tbody>

              <tr
                v-for="leave in filteredLeaves"
                :key="leave.id"
              >
                <td class="px-[18px] py-[13px] border-b border-[#eef0f4] text-xs">
                  <strong class="block">{{ leave.name }}</strong>
                  <small class="block mt-[3px] text-[#98a2b3]">
                    {{ leave.employeeId }}
                  </small>
                </td>

                <td class="px-[18px] py-[13px] border-b border-[#eef0f4] text-xs">
                  {{ leave.startDate }} - {{ leave.endDate }}
                </td>

                <td class="px-[18px] py-[13px] border-b border-[#eef0f4] text-xs">
                  {{ leave.duration }} hari
                </td>

                <td class="px-[18px] py-[13px] border-b border-[#eef0f4] text-xs">
                  {{ leave.reason }}
                </td>

                <td class="px-[18px] py-[13px] border-b border-[#eef0f4] text-xs">
                  <span
                    class="px-[9px] py-[5px] rounded-full text-[10px] font-bold"
                    :class="{
                      'text-[#b7791f] bg-[#fff7e6]': leave.status === 'pending',
                      'text-green-700 bg-[#eaf7ef]': leave.status === 'approved',
                      'text-red-700 bg-[#fdecec]': leave.status === 'rejected'
                    }"
                  >
                    {{ getStatusLabel(leave.status) }}
                  </span>
                </td>
              </tr>

              <tr v-if="filteredLeaves.length === 0">
                <td
                  colspan="5"
                  class="p-[30px] text-center text-[#98a2b3]"
                >
                  Belum ada pengajuan cuti.
                </td>
              </tr>

            </tbody>

          </table>

        </div>
      </div>
    </section>

    <!-- MODAL -->
    <div
      v-if="showForm"
      class="fixed inset-0 z-10 flex items-center justify-center bg-slate-900/40"
      @click.self="showForm = false"
    >
      <div
        class="w-[420px] p-6 bg-white rounded-xl shadow-[0_15px_40px_rgba(15,23,42,0.15)] max-[600px]:w-[calc(100%-32px)]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-title"
      >

        <div class="flex justify-between mb-5">

          <div>
            <p class="mb-1.5 text-[#1E4F8A] text-[11px] font-bold tracking-[0.14em] uppercase">
              Pengajuan
            </p>

            <h2 id="modal-title" class="m-0 text-xl font-semibold">
              Ajukan Annual Leave
            </h2>
          </div>

          <button
            type="button"
            aria-label="Tutup"
            class="w-8 h-8 border-0 bg-slate-100 rounded-[7px] text-xl cursor-pointer hover:bg-slate-200"
            @click="showForm = false"
          >
            ×
          </button>

        </div>

        <form @submit.prevent="submit">

          <label class="block mb-3.5 text-xs font-semibold">
            Nama

            <input
              v-model="form.name"
              type="text"
              placeholder="Masukkan nama karyawan"
              required
              class="w-full mt-1.5 px-2.5 py-2.5 border border-[#d9dee7] rounded-[7px] text-xs outline-none focus:border-[#1E4F8A]"
            />
          </label>

          <label class="block mb-3.5 text-xs font-semibold">
            Tanggal Mulai

            <input
              v-model="form.startDate"
              type="date"
              required
              class="w-full mt-1.5 px-2.5 py-2.5 border border-[#d9dee7] rounded-[7px] text-xs outline-none focus:border-[#1E4F8A]"
            />
          </label>

          <label class="block mb-3.5 text-xs font-semibold">
            Tanggal Selesai

            <input
              v-model="form.endDate"
              type="date"
              required
              class="w-full mt-1.5 px-2.5 py-2.5 border border-[#d9dee7] rounded-[7px] text-xs outline-none focus:border-[#1E4F8A]"
            />
          </label>

          <label class="block mb-3.5 text-xs font-semibold">
            Alasan

            <textarea
              v-model="form.reason"
              rows="4"
              placeholder="Masukkan alasan cuti..."
              required
              class="w-full mt-1.5 px-2.5 py-2.5 border border-[#d9dee7] rounded-[7px] text-xs outline-none resize-y focus:border-[#1E4F8A]"
            ></textarea>
          </label>

          <div class="flex justify-end gap-2 mt-5">

            <button
              type="button"
              class="px-3.5 py-[9px] border-0 rounded-[7px] bg-slate-100 text-slate-600 text-xs font-bold cursor-pointer hover:bg-slate-200"
              @click="showForm = false"
            >
              Batal
            </button>

            <button
              type="submit"
              class="px-3.5 py-[9px] border-0 rounded-[7px] bg-[#1E4F8A] text-white text-xs font-bold cursor-pointer hover:bg-[#173f70]"
            >
              Ajukan
            </button>

          </div>

        </form>

      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'

const showForm = ref(false)
const search = ref('')

const form = reactive({
  name: '',
  startDate: '',
  endDate: '',
  reason: ''
})

const leaves = ref([])

const pending = computed(() =>
  leaves.value.filter(
    leave => leave.status === 'pending'
  ).length
)

const approved = computed(() =>
  leaves.value.filter(
    leave => leave.status === 'approved'
  ).length
)

const rejected = computed(() =>
  leaves.value.filter(
    leave => leave.status === 'rejected'
  ).length
)

const filteredLeaves = computed(() => {
  const keyword = search.value.toLowerCase().trim()

  return leaves.value.filter(leave =>
    leave.name.toLowerCase().includes(keyword)
  )
})

const calculateDuration = (start, end) => {
  if (!start || !end) {
    return 0
  }

  const startDate = new Date(start)
  const endDate = new Date(end)

  const difference =
    endDate.getTime() - startDate.getTime()

  return Math.floor(
    difference / (1000 * 60 * 60 * 24)
  ) + 1
}

const submit = () => {
  const duration = calculateDuration(
    form.startDate,
    form.endDate
  )

  if (duration <= 0) {
    return
  }

  leaves.value.push({
    id: Date.now(),
    name: form.name,
    employeeId: 'EMP-NEW',
    startDate: form.startDate,
    endDate: form.endDate,
    duration,
    reason: form.reason,
    status: 'pending'
  })

  form.name = ''
  form.startDate = ''
  form.endDate = ''
  form.reason = ''

  showForm.value = false
}

const getStatusLabel = (status) => {
  return {
    pending: 'Menunggu',
    approved: 'Disetujui',
    rejected: 'Ditolak'
  }[status]
}
</script>
