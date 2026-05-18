<template>
  <Modal v-model="show" title="📍 Báo cáo sự cố mới" size="lg">
    <form @submit.prevent="submit" class="space-y-4">
      <!-- Title -->
      <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Tiêu đề *</label>
        <input v-model="form.tieu_de" type="text" required class="input-field" placeholder="Mô tả ngắn sự cố..." />
      </div>

      <!-- Content -->
      <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">
          Nội dung chi tiết *
          <span v-if="aiAnalyzing" class="ml-2 inline-flex items-center gap-1 text-xs text-primary-500 font-normal">
            <span class="w-3 h-3 border-2 border-primary-300 border-t-primary-600 rounded-full animate-spin inline-block"></span>
            AI đang phân tích ảnh...
          </span>
          <span v-if="aiDone && !aiAnalyzing" class="ml-2 text-xs text-green-500 font-normal">✓ AI đã mô tả từ ảnh</span>
        </label>
        <textarea v-model="form.noi_dung" required rows="3" class="input-field resize-none" placeholder="Mô tả chi tiết... (tự động điền khi upload ảnh)" />
      </div>

      <!-- Address Autocomplete -->
      <div class="relative" ref="addressWrap">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Địa chỉ *</label>
        <div class="relative">
          <input
            v-model="addressQuery"
            @input="onAddressInput"
            @keydown.down.prevent="moveSuggestion(1)"
            @keydown.up.prevent="moveSuggestion(-1)"
            @keydown.enter.prevent="pickSuggestion(activeSuggestion)"
            @keydown.escape="closeSuggestions"
            type="text"
            required
            autocomplete="off"
            class="input-field pr-8"
            placeholder="Nhập địa chỉ... (vd: 110A Nguyễn Thái Học)"
          />
          <span v-if="suggestionLoading" class="absolute right-3 top-1/2 -translate-y-1/2">
            <span class="w-4 h-4 border-2 border-gray-300 border-t-primary-500 rounded-full animate-spin block"></span>
          </span>
        </div>

        <!-- Suggestions dropdown -->
        <ul v-if="suggestions.length"
            class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl shadow-xl overflow-hidden max-h-52 overflow-y-auto">
          <li v-for="(s, i) in suggestions" :key="i"
              @mousedown.prevent="pickSuggestion(i)"
              :class="['flex items-start gap-2 px-3 py-2.5 cursor-pointer text-sm transition-colors',
                       i === activeSuggestion
                         ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300'
                         : 'hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200']">
            <span class="mt-0.5 text-primary-400 shrink-0">📍</span>
            <span class="leading-snug">{{ s.display_name }}</span>
          </li>
        </ul>
        <p v-if="addressError" class="text-xs text-red-500 mt-1">{{ addressError }}</p>
      </div>

      <!-- Coordinates -->
      <div>
        <div class="flex items-center justify-between mb-1">
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
            Tọa độ GPS
            <span v-if="form.vi_do && form.kinh_do" class="ml-1 text-xs text-green-500 font-normal">✓ Đã có</span>
            <span v-else class="text-gray-400 text-xs font-normal"> (không bắt buộc)</span>
          </label>
          <button type="button" @click="getGPS" :disabled="gpsLoading"
                  class="flex items-center gap-1.5 text-xs font-medium text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20 px-3 py-1.5 rounded-lg transition-colors hover:bg-primary-100">
            <span v-if="gpsLoading" class="w-3 h-3 border border-primary-400 border-t-transparent rounded-full animate-spin"></span>
            <span v-else>📍</span>
            {{ gpsLoading ? 'Đang lấy...' : 'GPS hiện tại' }}
          </button>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <input v-model="form.vi_do" type="number" step="any" class="input-field text-sm" placeholder="Vĩ độ" />
          <input v-model="form.kinh_do" type="number" step="any" class="input-field text-sm" placeholder="Kinh độ" />
        </div>
        <p v-if="gpsError" class="text-xs text-red-500 mt-1">{{ gpsError }}</p>
      </div>

      <!-- Category & Level -->
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Loại sự cố *</label>
          <select v-model="form.id_loai_su_co" required class="select-field">
            <option value="">-- Chọn loại --</option>
            <option v-for="cat in categories" :key="cat.id_loai_su_co" :value="cat.id_loai_su_co">{{ cat.ten_loai }}</option>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Mức độ *</label>
          <select v-model="form.id_muc_do" required class="select-field">
            <option value="">-- Chọn mức --</option>
            <option v-for="lv in levels" :key="lv.id_muc_do" :value="lv.id_muc_do">{{ lv.ten_muc_do }}</option>
          </select>
        </div>
      </div>

      <!-- ── Multiple Image Upload (max 5) ───────────────────────────────────── -->
      <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">
          Hình ảnh
          <span class="text-gray-400 font-normal text-xs">(tối đa 5 ảnh)</span>
        </label>

        <!-- Drop zone (shown when < 5 images) -->
        <input type="file" ref="fileInput" accept="image/*" multiple @change="onFileChange" class="hidden" />
        <div v-if="imagePreviews.length < 5"
             @click="fileInput.click()"
             class="border-2 border-dashed border-gray-200 dark:border-gray-600 rounded-xl p-4 text-center cursor-pointer hover:border-primary-300 transition-colors">
          <p class="text-2xl mb-1">📷</p>
          <p class="text-sm text-gray-500">Click để thêm ảnh ({{ imagePreviews.length }}/5)</p>
          <p class="text-xs text-gray-400">JPG, PNG, WebP — mỗi ảnh tối đa 10MB</p>
        </div>

        <!-- Preview grid -->
        <div v-if="imagePreviews.length" class="mt-2 grid grid-cols-3 gap-2">
          <div v-for="(prev, idx) in imagePreviews" :key="idx" class="relative rounded-xl overflow-hidden">
            <img :src="prev" class="w-full h-24 object-cover rounded-xl" />
            <!-- AI analyzing overlay on first image -->
            <div v-if="idx === 0 && aiAnalyzing"
                 class="absolute inset-0 bg-black/40 backdrop-blur-sm flex flex-col items-center justify-center gap-1 rounded-xl">
              <span class="w-6 h-6 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
              <span class="text-white text-xs">AI đang đọc...</span>
            </div>
            <button type="button" @click="removeImage(idx)" :disabled="aiAnalyzing"
                    class="absolute top-1 right-1 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-xs hover:bg-red-600 shadow">✕</button>
            <!-- Primary badge -->
            <span v-if="idx === 0" class="absolute bottom-1 left-1 text-xs bg-primary-600 text-white px-1.5 py-0.5 rounded-full">Chính</span>
          </div>
        </div>
      </div>

      <!-- AI Warnings -->
      <div v-if="aiWarning" :class="['rounded-xl px-4 py-3 text-sm border', aiWarning.type === 'spam' ? 'bg-red-50 border-red-200 text-red-700 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300' : 'bg-yellow-50 border-yellow-200 text-yellow-700 dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-300']">
        <div class="flex items-start gap-2">
          <span class="text-lg">{{ aiWarning.type === 'spam' ? '🚫' : '⚠️' }}</span>
          <div>
            <p class="font-semibold">{{ aiWarning.title }}</p>
            <p class="mt-0.5 text-xs opacity-80">{{ aiWarning.message }}</p>
          </div>
        </div>
      </div>

      <!-- AI Check status -->
      <div v-if="aiChecking" class="flex items-center gap-2 text-sm text-primary-600 dark:text-primary-400">
        <span class="w-4 h-4 border-2 border-primary-300 border-t-primary-600 rounded-full animate-spin inline-block"></span>
        <span>AI đang kiểm tra trùng lặp và spam...</span>
      </div>

      <!-- Actions -->
      <div class="flex gap-3 pt-1">
        <button type="button" @click="show = false" class="btn-ghost flex-1">Hủy</button>
        <button type="submit" :disabled="loading || aiChecking" class="btn-emergency flex-1 flex items-center justify-center gap-2">
          <span v-if="loading" class="w-4 h-4 border-2 border-white/50 border-t-white rounded-full animate-spin" />
          {{ loading ? 'Đang gửi...' : '🚨 Báo cáo ngay' }}
        </button>
      </div>
    </form>
  </Modal>
