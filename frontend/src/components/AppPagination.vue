<script setup>
import { computed } from 'vue'

const props = defineProps({
  currentPage: { type: Number, required: true },
  lastPage: { type: Number, required: true },
  total: { type: Number, required: true },
  perPage: { type: Number, required: true },
})

const emit = defineEmits(['change'])

const from = computed(() =>
  props.total === 0
    ? 0
    : (props.currentPage - 1) * props.perPage + 1
)

const to = computed(() =>
  Math.min(
    props.currentPage * props.perPage,
    props.total
  )
)
</script>

<template>
  <div
    class="flex items-center justify-between border-t border-gray-200 px-6 py-4"
  >
    <p class="text-sm text-gray-500">
      Showing
      <span class="font-medium text-gray-700">
        {{ from }}
      </span>
      to
      <span class="font-medium text-gray-700">
        {{ to }}
      </span>
      of
      <span class="font-medium text-gray-700">
        {{ total }}
      </span>
      results
    </p>

    <div
      class="flex items-center overflow-hidden rounded-lg border border-gray-300 divide-x divide-gray-300"
    >
      <button
        type="button"
        @click="emit('change', 1)"
        :disabled="currentPage === 1"
        class="px-3 py-1.5 text-sm text-gray-600 transition-colors hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent"
      >
        First
      </button>

      <button
        type="button"
        @click="emit('change', currentPage - 1)"
        :disabled="currentPage === 1"
        class="px-3 py-1.5 text-sm text-gray-600 transition-colors hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent"
      >
        Previous
      </button>

      <span
        class="whitespace-nowrap bg-gray-50 px-3 py-1.5 text-sm font-medium text-gray-700"
      >
        Page {{ currentPage }} of {{ lastPage }}
      </span>

      <button
        type="button"
        @click="emit('change', currentPage + 1)"
        :disabled="currentPage === lastPage"
        class="px-3 py-1.5 text-sm text-gray-600 transition-colors hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent"
      >
        Next
      </button>

      <button
        type="button"
        @click="emit('change', lastPage)"
        :disabled="currentPage === lastPage"
        class="px-3 py-1.5 text-sm text-gray-600 transition-colors hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent"
      >
        Last
      </button>
    </div>
  </div>
</template>
 