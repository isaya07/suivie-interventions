<template>
  <div class="relative" ref="multiSelectRef">
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1">
      {{ label }}
    </label>

    <!-- Trigger -->
    <div
      @click="toggleDropdown"
      class="min-h-[2.5rem] w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm cursor-pointer focus-within:ring-1 focus-within:ring-blue-500 focus-within:border-blue-500 bg-white"
      :class="{ 'ring-1 ring-blue-500 border-blue-500': isOpen }"
    >
      <div v-if="selectedItems.length === 0" class="text-gray-500 text-sm">
        {{ placeholder }}
      </div>

      <!-- Selected items as chips -->
      <div v-else class="flex flex-wrap gap-1">
        <span
          v-for="item in selectedItems"
          :key="getItemValue(item)"
          class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded-md"
        >
          {{ getItemLabel(item) }}
          <button
            @click.stop="removeItem(item)"
            class="hover:bg-blue-200 rounded-full w-4 h-4 flex items-center justify-center"
            type="button"
          >
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </span>
      </div>

      <!-- Chevron -->
      <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
        <svg
          class="h-4 w-4 text-gray-400 transition-transform duration-200"
          :class="{ 'rotate-180': isOpen }"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
      </div>
    </div>

    <!-- Dropdown -->
    <Transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-show="isOpen"
        class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto"
      >
        <div class="py-1">
          <div
            v-for="option in options"
            :key="getItemValue(option)"
            @click="toggleSelection(option)"
            class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-100 flex items-center justify-between"
            :class="{ 'bg-blue-50': isSelected(option) }"
          >
            <span>{{ getItemLabel(option) }}</span>
            <div
              v-if="isSelected(option)"
              class="text-blue-600"
            >
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
              </svg>
            </div>
          </div>
        </div>

        <!-- Clear all button -->
        <div
          v-if="selectedItems.length > 0"
          class="border-t border-gray-200 px-3 py-2"
        >
          <button
            @click="clearAll"
            class="text-xs text-red-600 hover:text-red-800"
            type="button"
          >
            Tout désélectionner
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  },
  options: {
    type: Array,
    required: true
  },
  optionLabel: {
    type: String,
    default: null
  },
  optionValue: {
    type: String,
    default: null
  },
  placeholder: {
    type: String,
    default: 'Sélectionner...'
  },
  label: {
    type: String,
    default: null
  }
})

const emit = defineEmits(['update:modelValue'])

const isOpen = ref(false)
const multiSelectRef = ref(null)

const selectedItems = computed(() => {
  return props.options.filter(option =>
    props.modelValue.includes(getItemValue(option))
  )
})

const getItemValue = (item) => {
  return props.optionValue ? item[props.optionValue] : item
}

const getItemLabel = (item) => {
  return props.optionLabel ? item[props.optionLabel] : item
}

const isSelected = (option) => {
  return props.modelValue.includes(getItemValue(option))
}

const toggleDropdown = () => {
  isOpen.value = !isOpen.value
}

const toggleSelection = (option) => {
  const value = getItemValue(option)
  const newValue = [...props.modelValue]

  const index = newValue.indexOf(value)
  if (index > -1) {
    newValue.splice(index, 1)
  } else {
    newValue.push(value)
  }

  emit('update:modelValue', newValue)
}

const removeItem = (item) => {
  const value = getItemValue(item)
  const newValue = props.modelValue.filter(v => v !== value)
  emit('update:modelValue', newValue)
}

const clearAll = () => {
  emit('update:modelValue', [])
}

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
  if (multiSelectRef.value && !multiSelectRef.value.contains(event.target)) {
    isOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>