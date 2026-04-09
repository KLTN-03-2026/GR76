<template>
  <div class="p-6 max-w-6xl mx-auto space-y-6">

    <!-- Welcome Banner -->
    <div class="rounded-2xl bg-gradient-to-r from-blue-600 via-blue-500 to-indigo-500 p-6 flex items-center justify-between shadow-xl">
      <div>
        <p class="text-blue-100 text-sm mb-1">Xin chào 👋</p>
        <h1 class="text-2xl font-bold text-white">{{ auth.user?.ten || 'Người dùng' }}</h1>
        <p class="text-blue-200 text-sm mt-1">
          {{ auth.user?.email }}
          <span v-if="auth.user?.so_dien_thoai"> · {{ auth.user?.so_dien_thoai }}</span>
        </p>
      </div>
      <button @click="showReport = true"
              class="hidden sm:flex items-center gap-2 bg-white/20 hover:bg-white/30 text-white font-semibold px-5 py-2.5 rounded-xl transition-all backdrop-blur-sm border border-white/30 shadow">
        <PlusCircleIcon class="w-5 h-5" /> Báo cáo sự cố mới
      </button>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="card in statCards" :key="card.label"
           :class="['rounded-2xl p-5 border', card.bg]">
        <div class="text-2xl mb-2">{{ card.icon }}</div>
        <div class="text-3xl font-extrabold" :class="card.color">{{ card.value }}</div>
        <div class="text-sm mt-1 text-gray-500 dark:text-gray-400">{{ card.label }}</div>
      </div>
    </div>

    <!-- Status Progress -->
    <div class="glass-card p-6">
      <h2 class="font-semibold text-gray-700 dark:text-gray-200 mb-5">📊 Trạng thái báo cáo của bạn</h2>
      <div class="flex items-center justify-between gap-2">
        <template v-for="(step, i) in statusSteps" :key="step.key">
          <div class="flex flex-col items-center gap-2 flex-1">
            <div :class="['w-10 h-10 rounded-full flex items-center justify-center text-lg border-2 transition-all',
                          step.count > 0 ? 'border-primary-400 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 bg-gray-50 dark:bg-gray-700 opacity-40']">
              {{ step.icon }}
            </div>
            <p class="text-xs font-medium text-center text-gray-600 dark:text-gray-400 leading-tight">{{ step.label }}</p>
            <span :class="['text-lg font-bold', step.count > 0 ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400']">
              {{ step.count }}
            </span>
          </div>
          <div v-if="i < statusSteps.length - 1" class="h-0.5 flex-1 bg-gray-200 dark:bg-gray-600 mt-[-20px]" />
        </template>
      </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
      <!-- Recent Incidents -->
      <div class="lg:col-span-2 glass-card overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
          <h2 class="font-semibold text-gray-700 dark:text-gray-200">🕐 Báo cáo gần đây</h2>
          <router-link to="/my-incidents" class="text-xs text-primary-600 dark:text-primary-400 hover:underline font-medium">
            Xem tất cả →
          </router-link>
        </div>
        <div class="divide-y divide-gray-50 dark:divide-gray-700">
          <div v-if="loading" class="p-4 space-y-3">
            <div v-for="i in 3" :key="i" class="skeleton h-14 rounded-xl" />
          </div>
          <div v-else-if="!recentIncidents.length" class="py-12 text-center text-gray-400">
            <p class="text-3xl mb-2">📭</p>
            <p class="text-sm">Chưa có báo cáo nào</p>
          </div>
          <div v-else v-for="inc in recentIncidents" :key="inc.id_su_co"
               class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50/50 dark:hover:bg-gray-700/30 cursor-pointer transition-colors"
               @click="$router.push('/my-incidents')">
            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-lg shrink-0">🚨</div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">{{ inc.tieu_de }}</p>
              <p class="text-xs text-gray-400 mt-0.5">{{ formatDate(inc.thoi_gian_dang || inc.created_at) }}</p>
            </div>
            <span :class="statusBadge(inc.trang_thai)">{{ statusLabelMap[inc.trang_thai] || inc.trang_thai }}</span>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="glass-card p-6 space-y-3">
        <h2 class="font-semibold text-gray-700 dark:text-gray-200 mb-4">⚡ Truy cập nhanh</h2>
        <button @click="showReport = true"
                class="w-full flex items-center gap-3 p-3.5 rounded-xl bg-red-50 hover:bg-red-100 dark:bg-red-900/10 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 font-medium text-sm transition-all text-left">
          <span class="text-2xl">🚨</span>
          <div>
            <p class="font-semibold">Báo cáo sự cố mới</p>
            <p class="text-xs opacity-70">Gửi báo cáo kèm ảnh và tọa độ GPS</p>
          </div>
        </button>
        <router-link to="/my-incidents"
                class="w-full flex items-center gap-3 p-3.5 rounded-xl bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/10 dark:hover:bg-blue-900/20 text-blue-600 dark:text-blue-400 font-medium text-sm transition-all">
          <span class="text-2xl">📋</span>
          <div>
            <p class="font-semibold">Lịch sử báo cáo</p>
            <p class="text-xs opacity-70">Xem tất cả sự cố đã báo cáo</p>
          </div>
        </router-link>
        <router-link to="/map"
                class="w-full flex items-center gap-3 p-3.5 rounded-xl bg-green-50 hover:bg-green-100 dark:bg-green-900/10 dark:hover:bg-green-900/20 text-green-600 dark:text-green-400 font-medium text-sm transition-all">
          <span class="text-2xl">🗺️</span>
          <div>
            <p class="font-semibold">Bản đồ sự cố</p>
            <p class="text-xs opacity-70">Xem sự cố trên bản đồ</p>
          </div>
        </router-link>
        <router-link to="/profile"
                class="w-full flex items-center gap-3 p-3.5 rounded-xl bg-purple-50 hover:bg-purple-100 dark:bg-purple-900/10 dark:hover:bg-purple-900/20 text-purple-600 dark:text-purple-400 font-medium text-sm transition-all">
          <span class="text-2xl">👤</span>
          <div>
            <p class="font-semibold">Thông tin cá nhân</p>
            <p class="text-xs opacity-70">Cập nhật hồ sơ và mật khẩu</p>
          </div>
        </router-link>
      </div>
    </div>
  </div>

  <ReportIncidentModal v-model="showReport" @created="load" />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { PlusCircleIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'
import ReportIncidentModal from '@/components/ReportIncidentModal.vue'
import { incidentApi } from '@/services/api'

const auth  = useAuthStore()
const notifStore = useNotificationStore()
const incidents  = ref([])
const loading    = ref(false)
const showReport = ref(false)

const recentIncidents = computed(() => incidents.value.slice(0, 4))

const statusLabelMap = {
  pending: 'Chờ xử lý',
  in_progress: 'Đang xử lý',
  resolved: 'Đã giải quyết',
  rejected: 'Từ chối'
}

const stats = computed(() => {
  const list = incidents.value
  return {
    total:    list.length,
    pending:  list.filter(i => i.trang_thai === 'pending').length,
    active:   list.filter(i => i.trang_thai === 'in_progress').length,
    resolved: list.filter(i => i.trang_thai === 'resolved').length,
  }
})

const statCards = computed(() => [
  { icon:'📋', label:'Tổng báo cáo', value: stats.value.total,    bg:'bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700', color:'text-gray-800 dark:text-gray-100' },
  { icon:'⚡', label:'Đang xử lý',   value: stats.value.active,   bg:'bg-yellow-50 dark:bg-yellow-900/10 border-yellow-100 dark:border-yellow-900/20', color:'text-yellow-600' },
  { icon:'⏳', label:'Chờ xử lý',     value: stats.value.pending,  bg:'bg-blue-50 dark:bg-blue-900/10 border-blue-100 dark:border-blue-900/20', color:'text-blue-600' },
  { icon:'✅', label:'Đã giải quyết', value: stats.value.resolved, bg:'bg-green-50 dark:bg-green-900/10 border-green-100 dark:border-green-900/20', color:'text-green-600' },
])

const statusSteps = computed(() => [
  { key:'new',      icon:'📥', label:'Chờ xử lý',    count: stats.value.pending },
  { key:'active',   icon:'⚙️', label:'Đang xử lý',   count: stats.value.active },
  { key:'resolved', icon:'✅', label:'Đã giải quyết', count: stats.value.resolved },
  { key:'rejected', icon:'❌', label:'Từ chối',       count: incidents.value.filter(i => i.trang_thai === 'rejected').length },
])

const statusColors = {
  'pending':     'badge bg-yellow-100 text-yellow-700 text-xs',
  'in_progress': 'badge bg-blue-100 text-blue-700 text-xs',
  'resolved':    'badge bg-green-100 text-green-700 text-xs',
  'rejected':    'badge bg-red-100 text-red-700 text-xs',
}
function statusBadge(s) { return statusColors[s] || 'badge bg-gray-100 text-gray-600 text-xs' }
function formatDate(ts) {
  return ts ? new Date(ts).toLocaleDateString('vi-VN', { day:'2-digit', month:'2-digit', year:'numeric' }) : ''
}

async function load() {
  loading.value = true
  try {
    const res = await incidentApi.myIncidents()
    incidents.value = res.data.data ?? res.data
  } catch {}
  finally { loading.value = false }
}

onMounted(() => { load(); notifStore.fetchNotifications() })
</script>
