<template>
  <div class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
    <!-- Background orbs -->
    <div class="absolute w-96 h-96 bg-primary-200/40 rounded-full blur-3xl -top-20 -left-20 pointer-events-none" />
    <div class="absolute w-80 h-80 bg-emerald-200/30 rounded-full blur-3xl -bottom-10 -right-10 pointer-events-none" />

    <div class="glass-card w-full max-w-md p-8 z-10 animate-slide-up">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg overflow-hidden border border-gray-100 dark:border-gray-700 bg-white">
          <img src="https://copilot.microsoft.com/th/id/BCO.19b7de78-f747-4582-9c82-d0d48170234f.png" alt="SOS Logo" class="w-full h-full object-contain" />
        </div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">SOS System</h1>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Đăng nhập để tiếp tục</p>
      </div>

      <form @submit.prevent="handleLogin" class="space-y-4">
        <!-- Single identifier field for both admin and user -->
        <div>
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Tên đăng nhập hoặc Email</label>
          <input v-model="form.identifier" type="text" required class="input-field" placeholder="Nhập tên đăng nhập hoặc email" autocomplete="username" />
        </div>
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Mật khẩu</label>
            <router-link to="/forgot-password" class="text-xs text-primary-600 dark:text-primary-400 hover:underline">
              Quên mật khẩu?
            </router-link>
          </div>
          <div class="relative">
            <input v-model="form.mat_khau" :type="showPwd ? 'text' : 'password'" required class="input-field pr-11" placeholder="••••••••" autocomplete="current-password" />
            <button type="button" @click="showPwd = !showPwd" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
              <EyeSlashIcon v-if="showPwd" class="w-5 h-5" />
              <EyeIcon v-else class="w-5 h-5" />
            </button>
          </div>
        </div>

        <div v-if="error" class="flex flex-col gap-2 text-emergency text-sm bg-red-50 dark:bg-red-900/20 px-3 py-2 rounded-lg">
          <div class="flex items-center gap-2">
            <ExclamationTriangleIcon class="w-4 h-4 shrink-0" /> {{ error }}
          </div>
          <button v-if="isUnverified" type="button" @click="openVerifyModal" class="text-primary-600 dark:text-primary-400 text-xs font-semibold hover:underline self-start">
            Gửi lại mã xác minh và kích hoạt tài khoản
          </button>
        </div>

        <button type="submit" :disabled="loading" class="btn-primary w-full flex items-center justify-center gap-2">
          <span v-if="loading" class="w-4 h-4 border-2 border-gray-400 border-t-gray-800 rounded-full animate-spin" />
          {{ loading ? 'Đang đăng nhập...' : 'Đăng nhập' }}
        </button>
      </form>

      <p class="text-center text-sm text-gray-500 mt-6">
        Chưa có tài khoản?
        <router-link to="/register" class="text-primary-600 dark:text-primary-400 font-semibold hover:underline">Đăng ký ngay</router-link>
      </p>
      <p class="text-center text-sm text-gray-400 mt-2">
        <router-link to="/public" class="hover:underline text-xs">🗺️ Xem bản đồ công khai</router-link>
      </p>
    </div>

    <!-- Modal Verify OTP -->
    <Modal v-model="showVerifyModal" title="Xác minh tài khoản" size="sm">
      <div class="space-y-4">
        <p class="text-sm text-gray-600 dark:text-gray-300">
          Mã xác minh đã được gửi đến email <strong class="text-gray-800 dark:text-gray-100">{{ form.identifier }}</strong>.
        </p>
        
        <div v-if="otpDemo" class="rounded-xl bg-amber-50 border border-amber-200 p-3 text-center">
           <p class="text-xs text-amber-700 mb-1">Chế độ Demo (Không có SMTP):</p>
           <p class="text-2xl font-mono font-bold text-amber-600 tracking-[0.3em]">{{ otpDemo }}</p>
        </div>

        <div>
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Nhập mã OTP (6 số)</label>
          <input
            v-model="verifyOtp"
            type="text"
            inputmode="numeric"
            maxlength="6"
            class="input-field text-center text-3xl font-mono tracking-[0.4em] h-14"
            placeholder="000000"
            @input="verifyOtp = verifyOtp.replace(/\D/g, '')"
            @keyup.enter="verifyEmailOtp"
          />
        </div>
        
        <div v-if="verifyError" class="text-xs text-red-600 bg-red-50 px-3 py-2 rounded-lg">
          {{ verifyError }}
        </div>

        <button @click="verifyEmailOtp" :disabled="verifyLoading || verifyOtp.length < 6"
          class="btn-primary w-full flex items-center justify-center gap-2 h-11">
          <span v-if="verifyLoading" class="w-4 h-4 border-2 border-white/50 border-t-white rounded-full animate-spin" />
          {{ verifyLoading ? 'Đang xác minh...' : 'Xác minh' }}
        </button>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import { authApi } from '@/services/api'
import Modal from '@/components/ui/Modal.vue'
import { ShieldExclamationIcon, EyeIcon, EyeSlashIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

const auth    = useAuthStore()
const router  = useRouter()
const toast   = useToast()

const showPwd = ref(false)
const loading = ref(false)
const error   = ref('')
const isUnverified = ref(false)
const form    = reactive({ identifier: '', mat_khau: '' })

// Verify modal state
const showVerifyModal = ref(false)
const verifyOtp = ref('')
const verifyError = ref('')
const verifyLoading = ref(false)
const otpDemo = ref('')

async function handleLogin() {
  loading.value = true
  error.value   = ''
  try {
    await auth.loginUnified(form)
    toast.success('👋 Chào mừng trở lại!')
    router.push(auth.isAdmin ? '/dashboard' : '/map')
  } catch (e) {
    const msg = e.response?.data?.message || e.response?.data?.errors?.identifier?.[0] || e.response?.data?.errors?.email?.[0] || 'Thông tin đăng nhập không đúng'
    error.value = msg
    isUnverified.value = msg.includes('chưa được xác minh')
  } finally {
    loading.value = false
  }
}

async function openVerifyModal() {
  if (!form.identifier.includes('@')) {
    toast.error('Vui lòng nhập Email vào ô Tên đăng nhập để gửi lại mã.');
    return;
  }
  
  try {
    const res = await authApi.sendVerifyEmail({ email: form.identifier })
    otpDemo.value = res.data?.otp_demo || ''
    verifyOtp.value = ''
    verifyError.value = ''
    showVerifyModal.value = true
    toast.success('Mã xác minh mới đã được gửi!')
  } catch (e) {
    toast.error('Không gửi được mã xác minh. Vui lòng thử lại.')
  }
}

async function verifyEmailOtp() {
  verifyError.value = ''
  verifyLoading.value = true
  try {
    await authApi.verifyEmail({ email: form.identifier, otp: verifyOtp.value })
    toast.success('✅ Xác minh thành công! Đang đăng nhập...')
    showVerifyModal.value = false
    // Auto login
    await handleLogin()
  } catch (e) {
    verifyError.value = e.response?.data?.message || 'Mã OTP không đúng hoặc đã hết hạn.'
  } finally {
    verifyLoading.value = false
  }
}
</script>