</template>

<script setup>
import { ref, reactive, watch, onMounted, onBeforeUnmount } from 'vue'
import { useToast } from 'vue-toastification'
import Modal from '@/components/ui/Modal.vue'
import api, { adminApi } from '@/services/api'

const props = defineProps({
  modelValue: Boolean,
  prefillLat: { type: Number, default: null },
  prefillLng: { type: Number, default: null }
})
const emit = defineEmits(['update:modelValue', 'created'])

const show = ref(props.modelValue)
watch(() => props.modelValue, v => show.value = v)
watch(show, v => emit('update:modelValue', v))

const toast          = useToast()
const loading        = ref(false)
const gpsLoading     = ref(false)
const gpsError       = ref('')
const categories     = ref([])
const levels         = ref([])
const fileInput      = ref(null)
const imagePreviews  = ref([])   // array of object-URL strings
const imageFiles     = ref([])   // array of File objects
const aiAnalyzing    = ref(false)
const aiDone         = ref(false)
const aiWarning      = ref(null)
const aiChecking     = ref(false)
const addressWrap    = ref(null)

// ── Address autocomplete state ──────────────────────────────────
const addressQuery    = ref('')
const suggestions     = ref([])
const suggestionLoading = ref(false)
const activeSuggestion  = ref(-1)
const addressError    = ref('')
let   debounceTimer   = null

