<template>
  <div class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
    <!-- Background orbs -->
    <div class="absolute w-96 h-96 bg-primary-200/40 rounded-full blur-3xl -top-20 -left-20 pointer-events-none" />
    <div class="absolute w-80 h-80 bg-emerald-200/30 rounded-full blur-3xl -bottom-10 -right-10 pointer-events-none" />

    <div class="glass-card w-full max-w-md p-8 z-10 animate-slide-up">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="w-16 h-16 bg-gradient-to-br from-primary-300 to-emerald-400 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
          <ShieldExclamationIcon class="w-9 h-9 text-white" />
        </div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">SOS System</h1>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Đăng nhập để tiếp tục</p>
      </div>

      <!-- Role toggle -->
      <div class="flex rounded-xl bg-gray-100 dark:bg-gray-700 p-1 mb-6">
        <button @click="isAdmin = false" :class="['flex-1 py-2 rounded-lg text-sm font-semibold transition-all', !isAdmin ? 'bg-white dark:bg-gray-600 shadow text-primary-600 dark:text-primary-400' : 'text-gray-500']">
          👤 Người dùng
        </button>
        <button @click="isAdmin = true" :class="['flex-1 py-2 rounded-lg text-sm font-semibold transition-all', isAdmin ? 'bg-white dark:bg-gray-600 shadow text-primary-600 dark:text-primary-400' : 'text-gray-500']">
          🛡️ Admin
        </button>
      </div>

      <form @submit.prevent="handleLogin" class="space-y-4">
        <!-- Username field only for admin -->
        <div v-if="isAdmin">
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Tên đăng nhập</label>
          <input v-model="form.ten_dang_nhap" type="text" :required="isAdmin" class="input-field" placeholder="admin" autocomplete="username" />
        </div>
        <!-- Email field only for user -->
        <div v-if="!isAdmin">
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Email</label>
          <input v-model="form.email" type="email" :required="!isAdmin" class="input-field" placeholder="you@example.com" autocomplete="email" />
        </div>
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Mật khẩu</label>
            <router-link v-if="!isAdmin" to="/forgot-password" class="text-xs text-primary-600 dark:text-primary-400 hover:underline">
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

        <div v-if="error" class="flex items-center gap-2 text-emergency text-sm bg-red-50 dark:bg-red-900/20 px-3 py-2 rounded-lg">
          <ExclamationTriangleIcon class="w-4 h-4 shrink-0" /> {{ error }}
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
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import { ShieldExclamationIcon, EyeIcon, EyeSlashIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

const auth    = useAuthStore()
const router  = useRouter()
const toast   = useToast()

const isAdmin = ref(false)
const showPwd = ref(false)
const loading = ref(false)
const error   = ref('')
const form    = reactive({ email: '', ten_dang_nhap: '', mat_khau: '' })

async function handleLogin() {
  loading.value = true
  error.value   = ''
  try {
    if (isAdmin.value) await auth.loginAdmin(form)
    else               await auth.login(form)
    toast.success('👋 Chào mừng trở lại!')
    router.push(auth.isAdmin ? '/dashboard' : '/map')
  } catch (e) {
    error.value = e.response?.data?.message || 'Email hoặc mật khẩu không đúng'
  } finally {
    loading.value = false
  }
}
</script>
