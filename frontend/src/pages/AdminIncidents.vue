<template>
  <div class="p-6 max-w-7xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">🚨 Quản lý Sự cố</h1>
        <p class="text-sm text-gray-500 mt-0.5">Xem và xử lý tất cả sự cố trong hệ thống</p>
      </div>
      <button @click="load" class="btn-outline text-sm flex items-center gap-2">
        <ArrowPathIcon class="w-4 h-4" :class="loading && 'animate-spin'" /> Làm mới
      </button>
    </div>

    <!-- Search & Filter -->
    <div class="glass-card p-4 flex flex-wrap gap-3">
      <div class="flex items-center flex-1 min-w-[200px] gap-2 bg-gray-50 dark:bg-gray-700/50 rounded-xl px-3 py-2">
        <MagnifyingGlassIcon class="w-4 h-4 text-gray-400 shrink-0" />
        <input v-model="search" @keyup.enter="doSearch" type="text" placeholder="Tìm kiếm sự cố..." class="bg-transparent flex-1 text-sm focus:outline-none dark:text-white" />
      </div>
      <select v-model="filterStatus" class="select-field w-auto text-sm py-2">
        <option value="">Tất cả trạng thái</option>
        <option value="pending">Chờ xử lý</option>
        <option value="in_progress">Đang xử lý</option>
        <option value="resolved">Đã giải quyết</option>
        <option value="rejected">Từ chối</option>
      </select>
    </div>

    <!-- Table -->
    <div class="glass-card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">ID</th>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Tiêu đề</th>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Hình ảnh</th>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Người báo</th>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Loại</th>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Thời gian</th>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
            <template v-if="loading">
              <tr v-for="i in 6" :key="i"><td colspan="8" class="px-4 py-3"><div class="skeleton h-5 rounded" /></td></tr>
            </template>
            <template v-else>
              <tr v-for="inc in filtered" :key="inc.id_su_co" class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                <td class="px-4 py-3 text-gray-400 text-xs">#{{ inc.id_su_co }}</td>
                <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-100 max-w-[200px]">
                  <p class="truncate">{{ inc.tieu_de }}</p>
                  <p class="text-xs text-gray-400 truncate">📍 {{ inc.dia_chi }}</p>
                </td>
                <td class="px-4 py-3">
                  <div v-if="imageUrls(inc).length" class="flex gap-1">
                    <img v-for="(url, i) in imageUrls(inc).slice(0, 3)" :key="i"
                         :src="url"
                         class="w-10 h-10 rounded-lg object-cover cursor-pointer hover:opacity-80 transition-opacity border border-gray-200 dark:border-gray-600"
                         :title="`Ảnh ${i+1}`"
                         @click="openImageViewer(url, inc.tieu_de)" />
                    <span v-if="imageUrls(inc).length > 3" class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-xs text-gray-500 font-bold">+{{ imageUrls(inc).length - 3 }}</span>
                  </div>
                  <span v-else class="text-xs text-gray-400">—</span>
                </td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs">{{ inc.nguoi_dung?.ten || '-' }}</td>
                <td class="px-4 py-3 text-xs text-gray-500">{{ inc.loai_su_co?.ten_loai || '-' }}</td>
                <td class="px-4 py-3">
                  <span :class="statusClass(inc.trang_thai)">{{ statusLabelMap[inc.trang_thai] || inc.trang_thai }}</span>
                </td>
                <td class="px-4 py-3 text-xs text-gray-400">{{ formatDate(inc.thoi_gian_dang) }}</td>
                <td class="px-4 py-3">
                  <div class="flex gap-1 flex-wrap">
                    <button @click="openStatus(inc)" class="btn-xs bg-blue-50 text-blue-600 hover:bg-blue-100">✏️ TT</button>
                    <button @click="confirmDelete(inc)" class="btn-xs bg-red-50 text-red-600 hover:bg-red-100">🗑️</button>
                  </div>
                </td>
              </tr>
              <tr v-if="!filtered.length">
                <td colspan="8" class="text-center py-12 text-gray-400">Không có sự cố nào</td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Update Status Modal -->
    <Modal v-model="showStatus" :title="`Cập nhật trạng thái - ${selectedInc?.tieu_de}`" size="sm">
      <div class="space-y-4" v-if="selectedInc">
        <div>
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Trạng thái mới</label>
          <select v-model="newStatus" class="select-field">
            <option value="pending">Chờ xử lý</option>
            <option value="in_progress">Đang xử lý</option>
            <option value="resolved">Đã giải quyết</option>
            <option value="rejected">Từ chối</option>
          </select>
        </div>
        <div class="flex gap-3">
          <button @click="showStatus = false" class="btn-ghost flex-1">Hủy</button>
          <button @click="updateStatus" :disabled="saving" class="btn-primary flex-1 flex items-center justify-center gap-2">
            <span v-if="saving" class="w-4 h-4 border-2 border-gray-300 border-t-white rounded-full animate-spin" />
            Cập nhật
          </button>
        </div>
      </div>
    </Modal>

    <!-- Confirm Delete Modal -->
    <Modal v-model="showDelete" title="⚠️ Xác nhận xóa" size="sm">
      <div class="space-y-4">
        <p class="text-sm text-gray-600 dark:text-gray-300">Bạn có chắc muốn xóa sự cố <strong>{{ selectedInc?.tieu_de }}</strong>? Hành động này không thể hoàn tác.</p>
        <div class="flex gap-3">
          <button @click="showDelete = false" class="btn-ghost flex-1">Hủy</button>
          <button @click="deleteIncident" :disabled="saving" class="btn-emergency flex-1 flex items-center justify-center gap-2">
            <span v-if="saving" class="w-4 h-4 border-2 border-red-300 border-t-white rounded-full animate-spin" />
            Xóa
          </button>
        </div>
      </div>
    </Modal>

    <!-- Image Viewer -->
    <ImageViewerModal v-model="showImageViewer" :src="viewerImageSrc" :alt="viewerImageAlt" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useToast } from 'vue-toastification'
import { ArrowPathIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline'
import Modal from '@/components/ui/Modal.vue'
import ImageViewerModal from '@/components/ImageViewerModal.vue'
import { adminApi } from '@/services/api'
import echo from '@/services/echo'

const toast = useToast()
const incidents = ref([])
const loading   = ref(false)
const saving    = ref(false)
const search    = ref('')
const filterStatus = ref('')
const selectedInc  = ref(null)
const newStatus = ref('')
const showStatus = ref(false)
const showDelete = ref(false)

// Image viewer state
const showImageViewer = ref(false)
const viewerImageSrc  = ref('')
const viewerImageAlt  = ref('')

function openImageViewer(src, alt = '') {
  viewerImageSrc.value = src
  viewerImageAlt.value = alt
  showImageViewer.value = true
}

const statusLabelMap = {
  pending: 'Chờ xử lý',
  in_progress: 'Đang xử lý',
  resolved: 'Đã giải quyết',
  rejected: 'Từ chối'
}

const statusColors = {
  'pending':     'badge bg-yellow-100 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-400',
  'in_progress': 'badge bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400',
  'resolved':    'badge bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400',
  'rejected':    'badge bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400',
}
function statusClass(s) { return statusColors[s] || 'badge bg-gray-100 text-gray-600' }
function formatDate(ts) { return ts ? new Date(ts).toLocaleString('vi-VN', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' }) : '' }

const filtered = computed(() => {
  let list = incidents.value
  if (filterStatus.value) list = list.filter(i => i.trang_thai === filterStatus.value)
  if (search.value) {
    const q = search.value.toLowerCase()
    list = list.filter(i => (i.tieu_de||'').toLowerCase().includes(q) || (i.dia_chi||'').toLowerCase().includes(q) || (i.nguoi_dung?.ten||'').toLowerCase().includes(q))
  }
  return list
})

async function load() {
  loading.value = true
  try {
    const res = await adminApi.incidents()
    incidents.value = res.data.data ?? res.data
  } catch { toast.error('Không thể tải danh sách sự cố') }
  finally { loading.value = false }
}

async function doSearch() {
  if (!search.value.trim()) return load()
  loading.value = true
  try {
    const res = await adminApi.incidentSearch(search.value)
    incidents.value = res.data.data ?? res.data
  } catch {} finally { loading.value = false }
}

function openStatus(inc) {
  selectedInc.value = inc
  newStatus.value = inc.trang_thai
  showStatus.value = true
}

async function updateStatus() {
  saving.value = true
  try {
    await adminApi.incidentStatus(selectedInc.value.id_su_co, newStatus.value)
    selectedInc.value.trang_thai = newStatus.value
    showStatus.value = false
    toast.success('✅ Đã cập nhật trạng thái!')
  } catch (e) { toast.error(e.response?.data?.message || 'Lỗi cập nhật') }
  finally { saving.value = false }
}

function confirmDelete(inc) {
  selectedInc.value = inc
  showDelete.value = true
}

async function deleteIncident() {
  saving.value = true
  try {
    await adminApi.incidentDelete(selectedInc.value.id_su_co)
    incidents.value = incidents.value.filter(i => i.id_su_co !== selectedInc.value.id_su_co)
    showDelete.value = false
    toast.success('🗑️ Đã xóa sự cố!')
  } catch (e) { toast.error(e.response?.data?.message || 'Lỗi xóa') }
  finally { saving.value = false }
}

// Helper: get all image URLs
function imageUrls(inc) {
  if (!inc) return []
  const multi = (inc.hinh_anhs_urls || []).filter(Boolean)
  if (multi.length > 0) return multi
  if (inc.hinh_anh_url) return [inc.hinh_anh_url]
  if (inc.hinh_anh) return [inc.hinh_anh]
  return []
}

// ── Realtime: listen for new incidents & status changes ───────────
let echoChannel = null

onMounted(() => {
  load()

  echoChannel = echo.channel('incidents')
  echoChannel.listen('.NewIncidentCreated', (data) => {
    const newInc = data.incident
    if (newInc) {
      const exists = incidents.value.some(i => i.id_su_co === newInc.id_su_co)
      if (!exists) {
        incidents.value.unshift(newInc)
        toast.info(`🆕 Sự cố mới: ${newInc.tieu_de || 'Không rõ'}`, { timeout: 5000 })
      }
    }
  })

  echoChannel.listen('.IncidentStatusChanged', (data) => {
    const inc = incidents.value.find(i => i.id_su_co === data.id_su_co)
    if (inc) {
      inc.trang_thai = data.trang_thai
      if (data.triggered_by === 'auto') {
        toast.info(`🔄 Sự cố #${data.id_su_co} tự động giải quyết sau 12 giờ`, { timeout: 5000 })
      }
    }
  })
})

onUnmounted(() => {
  if (echoChannel) echo.leave('incidents')
})
</script>

<style scoped>
.btn-xs { @apply px-2 py-1 rounded-lg text-xs font-medium transition-colors cursor-pointer; }
</style>