const defaultForm = () => ({
  tieu_de: '', noi_dung: '', dia_chi: '',
  vi_do: '', kinh_do: '', id_loai_su_co: '', id_muc_do: ''
})
const form = reactive(defaultForm())

watch(() => props.prefillLat, v => { if (v) form.vi_do = v })
watch(() => props.prefillLng, v => { if (v) form.kinh_do = v })

// ── Address Autocomplete (Nominatim / OpenStreetMap) ─────────────
function onAddressInput() {
  form.dia_chi = addressQuery.value   // keep form in sync while typing
  suggestions.value  = []
  activeSuggestion.value = -1
  addressError.value = ''

  clearTimeout(debounceTimer)
  if (addressQuery.value.trim().length < 3) return

  debounceTimer = setTimeout(fetchSuggestions, 400)
}

async function fetchSuggestions() {
  suggestionLoading.value = true
  try {
    const q = addressQuery.value.trim()
    const searchQ = /vi.t nam|vietnam/i.test(q) ? q : q + ', Việt Nam'

    const resp = await fetch(
      `https://nominatim.openstreetmap.org/search?` +
      new URLSearchParams({
        q: searchQ,
        format: 'json',
        limit: 7,
        addressdetails: 1,
        viewbox: '102.14,8.18,109.46,23.39',
        bounded: 0,
        'accept-language': 'vi,en'
      }),
      { headers: { 'Accept-Language': 'vi,en' } }
    )
    const results = await resp.json()
    suggestions.value = results
    if (!results.length) addressError.value = 'Không tìm thấy địa chỉ, thử nhập thêm chi tiết'
    else addressError.value = ''
  } catch {
    addressError.value = 'Không tải được gợi ý địa chỉ'
  } finally {
    suggestionLoading.value = false
  }
}

function pickSuggestion(index) {
  const s = suggestions.value[index < 0 ? 0 : index]
  if (!s) return
  addressQuery.value    = s.display_name
  form.dia_chi          = s.display_name
  form.vi_do            = parseFloat(s.lat)
  form.kinh_do          = parseFloat(s.lon)
  suggestions.value     = []
  activeSuggestion.value = -1
}

function moveSuggestion(dir) {
  const max = suggestions.value.length - 1
  activeSuggestion.value = Math.max(0, Math.min(max, activeSuggestion.value + dir))
}

function closeSuggestions() { suggestions.value = [] }

function onDocClick(e) {
  if (addressWrap.value && !addressWrap.value.contains(e.target)) closeSuggestions()
}

// ── GPS ──────────────────────────────────────────────────────────
function getGPS() {
  if (!navigator.geolocation) { gpsError.value = 'Trình duyệt không hỗ trợ GPS'; return }
  gpsLoading.value = true
  gpsError.value   = ''
  navigator.geolocation.getCurrentPosition(
    pos => {
      form.vi_do   = +pos.coords.latitude.toFixed(6)
      form.kinh_do = +pos.coords.longitude.toFixed(6)
      gpsLoading.value = false
      reverseGeocode(form.vi_do, form.kinh_do)
    },
    err => {
      gpsError.value = err.code === 1 ? 'Bạn từ chối quyền GPS' : 'Không lấy được vị trí'
      gpsLoading.value = false
    },
    { timeout: 10000, enableHighAccuracy: true }
  )
}

