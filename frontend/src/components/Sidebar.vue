<template>
  <!-- Mobile Toggle -->
  <button @click="open = !open"
          class="fixed top-20 left-3 z-40 lg:hidden p-2 rounded-md"
          style="background: var(--color-card); border: 1px solid var(--color-border); box-shadow: var(--shadow-card);">
    <MenuIcon class="w-5 h-5" style="color: var(--color-text-muted);" />
  </button>

  <!-- Backdrop -->
  <div v-if="open" @click="open = false"
       class="fixed inset-0 bg-black/30 z-30 lg:hidden" />

  <!-- Sidebar -->
  <aside :class="['fixed left-0 top-16 bottom-0 w-64 z-40 transition-transform duration-300',
                  open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0']"
         style="background: var(--color-card); border-right: 1px solid var(--color-border);">
    <div class="p-4 flex flex-col h-full overflow-y-auto">
      <!-- Admin badge -->
      <div class="mb-5 px-4 py-3 rounded-std"
           style="background: var(--color-brand-light, #d4fae8);">
        <div class="flex items-center gap-2">
          <ShieldCheckIcon class="w-5 h-5" style="color: var(--color-brand-deep, #0fa76e);" />
          <span class="text-sm font-semibold" style="color: var(--color-brand-deep, #0fa76e);">Admin Panel</span>
        </div>
      </div>

      <!-- Nav items -->
      <nav class="flex flex-col gap-1 flex-1">
        <p class="text-label-upper px-3 mb-1" style="color: var(--color-text-placeholder);">Tổng quan</p>
        <router-link v-for="item in mainItems" :key="item.to" :to="item.to"
                     @click="open=false"
                     :class="['sidebar-link', $route.path === item.to ? 'active' : '']">
          <component :is="item.icon" class="w-5 h-5" />
          {{ item.label }}
        </router-link>

        <p class="text-label-upper px-3 mb-1 mt-4" style="color: var(--color-text-placeholder);">Quản lý</p>
        <router-link v-for="item in adminItems" :key="item.to" :to="item.to"
                     @click="open=false"
                     :class="['sidebar-link', $route.path.startsWith(item.to) ? 'active' : '']">
          <component :is="item.icon" class="w-5 h-5" />
          {{ item.label }}
        </router-link>
      </nav>

      <!-- Version -->
      <div class="px-4 py-2 text-mono-code" style="color: var(--color-text-placeholder);">SOS System v1.0</div>
    </div>
  </aside>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import {
  Menu as MenuIcon,
  ShieldCheck as ShieldCheckIcon,
  LayoutDashboard as DashboardIcon,
  Map as MapIcon,
  FileText as IncidentIcon,
  Users as UsersIcon,
  Bell as BellIcon,
  Tag as TagIcon
} from 'lucide-vue-next'

const open = ref(false)
const $route = useRoute()

const mainItems = [
  { to: '/dashboard',    label: 'Dashboard',      icon: DashboardIcon },
  { to: '/map',          label: 'Bản đồ SOS',     icon: MapIcon },
]

const adminItems = [
  { to: '/admin/incidents',     label: 'Sự cố',           icon: IncidentIcon },
  { to: '/admin/users',         label: 'Người dùng',      icon: UsersIcon },
  { to: '/admin/categories',    label: 'Danh mục',        icon: TagIcon },
  { to: '/admin/notifications', label: 'Thông báo',       icon: BellIcon },
]
</script>
