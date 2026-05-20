<template>
  <div class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
    <!-- Background blobs -->
    <div class="absolute w-96 h-96 bg-primary-200/40 rounded-full blur-3xl -top-20 -right-20 pointer-events-none" />
    <div class="absolute w-80 h-80 bg-emerald-200/30 rounded-full blur-3xl -bottom-10 -left-10 pointer-events-none" />

    <div class="glass-card w-full max-w-md p-8 z-10 animate-slide-up">

      <!-- ── Logo ── -->
      <div class="text-center mb-7">
        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-lg overflow-hidden border border-gray-100 dark:border-gray-700 bg-white">
          <img src="https://copilot.microsoft.com/th/id/BCO.19b7de78-f747-4582-9c82-d0d48170234f.png" alt="SOS Logo" class="w-full h-full object-contain" />
        </div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Tạo tài khoản</h1>
        <p class="text-gray-500 text-sm mt-1">Đăng ký để sử dụng hệ thống SOS</p>
      </div>

      <!-- ── Step indicator ── -->
      <div class="flex items-center justify-center gap-2 mb-6">
        <div v-for="s in 3" :key="s"
             :class="['w-2.5 h-2.5 rounded-full transition-all duration-300',
               step === s ? 'bg-primary-500 w-6' :
               step > s  ? 'bg-green-400' : 'bg-gray-200 dark:bg-gray-600']" />
      </div>

      <!-- ═══════════════════════════════════ STEP 1: Form ═══ -->
      <form v-if="step === 1" @submit.prevent="handleRegister" class="space-y-4">

        <div>
          <label class="label-field">Họ và tên *</label>
          <input v-model.trim="form.ten" type="text" required class="input-field"
            placeholder="Nguyễn Văn A" autocomplete="name" />
        </div>

        <div>
          <label class="label-field">Email *</label>
          <input v-model.trim="form.email" type="email" required class="input-field"
            placeholder="you@example.com" autocomplete="email" />
          <p class="text-xs text-gray-400 mt-1">📧 Mã OTP xác minh sẽ gửi đến email này</p>
        </div>

        <div>
          <label class="label-field">Số điện thoại *</label>
          <input v-model.trim="form.so_dien_thoai" type="tel" required class="input-field"
            placeholder="0912345678" maxlength="10" autocomplete="tel"
            @input="form.so_dien_thoai = form.so_dien_thoai.replace(/\D/g, '')" />
          <p class="text-xs text-gray-400 mt-1">10 chữ số, bắt đầu bằng 0</p>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="label-field">Mật khẩu *</label>
            <input v-model="form.password" type="password" required class="input-field"
              placeholder="••••••••" minlength="6" autocomplete="new-password" />
          </div>
          <div>
            <label class="label-field">Nhập lại *</label>
            <input v-model="form.password_confirmation" type="password" required class="input-field"
              placeholder="••••••••" autocomplete="new-password" />
          </div>
        </div>

        <!-- Errors -->
        <div v-if="errors.length" class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3 space-y-1">
          <p v-for="e in errors" :key="e" class="text-xs text-red-600 dark:text-red-400 flex items-start gap-1.5">
            <ExclamationTriangleIcon class="w-3.5 h-3.5 shrink-0 mt-0.5" /> {{ e }}
          </p>
        </div>

        <button type="submit" :disabled="loading" class="btn-primary w-full flex items-center justify-center gap-2 h-11">
          <span v-if="loading" class="w-4 h-4 border-2 border-gray-300 border-t-gray-700 rounded-full animate-spin" />
          <UserPlusIcon v-else class="w-4 h-4" />
          {{ loading ? 'Đang tạo tài khoản...' : 'Đăng ký' }}
        </button>
      </form>

      <!-- ═══════════════════════════════════ STEP 2: OTP ════ -->
      <div v-else-if="step === 2" class="space-y-4">

        <!-- Email sent banner -->
        <div v-if="!otpDemo"
          class="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-xl">
          <div class="w-9 h-9 bg-green-100 dark:bg-green-800/40 rounded-full flex items-center justify-center shrink-0">
            <EnvelopeIcon class="w-5 h-5 text-green-600 dark:text-green-400" />
          </div>
          <div>
            <p class="text-sm font-semibold text-green-700 dark:text-green-300">Email đã được gửi!</p>
            <p class="text-xs text-green-600 dark:text-green-400">Kiểm tra hộp thư <strong>{{ form.email }}</strong></p>
          </div>
        </div>

        <!-- Demo OTP (when SMTP not working) -->
        <div v-else class="rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 p-4">
          <div class="flex items-center gap-2 mb-2">
            <span class="text-lg">🔔</span>
            <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">Chế độ Demo</p>
          </div>
          <p class="text-xs text-amber-700 dark:text-amber-400 mb-2">Email chưa cấu hình – mã OTP được hiển thị trực tiếp:</p>
          <div class="bg-white dark:bg-gray-800 rounded-lg p-3 text-center border border-amber-200 dark:border-amber-700">
            <p class="text-3xl font-mono font-bold tracking-[0.4em] text-amber-600 dark:text-amber-400">{{ otpDemo }}</p>
          </div>
        </div>

        <!-- OTP input -->
        <div>
          <label class="label-field">Nhập mã OTP (6 chữ số)</label>
          <input
            ref="otpInputRef"
            v-model="verifyOtp"
            type="text"
            inputmode="numeric"
            maxlength="6"
            class="input-field text-center text-3xl font-mono tracking-[0.5em] h-14"
            placeholder="000000"
            @input="verifyOtp = verifyOtp.replace(/\D/g, '')"
            @keyup.enter="verifyEmailOtp"
          />
          <p class="text-xs text-gray-400 mt-1 text-center">
            Mã có hiệu lực trong <strong>15 phút</strong>
          </p>
        </div>

        <!-- Verify error -->
        <div v-if="verifyError" class="text-xs text-red-600 dark:text-red-400 flex items-center gap-1.5 bg-red-50 dark:bg-red-900/20 px-3 py-2 rounded-lg">
          <ExclamationTriangleIcon class="w-3.5 h-3.5 shrink-0" /> {{ verifyError }}
        </div>

        <!-- Verify button -->
        <button @click="verifyEmailOtp" :disabled="verifyLoading || verifyOtp.length < 6"
          class="btn-primary w-full flex items-center justify-center gap-2 h-11">
          <span v-if="verifyLoading" class="w-4 h-4 border-2 border-gray-300 border-t-gray-700 rounded-full animate-spin" />
          <CheckCircleIcon v-else class="w-4 h-4" />
          {{ verifyLoading ? 'Đang xác minh...' : 'Xác minh email' }}
        </button>

        <!-- Resend + Back -->
        <div class="flex items-center justify-between text-sm">
          <button @click="step = 1; otpDemo = ''; verifyOtp = ''; verifyError = ''"
            type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
            ← Quay lại
          </button>
          <button @click="resendOtp" :disabled="resendCountdown > 0" type="button"
            class="text-primary-600 dark:text-primary-400 hover:underline disabled:opacity-40 disabled:cursor-not-allowed font-medium">
            {{ resendCountdown > 0 ? `Gửi lại (${resendCountdown}s)` : '↺ Gửi lại mã' }}
          </button>
        </div>
      </div>

      <!-- ═══════════════════════════════════ STEP 3: Done ═══ -->
      <div v-else-if="step === 3" class="text-center py-2">
        <div class="w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-5 animate-bounce-once">
          <CheckCircleIcon class="w-12 h-12 text-green-500" />
        </div>
        <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-2">Đăng ký thành công!</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm mb-6">
          Tài khoản của bạn đã được tạo và email đã được xác minh.
        </p>
        <button @click="goToLogin" class="btn-primary w-full flex items-center justify-center gap-2 h-11">
          Đăng nhập ngay →
        </button>
      </div>

      <!-- Login link -->
      <p class="text-center text-sm text-gray-500 dark:text-gray-400 mt-6">
        Đã có tài khoản?
        <router-link to="/login" class="text-primary-600 dark:text-primary-400 font-semibold hover:underline ml-1">
          Đăng nhập
        </router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, nextTick, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import { authApi } from '@/services/api'
