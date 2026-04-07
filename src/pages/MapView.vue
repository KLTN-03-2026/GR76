<template>
  <div class="h-[calc(100vh-4rem)] flex relative" style="overflow:clip">

    <!-- ── Left Sidebar ─────────────────────────────── -->
    <aside class="relative w-72 shrink-0 bg-gray-900 text-white flex flex-col z-[500] shadow-2xl">
      <!-- Search -->
      <div class="p-4 border-b border-gray-700/60">
        <div class="flex items-center gap-2 bg-gray-800 rounded-xl px-3 py-2.5">
          <MagnifyingGlassIcon class="w-4 h-4 text-gray-400 shrink-0" />
          <input v-model="search" type="text" placeholder="Tìm kiếm sự kiện..."
                 class="flex-1 bg-transparent text-sm text-white placeholder-gray-500 focus:outline-none" />
          <button class="p-1 text-gray-400 hover:text-white">
            <AdjustmentsHorizontalIcon class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Incident list -->
      <div class="flex-1 overflow-y-auto">
        <div v-if="loading" class="p-4 space-y-3">
          <div v-for="i in 5" :key="i" class="h-16 bg-gray-800 rounded-xl animate-pulse" />
        </div>
        <div v-else-if="!filteredIncidents.length" class="p-6 text-center">
          <p class="text-gray-500 text-sm">Không có sự kiện nào gần đây.</p>
        </div>
        <div v-else v-for="inc in filteredIncidents" :key="inc.id_su_co"
             @click="selectFromSidebar(inc)"
             :class="['flex items-start gap-3 px-4 py-3 cursor-pointer border-b border-gray-800 transition-colors',
                      selected?.id_su_co === inc.id_su_co ? 'bg-gray-700' : 'hover:bg-gray-800']">
          <!-- Pulse dot -->
          <div class="mt-1 shrink-0 relative">
            <span :class="['w-3 h-3 rounded-full block', urgencyDot(inc)]"></span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-white truncate">{{ inc.tieu_de }}</p>
            <p class="text-xs text-gray-400 mt-0.5 truncate">📍 {{ inc.dia_chi || 'Không có địa chỉ' }}</p>
            <div class="flex items-center gap-2 mt-1">
              <span :class="['text-xs px-2 py-0.5 rounded-full font-medium', statusBadge(inc)]">{{ inc.trang_thai }}</span>
              <span class="text-xs text-gray-500">{{ timeAgo(inc.thoi_gian_dang) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Gửi phản ánh button -->
      <div class="p-4 border-t border-gray-700/60">
        <button @click="showReport = true"
                class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-xl flex items-center justify-center gap-2 transition-colors shadow-lg">
          <PlusCircleIcon class="w-5 h-5" />
          Gửi phản ánh
        </button>
      </div>
    </aside>

    <!-- ── Map Area ───────────────────────────────────── -->
    <div class="flex-1 relative isolate">

      <!-- Filter tab bar (top center) -->
      <div class="absolute top-4 left-1/2 -translate-x-1/2 z-[1000] flex items-center gap-2 pointer-events-auto">
        <button v-for="tab in filterTabs" :key="tab.value"
                @click="activeFilter = tab.value"
                :class="['flex items-center gap-1.5 px-4 py-2 rounded-full text-sm font-semibold shadow-lg transition-all',
                         activeFilter === tab.value
                           ? 'text-white shadow-md scale-105 ' + tab.activeClass
                           : 'bg-gray-900/90 text-gray-300 hover:bg-gray-800']">
          <span>{{ tab.icon }}</span>
          {{ tab.label }}
          <span v-if="tab.count > 0" class="ml-1 bg-white/20 text-xs px-1.5 py-0.5 rounded-full">{{ tab.count }}</span>
        </button>
      </div>

      <!-- Map (only active incidents) -->
      <MapComponent
        ref="mapRef"
        :incidents="mapIncidents"
        class="absolute inset-0 w-full h-full"
        @marker-click="onMarkerClick"
      />

      <!-- Bottom-right controls -->
      <div class="absolute bottom-8 right-6 z-[1000] flex flex-col gap-2 pointer-events-auto">
        <!-- Refresh -->
        <button @click="loadIncidents"
                :class="['w-12 h-12 rounded-2xl text-white flex items-center justify-center shadow-xl transition-all hover:scale-105',
                         loading ? 'bg-gray-600 cursor-wait' : 'bg-green-600 hover:bg-green-700']">
          <ArrowPathIcon class="w-5 h-5" :class="loading && 'animate-spin'" />
        </button>
        <!-- My location -->
        <button @click="goToMyLocation"
                class="w-12 h-12 rounded-2xl bg-green-600 hover:bg-green-700 text-white flex items-center justify-center shadow-xl transition-all hover:scale-105">
          <MapPinIcon class="w-5 h-5" />
        </button>
      </div>

      <!-- Incident detail pop-up -->
      <Transition name="slide-right">
        <div v-if="selected"
             class="absolute right-4 top-16 w-80 z-[1000] bg-gray-900/95 backdrop-blur-lg text-white rounded-2xl shadow-2xl border border-gray-700 overflow-hidden">
          <!-- Image -->
          <div v-if="selected.hinh_anh" class="relative">
            <img :src="selected.hinh_anh" class="w-full h-36 object-cover" />
            <div class="absolute inset-0 bg-gradient-to-t from-gray-900/80 to-transparent" />
          </div>
          <!-- Content -->
          <div class="p-4">
            <div class="flex justify-between items-start mb-2">
              <h3 class="font-bold text-base leading-tight pr-2">{{ selected.tieu_de }}</h3>
              <button @click="selected = null" class="p-1 text-gray-400 hover:text-white shrink-0">✕</button>
            </div>
            <p class="text-xs text-gray-400 mb-2">📍 {{ selected.dia_chi || 'Không có địa chỉ' }}</p>
            <p class="text-sm text-gray-300 mb-3 line-clamp-3">{{ selected.noi_dung }}</p>
            <div class="flex gap-2 flex-wrap mb-3">
              <span :class="['text-xs px-2 py-1 rounded-full font-medium', statusBadge(selected)]">
                {{ selected.trang_thai }}
              </span>
              <span v-if="selected.muc_do_khan_cap" class="text-xs px-2 py-1 rounded-full bg-orange-900/40 text-orange-300">
                ⚡ {{ selected.muc_do_khan_cap.ten_muc_do }}
              </span>
            </div>
            <p class="text-xs text-gray-500 mb-3">{{ formatDate(selected.thoi_gian_dang) }}</p>
            <!-- Tiếp nhận button — only when status is Mới tiếp nhận -->
            <button v-if="selected.trang_thai === 'Mới tiếp nhận'"
                    @click="acceptIncident(selected)"
                    :disabled="accepting"
                    class="w-full bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white font-bold py-2.5 rounded-xl flex items-center justify-center gap-2 transition-colors text-sm">
              <span v-if="accepting" class="w-4 h-4 border-2 border-white/50 border-t-white rounded-full animate-spin" />
              {{ accepting ? 'Đang tiếp nhận...' : '🆘 Tiếp nhận sự cố' }}
            </button>
            <p v-else-if="selected.trang_thai === 'Đang xử lý'"
               class="text-center text-xs text-blue-400 py-1">🔧 Đang được xử lý</p>
          </div>
        </div>
      </Transition>
    </div>
  </div>

  <ReportIncidentModal
    v-model="showReport"
    :prefill-lat="clickedLat"
    :prefill-lng="clickedLng"
    @created="afterCreated"
  />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import MapComponent from '@/components/MapComponent.vue'
