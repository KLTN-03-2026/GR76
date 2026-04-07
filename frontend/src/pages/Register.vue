<template>
  <div class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
    <div class="absolute w-96 h-96 bg-primary-200/40 rounded-full blur-3xl -top-20 -right-20 pointer-events-none" />
    <div class="absolute w-80 h-80 bg-emerald-200/30 rounded-full blur-3xl -bottom-10 -left-10 pointer-events-none" />

    <div class="glass-card w-full max-w-md p-8 z-10 animate-slide-up">
      <div class="text-center mb-7">
        <div class="w-14 h-14 bg-gradient-to-br from-primary-300 to-emerald-400 rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-lg">
          <UserPlusIcon class="w-8 h-8 text-white" />
        </div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Tạo tài khoản</h1>
        <p class="text-gray-500 text-sm mt-1">Đăng ký để sử dụng hệ thống SOS</p>
      </div>

      <form @submit.prevent="handleRegister" class="space-y-4">
        <div>
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Họ tên *</label>
          <input v-model="form.ten" type="text" required class="input-field" placeholder="Nguyễn Văn A" />
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Email *</label>
          <input v-model="form.email" type="email" required class="input-field" placeholder="you@example.com" />
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Số điện thoại</label>
          <input v-model="form.so_dien_thoai" type="tel" class="input-field" placeholder="0912345678" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Mật khẩu *</label>
            <input v-model="form.password" type="password" required class="input-field" placeholder="••••••••" minlength="6" />
          </div>
          <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Nhập lại *</label>
            <input v-model="form.password_confirmation" type="password" required class="input-field" placeholder="••••••••" />
          </div>
        </div>

        <div v-if="errors.length" class="space-y-1">
          <p v-for="e in errors" :key="e" class="text-xs text-emergency flex items-center gap-1">
            <ExclamationTriangleIcon class="w-3 h-3" /> {{ e }}
          </p>
        </div>

        <button type="submit" :disabled="loading" class="btn-primary w-full flex items-center justify-center gap-2">
          <span v-if="loading" class="w-4 h-4 border-2 border-gray-400 border-t-gray-800 rounded-full animate-spin" />
          {{ loading ? 'Đang đăng ký...' : 'Đăng ký' }}
        </button>
      </form>

      <p class="text-center text-sm text-gray-500 mt-6">
        Đã có tài khoản?
        <router-link to="/login" class="text-primary-600 dark:text-primary-400 font-semibold hover:underline">Đăng nhập</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import { UserPlusIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

const auth    = useAuthStore()
const router  = useRouter()
const toast   = useToast()
const loading = ref(false)
const errors  = ref([])
const form = reactive({ ten: '', email: '', so_dien_thoai: '', password: '', password_confirmation: '' })

async function handleRegister() {
  errors.value = []
  if (form.password !== form.password_confirmation) { errors.value = ['Mật khẩu không khớp']; return }
  loading.value = true
  try {
    // Send mat_khau as backend expects, not password
    await auth.register({ ten: form.ten, email: form.email, so_dien_thoai: form.so_dien_thoai, mat_khau: form.password })
    toast.success('🎉 Đăng ký thành công!')
    router.push('/map')
  } catch (e) {
    const errs = e.response?.data?.errors
    if (errs) errors.value = Object.values(errs).flat()
    else errors.value = [e.response?.data?.message || 'Đăng ký thất bại']
  } finally { loading.value = false }
}
</script>
