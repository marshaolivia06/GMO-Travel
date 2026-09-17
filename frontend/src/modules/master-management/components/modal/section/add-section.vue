<template>
    <div
      class="fixed inset-0 z-[999999] flex items-center justify-center bg-slate-900/45 p-5"
      @click.self="close"
    >
      <div
        class="w-[500px] max-w-full rounded-xl bg-white p-6 shadow-[0_20px_50px_rgba(15,23,42,0.20)]"
      >
        <div class="mb-5">
          <h2 class="text-lg font-semibold text-[#172033]">
            Add Section
          </h2>
  
          <p class="mt-1 text-xs text-slate-400">
            Create a new section.
          </p>
        </div>
  
        <form @submit.prevent="submitSection">
          <div class="space-y-4">
            <div>
              <label
                class="mb-1.5 block text-xs font-semibold text-slate-600"
              >
                Section Name
              </label>
  
              <input
                v-model="form.name"
                type="text"
                placeholder="Enter section name"
                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-[#1E4F8A] focus:ring-2 focus:ring-[#1E4F8A]/10"
                :disabled="loading"
              />
  
              <p
                v-if="errors.name"
                class="mt-1 text-xs text-red-600"
              >
                {{ errors.name }}
              </p>
            </div>
  
            <div>
              <label
                class="mb-1.5 block text-xs font-semibold text-slate-600"
              >
                Sect Head
              </label>
  
              <select
                v-model="form.section_head_id"
                :class="[
                  'w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#1E4F8A] focus:ring-2 focus:ring-[#1E4F8A]/10',
                  form.section_head_id
                    ? 'text-slate-700'
                    : 'text-slate-400'
                ]"
                :disabled="loading || optionsLoading"
              >
                <option value="" disabled>
                  Select Sect Head
                </option>
  
                <option
                  v-for="user in options.section_heads"
                  :key="user.id"
                  :value="user.id"
                  class="text-slate-700"
                >
                  {{ user.name }}
                </option>
              </select>
  
              <p
                v-if="errors.section_head_id"
                class="mt-1 text-xs text-red-600"
              >
                {{ errors.section_head_id }}
              </p>
            </div>
  
            <div>
              <label
                class="mb-1.5 block text-xs font-semibold text-slate-600"
              >
                Department
              </label>
  
              <select
                v-model="form.department_id"
                :class="[
                  'w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#1E4F8A] focus:ring-2 focus:ring-[#1E4F8A]/10',
                  form.department_id
                    ? 'text-slate-700'
                    : 'text-slate-400'
                ]"
                :disabled="loading || optionsLoading"
              >
                <option value="" disabled>
                  Select department
                </option>
  
                <option
                  v-for="department in options.departments"
                  :key="department.id"
                  :value="department.id"
                  class="text-slate-700"
                >
                  {{ department.name }}
                </option>
              </select>
  
              <p
                v-if="errors.department_id"
                class="mt-1 text-xs text-red-600"
              >
                {{ errors.department_id }}
              </p>
            </div>
  
            <p
              v-if="generalError"
              class="rounded-lg bg-red-50 px-3 py-2 text-xs text-red-600"
            >
              {{ generalError }}
            </p>
          </div>
  
          <div class="mt-6 flex justify-end gap-2.5">
            <button
              type="button"
              class="rounded-lg bg-slate-100 px-5 py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-200 disabled:opacity-60"
              :disabled="loading"
              @click="close"
            >
              Cancel
            </button>
  
            <button
              type="submit"
              class="rounded-lg bg-green-600 px-5 py-2.5 text-xs font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="loading || optionsLoading"
            >
              {{ loading ? 'Saving...' : 'Add' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </template>
  
  <script setup>
  import { onMounted, reactive, ref } from 'vue'
  import {
    createSection,
    getSectionOptions,
  } from '../../../services/sectionService'
  import swal from '../../../../../plugins/swal'
  
  const emit = defineEmits(['close', 'success'])
  
  const loading = ref(false)
  const optionsLoading = ref(false)
  const generalError = ref('')
  
  const errors = reactive({
    name: '',
    section_head_id: '',
    department_id: '',
  })
  
  const form = reactive({
    name: '',
    section_head_id: '',
    department_id: '',
  })
  
  const options = reactive({
    section_heads: [],
    departments: [],
  })
  
  const loadOptions = async () => {
    optionsLoading.value = true
    generalError.value = ''
  
    try {
      const response = await getSectionOptions()
  
      options.section_heads = response.section_heads ?? []
      options.departments = response.departments ?? []
    } catch (err) {
      generalError.value =
        err.message || 'Failed to load section options.'
    } finally {
      optionsLoading.value = false
    }
  }
  
  const clearErrors = () => {
    errors.name = ''
    errors.section_head_id = ''
    errors.department_id = ''
    generalError.value = ''
  }
  
  const submitSection = async () => {
    if (loading.value) return
  
    clearErrors()
  
    if (!form.name.trim()) {
      errors.name = 'Section name is required.'
      return
    }
  
    if (!form.section_head_id) {
      errors.section_head_id = 'Sect Head is required.'
      return
    }
  
    if (!form.department_id) {
      errors.department_id = 'Department is required.'
      return
    }
  
    const result = await swal.confirm(
      'Are you sure you want to add this section?'
    )
  
    if (!result.isConfirmed) return
  
    loading.value = true
  
    try {
      await createSection({
        name: form.name.trim(),
        section_head_id: form.section_head_id,
        department_id: form.department_id,
      })
  
      emit('success')
      emit('close')
  
      await swal.success(
        'Section Added',
        'Section has been added successfully.'
      )
    } catch (err) {
      const message =
        err?.response?.data?.message ||
        err?.message ||
        'Failed to create section.'
  
      await swal.error(
        'Action Failed',
        message
      )
    } finally {
      loading.value = false
    }
  }
  
  const close = () => {
    if (!loading.value) {
      emit('close')
    }
  }
  
  onMounted(loadOptions)
  </script>
  