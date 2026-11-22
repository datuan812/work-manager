<script setup>
import { onMounted, reactive, ref } from 'vue'
import ParentLayout from '../../layouts/ParentLayout.vue'
import ConfirmDialog from '../../components/common/ConfirmDialog.vue'
import PaginationControls from '../../components/common/PaginationControls.vue'
import { api } from '../../services/api.service'
import { useToastStore } from '../../stores/toast.store'

const toast = useToastStore()
const blank = () => ({ id: null, title: '', category_id: '', icon: '', description: '', points: 10, min_age: '', max_age: '', stepsText: '', is_active: true })
const form = reactive(blank())
const categories = ref([])
const result = ref(null)
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const listError = ref('')
const pendingDelete = ref(null)
const confirmDelete = ref(false)

async function load(page = 1) {
    loading.value = true
    listError.value = ''
    try {
        result.value = await api('/api/admin/task-templates?page=' + page)
    } catch (failure) {
        listError.value = failure.message
    } finally {
        loading.value = false
    }
}

function reset() {
    Object.assign(form, blank())
    error.value = ''
}

function edit(template) {
    const { id, title, category_id, icon, description, points, min_age, max_age, steps, is_active } = template
    Object.assign(form, { id, title, category_id: category_id ?? '', icon, description, points, min_age: min_age ?? '', max_age: max_age ?? '', stepsText: (steps ?? []).join('\n'), is_active })
    error.value = ''
}

async function save() {
    const { id, stepsText, ...payload } = form
    saving.value = true
    error.value = ''
    try {
        await api('/api/admin/task-templates' + (id ? '/' + id : ''), {
            method: id ? 'PUT' : 'POST',
            body: { ...payload, steps: stepsText.split('\n').map((step) => step.trim()).filter(Boolean) },
        })
        reset()
        toast.show('Đã lưu nhiệm vụ mẫu.')
        await load(id ? result.value?.current_page : 1)
    } catch (failure) {
        error.value = failure.message
    } finally {
        saving.value = false
    }
}

function askDelete(template) {
    pendingDelete.value = template
    confirmDelete.value = true
}

async function remove() {
    const template = pendingDelete.value
    pendingDelete.value = null
    if (!template) return
    try {
        await api('/api/admin/task-templates/' + template.id, { method: 'DELETE' })
        if (form.id === template.id) reset()
        const page = result.value?.current_page ?? 1
        await load(result.value?.data.length === 1 ? Math.max(1, page - 1) : page)
        toast.show('Đã xóa nhiệm vụ mẫu.')
    } catch (failure) {
        toast.show(failure.message, 'error')
    }
}

onMounted(async () => {
    await Promise.all([
        load(),
        api('/api/admin/categories').then((data) => { categories.value = data }).catch((failure) => { error.value = failure.message }),
    ])
})
</script>

<template>
    <ParentLayout>
        <div class="mx-auto max-w-6xl">
            <h1 class="text-3xl font-black">Thư viện nhiệm vụ mẫu</h1>
            <p class="mt-2 text-sm text-slate-600">Phụ huynh có thể sao chép mẫu đang hiển thị về kho nhiệm vụ của gia đình.</p>
            <div class="mt-6 grid items-start gap-5 xl:grid-cols-[380px_1fr]">
                <form class="admin-card grid gap-4 p-5" @submit.prevent="save">
                    <h2 class="text-lg font-black">{{ form.id ? 'Sửa nhiệm vụ mẫu' : 'Tạo nhiệm vụ mẫu' }}</h2>
                    <label class="text-sm font-bold">Tên nhiệm vụ<input v-model.trim="form.title" class="home-input" maxlength="255" required /></label>
                    <label class="text-sm font-bold">
                        Danh mục
                        <select v-model="form.category_id" class="home-input" required>
                            <option value="" disabled>Chọn danh mục</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.icon }} {{ category.name }}</option>
                        </select>
                    </label>
                    <RouterLink v-if="!categories.length" to="/admin/categories" class="text-sm font-bold text-sky-700">Thêm danh mục</RouterLink>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="text-sm font-bold">Biểu tượng<input v-model.trim="form.icon" class="home-input" maxlength="20" /></label>
                        <label class="text-sm font-bold">Điểm thưởng<input v-model.number="form.points" class="home-input" type="number" min="1" max="500" required /></label>
                        <label class="text-sm font-bold">Tuổi tối thiểu<input v-model.number="form.min_age" class="home-input" type="number" min="0" max="18" /></label>
                        <label class="text-sm font-bold">Tuổi tối đa<input v-model.number="form.max_age" class="home-input" type="number" :min="form.min_age === '' ? 0 : form.min_age" max="18" /></label>
                    </div>
                    <label class="text-sm font-bold">Mô tả<textarea v-model.trim="form.description" class="home-input" rows="2" maxlength="2000" /></label>
                    <label class="text-sm font-bold">Các bước thực hiện<textarea v-model="form.stepsText" class="home-input" rows="3" placeholder="Mỗi dòng một bước, tối đa 12 bước" /></label>
                    <label class="flex items-center gap-2 text-sm font-bold"><input v-model="form.is_active" type="checkbox" /> Hiển thị cho phụ huynh</label>
                    <p v-if="error" class="text-sm font-bold text-red-600" role="alert">{{ error }}</p>
                    <div class="flex gap-3">
                        <button class="rounded-xl bg-sky-600 px-4 py-2 text-sm font-bold text-white disabled:opacity-50" type="submit" :disabled="saving || !categories.length">{{ saving ? 'Đang lưu…' : 'Lưu mẫu' }}</button>
                        <button v-if="form.id" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold" type="button" @click="reset">Hủy sửa</button>
                    </div>
                </form>

                <section class="space-y-3">
                    <p v-if="listError" class="text-sm font-bold text-red-600" role="alert">{{ listError }}</p>
                    <p v-if="loading" class="text-sm text-slate-500">Đang tải thư viện…</p>
                    <p v-if="result && !result.data.length" class="admin-card p-5 text-sm text-slate-500">Chưa có nhiệm vụ mẫu.</p>
                    <article v-for="template in result?.data" :key="template.id" class="admin-card flex flex-wrap justify-between gap-3 p-4">
                        <div class="min-w-0">
                            <h2 class="break-words font-bold">{{ template.icon }} {{ template.title }}</h2>
                            <p class="mt-1 text-xs text-slate-600">{{ template.category?.name ?? 'Cần gán danh mục' }} · {{ template.points }} điểm · {{ template.families_count }} gia đình đã dùng</p>
                            <p class="mt-1 text-xs font-semibold" :class="template.is_active && template.category ? 'text-emerald-700' : 'text-slate-500'">{{ template.is_active && template.category ? 'Đang hiển thị' : 'Đang ẩn' }}</p>
                        </div>
                        <div class="flex gap-3 text-sm font-bold">
                            <button type="button" class="text-sky-700" @click="edit(template)">Sửa</button>
                            <button type="button" class="text-red-600" @click="askDelete(template)">Xóa</button>
                        </div>
                    </article>
                    <PaginationControls v-if="result" :meta="result" :loading="loading" @change="load" />
                </section>
            </div>
        </div>
        <ConfirmDialog v-model="confirmDelete" title="Xóa nhiệm vụ mẫu" message="Mẫu sẽ biến mất khỏi thư viện. Các bản đã sao chép về gia đình vẫn dùng được." @confirm="remove" @cancel="pendingDelete = null" />
    </ParentLayout>
</template>