async function reverseGeocode(lat, lng) {
  try {
    const resp = await fetch(
      `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`,
      { headers: { 'Accept-Language': 'vi,en' } }
    )
    const data = await resp.json()
    if (data.display_name) {
      addressQuery.value = data.display_name
      form.dia_chi       = data.display_name
    }
  } catch {}
}

// ── Multiple Image Upload (max 5) ─────────────────────────────────
function onFileChange(e) {
  const files = Array.from(e.target.files || [])
  if (!files.length) return

  const remaining = 5 - imageFiles.value.length
  const toAdd = files.slice(0, remaining)

  for (const file of toAdd) {
    if (file.size > 10 * 1024 * 1024) {
      toast.error(`Ảnh "${file.name}" quá lớn (tối đa 10MB)`)
      continue
    }
    imageFiles.value.push(file)
    imagePreviews.value.push(URL.createObjectURL(file))
  }

  if (imageFiles.value.length > remaining) {
    toast.warning(`Chỉ thêm được tối đa 5 ảnh. ${files.length - remaining} ảnh đã bỏ qua.`)
  }

  // AI analyze first image
  if (imageFiles.value.length === toAdd.length && toAdd.length > 0) {
    aiDone.value   = false
    analyzeImageWithAI(imageFiles.value[0])
  }

  // Reset input so same file can be re-selected
  if (fileInput.value) fileInput.value.value = ''
}

function removeImage(idx) {
  URL.revokeObjectURL(imagePreviews.value[idx])
  imagePreviews.value.splice(idx, 1)
  imageFiles.value.splice(idx, 1)
  if (idx === 0) {
    aiDone.value   = false
    aiWarning.value = null
  }
}

async function analyzeImageWithAI(file) {
  aiAnalyzing.value = true
  aiWarning.value   = null

  try {
    const base64 = await new Promise((resolve, reject) => {
      const reader = new FileReader()
      reader.onload  = () => resolve(reader.result.split(',')[1])
      reader.onerror = reject
      reader.readAsDataURL(file)
    })

    const resp = await api.post('/analyze-image', {
      image_base64: base64,
      tieu_de:      form.tieu_de?.trim() || 'Sự cố',
    })

    const data = resp.data
    console.log('[Groq AI Response]', data)

    if (!data.success) {
      const errorCode = data.error_code
      if (errorCode === 401) {
        toast.error('🔑 Groq API Key hết hạn! Cần cập nhật key mới.')
      } else {
        toast.warning('⚠️ AI phân tích thất bại: ' + (data.error || 'Lỗi không xác định'))
      }
      return
    }

    let anyFill = false

    if (data.image_description && data.image_description.trim()) {
      form.noi_dung = data.image_description.trim()
      aiDone.value  = true
      anyFill = true
    }

    if (data.id_loai_su_co && Number(data.id_loai_su_co) > 0) {
      form.id_loai_su_co = Number(data.id_loai_su_co)
      anyFill = true
    }

    if (data.id_muc_do && Number(data.id_muc_do) > 0) {
      form.id_muc_do = Number(data.id_muc_do)
      anyFill = true
    }

    if (anyFill) {
      toast.success('🤖 AI đã phân tích và tự động điền thông tin!')
    } else {
      toast.info('🤖 AI chưa xác định được thông tin. Vui lòng điền thủ công.')
    }

  } catch (err) {
    console.warn('[AI] Lỗi phân tích ảnh:', err)
    toast.warning('⚠️ Không thể phân tích ảnh. Vui lòng điền thủ công.')
  } finally {
    aiAnalyzing.value = false
  }
}

// ── AI Local Duplicate / Spam Detection ──────────────────────────
/**
 * Calls the Python AI service /predict-json to detect duplicate & spam
 * BEFORE submitting. Shows toast warnings but allows user to override.
 * Returns false only if spam is confirmed (block submit).
 */
