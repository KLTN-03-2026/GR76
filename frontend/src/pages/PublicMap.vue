<template>
  <div class="min-h-screen relative">
    <!-- Hero Header -->
    <div class="bg-gradient-to-r from-primary-300 to-emerald-400 px-6 py-8 text-gray-800">
      <div class="max-w-5xl mx-auto flex items-center justify-between">
        <div>
          <div class="flex items-center gap-3 mb-2">
            <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center overflow-hidden shadow-sm">
              <img src="https://copilot.microsoft.com/th/id/BCO.19b7de78-f747-4582-9c82-d0d48170234f.png" alt="SOS Logo" class="w-full h-full object-contain" />
            </div>
            <h1 class="text-2xl font-bold">SOS System</h1>
          </div>
          <p class="text-sm text-gray-700">Bản đồ sự cố công khai – xem thời gian thực</p>
        </div>
        <div class="flex gap-2">
          <router-link to="/login" class="btn-ghost bg-white/30 hover:bg-white/50 text-gray-800 text-sm">Đăng nhập</router-link>
          <router-link to="/register" class="bg-white text-primary-700 font-semibold px-4 py-2 rounded-xl text-sm hover:bg-white/90 transition-colors">Đăng ký</router-link>
        </div>
      </div>
    </div>

    <!-- Stats strip -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 px-6 py-3">
      <div class="max-w-5xl mx-auto flex gap-6 text-sm">
        <span class="text-gray-500 flex items-center gap-1.5"><DocumentTextIcon class="w-4 h-4" /> Tổng sự cố: <strong class="text-gray-800 dark:text-gray-100">{{ incidents.length }}</strong></span>
        <span class="text-gray-500 flex items-center gap-1.5"><ClockIcon class="w-4 h-4 text-yellow-600" /> Chờ xử lý: <strong class="text-yellow-600">{{ pending }}</strong></span>
        <span class="text-gray-500 flex items-center gap-1.5"><CheckCircleIcon class="w-4 h-4 text-green-600" /> Đã giải quyết: <strong class="text-green-600">{{ resolved }}</strong></span>
      </div>
    </div>

    <!-- Map -->
    <div style="height: calc(100vh - 180px);">
      <div v-if="loading" class="h-full flex items-center justify-center bg-gray-50 dark:bg-gray-900">
        <div class="glass-card px-6 py-4 flex items-center gap-3">
          <span class="w-5 h-5 border-2 border-primary-300 border-t-primary-600 rounded-full animate-spin" />
          <span class="text-sm font-medium">Đang tải bản đồ...</span>
        </div>
      </div>
      <MapComponent
        v-else
        :incidents="incidents"
        :clickable="false"
        class="h-full"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { DocumentTextIcon, ClockIcon, CheckCircleIcon } from '@heroicons/vue/24/outline'
import { ShieldExclamationIcon } from '@heroicons/vue/24/outline'
import MapComponent from '@/components/MapComponent.vue'
import { incidentApi } from '@/services/api'

const incidents = ref([])
const loading   = ref(false)

const pending  = computed(() => incidents.value.filter(i => i.trang_thai === 'pending').length)
const resolved = computed(() => incidents.value.filter(i => i.trang_thai === 'resolved').length)

async function load() {
  loading.value = true
  try {
    const res = await incidentApi.publicMap()
    incidents.value = res.data.data ?? res.data
  } catch {}
  finally { loading.value = false }
}

onMounted(load)
</script>
