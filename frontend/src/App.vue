<template>
  <div class="min-h-screen" :class="darkMode ? 'dark' : ''">
    <div class="min-h-screen bg-gradient-to-br from-green-50 via-white to-emerald-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
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
import Navbar from '@/components/Navbar.vue'
import Sidebar from '@/components/Sidebar.vue'

const auth = useAuthStore()
const $route = useRoute()
const isLoggedIn = computed(() => auth.isLoggedIn)
const isAdmin    = computed(() => auth.isAdmin)

const darkMode = ref(localStorage.getItem('sos_dark') === 'true')

function applyDark(val) {
  if (val) document.documentElement.classList.add('dark')
  else     document.documentElement.classList.remove('dark')
}

function toggleDark() {
  darkMode.value = !darkMode.value
  localStorage.setItem('sos_dark', darkMode.value)
}

watch(darkMode, applyDark)
onMounted(() => applyDark(darkMode.value))
</script>

<style>
.page-enter-active, .page-leave-active { transition: opacity .15s ease, transform .15s ease; }
.page-enter-from  { opacity: 0; transform: translateY(6px); }
.page-leave-to    { opacity: 0; transform: translateY(-6px); }
</style>