import {
  UserPlusIcon,
  ExclamationTriangleIcon,
  EnvelopeIcon,
  CheckCircleIcon,
} from '@heroicons/vue/24/outline'

const auth   = useAuthStore()
const router = useRouter()
const toast  = useToast()

// State
const step              = ref(1)
const loading           = ref(false)
const errors            = ref([])
const verifyLoading     = ref(false)
const verifyError       = ref('')
const verifyOtp         = ref('')
const otpDemo           = ref('')
const resendCountdown   = ref(0)
const otpInputRef       = ref(null)
let resendTimer         = null

const form = reactive({
  ten: '', email: '', so_dien_thoai: '', password: '', password_confirmation: ''
})

// ── STEP 1: Validate client-side then call API ──────────────────────────
async function handleRegister() {
  errors.value = []

  // Client-side validation
  if (!form.ten.trim() || form.ten.trim().length < 2) {
    errors.value = ['Họ tên phải ít nhất 2 ký tự']
    return
  }
  if (!form.email.includes('@')) {
    errors.value = ['Email không hợp lệ']
    return
  }
  if (!/^0[0-9]{9}$/.test(form.so_dien_thoai)) {
    errors.value = ['Số điện thoại phải đúng 10 số và bắt đầu bằng 0']
    return
  }
  if (form.password.length < 6) {
    errors.value = ['Mật khẩu phải ít nhất 6 ký tự']
    return
  }
  if (form.password !== form.password_confirmation) {
    errors.value = ['Mật khẩu xác nhận không khớp']
    return
  }

  loading.value = true
  try {
    const res    = await authApi.register({
      ten:           form.ten,
      email:         form.email,
      so_dien_thoai: form.so_dien_thoai,
      mat_khau:      form.password,
    })
    const resData = res.data
    const payload = resData.data ?? resData

    // Không lưu token tạm thời ở đây, yêu cầu xác minh email xong mới đăng nhập.

    // Nếu SMTP lỗi, backend trả otp_demo
    otpDemo.value = resData.otp_demo || ''

    toast.success('📧 Tài khoản đã tạo! Vui lòng xác minh email.')
    step.value = 2
    startResendCountdown()

    // Auto-focus OTP input
    await nextTick()
    otpInputRef.value?.focus()

  } catch (e) {
    console.error('[Register] Error:', e.response?.data)
    const errs = e.response?.data?.errors
    if (errs) {
      // Lấy tất cả lỗi validation từ backend
      errors.value = Object.values(errs).flat()
    } else {
      errors.value = [e.response?.data?.message || 'Đăng ký thất bại. Vui lòng thử lại.']
    }
  } finally {
    loading.value = false
  }
}

