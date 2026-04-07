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
          <option value="cho_xu_ly">Chờ xử lý</option>
          <option value="dang_xu_ly">Đang xử lý</option>
          <option value="da_giai_quyet">Đã giải quyết</option>
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
      <div class="flex gap-2 flex-wrap">
        <span :class="['badge', statusClass(selected)]">{{ statusLabel(selected) }}</span>
        <span v-if="selected.muc_do" class="badge bg-orange-100 text-orange-700">⚡ {{ selected.muc_do?.ten }}</span>
        <span v-if="selected.loai_su_co" class="badge bg-blue-100 text-blue-700">{{ selected.loai_su_co?.ten }}</span>
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
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import IncidentCard from '@/components/IncidentCard.vue'
import SkeletonLoader from '@/components/ui/SkeletonLoader.vue'
import Modal from '@/components/ui/Modal.vue'
import ReportIncidentModal from '@/components/ReportIncidentModal.vue'
import { PlusIcon } from '@heroicons/vue/24/outline'
import { incidentApi } from '@/services/api'

const incidents    = ref([])
const loading      = ref(false)
const selected     = ref(null)
const showDetail   = ref(false)
const showReport   = ref(false)
const filterStatus = ref('')

const filtered = computed(() => {
  if (!filterStatus.value) return incidents.value
  return incidents.value.filter(i => (i.trang_thai || i.status) === filterStatus.value)
})

const smCls = { cho_xu_ly:'badge-pending', dang_xu_ly:'badge-active', da_giai_quyet:'badge-resolved', pending:'badge-pending' }
const smLbl = { cho_xu_ly:'Chờ xử lý', dang_xu_ly:'Đang xử lý', da_giai_quyet:'Đã giải quyết', pending:'Chờ xử lý' }
function statusClass(i) { return smCls[i.trang_thai || i.status] || 'badge-pending' }
function statusLabel(i) { return smLbl[i.trang_thai || i.status] || i.trang_thai }
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
