<template>
  <div class="p-6 max-w-5xl mx-auto space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">🔔 Quản lý Thông báo</h1>
      <p class="text-sm text-gray-500 mt-0.5">Gửi thông báo đến toàn bộ hoặc từng người dùng</p>
    </div>

    <div class="grid lg:grid-cols-5 gap-6">
      <!-- Send form -->
      <div class="lg:col-span-2 glass-card p-6">
        <h2 class="font-semibold text-gray-700 dark:text-gray-200 mb-5">📩 Gửi thông báo mới</h2>
        <form @submit.prevent="sendNotification" class="space-y-4">

          <!-- Recipient toggle -->
          <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">Người nhận</label>
            <div class="flex rounded-xl overflow-hidden border border-gray-200 dark:border-gray-600">
              <button type="button" @click="broadcastMode = true"
                :class="['flex-1 py-2.5 text-sm font-semibold transition-colors',
                         broadcastMode
                           ? 'bg-primary-600 text-white'
                           : 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100']">
                🌐 Tất cả người dùng
              </button>
              <button type="button" @click="broadcastMode = false"
                :class="['flex-1 py-2.5 text-sm font-semibold transition-colors',
                         !broadcastMode
                           ? 'bg-primary-600 text-white'
                           : 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100']">
                👤 Chọn 1 người
              </button>
            </div>
          </div>

          <!-- Single user select (hidden in broadcast mode) -->
          <div v-if="!broadcastMode">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Người nhận *</label>
            <select v-model="form.id_nguoi_dung" :required="!broadcastMode" class="select-field">
              <option value="">-- Chọn người dùng --</option>
              <option v-for="u in users" :key="u.id_nguoi_dung" :value="u.id_nguoi_dung">
                {{ u.ten }} ({{ u.email }})
              </option>
            </select>
          </div>

          <!-- Broadcast badge -->
          <div v-else class="rounded-xl bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-700 px-4 py-3">
            <p class="text-sm font-semibold text-primary-700 dark:text-primary-300">🌐 Gửi đến toàn bộ người dùng</p>
            <p class="text-xs text-primary-500 mt-0.5">Thông báo sẽ được gửi đến {{ users.length }} người dùng</p>
          </div>

          <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Tiêu đề *</label>
            <input v-model="form.tieu_de" type="text" required class="input-field" placeholder="Tiêu đề thông báo" />
          </div>
          <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Nội dung *</label>
            <textarea v-model="form.noi_dung" required rows="4" class="input-field resize-none" placeholder="Nội dung chi tiết..." />
          </div>
          <button type="submit" :disabled="sending" class="btn-primary w-full flex items-center justify-center gap-2">
            <span v-if="sending" class="w-4 h-4 border-2 border-gray-300 border-t-white rounded-full animate-spin" />
            {{ sending ? 'Đang gửi...' : (broadcastMode ? '📢 Gửi đến tất cả' : '📤 Gửi thông báo') }}
          </button>
        </form>
      </div>

      <!-- Notification list -->
      <div class="lg:col-span-3 glass-card overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
          <h2 class="font-semibold text-gray-700 dark:text-gray-200">📋 Lịch sử thông báo</h2>
          <button @click="loadNotifications" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1">
            <ArrowPathIcon class="w-4 h-4" :class="loading && 'animate-spin'" />
          </button>
        </div>
        <div class="divide-y divide-gray-50 dark:divide-gray-700 max-h-[600px] overflow-y-auto">
          <div v-if="loading" class="p-4 space-y-3">
            <div v-for="i in 4" :key="i" class="skeleton h-16 rounded-xl" />
          </div>
          <div v-else-if="!notifications.length" class="py-14 text-center text-gray-400">
            <p class="text-3xl mb-2">📭</p>
            <p class="text-sm">Chưa có thông báo nào</p>
          </div>
          <div v-else v-for="n in notifications" :key="n.id_thong_bao"
               class="px-6 py-4 hover:bg-gray-50/50 dark:hover:bg-gray-700/30">
            <div class="flex items-start justify-between gap-2">
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ n.tieu_de }}</p>
                <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ n.noi_dung }}</p>
                <div class="flex items-center gap-2 mt-1">
                  <span class="text-xs text-primary-600 dark:text-primary-400">
                    👤 {{ n.nguoi_dung?.ten || 'N/A' }}
                  </span>
                  <span class="text-xs text-gray-400">{{ formatDate(n.created_at) }}</span>
                </div>
              </div>
              <span :class="n.da_doc ? 'badge bg-gray-100 text-gray-500' : 'badge bg-yellow-100 text-yellow-700'">
                {{ n.da_doc ? '✓ Đã đọc' : '• Chưa đọc' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { ArrowPathIcon } from '@heroicons/vue/24/outline'
import { adminApi } from '@/services/api'

const toast = useToast()
const notifications = ref([])
const users  = ref([])
const loading = ref(false)
const sending = ref(false)
const broadcastMode = ref(true) // default: broadcast to all

const form = reactive({ id_nguoi_dung: '', tieu_de: '', noi_dung: '' })

function formatDate(ts) {
  return ts ? new Date(ts).toLocaleString('vi-VN', { day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit' }) : ''
}

async function loadNotifications() {
  loading.value = true
  try {
    const res = await adminApi.notifications()
    notifications.value = res.data.data ?? res.data
  } catch { toast.error('Không thể tải thông báo') }
  finally { loading.value = false }
}

async function loadUsers() {
  try {
    const res = await adminApi.users()
    users.value = res.data.data ?? res.data
  } catch {}
}

async function sendNotification() {
  if (!broadcastMode.value && !form.id_nguoi_dung) {
    toast.warning('Vui lòng chọn người nhận')
    return
  }
  sending.value = true
  try {
    const payload = {
      tieu_de: form.tieu_de,
      noi_dung: form.noi_dung,
      id_nguoi_dung: broadcastMode.value ? 'all' : form.id_nguoi_dung,
    }
    const res = await adminApi.sendNotification(payload)
    const d = res.data
    if (broadcastMode.value) {
      toast.success(`📢 Đã gửi đến ${d.count ?? 'tất cả'} người dùng!`)
    } else {
      notifications.value.unshift(d.data ?? d)
      toast.success('📤 Đã gửi thông báo thành công!')
    }
    Object.assign(form, { id_nguoi_dung: '', tieu_de: '', noi_dung: '' })
    // Reload list
    loadNotifications()
  } catch (e) { toast.error(e.response?.data?.message || 'Gửi thất bại') }
  finally { sending.value = false }
}

onMounted(() => { loadNotifications(); loadUsers() })
</script>

