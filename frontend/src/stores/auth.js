import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/services/api'
import router from '@/router'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('sos_token') || null)
  const user  = ref(JSON.parse(localStorage.getItem('sos_user') || 'null'))

  const isLoggedIn = computed(() => !!token.value)
  const isAdmin    = computed(() => user.value?.vai_tro === 'admin' || user.value?.role === 'admin')

  function setAuth(t, u) {
    token.value = t
    user.value  = u
    localStorage.setItem('sos_token', t)
    localStorage.setItem('sos_user', JSON.stringify(u))
  }

  async function login(credentials) {
    const res = await authApi.login(credentials)
    const payload = res.data.data ?? res.data
    setAuth(payload.token, payload.user)
    return payload
  }

  async function loginAdmin(credentials) {
    const res = await authApi.loginAdmin(credentials)
    const payload = res.data.data ?? res.data
    setAuth(payload.token, payload.user)
    return payload
  }

  async function register(data) {
    const res = await authApi.register(data)
    const payload = res.data.data ?? res.data
    setAuth(payload.token, payload.user)
    return payload
  }

  async function logout() {
    try { await authApi.logout() } catch {}
    token.value = null
    user.value  = null
    localStorage.removeItem('sos_token')
    localStorage.removeItem('sos_user')
    router.push('/login')
  }

  async function fetchMe() {
    const res = await authApi.me()
    user.value = res.data.user ?? res.data
    localStorage.setItem('sos_user', JSON.stringify(user.value))
    return user.value
  }

  return { token, user, isLoggedIn, isAdmin, login, loginAdmin, register, logout, fetchMe, setAuth }
})
