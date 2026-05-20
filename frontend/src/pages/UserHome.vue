<template>
  <div class="p-6 max-w-6xl mx-auto space-y-6">

    <!-- Welcome Banner -->
    <div class="rounded-3xl bg-gradient-to-br from-emerald-700 via-emerald-600 to-green-600 p-8 flex items-center justify-between shadow-2xl border border-emerald-400/30">
      <div>
        <p class="text-emerald-200 text-xs uppercase tracking-wide mb-2 font-semibold">Xin chào 👋</p>
        <h1 class="text-4xl font-bold text-white mb-1">{{ auth.user?.ten || 'Người dùng' }}</h1>
        <p class="text-emerald-100 text-sm font-medium mt-2">
          {{ auth.user?.email }}
          <span v-if="auth.user?.so_dien_thoai"> · {{ auth.user?.so_dien_thoai }}</span>
        </p>
      </div>
      <button @click="showReport = true"
              class="flex items-center gap-2 bg-white/15 hover:bg-white/25 text-white font-semibold px-4 py-2 sm:px-6 sm:py-3 rounded-xl transition-all duration-300 backdrop-blur-sm border border-white/40 shadow-lg hover:shadow-xl hover:scale-105 text-sm sm:text-base">
        <PlusCircleIcon class="w-5 h-5" /> <span class="hidden sm:inline">Báo cáo sự cố mới</span><span class="sm:hidden">Thêm</span>
      </button>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
      <div v-for="card in statCards" :key="card.label"
           :class="['rounded-2xl p-6 border shadow-md hover:shadow-lg transition-all duration-300 hover:scale-105', card.bg]">
        <div class="mb-3" :class="card.color">
          <component :is="card.icon" class="w-8 h-8" />
        </div>
        <div class="text-4xl font-extrabold" :class="card.color">{{ card.value }}</div>
        <div class="text-xs mt-3 font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">{{ card.label }}</div>
      </div>
    </div>

    <!-- Status Progress -->
    <div class="glass-card p-7 shadow-md border border-gray-200/50 dark:border-gray-700/50">
      <div class="flex items-center gap-2 mb-6">
        <ChartBarIcon class="w-6 h-6 text-primary-600 dark:text-primary-400" />
        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Trạng thái báo cáo của bạn</h2>
      </div>
      <div class="flex items-center justify-between gap-2">
        <template v-for="(step, i) in statusSteps" :key="step.key">
          <div class="flex flex-col items-center gap-2 flex-1">
            <div :class="['w-12 h-12 rounded-full flex items-center justify-center text-xl border-2 transition-all shadow-sm',
                          step.count > 0 ? 'border-primary-400 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 bg-gray-50 dark:bg-gray-700 opacity-40']">
              <component :is="step.icon" class="w-6 h-6" :class="step.count > 0 ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400'" />
            </div>
            <p class="text-xs font-semibold text-center text-gray-700 dark:text-gray-300 leading-tight">{{ step.label }}</p>
            <span :class="['text-xl font-extrabold', step.count > 0 ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400']">  
              {{ step.count }}
            </span>
          </div>
          <div v-if="i < statusSteps.length - 1" class="h-0.5 flex-1 bg-gray-200 dark:bg-gray-600 mt-[-20px]" />
        </template>
      </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
      <!-- Recent Incidents -->
      <div class="lg:col-span-2 glass-card overflow-hidden shadow-md border border-gray-200/50 dark:border-gray-700/50">
        <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700/80 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <ClockIcon class="w-5 h-5 text-gray-600 dark:text-gray-400" />
            <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">Báo cáo gần đây</h2>
          </div>
          <router-link to="/my-incidents" class="text-xs text-primary-600 dark:text-primary-400 hover:underline font-semibold transition-colors">
            Xem tất cả →
          </router-link>
        </div>
        <div class="divide-y divide-gray-50 dark:divide-gray-700">
          <div v-if="loading" class="p-4 space-y-3">
            <div v-for="i in 3" :key="i" class="skeleton h-14 rounded-xl" />
          </div>
          <div v-else-if="!recentIncidents.length" class="py-12 text-center text-gray-400">
            <p class="text-4xl mb-3">📭</p>
            <p class="text-sm font-medium">Chưa có báo cáo nào</p>
          </div>
          <div v-else v-for="inc in recentIncidents" :key="inc.id_su_co"
               class="flex items-start gap-4 px-6 py-5 hover:bg-gray-100/50 dark:hover:bg-gray-700/40 cursor-pointer transition-all duration-200"
               @click="$router.push('/my-incidents')">
            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-lg shrink-0">🚨</div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold text-gray-800 dark:text-gray-100 truncate">{{ inc.tieu_de }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-medium">{{ formatDate(inc.thoi_gian_dang || inc.created_at) }}</p>
            </div>
            <span :class="statusBadge(inc.trang_thai)">{{ statusLabelMap[inc.trang_thai] || inc.trang_thai }}</span>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="glass-card p-7 space-y-4 shadow-md border border-gray-200/50 dark:border-gray-700/50">
        <div class="flex items-center gap-2 mb-5">
          <BoltIcon class="w-6 h-6 text-yellow-500" />
          <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">Truy cập nhanh</h2>
        </div>
        <button @click="showReport = true"
                class="w-full flex items-center gap-3 p-4 rounded-xl bg-red-50 hover:bg-red-100 dark:bg-red-900/15 dark:hover:bg-red-900/25 text-red-600 dark:text-red-400 font-semibold text-sm transition-all duration-300 text-left shadow-sm hover:shadow-md border border-red-100/50 dark:border-red-900/30">
          <ExclamationTriangleIcon class="w-8 h-8 opacity-80" />
          <div>
            <p class="font-bold text-sm">Báo cáo sự cố mới</p>
            <p class="text-xs opacity-75 font-medium">Gửi báo cáo kèm ảnh và tọa độ GPS</p>
          </div>
        </button>
        <router-link to="/my-incidents"
                class="w-full flex items-center gap-3 p-4 rounded-xl bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/15 dark:hover:bg-blue-900/25 text-blue-600 dark:text-blue-400 font-semibold text-sm transition-all duration-300 shadow-sm hover:shadow-md border border-blue-100/50 dark:border-blue-900/30">
          <ClipboardDocumentListIcon class="w-8 h-8 opacity-80" />
          <div>
            <p class="font-bold text-sm">Lịch sử báo cáo</p>
            <p class="text-xs opacity-75 font-medium">Xem tất cả sự cố đã báo cáo</p>
          </div>
        </router-link>
        <router-link to="/map"
                class="w-full flex items-center gap-3 p-4 rounded-xl bg-green-50 hover:bg-green-100 dark:bg-green-900/15 dark:hover:bg-green-900/25 text-green-600 dark:text-green-400 font-semibold text-sm transition-all duration-300 shadow-sm hover:shadow-md border border-green-100/50 dark:border-green-900/30">
          <MapIcon class="w-8 h-8 opacity-80" />
          <div>
            <p class="font-bold text-sm">Bản đồ sự cố</p>
            <p class="text-xs opacity-75 font-medium">Xem sự cố trên bản đồ</p>
          </div>
        </router-link>
        <router-link to="/profile"
                class="w-full flex items-center gap-3 p-4 rounded-xl bg-purple-50 hover:bg-purple-100 dark:bg-purple-900/15 dark:hover:bg-purple-900/25 text-purple-600 dark:text-purple-400 font-semibold text-sm transition-all duration-300 shadow-sm hover:shadow-md border border-purple-100/50 dark:border-purple-900/30">
          <UserIcon class="w-8 h-8 opacity-80" />
          <div>
            <p class="font-bold text-sm">Thông tin cá nhân</p>
            <p class="text-xs opacity-75 font-medium">Cập nhật hồ sơ và mật khẩu</p>
          </div>
        </router-link>
      </div>
    </div>
  </div>

  <ReportIncidentModal v-model="showReport" @created="load" />
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { PlusCircleIcon, DocumentTextIcon, WrenchScrewdriverIcon, ClockIcon, CheckCircleIcon, InboxArrowDownIcon, XCircleIcon, ChartBarIcon, BellIcon, BoltIcon, ExclamationTriangleIcon, ClipboardDocumentListIcon, MapIcon, UserIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'
import ReportIncidentModal from '@/components/ReportIncidentModal.vue'
import { incidentApi, adminApi } from '@/services/api'

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
  { icon: DocumentTextIcon, label:'Tổng báo cáo', value: stats.value.total,    bg:'bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700', color:'text-gray-800 dark:text-gray-100' },
  { icon: WrenchScrewdriverIcon, label:'Đang xử lý',   value: stats.value.active,   bg:'bg-yellow-50 dark:bg-yellow-900/10 border-yellow-100 dark:border-yellow-900/20', color:'text-yellow-600' },
  { icon: ClockIcon, label:'Chờ xử lý',     value: stats.value.pending,  bg:'bg-blue-50 dark:bg-blue-900/10 border-blue-100 dark:border-blue-900/20', color:'text-blue-600' },
  { icon: CheckCircleIcon, label:'Đã giải quyết', value: stats.value.resolved, bg:'bg-green-50 dark:bg-green-900/10 border-green-100 dark:border-green-900/20', color:'text-green-600' },
])

