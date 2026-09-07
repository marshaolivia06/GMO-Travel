import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useToastStore = defineStore('toast', () => {
  const show = ref(false)
  const type = ref('info')
  const title = ref('')
  const message = ref('')

  let timer = null

  function close() {
    show.value = false

    if (timer) {
      clearTimeout(timer)
      timer = null
    }
  }

  function showToast(newTitle, newMessage, newType = 'info') {
    title.value = newTitle
    message.value = newMessage
    type.value = newType
    show.value = true

    if (timer) {
      clearTimeout(timer)
    }

    timer = setTimeout(() => {
      close()
    }, 2500)
  }

  function success(newTitle, newMessage) {
    showToast(newTitle, newMessage, 'success')
  }

  function error(newTitle, newMessage) {
    showToast(newTitle, newMessage, 'error')
  }

  function warning(newTitle, newMessage) {
    showToast(newTitle, newMessage, 'warning')
  }

  function info(newTitle, newMessage) {
    showToast(newTitle, newMessage, 'info')
  }

  return {
    show,
    type,
    title,
    message,
    showToast,
    success,
    error,
    warning,
    info,
    close
  }
})
