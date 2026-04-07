<template>
  <teleport to="body">
    <transition name="modal">
      <div v-if="modelValue" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="$emit('update:modelValue', false)" />
        <!-- Panel -->
        <div :class="['relative glass-card w-full shadow-2xl z-10 animate-slide-up', sizeClass]">
          <!-- Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ title }}</h2>
            <button @click="$emit('update:modelValue', false)"
                    class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 transition-colors">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>
          <!-- Body -->
          <div class="px-6 py-5 max-h-[75vh] overflow-y-auto scrollbar-thin">
            <slot />
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { computed } from 'vue'

const props = defineProps({
  modelValue: Boolean,
  title:      { type: String, default: '' },
  size:       { type: String, default: 'md' }
})
defineEmits(['update:modelValue'])

const sizeClass = computed(() => ({
  sm:  'max-w-sm',
  md:  'max-w-lg',
  lg:  'max-w-2xl',
  xl:  'max-w-4xl'
}[props.size] ?? 'max-w-lg'))
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity .2s; }
.modal-enter-active .relative, .modal-leave-active .relative { transition: transform .2s, opacity .2s; }
.modal-enter-from { opacity: 0; }
.modal-enter-from .relative { transform: scale(0.95) translateY(10px); opacity: 0; }
.modal-leave-to { opacity: 0; }
.modal-leave-to .relative { transform: scale(0.95); opacity: 0; }
</style>
