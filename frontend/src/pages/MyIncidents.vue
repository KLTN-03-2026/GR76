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
      <!-- Image -->
      <div v-if="selected.hinh_anh" class="cursor-pointer" @click="openImageViewer(selected.hinh_anh, selected.tieu_de)">
        <img :src="selected.hinh_anh" class="w-full h-48 object-cover rounded-xl hover:opacity-80 transition-opacity" />
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
import { ref, computed, onMounted } from 'vue'
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

async function load() {
  loading.value = true
  try {
    const res = await incidentApi.myIncidents()
    incidents.value = res.data.data ?? res.data
  } catch {}
  finally { loading.value = false }
}

onMounted(load)
</script>
