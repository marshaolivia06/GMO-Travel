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
            Edit Section
          </h2>
  
          <p class="mt-1 text-xs text-slate-400">
            Update the section information.
          </p>
        </div>
  
        <form @submit.prevent="submitSection">
          <div class="space-y-4">
            <!-- Section Name -->
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
  
            <!-- Sect Head -->
            <div>
              <label
                class="mb-1.5 block text-xs font-semibold text-slate-600"
              >
                Sect Head
              </label>
  
              <SelectTo
                v-model="form.section_head_id"
                :options="options.section_heads"
                placeholder="Select Sect Head"
                :loading="optionsLoading"
                :disabled="loading || optionsLoading"
                clearable
              />
  
              <p
                v-if="errors.section_head_id"
                class="mt-1 text-xs text-red-600"
              >
                {{ errors.section_head_id }}
              </p>
            </div>
  
            <!-- Department -->
            <div>
              <label
                class="mb-1.5 block text-xs font-semibold text-slate-600"
              >
                Department
              </label>
  
              <SelectTo
                v-model="form.department_id"
                :options="options.departments"
                placeholder="Select department"
                :loading="optionsLoading"
                :disabled="loading || optionsLoading"
                clearable
              />
  
              <p
                v-if="errors.department_id"
                class="mt-1 text-xs text-red-600"
              >
                {{ errors.department_id }}
              </p>
            </div>
  
            <!-- General Error -->
            <p
              v-if="generalError"
              class="rounded-lg bg-red-50 px-3 py-2 text-xs text-red-600"
            >
              {{ generalError }}
            </p>
          </div>
  
          <!-- Actions -->
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
              {{ loading ? 'Updating...' : 'Update' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </template>
  
  <script setup>
  import { onMounted, reactive, ref } from 'vue'
  import { getSection, getSectionOptions, updateSection } from '../../../services/sectionService'
  import SelectTo from '../../../../../components/SelectTo.vue'
  import swal from '../../../../../plugins/swal'
  
  const props = defineProps({
    section: {
      type: Object,
      required: true,
    },
  })
  
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
  
  const clearErrors = () => {
    errors.name = ''
    errors.section_head_id = ''
    errors.department_id = ''
    generalError.value = ''
  }
  
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
  
  const loadSection = async () => {
    try {
      const response = await getSection(props.section.id)
      const section = response.data ?? response
  
      form.name = section.name ?? ''
      form.section_head_id =
        section.section_head_id ??
        section.sectionHead?.id ??
        ''
  
      form.department_id =
        section.department_id ??
        section.department?.id ??
        ''
    } catch (err) {
      generalError.value =
        err.message || 'Failed to load section.'
    }
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
      'Are you sure you want to update this section?'
    )
  
    if (!result.isConfirmed) return
  
    loading.value = true
  
    try {
      await updateSection(props.section.id, {
        name: form.name.trim(),
        section_head_id: form.section_head_id,
        department_id: form.department_id,
      })
  
      emit('success')
      emit('close')
  
      await swal.success(
        'Section Updated',
        'Section has been updated successfully.'
      )
    } catch (err) {
      const message =
        err?.response?.data?.message ||
        err?.message ||
        'Failed to update section.'
  
      await swal.error('Action Failed', message)
    } finally {
      loading.value = false
    }
  }
  
  const close = () => {
    if (!loading.value) {
      emit('close')
    }
  }
  
  onMounted(async () => {
    await loadOptions()
    await loadSection()
  })  
  </script>
  