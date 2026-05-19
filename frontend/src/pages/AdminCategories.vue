<template>
  <div class="p-6 max-w-6xl mx-auto space-y-8">
    <div>
      <div class="flex items-center gap-2">
        <FolderOpenIcon class="w-7 h-7 text-gray-800 dark:text-gray-100" />
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Quản lý Danh mục</h1>
      </div>
      <p class="text-sm text-gray-500 mt-0.5">Quản lý loại sự cố và mức độ khẩn cấp</p>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
      <!-- Categories -->
      <div class="glass-card overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <TagIcon class="w-5 h-5 text-gray-700 dark:text-gray-200" />
            <h2 class="font-semibold text-gray-700 dark:text-gray-200">Loại sự cố</h2>
          </div>
          <button @click="openAddCat" class="btn-primary text-xs px-3 py-1.5 flex items-center gap-1">
            <PlusIcon class="w-3.5 h-3.5" /> Thêm
          </button>
        </div>
        <div class="divide-y divide-gray-50 dark:divide-gray-700">
          <div v-if="loadingCat" class="p-4 space-y-2">
            <div v-for="i in 3" :key="i" class="skeleton h-10 rounded" />
          </div>
          <div v-else-if="!categories.length" class="py-10 text-center text-gray-400 text-sm">Chưa có loại sự cố</div>
          <div v-else v-for="cat in categories" :key="cat.id_loai_su_co"
               class="flex items-center justify-between px-6 py-3 hover:bg-gray-50/50 dark:hover:bg-gray-700/30">
            <div>
              <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ cat.ten_loai }}</p>
              <p class="text-xs text-gray-400">#{{ cat.id_loai_su_co }}</p>
            </div>
            <div class="flex gap-2">
              <button @click="openEditCat(cat)" class="btn-xs bg-blue-50 text-blue-600 hover:bg-blue-100">✏️</button>
              <button @click="deleteCat(cat)" class="btn-xs bg-red-50 text-red-600 hover:bg-red-100">🗑️</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Levels -->
      <div class="glass-card overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <FireIcon class="w-5 h-5 text-gray-700 dark:text-gray-200" />
            <h2 class="font-semibold text-gray-700 dark:text-gray-200">Mức độ khẩn cấp</h2>
          </div>
          <button @click="openAddLvl" class="btn-primary text-xs px-3 py-1.5 flex items-center gap-1">
            <PlusIcon class="w-3.5 h-3.5" /> Thêm
          </button>
        </div>
        <div class="divide-y divide-gray-50 dark:divide-gray-700">
          <div v-if="loadingLvl" class="p-4 space-y-2">
            <div v-for="i in 3" :key="i" class="skeleton h-10 rounded" />
          </div>
          <div v-else-if="!levels.length" class="py-10 text-center text-gray-400 text-sm">Chưa có mức độ nào</div>
          <div v-else v-for="lv in levels" :key="lv.id_muc_do"
               class="flex items-center justify-between px-6 py-3 hover:bg-gray-50/50 dark:hover:bg-gray-700/30">
            <div>
              <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ lv.ten_muc_do }}</p>
              <p class="text-xs text-gray-400">Ưu tiên: {{ lv.do_uu_tien }} · #{{ lv.id_muc_do }}</p>
            </div>
            <div class="flex gap-2">
              <button @click="openEditLvl(lv)" class="btn-xs bg-blue-50 text-blue-600 hover:bg-blue-100">✏️</button>
              <button @click="deleteLvl(lv)" class="btn-xs bg-red-50 text-red-600 hover:bg-red-100">🗑️</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Category Modal -->
    <Modal v-model="showCatModal" :title="editCat ? 'Sửa loại sự cố' : 'Thêm loại sự cố'" size="sm">
      <form @submit.prevent="saveCat" class="space-y-4">
        <div>
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Tên loại *</label>
          <input v-model="catForm.ten_loai" type="text" required class="input-field" placeholder="VD: Cháy nổ" />
        </div>
        <div class="flex gap-3">
          <button type="button" @click="showCatModal = false" class="btn-ghost flex-1">Hủy</button>
          <button type="submit" :disabled="savingCat" class="btn-primary flex-1 flex items-center justify-center gap-2">
            <span v-if="savingCat" class="w-4 h-4 border-2 border-gray-300 border-t-white rounded-full animate-spin" />
            {{ editCat ? 'Lưu' : 'Thêm' }}
          </button>
        </div>
      </form>
    </Modal>

    <!-- Level Modal -->
    <Modal v-model="showLvlModal" :title="editLvl ? 'Sửa mức độ' : 'Thêm mức độ'" size="sm">
      <form @submit.prevent="saveLvl" class="space-y-4">
        <div>
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Tên mức độ *</label>
          <input v-model="lvlForm.ten_muc_do" type="text" required class="input-field" placeholder="VD: Khẩn cấp" />
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Độ ưu tiên (số, cao hơn = ưu tiên hơn)</label>
          <input v-model.number="lvlForm.do_uu_tien" type="number" min="0" class="input-field" />
        </div>
        <div class="flex gap-3">
          <button type="button" @click="showLvlModal = false" class="btn-ghost flex-1">Hủy</button>
          <button type="submit" :disabled="savingLvl" class="btn-primary flex-1 flex items-center justify-center gap-2">
            <span v-if="savingLvl" class="w-4 h-4 border-2 border-gray-300 border-t-white rounded-full animate-spin" />
            {{ editLvl ? 'Lưu' : 'Thêm' }}
          </button>
        </div>
      </form>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { PlusIcon, FolderOpenIcon, TagIcon, FireIcon } from '@heroicons/vue/24/outline'
