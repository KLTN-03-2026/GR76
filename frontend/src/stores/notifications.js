import { defineStore } from 'pinia'
import { ref } from 'vue'
import { notificationApi } from '@/services/api'

export const useNotificationStore = defineStore('notifications', () => {
  const notifications = ref([])
  const unreadCount   = ref(0)
  const loading       = ref(false)

  async function fetchNotifications() {
    loading.value = true
    try {
      const res = await notificationApi.list()
      const data = res.data.data ?? res.data
      notifications.value = Array.isArray(data) ? data : []
      unreadCount.value = notifications.value.filter(n => !n.da_doc).length
    } catch {}
    finally { loading.value = false }
  }

  async function markRead(id) {
    await notificationApi.read(id)
    const n = notifications.value.find(n => n.id_thong_bao === id)
    if (n) n.da_doc = true
    unreadCount.value = notifications.value.filter(n => !n.da_doc).length
  }

  return { notifications, unreadCount, loading, fetchNotifications, markRead }
})
