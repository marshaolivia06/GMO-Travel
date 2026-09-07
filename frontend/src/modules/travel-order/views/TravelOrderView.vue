<template>
  <div class="mx-auto w-full max-w-[1180px] px-0 pb-8 pt-2 text-left font-['Segoe_UI','Helvetica_Neue',Arial,sans-serif] text-[#172033]">

    <!-- HEADER -->
    <section
      class="flex items-center justify-between gap-6 rounded-xl bg-gradient-to-br from-[#1E4F8A] via-[#2D659C] to-[#DCEBF7] px-[34px] py-[30px] text-white shadow-[0_10px_30px_rgba(30,79,138,0.14)] max-[600px]:flex-col max-[600px]:items-start max-[600px]:px-6 max-[600px]:py-6"
    >
      <div>
        <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.14em] text-[#DCEBF7]">
          GMO TRAVEL · ADMINISTRASI
        </p>

        <h1 class="mb-2 text-[clamp(25px,3vw,34px)] font-bold tracking-[-0.02em]">
          Travel Order
        </h1>

        <p class="m-0 max-w-[590px] text-sm leading-[1.6] text-[#e5f2ff]">
          Kelola pengajuan perjalanan dinas karyawan
          secara terpusat dalam satu panel administrasi.
        </p>
      </div>

      <button
        type="button"
        class="shrink-0 rounded-[7px] border-0 bg-white px-[15px] py-2.5 text-xs font-bold text-[#1E4F8A] transition hover:bg-[#f5f9fd] max-[600px]:w-full"
        @click="openForm"
      >
        + Ajukan Travel Order
      </button>
    </section>


    <!-- TYPE SELECTOR -->
    <section class="mt-8">
      <div class="mb-3.5 flex items-end justify-between gap-4 max-[600px]:flex-col max-[600px]:items-start">

        <div>
          <p class="mb-[5px] text-[11px] font-bold uppercase tracking-[0.14em] text-[#1E4F8A]">
            Jenis Pengajuan
          </p>

          <h2 class="m-0 text-xl font-semibold">
            Pilih jenis Travel Order
          </h2>
        </div>

      </div>


      <div class="grid grid-cols-2 gap-3.5 max-[700px]:grid-cols-1">

        <!-- BUSINESS GROUP -->
        <button
          type="button"
          class="min-h-[170px] cursor-pointer rounded-[10px] border bg-white p-5 text-left text-[#172033] transition duration-200 hover:-translate-y-0.5 hover:border-[#9fc1df] hover:shadow-[0_6px_18px_rgba(30,79,138,0.07)]"
          :class="
            activeType === 'business'
              ? 'border-[#1E4F8A] shadow-[0_8px_22px_rgba(30,79,138,0.10)]'
              : 'border-[#e2e8f0]'
          "
          @click="activeType = 'business'"
        >
          <div class="mb-[22px] flex justify-between">
            <span class="text-[11px] font-bold tracking-[0.1em] text-[#1E4F8A]">
              01
            </span>

            <span class="text-lg font-bold text-[#1E4F8A]">
              {{ activeType === 'business' ? '✓' : '→' }}
            </span>
          </div>

          <h3 class="mb-[7px] text-lg font-semibold text-[#1E4F8A]">
            Business Group
          </h3>

          <p class="m-0 text-[13px] leading-[1.55] text-[#64748b]">
            Pengajuan perjalanan dinas untuk
            beberapa karyawan dalam satu grup.
          </p>
        </button>


        <!-- INDIVIDUAL -->
        <button
          type="button"
          class="min-h-[170px] cursor-pointer rounded-[10px] border bg-white p-5 text-left text-[#172033] transition duration-200 hover:-translate-y-0.5 hover:border-[#9fc1df] hover:shadow-[0_6px_18px_rgba(30,79,138,0.07)]"
          :class="
            activeType === 'individual'
              ? 'border-[#1E4F8A] shadow-[0_8px_22px_rgba(30,79,138,0.10)]'
              : 'border-[#e2e8f0]'
          "
          @click="activeType = 'individual'"
        >
          <div class="mb-[22px] flex justify-between">
            <span class="text-[11px] font-bold tracking-[0.1em] text-[#1E4F8A]">
              02
            </span>

            <span class="text-lg font-bold text-[#1E4F8A]">
              {{ activeType === 'individual' ? '✓' : '→' }}
            </span>
          </div>

          <h3 class="mb-[7px] text-lg font-semibold text-[#1E4F8A]">
            Individual
          </h3>

          <p class="m-0 text-[13px] leading-[1.55] text-[#64748b]">
            Pengajuan perjalanan dinas untuk
            satu karyawan secara individual.
          </p>
        </button>

      </div>
    </section>


    <!-- OVERVIEW -->
    <section class="mt-8">

      <div class="mb-3.5 flex items-end justify-between gap-4 max-[600px]:flex-col max-[600px]:items-start">

        <div>
          <p class="mb-[5px] text-[11px] font-bold uppercase tracking-[0.14em] text-[#1E4F8A]">
            Ikhtisar
          </p>

          <h2 class="m-0 text-xl font-semibold">
            Aktivitas Travel Order
          </h2>
        </div>

        <span class="text-xs text-[#64748b]">
          Ringkasan pengajuan perjalanan dinas
        </span>

      </div>


      <div class="grid grid-cols-4 gap-3.5 max-[900px]:grid-cols-2 max-[600px]:grid-cols-1">

        <article class="flex min-h-[92px] items-center gap-[13px] rounded-[10px] border border-[#e2e8f0] bg-white p-[18px]">

          <span class="h-[34px] w-1 shrink-0 rounded bg-gradient-to-b from-[#1E4F8A] to-[#2D659C]"></span>

          <div class="grid gap-[3px]">
            <strong class="text-[25px] font-semibold leading-none">
              {{ currentOrders.length }}
            </strong>

            <span class="text-xs text-[#64748b]">
              Total Pengajuan
            </span>
          </div>

        </article>


        <article class="flex min-h-[92px] items-center gap-[13px] rounded-[10px] border border-[#e2e8f0] bg-white p-[18px]">

          <span class="h-[34px] w-1 shrink-0 rounded bg-gradient-to-b from-[#1E4F8A] to-[#2D659C]"></span>

          <div class="grid gap-[3px]">
            <strong class="text-[25px] font-semibold leading-none">
              {{ pending }}
            </strong>

            <span class="text-xs text-[#64748b]">
              Menunggu
            </span>
          </div>

        </article>


        <article class="flex min-h-[92px] items-center gap-[13px] rounded-[10px] border border-[#e2e8f0] bg-white p-[18px]">

          <span class="h-[34px] w-1 shrink-0 rounded bg-gradient-to-b from-[#1E4F8A] to-[#2D659C]"></span>

          <div class="grid gap-[3px]">
            <strong class="text-[25px] font-semibold leading-none">
              {{ approved }}
            </strong>

            <span class="text-xs text-[#64748b]">
              Disetujui
            </span>
          </div>

        </article>


        <article class="flex min-h-[92px] items-center gap-[13px] rounded-[10px] border border-[#e2e8f0] bg-white p-[18px]">

          <span class="h-[34px] w-1 shrink-0 rounded bg-gradient-to-b from-[#1E4F8A] to-[#2D659C]"></span>

          <div class="grid gap-[3px]">
            <strong class="text-[25px] font-semibold leading-none">
              {{ rejected }}
            </strong>

            <span class="text-xs text-[#64748b]">
              Ditolak
            </span>
          </div>

        </article>

      </div>
    </section>


    <!-- TABLE -->
    <section class="mt-8">

      <div class="overflow-hidden rounded-[10px] border border-[#e2e8f0] bg-white">

        <!-- TABLE HEADER -->
        <div class="flex items-center justify-between gap-4 border-b border-[#eef0f4] p-5 max-[600px]:flex-col max-[600px]:items-start">

          <div>
            <p class="mb-[5px] text-[11px] font-bold uppercase tracking-[0.14em] text-[#1E4F8A]">
              Data Travel Order
            </p>

            <h2 class="m-0 text-xl font-semibold">
              {{
                activeType === 'business'
                  ? 'Business Group'
                  : 'Individual'
              }}
            </h2>
          </div>

          <input
            v-model="search"
            type="search"
            placeholder="Cari..."
            class="w-[220px] rounded-[7px] border border-[#d9dee7] px-3 py-[9px] text-[13px] font-inherit outline-none transition focus:border-[#1E4F8A] max-[600px]:w-full"
          />

        </div>


        <div class="overflow-x-auto">

          <!-- BUSINESS GROUP TABLE -->
          <table
            v-if="activeType === 'business'"
            class="w-full border-collapse"
          >

            <thead>
              <tr>
                <th class="bg-[#f8fafc] px-[18px] py-[13px] text-left text-[10px] font-semibold text-[#667085]">
                  Group
                </th>

                <th class="bg-[#f8fafc] px-[18px] py-[13px] text-left text-[10px] font-semibold text-[#667085]">
                  Peserta
                </th>

                <th class="bg-[#f8fafc] px-[18px] py-[13px] text-left text-[10px] font-semibold text-[#667085]">
                  Tujuan
                </th>

                <th class="bg-[#f8fafc] px-[18px] py-[13px] text-left text-[10px] font-semibold text-[#667085]">
                  Periode
                </th>

                <th class="bg-[#f8fafc] px-[18px] py-[13px] text-left text-[10px] font-semibold text-[#667085]">
                  Status
                </th>

                <th class="bg-[#f8fafc] px-[18px] py-[13px] text-left text-[10px] font-semibold text-[#667085]">
                  Aksi
                </th>
              </tr>
            </thead>


            <tbody>

              <tr
                v-for="order in filteredBusinessOrders"
                :key="order.id"
                class="hover:bg-[#fafcff]"
              >

                <td class="border-b border-[#eef0f4] px-[18px] py-[13px] text-xs">
                  <strong class="block">
                    {{ order.groupName }}
                  </strong>

                  <small class="mt-[3px] block text-[#98a2b3]">
                    {{ order.groupId }}
                  </small>
                </td>

                <td class="border-b border-[#eef0f4] px-[18px] py-[13px] text-xs">
                  {{ order.participants.length }} orang
                </td>

                <td class="border-b border-[#eef0f4] px-[18px] py-[13px] text-xs">
                  {{ order.destination }}
                </td>

                <td class="border-b border-[#eef0f4] px-[18px] py-[13px] text-xs">
                  {{ order.startDate }} - {{ order.endDate }}
                </td>

                <td class="border-b border-[#eef0f4] px-[18px] py-[13px] text-xs">
                  <span
                    class="inline-block rounded-full px-[9px] py-[5px] text-[10px] font-bold"
                    :class="{
                      'bg-[#fff7e6] text-[#b7791f]': order.status === 'pending',
                      'bg-[#eaf7ef] text-[#15803d]': order.status === 'approved',
                      'bg-[#fdecec] text-[#c53030]': order.status === 'rejected'
                    }"
                  >
                    {{ getStatusLabel(order.status) }}
                  </span>
                </td>

                <td class="border-b border-[#eef0f4] px-[18px] py-[13px] text-xs">
                  <div class="flex gap-1.5">

                    <button
                      type="button"
                      class="rounded-[5px] bg-[#f4f8fc] px-2 py-[5px] text-[10px] font-semibold text-[#1E4F8A]"
                      @click="editOrder(order)"
                    >
                      Edit
                    </button>

                    <button
                      type="button"
                      class="rounded-[5px] bg-[#fdecec] px-2 py-[5px] text-[10px] font-semibold text-[#c53030]"
                      @click="deleteOrder(order.id, 'business')"
                    >
                      Hapus
                    </button>

                  </div>
                </td>

              </tr>


              <tr v-if="filteredBusinessOrders.length === 0">
                <td
                  colspan="6"
                  class="border-b border-[#eef0f4] px-[18px] py-[30px] text-center text-[#98a2b3]"
                >
                  Belum ada pengajuan Business Group.
                </td>
              </tr>

            </tbody>

          </table>


          <!-- INDIVIDUAL TABLE -->
          <table
            v-else
            class="w-full border-collapse"
          >

            <thead>
              <tr>
                <th class="bg-[#f8fafc] px-[18px] py-[13px] text-left text-[10px] font-semibold text-[#667085]">
                  Nama
                </th>

                <th class="bg-[#f8fafc] px-[18px] py-[13px] text-left text-[10px] font-semibold text-[#667085]">
                  Tujuan
                </th>

                <th class="bg-[#f8fafc] px-[18px] py-[13px] text-left text-[10px] font-semibold text-[#667085]">
                  Periode
                </th>

                <th class="bg-[#f8fafc] px-[18px] py-[13px] text-left text-[10px] font-semibold text-[#667085]">
                  Keperluan
                </th>

                <th class="bg-[#f8fafc] px-[18px] py-[13px] text-left text-[10px] font-semibold text-[#667085]">
                  Status
                </th>

                <th class="bg-[#f8fafc] px-[18px] py-[13px] text-left text-[10px] font-semibold text-[#667085]">
                  Aksi
                </th>
              </tr>
            </thead>


            <tbody>

              <tr
                v-for="order in filteredIndividualOrders"
                :key="order.id"
                class="hover:bg-[#fafcff]"
              >

                <td class="border-b border-[#eef0f4] px-[18px] py-[13px] text-xs">
                  <strong class="block">
                    {{ order.name }}
                  </strong>

                  <small class="mt-[3px] block text-[#98a2b3]">
                    {{ order.employeeId }}
                  </small>
                </td>

                <td class="border-b border-[#eef0f4] px-[18px] py-[13px] text-xs">
                  {{ order.destination }}
                </td>

                <td class="border-b border-[#eef0f4] px-[18px] py-[13px] text-xs">
                  {{ order.startDate }} - {{ order.endDate }}
                </td>

                <td class="border-b border-[#eef0f4] px-[18px] py-[13px] text-xs">
                  {{ order.purpose }}
                </td>

                <td class="border-b border-[#eef0f4] px-[18px] py-[13px] text-xs">
                  <span
                    class="inline-block rounded-full px-[9px] py-[5px] text-[10px] font-bold"
                    :class="{
                      'bg-[#fff7e6] text-[#b7791f]': order.status === 'pending',
                      'bg-[#eaf7ef] text-[#15803d]': order.status === 'approved',
                      'bg-[#fdecec] text-[#c53030]': order.status === 'rejected'
                    }"
                  >
                    {{ getStatusLabel(order.status) }}
                  </span>
                </td>

                <td class="border-b border-[#eef0f4] px-[18px] py-[13px] text-xs">
                  <div class="flex gap-1.5">

                    <button
                      type="button"
                      class="rounded-[5px] bg-[#f4f8fc] px-2 py-[5px] text-[10px] font-semibold text-[#1E4F8A]"
                      @click="editOrder(order)"
                    >
                      Edit
                    </button>

                    <button
                      type="button"
                      class="rounded-[5px] bg-[#fdecec] px-2 py-[5px] text-[10px] font-semibold text-[#c53030]"
                      @click="deleteOrder(order.id, 'individual')"
                    >
                      Hapus
                    </button>

                  </div>
                </td>

              </tr>


              <tr v-if="filteredIndividualOrders.length === 0">
                <td
                  colspan="6"
                  class="border-b border-[#eef0f4] px-[18px] py-[30px] text-center text-[#98a2b3]"
                >
                  Belum ada pengajuan Individual.
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
      class="fixed inset-0 z-20 flex items-center justify-center bg-[rgba(15,23,42,0.4)] p-5"
      @click.self="closeForm"
    >

      <div
        class="max-h-[90vh] w-full max-w-[500px] overflow-y-auto rounded-xl bg-white p-6"
        role="dialog"
        aria-modal="true"
      >

        <div class="mb-5 flex items-start justify-between">

          <div>
            <p class="mb-[5px] text-[11px] font-bold uppercase tracking-[0.14em] text-[#1E4F8A]">
              {{ isEditing ? 'Edit' : 'Pengajuan' }}
            </p>

            <h2 class="m-0 text-xl font-semibold">
              {{
                activeType === 'business'
                  ? 'Business Group'
                  : 'Individual'
              }}
            </h2>
          </div>


          <button
            type="button"
            class="flex h-[30px] w-[30px] items-center justify-center rounded-md border-0 bg-[#f1f5f9] text-xl text-[#64748b]"
            @click="closeForm"
          >
            ×
          </button>

        </div>


        <!-- BUSINESS FORM -->
        <form
          v-if="activeType === 'business'"
          @submit.prevent="submitBusiness"
        >

          <label class="mb-3.5 block text-xs font-semibold">
            Nama Group

            <input
              v-model="businessForm.groupName"
              type="text"
              placeholder="Contoh: JMO Jakarta"
              required
              class="mt-1.5 block w-full rounded-[7px] border border-[#d9dee7] bg-white px-2.5 py-2.5 text-[13px] font-normal text-[#172033] outline-none transition focus:border-[#1E4F8A]"
            />
          </label>


          <label class="mb-3.5 block text-xs font-semibold">
            Tujuan

            <input
              v-model="businessForm.destination"
              type="text"
              placeholder="Masukkan tujuan perjalanan"
              required
              class="mt-1.5 block w-full rounded-[7px] border border-[#d9dee7] bg-white px-2.5 py-2.5 text-[13px] font-normal text-[#172033] outline-none transition focus:border-[#1E4F8A]"
            />
          </label>


          <div class="grid grid-cols-2 gap-3 max-[600px]:grid-cols-1">

            <label class="mb-3.5 block text-xs font-semibold">
              Tanggal Mulai

              <input
                v-model="businessForm.startDate"
                type="date"
                required
                class="mt-1.5 block w-full rounded-[7px] border border-[#d9dee7] bg-white px-2.5 py-2.5 text-[13px] font-normal text-[#172033] outline-none transition focus:border-[#1E4F8A]"
              />
            </label>


            <label class="mb-3.5 block text-xs font-semibold">
              Tanggal Selesai

              <input
                v-model="businessForm.endDate"
                type="date"
                required
                class="mt-1.5 block w-full rounded-[7px] border border-[#d9dee7] bg-white px-2.5 py-2.5 text-[13px] font-normal text-[#172033] outline-none transition focus:border-[#1E4F8A]"
              />
            </label>

          </div>


          <label class="mb-3.5 block text-xs font-semibold">
            Peserta

            <textarea
              v-model="businessForm.participants"
              rows="4"
              placeholder="Masukkan nama peserta, satu nama per baris"
              required
              class="mt-1.5 block w-full resize-y rounded-[7px] border border-[#d9dee7] bg-white px-2.5 py-2.5 text-[13px] font-normal text-[#172033] outline-none transition focus:border-[#1E4F8A]"
            ></textarea>
          </label>


          <label class="mb-3.5 block text-xs font-semibold">
            Keperluan

            <textarea
              v-model="businessForm.purpose"
              rows="3"
              placeholder="Masukkan keperluan perjalanan"
              required
              class="mt-1.5 block w-full resize-y rounded-[7px] border border-[#d9dee7] bg-white px-2.5 py-2.5 text-[13px] font-normal text-[#172033] outline-none transition focus:border-[#1E4F8A]"
            ></textarea>
          </label>


          <div class="mt-5 flex justify-end gap-2">

            <button
              type="button"
              class="rounded-[7px] border-0 bg-[#f1f5f9] px-[15px] py-2.5 text-xs font-bold text-[#475569]"
              @click="closeForm"
            >
              Batal
            </button>

            <button
              type="submit"
              class="rounded-[7px] border-0 bg-[#1E4F8A] px-[15px] py-2.5 text-xs font-bold text-white"
            >
              {{ isEditing ? 'Simpan Perubahan' : 'Ajukan' }}
            </button>

          </div>

        </form>


        <!-- INDIVIDUAL FORM -->
        <form
          v-else
          @submit.prevent="submitIndividual"
        >

          <label class="mb-3.5 block text-xs font-semibold">
            Nama Karyawan

            <input
              v-model="individualForm.name"
              type="text"
              placeholder="Masukkan nama karyawan"
              required
              class="mt-1.5 block w-full rounded-[7px] border border-[#d9dee7] bg-white px-2.5 py-2.5 text-[13px] font-normal text-[#172033] outline-none transition focus:border-[#1E4F8A]"
            />
          </label>


          <label class="mb-3.5 block text-xs font-semibold">
            Employee ID

            <input
              v-model="individualForm.employeeId"
              type="text"
              placeholder="Contoh: EMP-001"
              required
              class="mt-1.5 block w-full rounded-[7px] border border-[#d9dee7] bg-white px-2.5 py-2.5 text-[13px] font-normal text-[#172033] outline-none transition focus:border-[#1E4F8A]"
            />
          </label>


          <label class="mb-3.5 block text-xs font-semibold">
            Tujuan

            <input
              v-model="individualForm.destination"
              type="text"
              placeholder="Masukkan tujuan perjalanan"
              required
              class="mt-1.5 block w-full rounded-[7px] border border-[#d9dee7] bg-white px-2.5 py-2.5 text-[13px] font-normal text-[#172033] outline-none transition focus:border-[#1E4F8A]"
            />
          </label>


          <div class="grid grid-cols-2 gap-3 max-[600px]:grid-cols-1">

            <label class="mb-3.5 block text-xs font-semibold">
              Tanggal Mulai

              <input
                v-model="individualForm.startDate"
                type="date"
                required
                class="mt-1.5 block w-full rounded-[7px] border border-[#d9dee7] bg-white px-2.5 py-2.5 text-[13px] font-normal text-[#172033] outline-none transition focus:border-[#1E4F8A]"
              />
            </label>


            <label class="mb-3.5 block text-xs font-semibold">
              Tanggal Selesai

              <input
                v-model="individualForm.endDate"
                type="date"
                required
                class="mt-1.5 block w-full rounded-[7px] border border-[#d9dee7] bg-white px-2.5 py-2.5 text-[13px] font-normal text-[#172033] outline-none transition focus:border-[#1E4F8A]"
              />
            </label>

          </div>


          <label class="mb-3.5 block text-xs font-semibold">
            Keperluan

            <textarea
              v-model="individualForm.purpose"
              rows="4"
              placeholder="Masukkan keperluan perjalanan"
              required
              class="mt-1.5 block w-full resize-y rounded-[7px] border border-[#d9dee7] bg-white px-2.5 py-2.5 text-[13px] font-normal text-[#172033] outline-none transition focus:border-[#1E4F8A]"
            ></textarea>
          </label>


          <div class="mt-5 flex justify-end gap-2">

            <button
              type="button"
              class="rounded-[7px] border-0 bg-[#f1f5f9] px-[15px] py-2.5 text-xs font-bold text-[#475569]"
              @click="closeForm"
            >
              Batal
            </button>

            <button
              type="submit"
              class="rounded-[7px] border-0 bg-[#1E4F8A] px-[15px] py-2.5 text-xs font-bold text-white"
            >
              {{ isEditing ? 'Simpan Perubahan' : 'Ajukan' }}
            </button>

          </div>

        </form>

      </div>
    </div>

  </div>
