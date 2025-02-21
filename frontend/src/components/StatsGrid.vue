<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <StatCard v-for="stat in stats" :key="stat.title" v-bind="stat" />
  </div>
</template>

<script setup>
import StatCard from './StatCard.vue'

defineProps({
  stats: {
    type: Array,
    required: true,
  },
})
</script>
