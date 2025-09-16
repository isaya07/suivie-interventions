<template>
  <div class="field">
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-2">
      {{ label }}
    </label>

    <MultiSelect
      :modelValue="modelValue"
      @update:modelValue="$emit('update:modelValue', $event)"
      :options="options"
      :optionLabel="optionLabel"
      :optionValue="optionValue"
      :placeholder="placeholder"
      display="chip"
      :maxSelectedLabels="3"
      class="w-full"
      :pt="{
        root: 'border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500',
        label: 'text-gray-700',
        chip: 'bg-blue-100 text-blue-800',
        chipIcon: 'text-blue-600 hover:text-blue-800'
      }"
    />
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
</script>