</template>


<script setup>
import {
  ref,
  computed,
  reactive
} from 'vue'


/* =========================
   STATE
========================= */

const activeType = ref('business')

const showForm = ref(false)

const search = ref('')

const isEditing = ref(false)

const editingId = ref(null)


/* =========================
   DATA
========================= */

const businessOrders = ref([])

const individualOrders = ref([])


/* =========================
   FORM
========================= */

const businessForm = reactive({
  groupName: '',
  destination: '',
  startDate: '',
  endDate: '',
  participants: '',
  purpose: ''
})


const individualForm = reactive({
  name: '',
  employeeId: '',
  destination: '',
  startDate: '',
  endDate: '',
  purpose: ''
})


/* =========================
   CURRENT DATA
========================= */

const currentOrders = computed(() => {
  return activeType.value === 'business'
    ? businessOrders.value
    : individualOrders.value
})


/* =========================
   STATISTICS
========================= */

const pending = computed(() => {
  return currentOrders.value.filter(
    order => order.status === 'pending'
  ).length
})


const approved = computed(() => {
  return currentOrders.value.filter(
    order => order.status === 'approved'
  ).length
})


const rejected = computed(() => {
  return currentOrders.value.filter(
    order => order.status === 'rejected'
  ).length
})


/* =========================
   SEARCH
========================= */

