<template>
  <div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-600">{{ title }}</p>
        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ value }}</p>
      </div>
      <div class="p-3 bg-blue-50 rounded-full">
        <component :is="icons[icon]" class="w-6 h-6 text-blue-600" />
      </div>
    </div>
    <div class="mt-4">
      <span
        :class="[
          'inline-flex items-center text-sm',
          trendDirection === 'up' ? 'text-green-600' : 'text-red-600',
        ]"
      >
        <TrendIcon
          :class="[
            'w-4 h-4 mr-1',
            trendDirection === 'up' ? 'transform rotate-0' : 'transform rotate-180',
          ]"
        />
        {{ trend }}
      </span>
      <span class="text-sm text-gray-600 ml-2">vs last month</span>
    </div>
  </div>
</template>

<script setup>
import { Users, DollarSign, ClipboardCheck, TrendingUp, TrendingDown } from 'lucide-react'

const icons = {
  users: Users,
  'dollar-sign': DollarSign,
  'clipboard-check': ClipboardCheck,
  'trending-up': TrendingUp,
}

defineProps({
  title: String,
  value: [String, Number],
  icon: String,
  trend: String,
  trendDirection: String,
})
</script>
