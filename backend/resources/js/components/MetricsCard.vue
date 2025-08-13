<template>
  <div class="bg-white rounded-lg border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-sm font-medium text-gray-500">{{ title }}</h3>
      <span :class="trendClass">
        {{ trendText }}
      </span>
    </div>
    <p class="text-2xl font-semibold text-gray-900">{{ value }}</p>
    <p class="text-sm text-gray-500 mt-2">{{ secondaryText }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: String,
  value: [String, Number],
  trendText: String,
  trendType: {
    type: String,
    default: 'neutral',
    validator: (value) => ['positive', 'neutral', 'negative'].includes(value)
  },
  secondaryText: String
})

const trendClass = computed(() => {
  const base = 'flex items-center px-2 py-1 rounded text-xs font-medium'
  const variants = {
    positive: 'text-green-600 bg-green-50',
    neutral: 'text-blue-600 bg-blue-50',
    negative: 'text-red-600 bg-red-50'
  }
  return `${base} ${variants[props.trendType]}`
})
</script>
