<template>
  <div class="p-6 max-w-3xl mx-auto space-y-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">👤 Hồ sơ cá nhân</h1>

    <SkeletonLoader v-if="loading" :count="1" :height="400" />

    <div v-else class="space-y-6">
      <!-- Avatar + name -->
      <div class="glass-card p-6 flex items-center gap-5">
        <div class="w-20 h-20 bg-gradient-to-br from-primary-300 to-emerald-400 rounded-2xl flex items-center justify-center text-3xl font-bold text-white shadow-lg shrink-0">
          {{ initials }}
        </div>
        <div>
          <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ user?.ten }}</h2>
          <p class="text-sm text-gray-500">{{ user?.email }}</p>
          <span class="badge bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400 mt-1">
            {{ user?.vai_tro === 'admin' ? '🛡️ Admin' : '👤 Người dùng' }}
          </span>
        </div>
      </div>

      <!-- Edit profile -->
      <div class="glass-card p-6">
        <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-4">✏️ Cập nhật thông tin</h3>
        <form @submit.prevent="saveProfile" class="space-y-4">
          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Họ tên</label>
              <input v-model="profileForm.ten" type="text" class="input-field" />
            </div>
            <div>
              <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Số điện thoại</label>
              <input v-model="profileForm.so_dien_thoai" type="tel" class="input-field" />
            </div>
          </div>
          <button type="submit" :disabled="savingProfile" class="btn-primary flex items-center gap-2">
            <span v-if="savingProfile" class="w-4 h-4 border-2 border-gray-400 border-t-gray-800 rounded-full animate-spin" />
            💾 Lưu thay đổi
          </button>
        </form>
      </div>

      <!-- Change password -->
      <div class="glass-card p-6">
        <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-4">🔒 Đổi mật khẩu</h3>
        <form @submit.prevent="savePassword" class="space-y-4">
          <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Mật khẩu hiện tại</label>
            <input v-model="pwdForm.mat_khau_cu" type="password" class="input-field" required />
          </div>
          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Mật khẩu mới</label>
              <input v-model="pwdForm.mat_khau_moi" type="password" class="input-field" required minlength="6" />
            </div>
            <div>
              <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Nhập lại mật khẩu</label>
              <input v-model="pwdForm.mat_khau_moi_confirmation" type="password" class="input-field" required />
            </div>
          </div>
          <button type="submit" :disabled="savingPwd" class="btn-outline flex items-center gap-2">
            <span v-if="savingPwd" class="w-4 h-4 rounded-full border-2 border-primary-400 border-t-transparent animate-spin" />
            🔑 Đổi mật khẩu
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import SkeletonLoader from '@/components/ui/SkeletonLoader.vue'
import { authApi } from '@/services/api'

const auth    = useAuthStore()
const toast   = useToast()
const loading = ref(false)
const user    = ref(null)

const profileForm = reactive({ ten: '', so_dien_thoai: '' })
const pwdForm     = reactive({ mat_khau_cu: '', mat_khau_moi: '', mat_khau_moi_confirmation: '' })
const savingProfile = ref(false)
const savingPwd     = ref(false)

const initials = computed(() => {
  const n = user.value?.ten || 'U'
  return n.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()
})

async function load() {
  loading.value = true
  try {
    const res = await authApi.me()
    const data = res.data.data ?? res.data
    user.value = data.user ?? data
    Object.assign(profileForm, {
      ten:            user.value.ten || '',
      so_dien_thoai:  user.value.so_dien_thoai || ''
    })
  } catch {}
  finally { loading.value = false }
}

async function saveProfile() {
  savingProfile.value = true
  try {
    const res = await authApi.updateProfile(profileForm)
    const updated = res.data.data ?? res.data
    user.value = updated
    auth.setAuth(auth.token, updated)
    toast.success('✅ Cập nhật hồ sơ thành công!')
  } catch (e) { toast.error(e.response?.data?.message || 'Có lỗi xảy ra') }
  finally { savingProfile.value = false }
}

async function savePassword() {
  if (pwdForm.mat_khau_moi !== pwdForm.mat_khau_moi_confirmation) {
    toast.error('Mật khẩu không khớp'); return
  }
  savingPwd.value = true
  try {
    await authApi.changePassword({ mat_khau_cu: pwdForm.mat_khau_cu, mat_khau_moi: pwdForm.mat_khau_moi })
    toast.success('🔑 Đổi mật khẩu thành công!')
    Object.assign(pwdForm, { mat_khau_cu: '', mat_khau_moi: '', mat_khau_moi_confirmation: '' })
  } catch (e) { toast.error(e.response?.data?.message || 'Đổi mật khẩu thất bại') }
  finally { savingPwd.value = false }
}

onMounted(load)
</script>
