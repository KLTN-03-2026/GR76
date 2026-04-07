<template>
  <div :class="['glass-card p-5 flex flex-col gap-3 hover:shadow-xl transition-all duration-300 hover:-translate-y-1 cursor-pointer group', compact ? 'p-4' : '']"
       @click="$emit('click', incident)">
    <!-- Header row -->
    <div class="flex items-start justify-between gap-2">
      <div class="flex-1 min-w-0">
        <h3 class="font-semibold text-gray-800 dark:text-gray-100 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors truncate">
          {{ incident.tieu_de || incident.title }}
        </h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
          📍 {{ incident.dia_chi || 'Chưa có địa chỉ' }}
        </p>
      </div>
      <span :class="statusBadge">{{ statusLabel }}</span>
    </div>

    <!-- Content preview -->
    <p v-if="!compact" class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2">
      {{ incident.noi_dung || incident.description || 'Không có mô tả' }}
    </p>

    <!-- Tags row -->
    <div class="flex items-center gap-2 flex-wrap">
      <span v-if="incident.loai_su_co || incident.category"
            class="text-xs px-2.5 py-1 rounded-full bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 font-medium">
        {{ incident.loai_su_co?.ten || incident.category?.name || incident.id_loai_su_co }}
      </span>
      <span v-if="incident.muc_do || incident.level"
            :class="['text-xs px-2.5 py-1 rounded-full font-medium', severityClass]">
        ⚡ {{ incident.muc_do?.ten || incident.level?.name || 'Chưa phân loại' }}
      </span>
    </div>

    <!-- Footer -->
    <div class="flex items-center justify-between text-xs text-gray-400 dark:text-gray-500 pt-1 border-t border-gray-50 dark:border-gray-700/50">
      <span>{{ formatDate(incident.created_at) }}</span>
      <span v-if="incident.user || incident.nguoi_bao" class="truncate max-w-[120px]">
        👤 {{ incident.user?.name || incident.user?.ho_ten || incident.nguoi_bao }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  incident: { type: Object, required: true },
  compact:  { type: Boolean, default: false }
})
defineEmits(['click'])

const statusMap = {
  cho_xu_ly:     { label: 'Chờ xử lý',   cls: 'badge-pending' },
  pending:       { label: 'Chờ xử lý',   cls: 'badge-pending' },
  dang_xu_ly:    { label: 'Đang xử lý',  cls: 'badge-active' },
  active:        { label: 'Đang xử lý',  cls: 'badge-active' },
  in_progress:   { label: 'Đang xử lý',  cls: 'badge-active' },
  da_giai_quyet: { label: 'Đã giải quyết', cls: 'badge-resolved' },
  resolved:      { label: 'Đã giải quyết', cls: 'badge-resolved' },
  tu_choi:       { label: 'Từ chối',     cls: 'badge-rejected' },
  rejected:      { label: 'Từ chối',     cls: 'badge-rejected' }
}

const status = computed(() => props.incident.trang_thai || props.incident.status || 'pending')
const statusBadge = computed(() => statusMap[status.value]?.cls || 'badge-pending')
const statusLabel = computed(() => statusMap[status.value]?.label || status.value)

const sevMap = {
  cao:   'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400',
  high:  'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400',
  trung_binh: 'bg-orange-100 text-orange-700 dark:bg-orange-900/20 dark:text-orange-400',
  medium: 'bg-orange-100 text-orange-700 dark:bg-orange-900/20 dark:text-orange-400',
  thap:  'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400',
  low:   'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400'
}
const sevKey = computed(() => {
  const m = props.incident.muc_do || props.incident.level
  return (m?.ten || m?.name || '').toLowerCase()
})
const severityClass = computed(() => sevMap[sevKey.value] || 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300')

function formatDate(ts) {
  if (!ts) return ''
  return new Date(ts).toLocaleString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>
