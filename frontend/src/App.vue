<template>
  <div class="min-h-screen" :class="darkMode ? 'dark' : ''">
    <div class="min-h-screen" style="background-color: var(--color-bg); color: var(--color-text);">
      <Navbar v-if="isLoggedIn" @toggle-dark="toggleDark" :dark-mode="darkMode" />
      <div class="flex">
        <Sidebar v-if="isLoggedIn && isAdmin" />
        <main :class="['flex-1 transition-all duration-300', isLoggedIn && isAdmin ? 'ml-0 lg:ml-64' : '']">
          <router-view :key="$route.path" />
        </main>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import Navbar from '@/components/Navbar.vue'
import Sidebar from '@/components/Sidebar.vue'

const auth = useAuthStore()
const $route = useRoute()
const toast = useToast()
const isLoggedIn = computed(() => auth.isLoggedIn)
const isAdmin = computed(() => auth.isAdmin)

const darkMode = ref(localStorage.getItem('sos_dark') === 'true')

function applyDark(val) {
  if (val) document.documentElement.classList.add('dark')
  else document.documentElement.classList.remove('dark')
}

function toggleDark() {
  darkMode.value = !darkMode.value
  localStorage.setItem('sos_dark', darkMode.value)
}

watch(darkMode, applyDark)

onMounted(() => {
  applyDark(darkMode.value)

  // ── Real-time: Auto-resolve after 12h ──────────────────────────
  // Listen on public channel 'incidents' for IncidentStatusChanged
  try {
    import('@/services/echo').then(({ default: echo }) => {
      echo.channel('incidents')
        .listen('.IncidentStatusChanged', (e) => {
          console.log('[WS] IncidentStatusChanged', e)
          if (e.triggered_by === 'auto' && e.trang_thai === 'resolved') {
            toast.info(`✅ Sự cố #${e.id_su_co} đã được tự động đánh dấu là Đã giải quyết sau 12 giờ`, {
              timeout: 6000,
            })
            // Dispatch a custom DOM event so pages can refresh their list
            window.dispatchEvent(new CustomEvent('incident-auto-resolved', { detail: e }))
          }
        })
    })
  } catch (err) {
    console.warn('[WS] Echo not available:', err)
  }
})
</script>

<style>
.page-enter-active,
.page-leave-active {
  transition: opacity .15s ease, transform .15s ease;
}

.page-enter-from {
  opacity: 0;
  transform: translateY(6px);
}

.page-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
