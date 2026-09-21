import { reactive } from 'vue'

const state = reactive({
  show: false,
  title: 'Are you sure?',
  text: '',
  color: 'primary',
  resolve: null,
})

export function useConfirm() {
  function confirm({ title = 'Are you sure?', text = '', color = 'primary' } = {}) {
    state.title = title
    state.text = text
    state.color = color
    state.show = true

    return new Promise(resolve => {
      state.resolve = resolve
    })
  }

  function respond(result) {
    state.show = false
    state.resolve?.(result)
    state.resolve = null
  }

  return { state, confirm, respond }
}
