<template>
  <div class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
    <div class="absolute w-96 h-96 bg-primary-200/40 rounded-full blur-3xl -top-20 -left-20 pointer-events-none" />
    <div class="absolute w-80 h-80 bg-orange-200/30 rounded-full blur-3xl -bottom-10 -right-10 pointer-events-none" />

    <div class="glass-card w-full max-w-md p-8 z-10 animate-slide-up">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="w-16 h-16 bg-gradient-to-br from-orange-400 to-red-500 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
          <KeyIcon class="w-9 h-9 text-white" />
        </div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Quên mật khẩu</h1>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Nhập email để nhận mã OTP đặt lại mật khẩu</p>
      </div>

      <!-- Step 1: Enter email -->
      <div v-if="step === 1">
        <form @submit.prevent="sendOtp" class="space-y-4">
          <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Email đăng ký</label>
            <input v-model="email" type="email" required class="input-field"
              placeholder="you@example.com" autocomplete="email" />
          </div>
          <div v-if="error" class="flex items-center gap-2 text-red-600 text-sm bg-red-50 dark:bg-red-900/20 px-3 py-2 rounded-lg">
            <ExclamationTriangleIcon class="w-4 h-4 shrink-0" /> {{ error }}
          </div>
          <button type="submit" :disabled="loading" class="btn-primary w-full flex items-center justify-center gap-2">
            <span v-if="loading" class="w-4 h-4 border-2 border-gray-400 border-t-gray-800 rounded-full animate-spin" />
            {{ loading ? 'Đang gửi...' : '📧 Gửi mã OTP' }}
          </button>
        </form>
      </div>

      <!-- Step 2: Enter OTP + new password -->
      <div v-else-if="step === 2">
        <div v-if="otpDemo" class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-xl text-sm text-yellow-800 dark:text-yellow-300">
          <p class="font-semibold">🔔 Chế độ demo:</p>
          <p>Mã OTP của bạn là: <strong class="text-lg tracking-widest font-mono">{{ otpDemo }}</strong></p>
          <p class="text-xs mt-1 opacity-70">(Email chưa được cấu hình, OTP hiển thị trực tiếp)</p>
        </div>
        <form @submit.prevent="resetPassword" class="space-y-4">
          <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">
              Mã OTP
              <span class="text-gray-400 font-normal text-xs">(gửi đến {{ email }})</span>
            </label>
            <input v-model="otp" type="text" required maxlength="6" class="input-field text-center text-2xl font-mono tracking-[0.5em]"
              placeholder="000000" inputmode="numeric" />
          </div>
          <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Mật khẩu mới</label>
            <div class="relative">
              <input v-model="mat_khau" :type="showPwd ? 'text' : 'password'" required minlength="6" class="input-field pr-11"
                placeholder="••••••••" />
              <button type="button" @click="showPwd = !showPwd" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                <EyeSlashIcon v-if="showPwd" class="w-5 h-5" />
                <EyeIcon v-else class="w-5 h-5" />
              </button>
            </div>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Xác nhận mật khẩu</label>
            <input v-model="mat_khau_confirmation" :type="showPwd ? 'text' : 'password'" required class="input-field"
              placeholder="••••••••" />
          </div>
          <div v-if="error" class="flex items-center gap-2 text-red-600 text-sm bg-red-50 dark:bg-red-900/20 px-3 py-2 rounded-lg">
            <ExclamationTriangleIcon class="w-4 h-4 shrink-0" /> {{ error }}
          </div>
          <button type="submit" :disabled="loading" class="btn-primary w-full flex items-center justify-center gap-2">
            <span v-if="loading" class="w-4 h-4 border-2 border-gray-400 border-t-gray-800 rounded-full animate-spin" />
            {{ loading ? 'Đang đặt lại...' : '🔑 Đặt lại mật khẩu' }}
          </button>
          <button type="button" @click="step = 1; otpDemo = ''" class="text-sm text-gray-500 hover:text-gray-700 w-full text-center mt-1">
            ← Quay lại
          </button>
        </form>
      </div>

      <!-- Step 3: Success -->
      <div v-else-if="step === 3" class="text-center py-4">
        <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
          <CheckCircleIcon class="w-10 h-10 text-green-500" />
        </div>
        <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-2">Thành công!</h2>
        <p class="text-gray-500 text-sm mb-6">Mật khẩu đã được đặt lại. Bạn có thể đăng nhập với mật khẩu mới.</p>
        <router-link to="/login" class="btn-primary w-full flex items-center justify-center gap-2">
          Đăng nhập ngay →
        </router-link>
      </div>

      <p class="text-center text-sm text-gray-500 mt-6">
        <router-link to="/login" class="text-primary-600 dark:text-primary-400 font-semibold hover:underline">
          ← Quay về đăng nhập
        </router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useToast } from 'vue-toastification'
import { authApi } from '@/services/api'
import { KeyIcon, ExclamationTriangleIcon, EyeIcon, EyeSlashIcon, CheckCircleIcon } from '@heroicons/vue/24/outline'

const toast   = useToast()
const step    = ref(1)
const email   = ref('')
const otp     = ref('')
const otpDemo = ref('')
const mat_khau             = ref('')
const mat_khau_confirmation = ref('')
const showPwd = ref(false)
const loading = ref(false)
const error   = ref('')

async function sendOtp() {
  loading.value = true
  error.value   = ''
  try {
    const res = await authApi.forgotPassword({ email: email.value })
    const data = res.data
    // Demo mode: OTP returned in response
    if (data.otp_demo) {
      otpDemo.value = data.otp_demo
    }
    toast.success('📧 Mã OTP đã gửi! Kiểm tra email của bạn.')
    step.value = 2
  } catch (e) {
    error.value = e.response?.data?.message || e.response?.data?.errors?.email?.[0] || 'Email không tồn tại trong hệ thống'
  } finally {
    loading.value = false
  }
}

async function resetPassword() {
  if (mat_khau.value !== mat_khau_confirmation.value) {
    error.value = 'Mật khẩu xác nhận không khớp'
    return
  }
  loading.value = true
  error.value   = ''
  try {
    await authApi.resetPassword({
      email:                 email.value,
      otp:                   otp.value,
      mat_khau:              mat_khau.value,
      mat_khau_confirmation: mat_khau_confirmation.value,
    })
    toast.success('✅ Mật khẩu đã được đặt lại thành công!')
    step.value = 3
  } catch (e) {
    error.value = e.response?.data?.message || 'OTP không đúng hoặc đã hết hạn'
  } finally {
    loading.value = false
  }
}
</script>
