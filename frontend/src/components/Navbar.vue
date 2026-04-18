<template>
  <nav class="fixed top-0 left-0 right-0 z-50 h-16 px-6 flex items-center justify-between"
       style="background: var(--color-card); border-bottom: 1px solid var(--color-border); backdrop-filter: blur(12px);">
    <!-- Logo -->
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-std flex items-center justify-center" style="background: var(--color-brand);">
        <ShieldExclamationIcon class="w-5 h-5 text-white" />
      </div>
      <span class="font-semibold text-lg hidden sm:block" style="letter-spacing: -0.4px; color: var(--color-text);">SOS System</span>
    </div>

    <!-- Nav Links -->
    <div class="hidden md:flex items-center gap-1">
      <template v-if="!isAdmin">
        <router-link to="/home"         class="btn-ghost text-sm">Tổng quan</router-link>
        <router-link to="/map"          class="btn-ghost text-sm">Bản đồ</router-link>
        <router-link to="/my-incidents" class="btn-ghost text-sm">Sự cố của tôi</router-link>
      </template>
      <template v-if="isAdmin">
        <router-link to="/dashboard"        class="btn-ghost text-sm">Dashboard</router-link>
        <router-link to="/admin/incidents"  class="btn-ghost text-sm">Sự cố</router-link>
        <router-link to="/admin/users"      class="btn-ghost text-sm">Người dùng</router-link>
      </template>
    </div>

    <!-- Right actions -->
    <div class="flex items-center gap-2">
      <!-- Dark mode -->
      <button @click="$emit('toggle-dark')"
              class="p-2 rounded-md transition-colors" style="color: var(--color-text-muted);"
              onmouseover="this.style.background='var(--color-surface-100)'"
              onmouseout="this.style.background='transparent'">
        <SunIcon v-if="darkMode" class="w-5 h-5" style="color: #fbbf24;" />
        <MoonIcon v-else class="w-5 h-5" />
      </button>

      <!-- Notification Bell -->
      <NotificationBell />

      <!-- User menu -->
      <div class="relative" ref="userMenu">
        <button @click="showMenu = !showMenu"
                class="flex items-center gap-2 px-3 py-1.5 rounded-pill transition-colors"
                style="border: 1px solid var(--color-border);"
                onmouseover="this.style.background='var(--color-surface-100)'"
                onmouseout="this.style.background='transparent'">
          <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold"
               style="background: var(--color-brand); color: #0d0d0d;">
            {{ userInitials }}
          </div>
          <span class="hidden sm:block text-sm font-medium" style="color: var(--color-text);">{{ userName }}</span>
          <ChevronDownIcon class="w-4 h-4" style="color: var(--color-text-placeholder);" />
        </button>

        <transition name="dropdown">
          <div v-if="showMenu" class="absolute right-0 top-12 w-48 py-2 z-50 rounded-std"
               style="background: var(--color-card); border: 1px solid var(--color-border); box-shadow: 0 8px 24px rgba(0,0,0,0.12);">
            <router-link to="/profile" @click="showMenu=false"
                         class="flex items-center gap-2 px-4 py-2.5 text-sm transition-colors"
                         style="color: var(--color-text);"
                         onmouseover="this.style.background='var(--color-surface-100)'"
                         onmouseout="this.style.background='transparent'">
              <UserCircleIcon class="w-4 h-4" /> Hồ sơ
            </router-link>
            <hr style="border-color: var(--color-border); margin: 4px 0;" />
            <button @click="handleLogout"
                    class="w-full flex items-center gap-2 px-4 py-2.5 text-sm transition-colors"
                    style="color: #d45656;"
                    onmouseover="this.style.background='#fde8e8'"
                    onmouseout="this.style.background='transparent'">
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
