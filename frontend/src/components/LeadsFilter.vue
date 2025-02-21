<template>
  <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <div class="flex flex-col sm:flex-row gap-4">
      <div class="flex-1">
        <input
          :value="search"
          @input="$emit('update:search', $event.target.value)"
          type="text"
          placeholder="Search leads..."
          class="input-field"
        />
      </div>
      <div class="flex gap-4">
        <select
          :value="status"
          @change="$emit('update:status', $event.target.value)"
          class="select-field"
        >
          <option value="">All Statuses</option>
          <option value="Unique">Unique</option>
          <option value="Duplicate">Duplicate</option>
        </select>
        <select
          :value="sortBy"
          @change="$emit('update:sortBy', $event.target.value)"
          class="select-field"
        >
          <option value="date_created">Date Created</option>
          <option value="sales_value">Sales Value</option>
          <option value="quote_value">Quote Value</option>
        </select>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  search: String,
  status: String,
  sortBy: String,
})

defineEmits(['update:search', 'update:status', 'update:sortBy'])
</script>
