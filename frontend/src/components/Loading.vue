<template>
  <div
    class="fixed top-0 left-0 w-full z-50 transition-opacity duration-300"
    :class="isVisible ? 'opacity-100' : 'opacity-0'"
  >
    <div class="h-1 w-full bg-gray-200">
      <div
        class="h-full bg-blue-600 transition-all duration-300 ease-out"
        :style="{ width: `${progress}%` }"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onUnmounted } from 'vue'

const props = defineProps({
  isLoading: {
    type: Boolean,
    default: false,
  },
})

const progress = ref(0)
const isVisible = ref(false)
let interval = null

const startProgress = () => {
  progress.value = 0
  isVisible.value = true

  interval = setInterval(() => {
    if (progress.value < 70) {
      progress.value = Math.min(progress.value + 2, 70)
    } else if (progress.value < 90) {
      progress.value = Math.min(progress.value + 0.5, 90)
    } else if (progress.value < 98) {
      progress.value = Math.min(progress.value + 0.1, 98)
    }
  }, 100)
}

const completeProgress = () => {
  clearInterval(interval)
  progress.value = 100

  setTimeout(() => {
    isVisible.value = false
    setTimeout(() => {
      progress.value = 0
    }, 300)
  }, 400)
}

watch(
  () => props.isLoading,
  (newValue) => {
    if (newValue) {
      startProgress()
    } else {
      completeProgress()
    }
  },
)

onUnmounted(() => {
  if (interval) {
    clearInterval(interval)
  }
})
</script>
