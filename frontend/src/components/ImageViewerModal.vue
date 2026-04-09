<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="modelValue" class="image-viewer-overlay" @click.self="close">
        <!-- Close button -->
        <button @click="close"
                class="absolute top-4 right-4 z-10 w-10 h-10 bg-black/60 hover:bg-black/80 text-white rounded-full flex items-center justify-center text-xl backdrop-blur-sm transition-colors">
          ✕
        </button>

        <!-- Image container -->
        <div class="image-viewer-content" @click.stop>
          <img
            :src="src"
            :alt="alt"
            class="max-w-[90vw] max-h-[85vh] object-contain rounded-xl shadow-2xl"
            @load="loaded = true"
          />
          <!-- Loading spinner -->
          <div v-if="!loaded" class="absolute inset-0 flex items-center justify-center">
            <span class="w-10 h-10 border-3 border-white/30 border-t-white rounded-full animate-spin"></span>
          </div>
        </div>

        <!-- Caption -->
        <p v-if="alt" class="absolute bottom-6 left-1/2 -translate-x-1/2 text-white/80 text-sm bg-black/40 px-4 py-2 rounded-full backdrop-blur-sm">
          {{ alt }}
        </p>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  modelValue: Boolean,
  src: { type: String, default: '' },
  alt: { type: String, default: '' }
})
const emit = defineEmits(['update:modelValue'])

const loaded = ref(false)

watch(() => props.modelValue, (val) => {
  if (val) loaded.value = false
})

function close() {
  emit('update:modelValue', false)
}
</script>

<style scoped>
.image-viewer-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: rgba(0, 0, 0, 0.85);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
}
.image-viewer-content {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}
.fade-enter-active, .fade-leave-active { transition: opacity .2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
