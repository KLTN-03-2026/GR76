<template>
  <div class="p-6 max-w-3xl mx-auto space-y-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">🔔 Thông báo của tôi</h1>

    <div class="space-y-3">
      <div v-if="loading" class="space-y-3">
        <div v-for="i in 4" :key="i" class="skeleton h-20 rounded-2xl" />
      </div>

      <div v-else-if="!notifications.length" class="flex flex-col items-center justify-center py-20 text-center">
        <div class="text-5xl mb-4">📭</div>
        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Không có thông báo</h3>
        <p class="text-sm text-gray-400 mt-1">Bạn chưa nhận được thông báo nào</p>
      </div>

      <div v-else v-for="n in notifications" :key="n.id_thong_bao"
           @click="markRead(n)"
           :class="['glass-card p-5 cursor-pointer transition-all hover:shadow-md',
                    !n.da_doc ? 'border-l-4 border-primary-400' : 'opacity-80']">
        <div class="flex items-start gap-4">
          <div :class="['w-2.5 h-2.5 rounded-full mt-1.5 shrink-0', !n.da_doc ? 'bg-primary-400' : 'bg-gray-300']" />
          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-2">
              <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ n.tieu_de }}</p>
              <span :class="n.da_doc ? 'badge bg-gray-100 text-gray-500 text-xs' : 'badge bg-primary-100 text-primary-700 text-xs'">
                {{ n.da_doc ? 'Đã đọc' : 'Mới' }}
              </span>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ n.noi_dung }}</p>
            <p class="text-xs text-gray-400 mt-2">{{ formatDate(n.created_at) }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useNotificationStore } from '@/stores/notifications'

const store = useNotificationStore()
const notifications = ref([])
const loading = ref(false)

function formatDate(ts) {
  return ts ? new Date(ts).toLocaleString('vi-VN', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' }) : ''
}

async function load() {
  loading.value = true
  await store.fetchNotifications()
  notifications.value = store.notifications
  loading.value = false
}

async function markRead(n) {
  if (!n.da_doc) {
    await store.markRead(n.id_thong_bao)
    n.da_doc = true
  }
}

onMounted(load)
</script>
