<template>
  <div class="p-6 max-w-7xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">👥 Quản lý Người dùng</h1>
        <p class="text-sm text-gray-500 mt-0.5">Quản lý tài khoản người dùng hệ thống</p>
      </div>
      <button @click="openCreate" class="btn-primary text-sm flex items-center gap-2">
        <PlusIcon class="w-4 h-4" /> Tạo tài khoản
      </button>
    </div>

    <!-- Search -->
    <div class="glass-card p-4 flex gap-3">
      <div class="flex items-center flex-1 gap-2 bg-gray-50 dark:bg-gray-700/50 rounded-xl px-3 py-2">
        <MagnifyingGlassIcon class="w-4 h-4 text-gray-400 shrink-0" />
        <input v-model="search" @keyup.enter="doSearch" type="text" placeholder="Tìm theo tên, email, SĐT..." class="bg-transparent flex-1 text-sm focus:outline-none dark:text-white" />
      </div>
      <button @click="doSearch" class="btn-outline text-sm px-4">Tìm</button>
      <button @click="load" class="btn-ghost text-sm px-3"><ArrowPathIcon class="w-4 h-4" /></button>
    </div>

    <!-- Table -->
    <div class="glass-card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">ID</th>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Họ tên</th>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Email</th>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Số điện thoại</th>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
            <template v-if="loading">
              <tr v-for="i in 6" :key="i"><td colspan="6" class="px-4 py-3"><div class="skeleton h-5 rounded" /></td></tr>
            </template>
            <template v-else>
              <tr v-for="u in users" :key="u.id_nguoi_dung" class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                <td class="px-4 py-3 text-gray-400 text-xs">#{{ u.id_nguoi_dung }}</td>
                <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-100">{{ u.ten }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ u.email }}</td>
                <td class="px-4 py-3 text-gray-500 text-xs">{{ u.so_dien_thoai || '-' }}</td>
                <td class="px-4 py-3">
                  <span :class="u.trang_thai === 'bi_khoa' ? 'badge bg-red-100 text-red-600' : 'badge bg-green-100 text-green-700'">
                    {{ u.trang_thai === 'bi_khoa' ? '🔒 Bị khóa' : '✅ Hoạt động' }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex gap-1 flex-wrap">
                    <button v-if="u.trang_thai !== 'bi_khoa'" @click="lockUser(u)" class="btn-xs bg-orange-50 text-orange-600 hover:bg-orange-100">🔒 Khóa</button>
                    <button v-else @click="unlockUser(u)" class="btn-xs bg-green-50 text-green-600 hover:bg-green-100">🔓 Mở</button>
                    <button @click="openChangePwd(u)" class="btn-xs bg-blue-50 text-blue-600 hover:bg-blue-100">🔑 MK</button>
                    <button @click="confirmDelete(u)" class="btn-xs bg-red-50 text-red-600 hover:bg-red-100">🗑️</button>
                  </div>
                </td>
              </tr>
              <tr v-if="!users.length">
                <td colspan="6" class="text-center py-12 text-gray-400">Không có người dùng nào</td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create User Modal -->
    <Modal v-model="showCreate" title="➕ Tạo tài khoản mới" size="md">
      <form @submit.prevent="createUser" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Họ tên *</label>
            <input v-model="createForm.ten" type="text" required class="input-field" />
          </div>
          <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Email *</label>
            <input v-model="createForm.email" type="email" required class="input-field" />
          </div>
          <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Số điện thoại *</label>
            <input v-model="createForm.so_dien_thoai" type="tel" required class="input-field" />
          </div>
          <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Mật khẩu *</label>
            <input v-model="createForm.mat_khau" type="password" required class="input-field" minlength="6" />
          </div>
        </div>
        <div class="flex gap-3">
          <button type="button" @click="showCreate = false" class="btn-ghost flex-1">Hủy</button>
          <button type="submit" :disabled="saving" class="btn-primary flex-1 flex items-center justify-center gap-2">
            <span v-if="saving" class="w-4 h-4 border-2 border-gray-300 border-t-white rounded-full animate-spin" />
            Tạo tài khoản
          </button>
        </div>
      </form>
    </Modal>

    <!-- Change Password Modal -->
    <Modal v-model="showPwd" :title="`🔑 Đổi mật khẩu - ${selected?.ten}`" size="sm">
      <form @submit.prevent="changePassword" class="space-y-4">
        <div>
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Mật khẩu mới *</label>
          <input v-model="pwdForm.mat_khau_moi" type="password" required class="input-field" minlength="6" />
        </div>
        <div class="flex gap-3">
          <button type="button" @click="showPwd = false" class="btn-ghost flex-1">Hủy</button>
          <button type="submit" :disabled="saving" class="btn-primary flex-1 flex items-center justify-center gap-2">
            <span v-if="saving" class="w-4 h-4 border-2 border-gray-300 border-t-white rounded-full animate-spin" />
            Lưu
          </button>
        </div>
      </form>
    </Modal>

    <!-- Confirm Delete Modal -->
    <Modal v-model="showDelete" title="⚠️ Xác nhận xóa" size="sm">
      <div class="space-y-4">
        <p class="text-sm text-gray-600 dark:text-gray-300">Bạn có chắc muốn xóa tài khoản <strong>{{ selected?.ten }}</strong>? Hành động này không thể hoàn tác.</p>
        <div class="flex gap-3">
          <button @click="showDelete = false" class="btn-ghost flex-1">Hủy</button>
          <button @click="deleteUser" :disabled="saving" class="btn-emergency flex-1 flex items-center justify-center gap-2">
            <span v-if="saving" class="w-4 h-4 border-2 border-red-300 border-t-white rounded-full animate-spin" />
            Xóa
          </button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { MagnifyingGlassIcon, PlusIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'
import Modal from '@/components/ui/Modal.vue'
import { adminApi } from '@/services/api'

const toast = useToast()
const users     = ref([])
const loading   = ref(false)
const saving    = ref(false)
const search    = ref('')
const selected  = ref(null)
const showCreate = ref(false)
const showPwd    = ref(false)
const showDelete = ref(false)

const createForm = reactive({ ten: '', email: '', so_dien_thoai: '', mat_khau: '' })
const pwdForm    = reactive({ mat_khau_moi: '' })

async function load() {
  loading.value = true
  try {
    const res = await adminApi.users()
    users.value = res.data.data ?? res.data
  } catch { toast.error('Không thể tải danh sách người dùng') }
  finally { loading.value = false }
}

async function doSearch() {
  if (!search.value.trim()) return load()
  loading.value = true
  try {
    const res = await adminApi.userSearch(search.value)
    users.value = res.data.data ?? res.data
  } catch {} finally { loading.value = false }
}

function openCreate() {
  Object.assign(createForm, { ten: '', email: '', so_dien_thoai: '', mat_khau: '' })
  showCreate.value = true
}

async function createUser() {
  saving.value = true
  try {
    const res = await adminApi.userCreate(createForm)
    users.value.push(res.data.data ?? res.data)
    showCreate.value = false
    toast.success('✅ Tạo tài khoản thành công!')
  } catch (e) {
    const errs = e.response?.data?.errors
    if (errs) toast.error(Object.values(errs).flat().join(', '))
    else toast.error(e.response?.data?.message || 'Lỗi tạo tài khoản')
  } finally { saving.value = false }
}

async function lockUser(u) {
  try {
    await adminApi.userLock(u.id_nguoi_dung)
    u.trang_thai = 'bi_khoa'
    toast.success('🔒 Đã khóa tài khoản!')
  } catch (e) { toast.error(e.response?.data?.message || 'Lỗi') }
}

async function unlockUser(u) {
  try {
    await adminApi.userUnlock(u.id_nguoi_dung)
    u.trang_thai = 'hoat_dong'
    toast.success('🔓 Đã mở khóa tài khoản!')
  } catch (e) { toast.error(e.response?.data?.message || 'Lỗi') }
}

function openChangePwd(u) {
  selected.value = u
  pwdForm.mat_khau_moi = ''
  showPwd.value = true
}

async function changePassword() {
  saving.value = true
  try {
    await adminApi.userChangePassword(selected.value.id_nguoi_dung, pwdForm)
    showPwd.value = false
    toast.success('🔑 Đã đổi mật khẩu!')
  } catch (e) { toast.error(e.response?.data?.message || 'Lỗi') }
  finally { saving.value = false }
}

function confirmDelete(u) {
  selected.value = u
  showDelete.value = true
}

async function deleteUser() {
  saving.value = true
  try {
    await adminApi.userDelete(selected.value.id_nguoi_dung)
    users.value = users.value.filter(u => u.id_nguoi_dung !== selected.value.id_nguoi_dung)
    showDelete.value = false
    toast.success('🗑️ Đã xóa người dùng!')
  } catch (e) { toast.error(e.response?.data?.message || 'Lỗi xóa') }
  finally { saving.value = false }
}

onMounted(load)
</script>

<style scoped>
.btn-xs { @apply px-2 py-1 rounded-lg text-xs font-medium transition-colors cursor-pointer; }
</style>
