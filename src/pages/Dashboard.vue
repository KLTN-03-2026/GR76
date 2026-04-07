<template>
  <div class="p-6 max-w-7xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">📊 Dashboard</h1>
        <p class="text-sm text-gray-500 mt-0.5">Tổng quan hệ thống SOS</p>
      </div>
      <button @click="load" class="btn-outline text-sm flex items-center gap-2">
        <ArrowPathIcon class="w-4 h-4" :class="loading && 'animate-spin'" /> Làm mới
      </button>
    </div>

    <!-- Stat cards -->
    <div v-if="loading" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="i in 4" :key="i" class="skeleton rounded-2xl h-28" />
    </div>
    <div v-else class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="card in statCards" :key="card.label" class="stat-card">
        <div class="flex items-center justify-between">
          <span class="text-3xl">{{ card.icon }}</span>
          <span :class="['text-xs font-bold px-2 py-0.5 rounded-full', card.color]">{{ card.change }}</span>
        </div>
        <div class="text-3xl font-extrabold text-gray-800 dark:text-gray-100">{{ card.value }}</div>
        <div class="text-sm text-gray-500 dark:text-gray-400">{{ card.label }}</div>
      </div>
    </div>

    <!-- Charts row -->
    <div class="grid lg:grid-cols-3 gap-6">
      <!-- Bar chart -->
      <div class="glass-card p-5 lg:col-span-2">
        <h2 class="font-semibold text-gray-700 dark:text-gray-200 mb-4">📈 Sự cố theo trạng thái</h2>
        <div class="h-56">
          <Bar v-if="barData" :data="barData" :options="barOptions" />
          <div v-else class="skeleton h-full rounded-xl" />
        </div>
      </div>

      <!-- Pie chart -->
      <div class="glass-card p-5">
        <h2 class="font-semibold text-gray-700 dark:text-gray-200 mb-4">🍩 Theo danh mục</h2>
        <div class="h-56 flex items-center justify-center">
          <Doughnut v-if="pieData" :data="pieData" :options="pieOptions" />
          <div v-else class="skeleton w-full h-full rounded-xl" />
        </div>
      </div>
    </div>

    <!-- Latest incidents table -->
    <div class="glass-card overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
        <h2 class="font-semibold text-gray-700 dark:text-gray-200">🕐 Sự cố gần đây</h2>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Tiêu đề</th>
              <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Địa chỉ</th>
              <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Trạng thái</th>
              <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Thời gian</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
            <template v-if="loading">
              <tr v-for="i in 5" :key="i"><td colspan="4" class="px-6 py-3"><div class="skeleton h-5 rounded" /></td></tr>
            </template>
            <template v-else>
              <tr v-for="inc in latestIncidents" :key="inc.id" class="hover:bg-primary-50/50 dark:hover:bg-gray-700/30 transition-colors">
                <td class="px-6 py-3 font-medium text-gray-800 dark:text-gray-100 max-w-[180px] truncate">{{ inc.tieu_de }}</td>
                <td class="px-6 py-3 text-gray-500 dark:text-gray-400 max-w-[160px] truncate">{{ inc.dia_chi || '-' }}</td>
                <td class="px-6 py-3"><span :class="statusClass(inc)">{{ statusLabel(inc) }}</span></td>
                <td class="px-6 py-3 text-gray-400 text-xs">{{ formatDate(inc.created_at) }}</td>
              </tr>
              <tr v-if="!latestIncidents.length">
                <td colspan="4" class="text-center py-10 text-gray-400">Không có dữ liệu</td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Bar, Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement } from 'chart.js'
import { ArrowPathIcon } from '@heroicons/vue/24/outline'
import { adminApi } from '@/services/api'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement)

const data    = ref(null)
const loading = ref(false)

const statCards = computed(() => {
  if (!data.value) return []
  const d = data.value
  return [
    { icon: '📋', label: 'Tổng sự cố',     value: d.tong_su_co   ?? 0, change: 'Tổng',  color: 'bg-blue-100 text-blue-700' },
    { icon: '🆕', label: 'Mới tiếp nhận',  value: d.moi_tiep_nhan ?? 0, change: 'Mới',   color: 'bg-yellow-100 text-yellow-700' },
    { icon: '🔧', label: 'Đang xử lý',     value: d.dang_xu_ly   ?? 0, change: 'Active', color: 'bg-orange-100 text-orange-600' },
    { icon: '✅', label: 'Đã xác thực',    value: d.da_xac_thuc  ?? 0, change: 'Xong',  color: 'bg-green-100 text-green-700' },
  ]
})

const latestIncidents = computed(() => (data.value?.moi_nhat ?? []).slice(0, 5))

const barData = computed(() => {
  if (!data.value) return null
  const d = data.value
  return {
    labels: ['Mới tiếp nhận', 'Đang xử lý', 'Đã xác thực', 'Từ chối'],
    datasets: [{
      label: 'Số lượng',
      data: [d.moi_tiep_nhan ?? 0, d.dang_xu_ly ?? 0, d.da_xac_thuc ?? 0, d.tu_choi ?? 0],
      backgroundColor: ['#fef08a', '#fdba74', '#86efac', '#fca5a5'],
      borderRadius: 8, borderSkipped: false
    }]
  }
})

const pieData = computed(() => {
  const cats = data.value?.theo_loai_su_co ?? []
  if (!cats.length) return null
  const palette = ['#A8E6CF','#7dd3a8','#93c5fd','#fca5a5','#fef08a','#d8b4fe','#fdba74']
  return {
    labels: cats.map(c => c.ten_loai ?? 'Không rõ'),
    datasets: [{ data: cats.map(c => c.so_luong ?? 0), backgroundColor: palette, hoverOffset: 8 }]
  }
})

const barOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
const pieOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } } }

// Status badge for latest incidents table
const statusColors = {
  'Mới tiếp nhận': 'badge bg-yellow-100 text-yellow-700',
  'Đang xử lý':   'badge bg-orange-100 text-orange-600',
  'Đã xác thực':  'badge bg-green-100 text-green-700',
  'Từ chối':      'badge bg-red-100 text-red-600',
}
function statusClass(i) { return statusColors[i.trang_thai] || 'badge bg-gray-100 text-gray-600' }
function statusLabel(i) { return i.trang_thai || '-' }
function formatDate(ts) { return ts ? new Date(ts).toLocaleString('vi-VN', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' }) : '' }

async function load() {
  loading.value = true
  try {
    const res = await adminApi.dashboard()
    data.value = res.data.data ?? res.data
  } catch (e) {
    console.error('Dashboard load error:', e)
  }
  finally { loading.value = false }
}

onMounted(load)
</script>
