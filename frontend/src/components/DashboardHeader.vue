<template>
  <header class="mb-8">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Leads Dashboard</h1>
        <p class="text-sm text-gray-600 mt-1">Last updated: {{ formatDate(lastUpdated) }}</p>
      </div>
      <div class="flex gap-3">
        <button @click="$emit('export')" class="btn-secondary">
          <ExportIcon class="w-4 h-4 mr-2" />
          Export
        </button>
        <button @click="$emit('refresh')" class="btn-primary">
          <RefreshIcon class="w-4 h-4 mr-2" />
          Refresh
        </button>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ExportIcon, RefreshIcon } from 'lucide-react'

defineProps({
  lastUpdated: {
    type: Date,
    required: true,
  },
})

defineEmits(['refresh', 'export'])
</script>