async function checkDuplicateAndSpam() {
  const aiUrl = 'http://localhost:8001'
  aiChecking.value = true
  aiWarning.value  = null

  try {
    const resp = await fetch(`${aiUrl}/predict-json`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        tieu_de:  form.tieu_de,
        noi_dung: form.noi_dung,
        vi_do:    form.vi_do   ? Number(form.vi_do)   : null,
        kinh_do:  form.kinh_do ? Number(form.kinh_do) : null,
      }),
      signal: AbortSignal.timeout(6000),
    })

    if (!resp.ok) return true  // AI service down — allow submit

    const data = await resp.json()
    console.log('[AI Local Check]', data)

    // ── SPAM detection ────────────────────────────────────────────
    if (data.is_spam === 1) {
      aiWarning.value = {
        type: 'spam',
        title: '🚫 Cảnh báo SPAM',
        message: 'Nội dung sự cố có dấu hiệu spam. Vui lòng cung cấp thông tin thực tế và chi tiết hơn.',
      }
      toast.error('🚫 Sự cố bị từ chối vì bị phát hiện là spam!')
      return false  // block submit
    }

    // ── DUPLICATE detection ───────────────────────────────────────
    if (data.is_duplicate === 1) {
      const sim = data.similarity_score ? Math.round(data.similarity_score * 100) : '?'
      aiWarning.value = {
        type: 'duplicate',
        title: '⚠️ Sự cố có thể trùng lặp',
        message: `Phát hiện sự cố tương tự đã được báo cáo (độ tương đồng: ${sim}%). Bạn có thể tiếp tục gửi nếu đây là sự cố mới.`,
      }
      toast.warning(`⚠️ Sự cố này có thể trùng với sự cố đã có (${sim}% giống nhau). Kiểm tra lại trước khi gửi.`)
      // Do NOT block — just warn; user can still submit
    }

    return true

  } catch (err) {
    console.warn('[AI Local] Service unavailable:', err.message)
    return true  // AI down — allow submit
  } finally {
    aiChecking.value = false
  }
}

// ── Dropdowns ────────────────────────────────────────────────────
async function loadDropdowns() {
  try {
    const [catRes, lvRes] = await Promise.all([adminApi.categories(), adminApi.levels()])
    categories.value = catRes.data.data ?? catRes.data
    levels.value     = lvRes.data.data ?? lvRes.data
  } catch {}
}

// ── Submit ───────────────────────────────────────────────────────
async function submit() {
  // Step 1: AI check (duplicate + spam)
  const canSubmit = await checkDuplicateAndSpam()
  if (!canSubmit) return

  loading.value = true
  try {
    const fd = new FormData()
    fd.append('tieu_de',       form.tieu_de)
    fd.append('noi_dung',      form.noi_dung)
    fd.append('dia_chi',       form.dia_chi)
    fd.append('id_loai_su_co', form.id_loai_su_co)
    fd.append('id_muc_do',     form.id_muc_do)
    if (form.vi_do)   fd.append('vi_do',   form.vi_do)
    if (form.kinh_do) fd.append('kinh_do', form.kinh_do)

    // Append all images
    imageFiles.value.forEach((file, idx) => {
      if (idx === 0) {
        fd.append('hinh_anh', file)           // primary (backward compat)
      }
      fd.append('hinh_anhs[]', file)          // full array (new field)
    })

    await api.post('/su-co', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    toast.success('✅ Sự cố đã được báo cáo thành công!')
    Object.assign(form, defaultForm())
    addressQuery.value = ''
    clearAllImages()
    show.value = false
    emit('created')
  } catch (err) {
    const errs = err.response?.data?.errors
    const msg  = errs ? Object.values(errs).flat().join('\n') : (err.response?.data?.message || 'Có lỗi xảy ra!')
    toast.error(`❌ ${msg}`)
  } finally {
    loading.value = false
  }
}

function clearAllImages() {
  imagePreviews.value.forEach(url => URL.revokeObjectURL(url))
  imagePreviews.value = []
  imageFiles.value    = []
  aiDone.value        = false
  aiWarning.value     = null
  if (fileInput.value) fileInput.value.value = ''
}

// ── Reset on open ─────────────────────────────────────────────────
watch(show, val => {
  if (val) {
    loadDropdowns()
    if (!form.vi_do) getGPS()
  } else {
    closeSuggestions()
  }
})

onMounted(() => {
  document.addEventListener('click', onDocClick)
})
onBeforeUnmount(() => {
  document.removeEventListener('click', onDocClick)
  clearTimeout(debounceTimer)
  clearAllImages()
})
</script>