import Modal from '@/components/ui/Modal.vue'
import { adminApi } from '@/services/api'

const toast = useToast()
const categories = ref([])
const levels     = ref([])
const loadingCat = ref(false)
const loadingLvl = ref(false)
const savingCat  = ref(false)
const savingLvl  = ref(false)

const editCat = ref(null)
const editLvl = ref(null)
const showCatModal = ref(false)
const showLvlModal = ref(false)
const catForm = reactive({ ten_loai: '' })
const lvlForm = reactive({ ten_muc_do: '', do_uu_tien: 0 })

async function loadCategories() {
  loadingCat.value = true
  try { const res = await adminApi.categories(); categories.value = res.data.data ?? res.data }
  catch {} finally { loadingCat.value = false }
}

async function loadLevels() {
  loadingLvl.value = true
  try { const res = await adminApi.levels(); levels.value = res.data.data ?? res.data }
  catch {} finally { loadingLvl.value = false }
}

function openAddCat() { editCat.value = null; catForm.ten_loai = ''; showCatModal.value = true }
function openEditCat(c) { editCat.value = c; catForm.ten_loai = c.ten_loai; showCatModal.value = true }

async function saveCat() {
  savingCat.value = true
  try {
    if (editCat.value) {
      await adminApi.categoryUpdate(editCat.value.id_loai_su_co, catForm)
      editCat.value.ten_loai = catForm.ten_loai
      toast.success('✅ Đã cập nhật loại sự cố!')
    } else {
      const res = await adminApi.categoryCreate(catForm)
      categories.value.push(res.data.data ?? res.data)
      toast.success('✅ Đã thêm loại sự cố!')
    }
    showCatModal.value = false
  } catch (e) { toast.error(e.response?.data?.message || 'Lỗi') }
  finally { savingCat.value = false }
}

async function deleteCat(c) {
  if (!confirm(`Xóa loại sự cố "${c.ten_loai}"?`)) return
  try {
    await adminApi.categoryDelete(c.id_loai_su_co)
    categories.value = categories.value.filter(x => x.id_loai_su_co !== c.id_loai_su_co)
    toast.success('🗑️ Đã xóa!')
  } catch (e) { toast.error(e.response?.data?.message || 'Lỗi xóa') }
}

function openAddLvl() { editLvl.value = null; lvlForm.ten_muc_do = ''; lvlForm.do_uu_tien = 0; showLvlModal.value = true }
function openEditLvl(l) { editLvl.value = l; lvlForm.ten_muc_do = l.ten_muc_do; lvlForm.do_uu_tien = l.do_uu_tien; showLvlModal.value = true }

async function saveLvl() {
  savingLvl.value = true
  try {
    if (editLvl.value) {
      await adminApi.levelUpdate(editLvl.value.id_muc_do, lvlForm)
      Object.assign(editLvl.value, lvlForm)
      toast.success('✅ Đã cập nhật mức độ!')
    } else {
      const res = await adminApi.levelCreate(lvlForm)
      levels.value.push(res.data.data ?? res.data)
      toast.success('✅ Đã thêm mức độ!')
    }
    showLvlModal.value = false
  } catch (e) { toast.error(e.response?.data?.message || 'Lỗi') }
  finally { savingLvl.value = false }
}

async function deleteLvl(l) {
  if (!confirm(`Xóa mức độ "${l.ten_muc_do}"?`)) return
  try {
    await adminApi.levelDelete(l.id_muc_do)
    levels.value = levels.value.filter(x => x.id_muc_do !== l.id_muc_do)
    toast.success('🗑️ Đã xóa!')
  } catch (e) { toast.error(e.response?.data?.message || 'Lỗi xóa') }
}

onMounted(() => { loadCategories(); loadLevels() })
</script>

<style scoped>
.btn-xs { @apply px-2 py-1 rounded-lg text-xs font-medium transition-colors cursor-pointer; }
</style>
