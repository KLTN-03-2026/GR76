<template>
  <div class="space-y-3">
    <div v-for="i in count" :key="i" class="skeleton rounded-2xl" :style="{ height: height + 'px' }" />
  </div>
</template>

<script setup>
defineProps({
  count:  { type: Number, default: 3 },
  height: { type: Number, default: 120 }
})
</script>
