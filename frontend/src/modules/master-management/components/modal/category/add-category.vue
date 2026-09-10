<template>
    <!-- ADD CATEGORY MODAL -->
    <div
  v-if="!showConfirmation"
  class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/50 p-5"
      @click.self="closeModal"
    >
      <div
        class="w-[420px] max-w-full rounded-xl bg-white p-[25px] shadow-[0_20px_50px_rgba(15,23,42,0.20)]"
      >
        <div class="mb-5">
          <h2 class="text-xl font-semibold text-[#172033]">
            Add Category
          </h2>
  
          <p class="mt-1 text-xs text-slate-400">
            Create a new category.
          </p>
        </div>
  
        <form @submit.prevent="openConfirmation">
          <div>
            <label
              class="mb-2 block text-xs font-semibold text-slate-600"
            >
              Category Name
            </label>
  
            <input
              v-model="categoryName"
              type="text"
              placeholder="Enter category name"
              class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-[#1E4F8A] focus:ring-2 focus:ring-[#1E4F8A]/10"
              :disabled="loading"
              autofocus
            />
  
          </div>
  
          <div class="mt-6 flex justify-end gap-2.5">
            <button
              type="button"
              class="min-w-[90px] rounded-lg bg-slate-100 px-5 py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="loading"
              @click="closeModal"
            >
              Cancel
            </button>
  
            <button
              type="submit"
              class="min-w-[90px] rounded-lg bg-green-600 px-5 py-2.5 text-xs font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="loading || !categoryName.trim()"
            >
              Add
            </button>
          </div>
        </form>
      </div>
    </div>
  
    <!-- CONFIRMATION MODAL -->
    <div
  v-if="showConfirmation"
  class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/50 p-5"
      @click.self="cancelConfirmation"
    >
      <div
        class="w-[400px] max-w-full rounded-xl bg-white p-[30px_25px] text-center shadow-[0_20px_50px_rgba(15,23,42,0.20)]"
      >
        <div
          class="mx-auto mb-[18px] flex h-[54px] w-[54px] items-center justify-center rounded-full bg-[#eaf2f9] text-[27px] font-bold text-[#1E4F8A]"
        >
          ?
        </div>
  
        <p class="m-0 text-base font-medium leading-[1.6] text-[#172033]">
          Are you sure you want to add this category?
        </p>
  
        <div class="mt-[25px] flex items-center justify-center gap-2.5">
          <button
            type="button"
            class="min-w-[90px] rounded-lg bg-slate-100 px-5 py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="loading"
            @click="cancelConfirmation"
          >
            Cancel
          </button>
  
          <button
            type="button"
            class="min-w-[90px] rounded-lg bg-green-600 px-5 py-2.5 text-xs font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="loading"
            @click="submitCategory"
          >
            {{ loading ? 'Adding...' : 'Yes' }}
          </button>
        </div>
      </div>
    </div>

  </template>
  
  <script setup>
  import {
    ref,
  } from 'vue'
  
  import {
  createCategory,
} from '../../../services/categoryService'

import { useToastStore } from '../../../../../stores/toast'
  
  const emit = defineEmits([
    'close',
    'success',
  ])
  const toast = useToastStore()
  
  const categoryName = ref('')
  const loading = ref(false)
  const errorMessage = ref('')
  
  const showConfirmation = ref(false)
  
  const closeModal = () => {
    if (loading.value) {
      return
    }
  
    emit('close')
  }
  
  const openConfirmation = () => {
    const name = categoryName.value.trim()
  
    if (!name) {
      errorMessage.value = 'Category name is required.'
      return
    }
  
    errorMessage.value = ''
    showConfirmation.value = true
  }
  
  const cancelConfirmation = () => {
    if (loading.value) {
      return
    }
  
    showConfirmation.value = false
  }
  
  const submitCategory = async () => {
  if (loading.value) return

  const name = categoryName.value.trim()

  if (!name) {
    showConfirmation.value = false
    errorMessage.value = 'Category name is required.'
    return
  }

  loading.value = true
  errorMessage.value = ''

  try {
    await createCategory({
      name,
    })

    toast.success(
      'Category Added',
      'Category has been added successfully.'
    )

    showConfirmation.value = false

    emit('success')
    emit('close')
  } catch (error) {
    errorMessage.value =
      error.message ||
      'Failed to create category.'

    toast.error(
      'Action Failed',
      errorMessage.value
    )

    showConfirmation.value = false
  } finally {
    loading.value = false
  }
}
  
  </script>