// ── STEP 2: Verify OTP ─────────────────────────────────────────────────
async function verifyEmailOtp() {
  if (verifyOtp.value.length !== 6) {
    verifyError.value = 'Vui lòng nhập đủ 6 chữ số'
    return
  }
  verifyError.value   = ''
  verifyLoading.value = true
  try {
    await authApi.verifyEmail({ email: form.email, otp: verifyOtp.value })
    toast.success('✅ Email đã xác minh thành công!')
    step.value = 3
  } catch (e) {
    verifyError.value = e.response?.data?.message || 'OTP không đúng hoặc đã hết hạn. Kiểm tra lại.'
    console.error('[VerifyOTP]', e.response?.data)
  } finally {
    verifyLoading.value = false
  }
}

// ── Gửi lại OTP ────────────────────────────────────────────────────────
async function resendOtp() {
  try {
    const res = await authApi.sendVerifyEmail({ email: form.email })
    otpDemo.value = res.data?.otp_demo || ''
    if (!otpDemo.value) {
      toast.success('📧 Mã OTP mới đã gửi đến email!')
    } else {
      toast.info('🔔 Mã OTP mới (demo): ' + otpDemo.value)
    }
    verifyOtp.value  = ''
    verifyError.value = ''
    startResendCountdown()
    await nextTick()
    otpInputRef.value?.focus()
  } catch (e) {
    toast.error('Không gửi được OTP. Vui lòng thử lại.')
  }
}

// ── Countdown 60s cho nút "Gửi lại" ───────────────────────────────────
function startResendCountdown() {
  clearInterval(resendTimer)
  resendCountdown.value = 60
  resendTimer = setInterval(() => {
    resendCountdown.value--
    if (resendCountdown.value <= 0) clearInterval(resendTimer)
  }, 1000)
}

// ── Step 3: Vào app ────────────────────────────────────────────────────
function goToLogin() {
  router.push('/login')
}

onBeforeUnmount(() => clearInterval(resendTimer))
</script>

<style scoped>
.label-field {
  @apply text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block;
}
@keyframes bounce-once {
  0%,100% { transform: translateY(0); }
  30%      { transform: translateY(-12px); }
  60%      { transform: translateY(-6px); }
}
.animate-bounce-once {
  animation: bounce-once 0.7s ease-out;
}
</style>