const filteredBusinessOrders = computed(() => {
  const keyword = search.value.toLowerCase().trim()

  return businessOrders.value.filter(order => {
    return (
      order.groupName
        .toLowerCase()
        .includes(keyword) ||

      order.destination
        .toLowerCase()
        .includes(keyword)
    )
  })
})


const filteredIndividualOrders = computed(() => {
  const keyword = search.value.toLowerCase().trim()

  return individualOrders.value.filter(order => {
    return (
      order.name
        .toLowerCase()
        .includes(keyword) ||

      order.employeeId
        .toLowerCase()
        .includes(keyword) ||

      order.destination
        .toLowerCase()
        .includes(keyword)
    )
  })
})


/* =========================
   OPEN FORM
========================= */

const openForm = () => {
  isEditing.value = false
  editingId.value = null

  resetForms()

  showForm.value = true
}


/* =========================
   CLOSE FORM
========================= */

const closeForm = () => {
  showForm.value = false

  resetForms()

  isEditing.value = false
  editingId.value = null
}


/* =========================
   RESET
========================= */

const resetForms = () => {
  businessForm.groupName = ''
  businessForm.destination = ''
  businessForm.startDate = ''
  businessForm.endDate = ''
  businessForm.participants = ''
  businessForm.purpose = ''

  individualForm.name = ''
  individualForm.employeeId = ''
  individualForm.destination = ''
  individualForm.startDate = ''
  individualForm.endDate = ''
  individualForm.purpose = ''
}


