import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useToastStore = defineStore('toast', () => {
  const queue = ref([])

  function add(message, color = 'info') {
    const id = Date.now() + Math.random()
    queue.value.push({ id, message, color })
    setTimeout(() => {
      queue.value = queue.value.filter(t => t.id !== id)
    }, 3000)
  }

  return {
    queue,
    success: message => add(message, 'success'),
    error: message => add(message, 'error'),
    warning: message => add(message, 'warning'),
    info: message => add(message, 'info'),
  }
})
