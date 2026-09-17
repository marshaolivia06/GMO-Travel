<template>
    <div ref="selectRef" class="relative w-full">
      <!-- Select Button -->
      <button
        type="button"
        class="flex w-full items-center justify-between gap-2 rounded-lg border bg-white px-3 py-2.5 text-left text-sm outline-none transition"
        :class="[
          isOpen
            ? 'border-[#1E4F8A] ring-2 ring-[#1E4F8A]/10'
            : 'border-slate-300 hover:border-slate-400',
          disabled
            ? 'cursor-not-allowed bg-slate-50 opacity-60'
            : 'cursor-pointer',
        ]"
        :disabled="disabled"
        @click="toggleDropdown"
      >
        <span
          class="min-w-0 flex-1 truncate"
          :class="
            selectedLabel
              ? 'text-slate-700'
              : 'text-slate-400'
          "
        >
          {{ selectedLabel || placeholder }}
        </span>
  
        <div class="flex shrink-0 items-center gap-1">
          <!-- Clear -->
          <span
            v-if="modelValue && clearable && !disabled"
            class="flex h-5 w-5 cursor-pointer items-center justify-center rounded-full text-slate-400 transition hover:bg-red-50 hover:text-red-500"
            title="Clear"
            @click.stop="clearSelection"
          >
            <X class="h-3.5 w-3.5" />
          </span>
  
          <!-- Chevron -->
          <ChevronDown
            class="h-4 w-4 text-slate-400 transition-transform duration-200"
            :class="{ 'rotate-180': isOpen }"
          />
        </div>
      </button>
  
      <!-- Dropdown -->
      <Transition
        enter-active-class="transition duration-100 ease-out"
        enter-from-class="translate-y-[-4px] opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-75 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-[-4px] opacity-0"
      >
        <div
          v-if="isOpen"
          class="absolute left-0 right-0 z-[100] overflow-hidden rounded-lg border border-slate-200 bg-white shadow-[0_10px_30px_rgba(15,23,42,0.12)]"
          :class="
            placement === 'top'
              ? 'bottom-full mb-1'
              : 'top-full mt-1'
          "
        >
          <!-- Search -->
          <div class="border-b border-slate-100 p-2">
            <div class="relative">
              <Search
                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
              />
  
              <input
                ref="searchInput"
                v-model="search"
                type="text"
                placeholder="Search..."
                class="w-full rounded-md border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-xs text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-[#1E4F8A] focus:bg-white focus:ring-2 focus:ring-[#1E4F8A]/10"
                @click.stop
              />
            </div>
          </div>
  
          <!-- Options -->
          <div class="max-h-52 overflow-y-auto p-1.5">
            <!-- Loading -->
            <div
              v-if="loading"
              class="flex items-center justify-center gap-2 px-3 py-4 text-xs text-slate-400"
            >
              <span
                class="h-4 w-4 animate-spin rounded-full border-2 border-slate-200 border-t-[#1E4F8A]"
              />
              Loading...
            </div>
  
            <!-- Empty -->
            <div
              v-else-if="filteredOptions.length === 0"
              class="px-3 py-3 text-center"
            >
              <p class="text-xs font-medium text-slate-500">
                No options found
              </p>
            </div>
  
            <!-- Options -->
            <button
              v-for="option in filteredOptions"
              :key="option[valueKey]"
              type="button"
              class="mb-0.5 flex w-full items-center rounded-md px-3 py-2.5 text-left text-sm transition-colors duration-100 last:mb-0"
              :class="
                isSelected(option)
                  ? 'bg-[#EAF3FA] font-medium text-[#1E4F8A] hover:bg-[#DCEBF7]'
                  : 'text-slate-700 hover:bg-slate-100 hover:text-[#1E4F8A]'
              "
              @click="selectOption(option)"
            >
              <span class="min-w-0 flex-1 truncate">
                {{ option[labelKey] }}
              </span>
            </button>
          </div>
        </div>
      </Transition>
    </div>
  </template>
  
  <script setup>
  import { computed, nextTick, onBeforeUnmount, ref } from 'vue'
  import { ChevronDown, Search, X } from 'lucide-vue-next'
  
  const props = defineProps({
    modelValue: {
      type: [String, Number, null],
      default: '',
    },
  
    options: {
      type: Array,
      default: () => [],
    },
  
    labelKey: {
      type: String,
      default: 'name',
    },
  
    valueKey: {
      type: String,
      default: 'id',
    },
  
    placeholder: {
      type: String,
      default: 'Select option',
    },
  
    disabled: {
      type: Boolean,
      default: false,
    },
  
    loading: {
      type: Boolean,
      default: false,
    },
  
    clearable: {
      type: Boolean,
      default: true,
    },
  })
  
  const emit = defineEmits(['update:modelValue'])
  
  const selectRef = ref(null)
  const searchInput = ref(null)
  
  const isOpen = ref(false)
  const search = ref('')
  const placement = ref('bottom')
  
  const selectedOption = computed(() => {
    return props.options.find(
      option =>
        String(option[props.valueKey]) ===
        String(props.modelValue)
    )
  })
  
  const selectedLabel = computed(() => {
    return selectedOption.value?.[props.labelKey] || ''
  })
  
  const filteredOptions = computed(() => {
    const keyword = search.value
      .trim()
      .toLowerCase()
  
    if (!keyword) {
      return props.options
    }
  
    return props.options.filter(option => {
      const label =
        option[props.labelKey]
          ?.toString()
          .toLowerCase() || ''
  
      return label.includes(keyword)
    })
  })
  
  const isSelected = option => {
    return (
      String(option[props.valueKey]) ===
      String(props.modelValue)
    )
  }
  
  const updatePlacement = () => {
    if (!selectRef.value) return
  
    const rect = selectRef.value.getBoundingClientRect()
  
    const spaceBelow = window.innerHeight - rect.bottom
    const spaceAbove = rect.top
  
    const dropdownHeight = 260
  
    if (
      spaceBelow < dropdownHeight &&
      spaceAbove > spaceBelow
    ) {
      placement.value = 'top'
    } else {
      placement.value = 'bottom'
    }
  }
  
  const toggleDropdown = async () => {
    if (props.disabled) return
  
    isOpen.value = !isOpen.value
  
    if (isOpen.value) {
      search.value = ''
  
      await nextTick()
  
      updatePlacement()
  
      searchInput.value?.focus()
    } else {
      search.value = ''
    }
  }
  
  const selectOption = option => {
    emit(
      'update:modelValue',
      option[props.valueKey]
    )
  
    isOpen.value = false
    search.value = ''
  }
  
  const clearSelection = () => {
    emit('update:modelValue', '')
  
    search.value = ''
    isOpen.value = false
  }
  
  const handleClickOutside = event => {
    if (
      selectRef.value &&
      !selectRef.value.contains(event.target)
    ) {
      isOpen.value = false
      search.value = ''
    }
  }
  
  const handleScroll = () => {
    if (isOpen.value) {
      updatePlacement()
    }
  }
  
  document.addEventListener(
    'click',
    handleClickOutside
  )
  
  window.addEventListener(
    'resize',
    updatePlacement
  )
  
  window.addEventListener(
    'scroll',
    handleScroll,
    true
  )
  
  onBeforeUnmount(() => {
    document.removeEventListener(
      'click',
      handleClickOutside
    )
  
    window.removeEventListener(
      'resize',
      updatePlacement
    )
  
    window.removeEventListener(
    'scroll',
    handleScroll,
    true
  )
})
</script>
