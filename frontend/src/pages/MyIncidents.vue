<template>
  <div class="p-6 max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">📋 Sự cố của tôi</h1>
        <p class="text-sm text-gray-500 mt-0.5">Danh sách sự cố bạn đã báo cáo</p>
      </div>
      <div class="flex gap-3">
        <select v-model="filterStatus" class="select-field w-auto text-sm py-2 px-3">
          <option value="">Tất cả trạng thái</option>
          <option value="pending">Chờ xử lý</option>
          <option value="in_progress">Đang xử lý</option>
          <option value="resolved">Đã giải quyết</option>
          <option value="rejected">Từ chối</option>
        </select>
        <button @click="showReport = true" class="btn-emergency text-sm flex items-center gap-2">
          <PlusIcon class="w-4 h-4" /> Báo cáo mới
        </button>
      </div>
    </div>

    <!-- Loading -->
    <SkeletonLoader v-if="loading" :count="4" :height="140" />

    <!-- Grid -->
    <div v-else-if="filtered.length" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <IncidentCard
        v-for="inc in filtered" :key="inc.id_su_co"
        :incident="inc"
        @click="selected = inc; showDetail = true"
      />
    </div>

    <!-- Empty -->
    <div v-else class="flex flex-col items-center justify-center py-20 text-center">
      <div class="w-24 h-24 bg-primary-50 dark:bg-primary-900/20 rounded-full flex items-center justify-center mb-4 text-5xl">📭</div>
      <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Chưa có sự cố nào</h3>
      <p class="text-sm text-gray-400 mt-1 mb-5">Hãy báo cáo sự cố đầu tiên của bạn</p>
      <button @click="showReport = true" class="btn-primary">➕ Báo cáo ngay</button>
    </div>
  </div>

  <!-- Detail modal -->
  <Modal v-model="showDetail" :title="selected?.tieu_de || ''" size="md">
    <div v-if="selected" class="space-y-4">
      <!-- Images (multiple) -->
      <div v-if="imageUrls(selected).length" class="grid gap-2" :class="imageUrls(selected).length === 1 ? 'grid-cols-1' : 'grid-cols-2'">
        <div v-for="(url, i) in imageUrls(selected)" :key="i"
             class="relative cursor-pointer rounded-xl overflow-hidden"
             @click="openImageViewer(url, selected.tieu_de)">
          <img :src="url" class="w-full h-32 object-cover hover:opacity-80 transition-opacity" @error="e => e.target.style.display='none'" />
          <span v-if="i === 0 && imageUrls(selected).length > 1" class="absolute top-1 left-1 text-xs bg-primary-600 text-white px-1.5 py-0.5 rounded-full">Chính</span>
        </div>
      </div>
      <div v-else class="w-full h-32 bg-gray-100 dark:bg-gray-700 rounded-xl flex items-center justify-center text-gray-400 text-sm">
        📷 Chưa có hình ảnh
      </div>
      <div class="flex gap-2 flex-wrap">
        <span :class="['badge', statusClass(selected)]">{{ statusLabelFn(selected) }}</span>
        <span v-if="selected.muc_do_khan_cap" class="badge bg-orange-100 text-orange-700">⚡ {{ selected.muc_do_khan_cap?.ten_muc_do }}</span>
        <span v-if="selected.loai_su_co" class="badge bg-blue-100 text-blue-700">{{ selected.loai_su_co?.ten_loai }}</span>
      </div>
      <div>
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Nội dung</p>
        <p class="text-sm text-gray-700 dark:text-gray-200">{{ selected.noi_dung }}</p>
      </div>
      <div>
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Địa chỉ</p>
        <p class="text-sm text-gray-700 dark:text-gray-200">{{ selected.dia_chi }}</p>
      </div>
      <div class="text-xs text-gray-400">Báo cáo lúc: {{ formatDate(selected.created_at) }}</div>
    </div>
  </Modal>

  <ReportIncidentModal v-model="showReport" @created="load" />
  <ImageViewerModal v-model="showImageViewer" :src="viewerImageSrc" :alt="viewerImageAlt" />
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import IncidentCard from '@/components/IncidentCard.vue'
import SkeletonLoader from '@/components/ui/SkeletonLoader.vue'
import Modal from '@/components/ui/Modal.vue'
import ReportIncidentModal from '@/components/ReportIncidentModal.vue'
import ImageViewerModal from '@/components/ImageViewerModal.vue'
import { PlusIcon } from '@heroicons/vue/24/outline'
import { incidentApi } from '@/services/api'

const incidents    = ref([])
const loading      = ref(false)
const selected     = ref(null)
const showDetail   = ref(false)
const showReport   = ref(false)
const filterStatus = ref('')

// Image viewer
const showImageViewer = ref(false)
const viewerImageSrc  = ref('')
const viewerImageAlt  = ref('')

function openImageViewer(src, alt = '') {
  viewerImageSrc.value = src
  viewerImageAlt.value = alt
  showImageViewer.value = true
}

const STATUS_LABELS = {
  pending: 'Chờ xử lý',
  in_progress: 'Đang xử lý',
  resolved: 'Đã giải quyết',
  rejected: 'Từ chối'
}

const filtered = computed(() => {
  if (!filterStatus.value) return incidents.value
  return incidents.value.filter(i => i.trang_thai === filterStatus.value)
})

const smCls = { pending:'badge-pending', in_progress:'badge-active', resolved:'badge-resolved', rejected:'badge-rejected' }
function statusClass(i) { return smCls[i.trang_thai] || 'badge-pending' }
function statusLabelFn(i) { return STATUS_LABELS[i.trang_thai] || i.trang_thai }
function formatDate(ts) { return ts ? new Date(ts).toLocaleString('vi-VN') : '' }

// Helper: get all image URLs from incident (supports both old single & new multi)
function imageUrls(inc) {
  if (!inc) return []
  const multi = (inc.hinh_anhs_urls || []).filter(Boolean)
  if (multi.length > 0) return multi
  if (inc.hinh_anh_url) return [inc.hinh_anh_url]
  return []
}

async function load() {
  loading.value = true
  try {
    const res = await incidentApi.myIncidents()
    incidents.value = res.data.data ?? res.data
  } catch {}
  finally { loading.value = false }
}

// Real-time auto-resolve
function onAutoResolved(e) {
  const { id_su_co, trang_thai } = e.detail
  const inc = incidents.value.find(i => i.id_su_co === id_su_co)
  if (inc) inc.trang_thai = trang_thai
  if (selected.value?.id_su_co === id_su_co) selected.value.trang_thai = trang_thai
}

onMounted(() => {
  load()
  window.addEventListener('incident-auto-resolved', onAutoResolved)
})

onBeforeUnmount(() => {
  window.removeEventListener('incident-auto-resolved', onAutoResolved)
})
</script>
