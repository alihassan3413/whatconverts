<template>
  <div class="flex flex-col sm:flex-row items-center justify-between px-6 py-4 border-t border-gray-200 gap-4">
    <div class="flex items-center gap-4 w-full sm:w-auto">
      <select
        :value="pageSize"
        @change="$emit('page-size-change', Number($event.target.value))"
        class="bg-white border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 py-2 px-5"
      >
        <option v-for="size in pageSizeOptions" :key="size" :value="size">
          {{ size }} per page
        </option>
      </select>
      <span class="text-sm text-gray-500">
        Showing {{ startIndex }} to {{ endIndex }} of {{ totalItems }} results
      </span>
    </div>

    <div class="flex items-center gap-2">
      <button
        @click="$emit('page-change', 1)"
        :disabled="currentPage === 1"
        class="px-2 py-1 rounded text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        :class="currentPage === 1 ? 'bg-blue-50 text-blue-600' : 'bg-white text-gray-600 hover:bg-gray-50'"
      >
        ««
      </button>
      <button
        @click="$emit('page-change', currentPage - 1)"
        :disabled="currentPage === 1"
        class="px-2 py-1 rounded text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        :class="currentPage === 1 ? 'bg-blue-50 text-blue-600' : 'bg-white text-gray-600 hover:bg-gray-50'"
      >
        «
      </button>

      <div class="flex gap-1">
        <template v-for="pageNum in displayedPages" :key="pageNum">
          <span
            v-if="pageNum === '...'"
            class="px-2 py-1 text-gray-400"
          >
            ...
          </span>
          <button
            v-else
            @click="$emit('page-change', pageNum)"
            class="px-3 py-1 rounded text-sm font-medium transition-colors"
            :class="pageNum === currentPage ? 'bg-blue-50 text-blue-600' : 'bg-white text-gray-600 hover:bg-gray-50'"
          >
            {{ pageNum }}
          </button>
        </template>
      </div>

      <button
        @click="$emit('page-change', currentPage + 1)"
        :disabled="currentPage === totalPages"
        class="px-2 py-1 rounded text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        :class="currentPage === totalPages ? 'bg-blue-50 text-blue-600' : 'bg-white text-gray-600 hover:bg-gray-50'"
      >
        »
      </button>
      <button
        @click="$emit('page-change', totalPages)"
        :disabled="currentPage === totalPages"
        class="px-2 py-1 rounded text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        :class="currentPage === totalPages ? 'bg-blue-50 text-blue-600' : 'bg-white text-gray-600 hover:bg-gray-50'"
      >
        »»
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  currentPage: Number,
  totalPages: Number,
  pageSize: Number,
  totalItems: Number,
  pageSizeOptions: {
    type: Array,
    default: () => [25, 50, 100, 150, 200, 250]
  }
})

const emit = defineEmits(['page-change', 'page-size-change'])

const displayedPages = computed(() => {
  const pages = []
  const total = props.totalPages
  const current = props.currentPage
  const range = 2 // Number of pages to show around current

  if (total <= 7) {
    for (let i = 1; i <= total; i++) pages.push(i)
  } else {
    pages.push(1)

    if (current > range + 2) pages.push('...')

    const start = Math.max(2, current - range)
    const end = Math.min(total - 1, current + range)

    for (let i = start; i <= end; i++) pages.push(i)

    if (current < total - range - 1) pages.push('...')

    pages.push(total)
  }

  return pages
})

const startIndex = computed(() => {
  return (props.currentPage - 1) * props.pageSize + 1
})

const endIndex = computed(() => {
  return Math.min(props.currentPage * props.pageSize, props.totalItems)
})
</script>