/* =========================
   DATE VALIDATION
========================= */

const validDate = (start, end) => {
  if (!start || !end) {
    return false
  }

  return new Date(end) >= new Date(start)
}


/* =========================
   BUSINESS SUBMIT
========================= */

const submitBusiness = () => {
  if (
    !validDate(
      businessForm.startDate,
      businessForm.endDate
    )
  ) {
    alert(
      'Tanggal selesai harus sama atau setelah tanggal mulai.'
    )

    return
  }


  const participants =
    businessForm.participants
      .split('\n')
      .map(name => name.trim())
      .filter(Boolean)


  if (participants.length === 0) {
    alert('Peserta belum diisi.')

    return
  }


  if (isEditing.value) {

    const index =
      businessOrders.value.findIndex(
        order => order.id === editingId.value
      )


    if (index !== -1) {
      businessOrders.value[index] = {
        ...businessOrders.value[index],

        groupName:
          businessForm.groupName,

        destination:
          businessForm.destination,

        startDate:
          businessForm.startDate,

        endDate:
          businessForm.endDate,

        participants,

        purpose:
          businessForm.purpose
      }
    }

  } else {

    businessOrders.value.push({

      id: Date.now(),

      groupId:
        `GRP-${Date.now()}`,

      groupName:
        businessForm.groupName,

      destination:
        businessForm.destination,

      startDate:
        businessForm.startDate,

      endDate:
        businessForm.endDate,

      participants,

      purpose:
        businessForm.purpose,

      status:
        'pending'
    })
  }


  closeForm()
}


