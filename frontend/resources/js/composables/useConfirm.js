import { reactive } from 'vue'

const state = reactive({
  isOpen: false,
  title: '',
  text: '',
  confirmText: 'Confirm',
  cancelText: 'Cancel',
  color: 'primary',
  loading: false,
  resolve: null,
})

export function useConfirm() {
  function confirm({ title = 'Confirmation', text = '', confirmText = 'Confirm', cancelText = 'Cancel', color = 'primary' } = {}) {
    state.title = title
    state.text = text
    state.confirmText = confirmText
    state.cancelText = cancelText
    state.color = color
    state.isOpen = true

    return new Promise(resolve => {
      state.resolve = resolve
    })
  }

  function onConfirm() {
    state.isOpen = false
    state.resolve?.(true)
  }

  function onCancel() {
    state.isOpen = false
    state.resolve?.(false)
  }

  return { state, confirm, onConfirm, onCancel }
}