const statusSteps = computed(() => [
  { key:'new',      icon: InboxArrowDownIcon, label:'Chờ xử lý',    count: stats.value.pending },
  { key:'active',   icon: WrenchScrewdriverIcon, label:'Đang xử lý',   count: stats.value.active },
  { key:'resolved', icon: CheckCircleIcon, label:'Đã giải quyết', count: stats.value.resolved },
  { key:'rejected', icon: XCircleIcon, label:'Từ chối',       count: incidents.value.filter(i => i.trang_thai === 'rejected').length },
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
    const res = auth.isAdmin ? await adminApi.incidents() : await incidentApi.myIncidents()
    incidents.value = res.data.data ?? res.data
  } catch {}
  finally { loading.value = false }
}

// Handle real-time auto-resolve: update local list or reload
function onAutoResolved(e) {
  const { id_su_co, trang_thai } = e.detail
  const inc = incidents.value.find(i => i.id_su_co === id_su_co)
  if (inc) {
    inc.trang_thai = trang_thai
  }
}

onMounted(() => {
  load()
  notifStore.fetchNotifications()
  window.addEventListener('incident-auto-resolved', onAutoResolved)
})

onBeforeUnmount(() => {
  window.removeEventListener('incident-auto-resolved', onAutoResolved)
})
</script>