import ReportIncidentModal from '@/components/ReportIncidentModal.vue'
import {
  MagnifyingGlassIcon, PlusCircleIcon, ArrowPathIcon,
  MapPinIcon, AdjustmentsHorizontalIcon
} from '@heroicons/vue/24/outline'
import { incidentApi } from '@/services/api'

const toast      = useToast()
const incidents  = ref([])
const loading    = ref(false)
const selected   = ref(null)
const showReport = ref(false)
const clickedLat = ref(null)
const clickedLng = ref(null)
const search      = ref('')
const activeFilter = ref('all')
const mapRef      = ref(null)
const accepting   = ref(false)

// ── Filter tabs ──────────────────────────────────────────────────
// CONST: resolved statuses are hidden from map
const ACTIVE_STATUSES = ['Mới tiếp nhận', 'Đang xử lý']

const filterTabs = computed(() => {
  const list = incidents.value.filter(i => ACTIVE_STATUSES.includes(i.trang_thai))
  return [
    { value: 'all',           label: 'Tất cả',     icon: '📌', activeClass: 'bg-gray-700',  count: list.length },
    { value: 'Mới tiếp nhận',label: 'Cần cứu',    icon: '🆘', activeClass: 'bg-red-600',   count: list.filter(i => i.trang_thai === 'Mới tiếp nhận').length },
    { value: 'Đang xử lý',  label: 'Đội cứu hộ', icon: '🚒', activeClass: 'bg-blue-600',  count: list.filter(i => i.trang_thai === 'Đang xử lý').length },
  ]
})

