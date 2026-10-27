// src/stores/toast.js
import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useToastStore = defineStore('toast', () => {
  const toasts = ref([])

  function add(message, type = 'info') {
    const id = Date.now()

    toasts.value.push({ id, message, type })

    // Auto hapus setelah 3 detik
    setTimeout(() => remove(id), 3000)
  }

  function remove(id) {
    toasts.value = toasts.value.filter(t => t.id !== id)
  }

  // Shorthand
  const success = msg => add(msg, 'success')
  const error   = msg => add(msg, 'error')
  const warning = msg => add(msg, 'warning')
  const info    = msg => add(msg, 'info')

  return { toasts, add, remove, success, error, warning, info }
})