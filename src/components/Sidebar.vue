<template>
  <!-- Mobile Toggle -->
  <button @click="open = !open"
          class="fixed top-20 left-3 z-40 lg:hidden p-2 glass-card rounded-xl shadow-lg">
    <Bars3Icon class="w-5 h-5 text-gray-600 dark:text-gray-300" />
  </button>

  <!-- Backdrop -->
  <div v-if="open" @click="open = false"
       class="fixed inset-0 bg-black/30 z-30 lg:hidden" />

  <!-- Sidebar -->
  <aside :class="['fixed left-0 top-16 bottom-0 w-64 z-40 glass-card rounded-none border-r transition-transform duration-300',
                  open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0']">
    <div class="p-4 flex flex-col h-full overflow-y-auto">
      <!-- Admin badge -->
      <div class="mb-5 px-4 py-3 bg-gradient-to-r from-primary-100 to-emerald-100 dark:from-primary-900/30 dark:to-emerald-900/30 rounded-xl">
        <div class="flex items-center gap-2">
          <ShieldCheckIcon class="w-5 h-5 text-primary-600 dark:text-primary-400" />
          <span class="text-sm font-semibold text-primary-700 dark:text-primary-300">Admin Panel</span>
        </div>
      </div>

      <!-- Nav items -->
      <nav class="flex flex-col gap-1 flex-1">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 mb-1">Tổng quan</p>
        <router-link v-for="item in mainItems" :key="item.to" :to="item.to"
                     @click="open=false"
                     :class="['sidebar-link', $route.path === item.to ? 'active' : '']">
          <component :is="item.icon" class="w-5 h-5" />
          {{ item.label }}
        </router-link>

        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 mb-1 mt-4">Quản lý</p>
        <router-link v-for="item in adminItems" :key="item.to" :to="item.to"
                     @click="open=false"
                     :class="['sidebar-link', $route.path.startsWith(item.to) ? 'active' : '']">
          <component :is="item.icon" class="w-5 h-5" />
          {{ item.label }}
        </router-link>
      </nav>

      <!-- Version -->
      <div class="px-4 py-2 text-xs text-gray-400">SOS System v1.0</div>
    </div>
  </aside>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import {
  Bars3Icon, ShieldCheckIcon, ChartBarIcon,
  MapIcon, UsersIcon, BellIcon, DocumentTextIcon, TagIcon
} from '@heroicons/vue/24/outline'

const open = ref(false)
const $route = useRoute()

const mainItems = [
  { to: '/dashboard',    label: 'Dashboard',      icon: ChartBarIcon },
  { to: '/map',          label: 'Bản đồ SOS',     icon: MapIcon },
]

const adminItems = [
  { to: '/admin/incidents',     label: 'Sự cố',           icon: DocumentTextIcon },
  { to: '/admin/users',         label: 'Người dùng',      icon: UsersIcon },
  { to: '/admin/categories',    label: 'Danh mục',        icon: TagIcon },
  { to: '/admin/notifications', label: 'Thông báo',       icon: BellIcon },
]
</script>