// Sidebar list = active incidents only (resolved hidden)
const filteredIncidents = computed(() => {
  let list = incidents.value.filter(i => ACTIVE_STATUSES.includes(i.trang_thai))
  if (activeFilter.value !== 'all') list = list.filter(i => i.trang_thai === activeFilter.value)
  if (search.value) {
    const q = search.value.toLowerCase()
    list = list.filter(i =>
      (i.tieu_de || '').toLowerCase().includes(q) ||
      (i.dia_chi || '').toLowerCase().includes(q)
    )
  }
  return list
})

// Map receives same active-only filtered list
const mapIncidents = computed(() => filteredIncidents.value)


// ── Urgency dot color ─────────────────────────────────────────────
function urgencyDot(inc) {
  const p = inc.muc_do_khan_cap?.do_uu_tien ?? inc.muc_do?.do_uu_tien ?? 0
  if (p >= 3) return 'bg-red-500'
  if (p >= 2) return 'bg-orange-400'
  if (p >= 1) return 'bg-yellow-400'
  return 'bg-green-400'
}

// ── Status badge ──────────────────────────────────────────────────
const statusStyles = {
  'Mới tiếp nhận': 'bg-yellow-700/50 text-yellow-300',
  'Đang xử lý':   'bg-blue-700/50 text-blue-300',
  'Đã xác thực':  'bg-green-700/50 text-green-300',
  'Từ chối':      'bg-red-700/50 text-red-300',
}
function statusBadge(inc) { return statusStyles[inc.trang_thai] || 'bg-gray-700/50 text-gray-300' }

// ── Time ago ──────────────────────────────────────────────────────
function timeAgo(ts) {
  if (!ts) return ''
  const diff = Date.now() - new Date(ts).getTime()
  const h = Math.floor(diff / 3600000)
  const m = Math.floor(diff / 60000)
  if (h > 24) return `${Math.floor(h/24)} ngày trước`
  if (h >= 1) return `${h}h trước`
  if (m >= 1) return `${m} phút trước`
  return 'Vừa xong'
}

function formatDate(ts) {
  return ts ? new Date(ts).toLocaleString('vi-VN', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' }) : ''
}

// ── API ───────────────────────────────────────────────────────────
async function loadIncidents() {
  loading.value = true
  try {
    const res = await incidentApi.mapIncidents()
    incidents.value = res.data.data ?? res.data
  } catch { toast.error('Không thể tải dữ liệu bản đồ') }
  finally { loading.value = false }
}

function onMapClick({ lat, lng }) {
  clickedLat.value = lat
  clickedLng.value = lng
  showReport.value = true
}

// Called when clicking a Leaflet marker — only set selected, do NOT fly
// (flyTo causes Leaflet to close the popup during pan animation)
function onMarkerClick(inc) {
  selected.value = inc
}

// Accept incident — change status to Đang xử lý
async function acceptIncident(inc) {
  accepting.value = true
  try {
    await incidentApi.tiepNhan(inc.id_su_co)
    toast.success('🆘 Đã tiếp nhận sự cố!')
    // Update in-place so button disappears immediately
    selected.value = { ...inc, trang_thai: 'Đang xử lý' }
    await loadIncidents()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Không thể tiếp nhận sự cố')
  } finally {
    accepting.value = false
  }
}

// Called from sidebar list — set selected AND fly to incident on map
function selectFromSidebar(inc) {
  selected.value = inc
  const lat = parseFloat(inc.vi_do)
  const lng = parseFloat(inc.kinh_do)
  if (!isNaN(lat) && !isNaN(lng)) {
    mapRef.value?.flyToIncident(lat, lng)
  }
}

async function afterCreated() {
  // Reload from API to get fresh data with server-assigned fields
  await loadIncidents()
  // Find the most recently created active incident (sort by id desc)
  const newest = [...incidents.value]
    .filter(i => ACTIVE_STATUSES.includes(i.trang_thai))
    .sort((a, b) => (b.id_su_co ?? 0) - (a.id_su_co ?? 0))[0]
  if (newest) onMarkerClick(newest)
}

function goToMyLocation() {
  if (!navigator.geolocation) {
    toast.error('Trình duyệt không hỗ trợ GPS')
    return
  }
  navigator.geolocation.getCurrentPosition(
    pos => {
      const { latitude: lat, longitude: lng } = pos.coords
      mapRef.value?.setCenter(lat, lng, 16) // shows blue dot + pans
    },
    () => toast.error('Không lấy được vị trí, vui lòng cho phép truy cập GPS')
  )
}

onMounted(loadIncidents)
</script>

<style scoped>
.slide-right-enter-active, .slide-right-leave-active { transition: transform .2s ease, opacity .2s ease; }
.slide-right-enter-from { transform: translateX(20px); opacity: 0; }
.slide-right-leave-to   { transform: translateX(20px); opacity: 0; }
</style>
