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
            Edit Department
          </h2>
  
          <p class="mt-1 text-xs text-slate-400">
            Update department information.
          </p>
        </div>
  
        <form @submit.prevent="submitDepartment">
          <div class="space-y-4">
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600">
                Department Name
              </label>
  
              <input
                v-model="form.name"
                type="text"
                placeholder="Enter department name"
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
              <label class="mb-1.5 block text-xs font-semibold text-slate-600">
                Category
              </label>
  
              <select
                v-model="form.category_id"
                :class="[
                  'w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#1E4F8A] focus:ring-2 focus:ring-[#1E4F8A]/10',
                  form.category_id ? 'text-slate-700' : 'text-slate-400'
                ]"
                :disabled="loading || optionsLoading"
              >
                <option value="" disabled>
                  Select category
                </option>
  
                <option
                  v-for="category in options.categories"
                  :key="category.id"
                  :value="category.id"
                  class="text-slate-700"
                >
                  {{ category.name }}
                </option>
              </select>
  
              <p
                v-if="errors.category_id"
                class="mt-1 text-xs text-red-600"
              >
                {{ errors.category_id }}
              </p>
            </div>
  
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600">
                Department Head
              </label>
  
              <select
                v-model="form.dept_head_id"
                :class="[
                  'w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#1E4F8A] focus:ring-2 focus:ring-[#1E4F8A]/10',
                  form.dept_head_id ? 'text-slate-700' : 'text-slate-400'
                ]"
                :disabled="loading || optionsLoading"
              >
                <option value="" disabled>
                  Select department head
                </option>
  
                <option
                  v-for="user in options.dept_heads"
                  :key="user.id"
                  :value="user.id"
                  class="text-slate-700"
                >
                  {{ user.name }}
                </option>
              </select>
  
              <p
                v-if="errors.dept_head_id"
                class="mt-1 text-xs text-red-600"
              >
                {{ errors.dept_head_id }}
              </p>
            </div>
  
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600">
                Department Admin
              </label>
  
              <select
                v-model="form.dept_admin_id"
                :class="[
                  'w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#1E4F8A] focus:ring-2 focus:ring-[#1E4F8A]/10',
                  form.dept_admin_id ? 'text-slate-700' : 'text-slate-400'
                ]"
                :disabled="loading || optionsLoading"
              >
                <option value="" disabled>
                  Select department admin
                </option>
  
                <option
                  v-for="user in options.dept_admins"
                  :key="user.id"
                  :value="user.id"
                  class="text-slate-700"
                >
                  {{ user.name }}
                </option>
              </select>
  
              <p
                v-if="errors.dept_admin_id"
                class="mt-1 text-xs text-red-600"
              >
                {{ errors.dept_admin_id }}
              </p>
            </div>
  
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600">
                Division Head
              </label>
  
              <select
                v-model="form.division_head_id"
                :class="[
                  'w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#1E4F8A] focus:ring-2 focus:ring-[#1E4F8A]/10',
                  form.division_head_id ? 'text-slate-700' : 'text-slate-400'
                ]"
                :disabled="loading || optionsLoading"
              >
                <option value="" disabled>
                  Select division head
                </option>
  
                <option
                  v-for="division in options.division_heads"
                  :key="division.id"
                  :value="division.id"
                  class="text-slate-700"
                >
                  {{ division.division_name }}
                </option>
              </select>
  
              <p
                v-if="errors.division_head_id"
                class="mt-1 text-xs text-red-600"
              >
                {{ errors.division_head_id }}
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
              {{ loading ? 'Updating...' : 'Update' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </template>
  
  <script setup>
  import { onMounted, reactive, ref } from 'vue'
  import {
    updateDepartment,
    getDepartmentOptions,
  } from '../../../services/departmentService'
  import swal from '../../../../../plugins/swal'
  
  const props = defineProps({
    department: {
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
    category_id: '',
    dept_head_id: '',
    dept_admin_id: '',
    division_head_id: '',
  })
  
  const form = reactive({
    name: props.department?.name || '',
    category_id:
      props.department?.category_id ??
      props.department?.category?.id ??
      '',
    dept_head_id:
      props.department?.dept_head_id ??
      props.department?.dept_head?.id ??
      '',
    dept_admin_id:
      props.department?.dept_admin_id ??
      props.department?.dept_admin?.id ??
      '',
    division_head_id:
      props.department?.division_head_id ??
      props.department?.division_head?.id ??
      '',
  })
  
  const options = reactive({
    categories: [],
    dept_heads: [],
    dept_admins: [],
    division_heads: [],
  })
  
  const loadOptions = async () => {
    optionsLoading.value = true
    generalError.value = ''
  
    try {
      const response = await getDepartmentOptions()
  
      options.categories = response.categories ?? []
      options.dept_heads = response.dept_heads ?? []
      options.dept_admins = response.dept_admins ?? []
      options.division_heads = response.division_heads ?? []
    } catch (err) {
      generalError.value =
        err?.message ||
        'Failed to load department options.'
    } finally {
      optionsLoading.value = false
    }
  }
  
  const clearErrors = () => {
    errors.name = ''
    errors.category_id = ''
    errors.dept_head_id = ''
    errors.dept_admin_id = ''
    errors.division_head_id = ''
    generalError.value = ''
  }
  
  const handleValidationError = err => {
    const validationErrors = err?.response?.data?.errors
  
    if (!validationErrors) return false
  
    errors.name = validationErrors.name?.[0] || ''
    errors.category_id =
      validationErrors.category_id?.[0] || ''
    errors.dept_head_id =
      validationErrors.dept_head_id?.[0] || ''
    errors.dept_admin_id =
      validationErrors.dept_admin_id?.[0] || ''
    errors.division_head_id =
      validationErrors.division_head_id?.[0] || ''
  
    return true
  }
  
  const submitDepartment = async () => {
    if (loading.value) return
  
    clearErrors()
  
    if (!form.name.trim()) {
      errors.name = 'Department name is required.'
      return
    }
  
    if (!form.category_id) {
      errors.category_id = 'Category is required.'
      return
    }
  
    if (!form.dept_head_id) {
      errors.dept_head_id = 'Department head is required.'
      return
    }
  
    if (!form.dept_admin_id) {
      errors.dept_admin_id = 'Department admin is required.'
      return
    }
  
    if (!form.division_head_id) {
      errors.division_head_id = 'Division head is required.'
      return
    }
  
    const result = await swal.confirm(
      'Are you sure you want to update this department?'
    )
  
    if (!result.isConfirmed) return
  
    loading.value = true
  
    try {
      await updateDepartment(props.department.id, {
        name: form.name.trim(),
        category_id: form.category_id,
        dept_head_id: form.dept_head_id,
        dept_admin_id: form.dept_admin_id,
        division_head_id: form.division_head_id,
      })
  
      emit('success')
      emit('close')
  
      await swal.success(
        'Department Updated',
        'Department has been updated successfully.'
      )
    } catch (err) {
      handleValidationError(err)
  
      const message =
        err?.response?.data?.message ||
        err?.message ||
        'Failed to update department.'
  
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
  