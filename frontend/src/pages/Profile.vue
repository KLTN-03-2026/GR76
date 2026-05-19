  <template>
    <div class="p-6 max-w-3xl mx-auto space-y-6">
      <div class="flex items-center gap-2 mb-6">
        <UserCircleIcon class="w-7 h-7 text-gray-700 dark:text-gray-300" />
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Hồ sơ cá nhân</h1>
      </div>

      <SkeletonLoader v-if="loading" :count="1" :height="400" />

      <div v-else class="space-y-6">
        <!-- Avatar + name -->
        <div class="glass-card p-6 flex items-center gap-6">
          <div class="relative group cursor-pointer" @click="triggerAvatarUpload">
            <img v-if="previewAvatar || user?.avatar_url" :src="previewAvatar || user.avatar_url" 
                 class="w-24 h-24 rounded-2xl object-cover shadow-lg border-2 border-primary-100 dark:border-primary-900/50" />
            <div v-else class="w-24 h-24 bg-gradient-to-br from-primary-300 to-emerald-400 rounded-2xl flex items-center justify-center text-4xl font-bold text-white shadow-lg">
              {{ initials }}
            </div>
            
            <div class="absolute inset-0 bg-black/40 rounded-2xl flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
              <CameraIcon class="w-6 h-6 text-white mb-1" />
              <span class="text-white text-xs font-medium">Thay đổi</span>
            </div>
            <input ref="avatarInput" type="file" accept="image/*" class="hidden" @change="handleAvatarChange" />
          </div>

          <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ user?.ten || user?.ho_ten }}</h2>
            <p class="text-gray-500 mt-0.5">{{ user?.email }}</p>
            <div class="mt-2 inline-flex items-center gap-1.5 px-3 py-1 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 rounded-full text-sm font-medium border border-primary-100 dark:border-primary-800/50">
              <ShieldCheckIcon v-if="user?.vai_tro === 'admin' || user?.id_admin" class="w-4 h-4" />
              <UserIcon v-else class="w-4 h-4" />
              {{ user?.vai_tro === 'admin' || user?.id_admin ? 'Quản trị viên' : 'Người dùng' }}
            </div>
          </div>
        </div>

        <!-- Edit profile -->
        <div class="glass-card p-6">
          <div class="flex items-center gap-2 mb-4">
            <PencilSquareIcon class="w-5 h-5 text-gray-600 dark:text-gray-400" />
            <h3 class="font-semibold text-gray-700 dark:text-gray-200">Cập nhật thông tin</h3>
          </div>
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
              <ArrowDownTrayIcon v-else class="w-4 h-4" />
              Lưu thay đổi
            </button>
          </form>
        </div>

        <!-- Change password -->
        <div class="glass-card p-6">
          <div class="flex items-center gap-2 mb-4">
            <KeyIcon class="w-5 h-5 text-gray-600 dark:text-gray-400" />
            <h3 class="font-semibold text-gray-700 dark:text-gray-200">Đổi mật khẩu</h3>
          </div>
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
              <LockClosedIcon v-else class="w-4 h-4" />
              Đổi mật khẩu
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
import { 
  UserCircleIcon, CameraIcon, PencilSquareIcon, KeyIcon, 
  ArrowDownTrayIcon, LockClosedIcon, ShieldCheckIcon, UserIcon 
} from '@heroicons/vue/24/outline'

const auth = useAuthStore()
const toast = useToast()
const loading = ref(false)
const user = ref(null)

const profileForm = reactive({ ten: '', so_dien_thoai: '' })
const pwdForm = reactive({ mat_khau_cu: '', mat_khau_moi: '', mat_khau_moi_confirmation: '' })
const savingProfile = ref(false)
const savingPwd = ref(false)

const avatarInput = ref(null)
const selectedAvatar = ref(null)
const previewAvatar = ref(null)

function triggerAvatarUpload() {
  avatarInput.value?.click()
}

function handleAvatarChange(e) {
  const file = e.target.files[0]
  if (!file) return
  if (!file.type.startsWith('image/')) {
    toast.error('Vui lòng chọn file hình ảnh')
    return
  }
  selectedAvatar.value = file
  previewAvatar.value = URL.createObjectURL(file)
}

const initials = computed(() => {
  const n = user.value?.ten || user.value?.ho_ten || 'U'
  return n.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()
})

async function load() {
  loading.value = true
  try {
    const res = await authApi.me()
    const data = res.data.data ?? res.data
    user.value = data.user ?? data
    Object.assign(profileForm, {
      ten: user.value.ten || user.value.ho_ten || '',
      so_dien_thoai: user.value.so_dien_thoai || ''
    })
  } catch { }
  finally { loading.value = false }
}

async function saveProfile() {
  savingProfile.value = true
  try {
    let payload = profileForm
    if (selectedAvatar.value) {
      payload = new FormData()
      payload.append('ten', profileForm.ten)
      payload.append('ho_ten', profileForm.ten)
      if (profileForm.so_dien_thoai) payload.append('so_dien_thoai', profileForm.so_dien_thoai)
      payload.append('avatar', selectedAvatar.value)
    }

    const res = await authApi.updateProfile(payload)
    const updated = res.data.data ?? res.data
    user.value = updated
    auth.setAuth(auth.token, updated)
    toast.success('Cập nhật hồ sơ thành công!')
    selectedAvatar.value = null
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
    toast.success('Đổi mật khẩu thành công!')
    Object.assign(pwdForm, { mat_khau_cu: '', mat_khau_moi: '', mat_khau_moi_confirmation: '' })
  } catch (e) { toast.error(e.response?.data?.message || 'Đổi mật khẩu thất bại') }
  finally { savingPwd.value = false }
}

onMounted(load)
</script>