/* =========================
   INDIVIDUAL SUBMIT
========================= */

const submitIndividual = () => {

  if (
    !validDate(
      individualForm.startDate,
      individualForm.endDate
    )
  ) {
    alert(
      'Tanggal selesai harus sama atau setelah tanggal mulai.'
    )

    return
  }


  if (isEditing.value) {

    const index =
      individualOrders.value.findIndex(
        order => order.id === editingId.value
      )


    if (index !== -1) {

      individualOrders.value[index] = {

        ...individualOrders.value[index],

        name:
          individualForm.name,

        employeeId:
          individualForm.employeeId,

        destination:
          individualForm.destination,

        startDate:
          individualForm.startDate,

        endDate:
          individualForm.endDate,

        purpose:
          individualForm.purpose
      }
    }

  } else {

    individualOrders.value.push({

      id: Date.now(),

      name:
        individualForm.name,

      employeeId:
        individualForm.employeeId,

      destination:
        individualForm.destination,

      startDate:
        individualForm.startDate,

      endDate:
        individualForm.endDate,

      purpose:
        individualForm.purpose,

      status:
        'pending'
    })
  }


  closeForm()
}


/* =========================
   EDIT
========================= */

const editOrder = (order) => {

  isEditing.value = true

  editingId.value = order.id

  resetForms()


  if (activeType.value === 'business') {

    businessForm.groupName =
      order.groupName

    businessForm.destination =
      order.destination

    businessForm.startDate =
      order.startDate

    businessForm.endDate =
      order.endDate

    businessForm.participants =
      order.participants.join('\n')

    businessForm.purpose =
      order.purpose

  } else {

    individualForm.name =
      order.name

    individualForm.employeeId =
      order.employeeId

    individualForm.destination =
      order.destination

    individualForm.startDate =
      order.startDate

    individualForm.endDate =
      order.endDate

    individualForm.purpose =
      order.purpose
  }


  showForm.value = true
}


/* =========================
   DELETE
========================= */

const deleteOrder = (id, type) => {

  const confirmed =
    confirm(
      'Yakin ingin menghapus pengajuan ini?'
    )


  if (!confirmed) {
    return
  }


  if (type === 'business') {

    businessOrders.value =
      businessOrders.value.filter(
        order => order.id !== id
      )

  } else {

    individualOrders.value =
      individualOrders.value.filter(
        order => order.id !== id
      )
  }
}


/* =========================
   STATUS LABEL
========================= */

const getStatusLabel = (status) => {

  return {

    pending: 'Menunggu',

    approved: 'Disetujui',

    rejected: 'Ditolak'

  }[status]
}
</script>
