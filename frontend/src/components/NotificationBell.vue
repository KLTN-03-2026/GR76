<template>
  <div class="relative" ref="bellContainer">
    <button @click="toggle"
            class="relative p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
      <BellIcon class="w-6 h-6 text-gray-600 dark:text-gray-300" />
      <span v-if="store.unreadCount > 0"
            class="absolute -top-1 -right-1 w-5 h-5 bg-emergency text-white text-xs font-bold rounded-full flex items-center justify-center animate-pulse">
        {{ store.unreadCount > 9 ? '9+' : store.unreadCount }}
      </span>
    </button>

    <transition name="dropdown">
      <div v-if="open"
           class="absolute right-0 top-12 w-80 glass-card shadow-2xl z-50 max-h-96 overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
          <span class="font-semibold text-sm text-gray-800 dark:text-gray-100">🔔 Thông báo</span>
          <span class="text-xs text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20 px-2 py-0.5 rounded-full">
            {{ store.unreadCount }} mới
          </span>
        </div>

        <!-- List -->
        <div class="overflow-y-auto scrollbar-thin flex-1">
          <div v-if="store.loading" class="p-4 space-y-3">
            <div v-for="i in 3" :key="i" class="skeleton h-14 rounded-xl" />
          </div>

          <template v-else-if="store.notifications.length">
            <div v-for="n in store.notifications" :key="n.id_thong_bao"
                 @click="handleClick(n)"
                 :class="['px-4 py-3 border-b border-gray-50 dark:border-gray-700/50 cursor-pointer transition-colors',
                          !n.da_doc ? 'bg-primary-50/70 dark:bg-primary-900/10' : 'hover:bg-gray-50 dark:hover:bg-gray-700/40']">
              <div class="flex gap-3 items-start">
                <div :class="['w-2 h-2 rounded-full mt-1.5 shrink-0', !n.da_doc ? 'bg-primary-400' : 'bg-gray-300']" />
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">{{ n.tieu_de || 'Thông báo' }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">{{ n.noi_dung }}</p>
                  <div class="flex items-center gap-2 mt-1">
                    <p class="text-xs text-gray-400">{{ formatTime(n.created_at) }}</p>
                    <span v-if="n.su_co || n.id_su_co" class="text-xs text-primary-500">📍 Xem vị trí</span>
                  </div>
                </div>
              </div>
            </div>
          </template>

          <div v-else class="py-10 text-center">
            <BellSlashIcon class="w-10 h-10 mx-auto text-gray-300 mb-2" />
            <p class="text-sm text-gray-400">Không có thông báo</p>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { BellIcon, BellSlashIcon } from '@heroicons/vue/24/outline'
import { useNotificationStore } from '@/stores/notifications'

const router = useRouter()
const store = useNotificationStore()
const open  = ref(false)
const bellContainer = ref(null)

function toggle() {
  open.value = !open.value
  if (open.value) store.fetchNotifications()
}

async function handleClick(n) {
  // Mark as read
  if (!n.da_doc) await store.markRead(n.id_thong_bao)

  // Navigate to map with incident location
  const incidentId = n.id_su_co || n.su_co?.id_su_co
  if (incidentId) {
    open.value = false
    router.push({ path: '/map', query: { incident: incidentId } })
  }
}

function formatTime(ts) {
  if (!ts) return ''
  const d = new Date(ts)
  return d.toLocaleString('vi-VN', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit' })
}

function outside(e) {
  if (bellContainer.value && !bellContainer.value.contains(e.target)) open.value = false
}
onMounted(() => {
  document.addEventListener('click', outside)
  store.fetchNotifications()
})
onUnmounted(() => document.removeEventListener('click', outside))
</script>

<style scoped>
.dropdown-enter-active, .dropdown-leave-active { transition: opacity .15s, transform .15s; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-8px) scale(0.97); }
</style>
