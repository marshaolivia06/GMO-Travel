<template>
  <div
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

      <form @submit.prevent="submitCategory">
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
            {{ loading ? 'Adding...' : 'Add' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { createCategory } from '../../../services/categoryService'
import swal from '../../../../../plugins/swal'

const emit = defineEmits(['close', 'success'])

const categoryName = ref('')
const loading = ref(false)

const closeModal = () => {
  if (loading.value) return

  emit('close')
}

const submitCategory = async () => {
  if (loading.value) return

  const name = categoryName.value.trim()

  if (!name) {
    await swal.error(
      'Invalid Category',
      'Category name is required.'
    )
    return
  }

  const result = await swal.confirm(
    'Are you sure you want to add this category?'
  )

  if (!result.isConfirmed) return

  loading.value = true

  try {
    await createCategory({ name })

    await swal.success(
      'Category Added',
      'Category has been added successfully.'
    )

    emit('success')
    emit('close')
  } catch (error) {
    await swal.error(
      'Action Failed',
      error.message || 'Failed to create category.'
    )
  } finally {
    loading.value = false
  }
}
</script>