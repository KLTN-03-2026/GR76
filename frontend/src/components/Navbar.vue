<template>
  <nav class="fixed top-0 left-0 right-0 z-50 h-16 glass-card rounded-none border-b border-primary-200/50 dark:border-gray-700/50 px-4 flex items-center justify-between">
    <!-- Logo -->
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 bg-gradient-to-br from-primary-300 to-emerald-400 rounded-xl flex items-center justify-center shadow-md">
        <ShieldExclamationIcon class="w-5 h-5 text-white" />
      </div>
      <span class="font-bold text-lg text-gradient hidden sm:block">SOS System</span>
    </div>

    <!-- Nav Links -->
    <div class="hidden md:flex items-center gap-1">
      <template v-if="!isAdmin">
        <router-link to="/home"            class="btn-ghost text-sm">💻 Tổng quan</router-link>
        <router-link to="/map"             class="btn-ghost text-sm">🌐 Bản đồ</router-link>
        <router-link to="/my-incidents"    class="btn-ghost text-sm">📋 Sự cố của tôi</router-link>
      </template>
      <template v-if="isAdmin">
        <router-link to="/dashboard"             class="btn-ghost text-sm">📊 Dashboard</router-link>
        <router-link to="/admin/incidents"       class="btn-ghost text-sm">🚨 Sự cố</router-link>
        <router-link to="/admin/users"           class="btn-ghost text-sm">👥 Người dùng</router-link>
      </template>
    </div>

    <!-- Right actions -->
    <div class="flex items-center gap-2">
      <!-- Dark mode -->
      <button @click="$emit('toggle-dark')"
              class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
        <SunIcon v-if="darkMode" class="w-5 h-5 text-yellow-500" />
        <MoonIcon v-else class="w-5 h-5 text-gray-500" />
      </button>

      <!-- Notification Bell -->
      <NotificationBell />

      <!-- User menu -->
      <div class="relative" ref="userMenu">
        <button @click="showMenu = !showMenu"
                class="flex items-center gap-2 px-3 py-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
          <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary-300 to-emerald-400 flex items-center justify-center text-white font-semibold text-sm shadow">
            {{ userInitials }}
          </div>
          <span class="hidden sm:block text-sm font-medium text-gray-700 dark:text-gray-200">{{ userName }}</span>
          <ChevronDownIcon class="w-4 h-4 text-gray-400" />
        </button>

        <transition name="dropdown">
          <div v-if="showMenu" class="absolute right-0 top-12 w-48 glass-card py-2 shadow-xl z-50">
            <router-link to="/profile" @click="showMenu=false"
                         class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-primary-50 dark:hover:bg-gray-700 transition-colors">
              <UserCircleIcon class="w-4 h-4" /> Hồ sơ
            </router-link>
            <hr class="my-1 border-gray-100 dark:border-gray-700" />
            <button @click="handleLogout"
                    class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-emergency hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
              <ArrowRightOnRectangleIcon class="w-4 h-4" /> Đăng xuất
            </button>
          </div>
        </transition>
      </div>
    </div>
  </nav>
  <!-- spacer -->
  <div class="h-16"></div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import NotificationBell from '@/components/NotificationBell.vue'
import {
  ShieldExclamationIcon, SunIcon, MoonIcon,
  ChevronDownIcon, UserCircleIcon, ArrowRightOnRectangleIcon
} from '@heroicons/vue/24/outline'

defineProps({ darkMode: Boolean })
defineEmits(['toggle-dark'])

const auth    = useAuthStore()
const isAdmin = computed(() => auth.isAdmin)
const userName = computed(() => auth.user?.ten || auth.user?.name || 'User')
const userInitials = computed(() => userName.value.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase())

const showMenu = ref(false)
const userMenu = ref(null)

function handleLogout() { auth.logout() }

function handleClickOutside(e) {
  if (userMenu.value && !userMenu.value.contains(e.target)) showMenu.value = false
}
onMounted(()  => document.addEventListener('click', handleClickOutside))
onUnmounted(()=> document.removeEventListener('click', handleClickOutside))
</script>

<style scoped>
.dropdown-enter-active, .dropdown-leave-active { transition: opacity .15s, transform .15s; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-8px) scale(0.97); }
</style>
