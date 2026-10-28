<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { Pencil, Plus, Trash2 } from 'lucide-vue-next'
import ParentLayout from '../../layouts/ParentLayout.vue'
import { api } from '../../services/api.service'

const categories = ref([])
const loading = ref(true)
const saving = ref(false)
const error = ref('')
const editingId = ref(null)
const form = reactive({ name: '', icon: '', color: '#2563eb' })

async function loadCategories() {
    loading.value = true
    try {
        categories.value = await api('/api/admin/categories')
    } finally {
        loading.value = false
    }
}

function resetForm() {
    editingId.value = null
    Object.assign(form, { name: '', icon: '', color: '#2563eb' })
    error.value = ''
}

function editCategory(category) {
    editingId.value = category.id
    Object.assign(form, {
        name: category.name,
        icon: category.icon || '',
        color: category.color || '#2563eb',
    })
    error.value = ''
}

async function save() {
    error.value = ''
    saving.value = true
    try {
        const body = { ...form }
        const category = await api(editingId.value ? `/api/admin/categories/${editingId.value}` : '/api/admin/categories', {
            method: editingId.value ? 'PATCH' : 'POST',
            body,
        })
        if (editingId.value) {
            const index = categories.value.findIndex((item) => item.id === category.id)
            if (index !== -1) categories.value[index] = category
        } else {
            categories.value.push(category)
            categories.value.sort((a, b) => a.name.localeCompare(b.name))
        }
        resetForm()
    } catch (exception) {
        error.value = exception.message
    } finally {
        saving.value = false
    }
}

async function removeCategory(category) {
    if (!window.confirm(`Xóa danh mục “${category.name}”? Các nhiệm vụ đang dùng danh mục này sẽ không còn danh mục.`)) return

    try {
        await api(`/api/admin/categories/${category.id}`, { method: 'DELETE' })
        categories.value = categories.value.filter((item) => item.id !== category.id)
        if (editingId.value === category.id) resetForm()
    } catch (exception) {
        error.value = exception.message
    }
}

onMounted(async () => {
    try {
        await loadCategories()
    } catch (exception) {
        error.value = exception.message
        loading.value = false
    }
})
</script>

<template>
    <ParentLayout>
        <div class="mx-auto max-w-6xl">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="admin-section-title">Task categories</p>
                    <h1 class="mt-2 text-3xl font-black">Quản lý danh mục</h1>
                    <p class="mt-2 font-semibold text-slate-500">Bộ danh mục dùng chung cho toàn bộ gia đình trong hệ thống.</p>
                </div>
            </div>

            <div class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
                <section class="admin-card overflow-hidden">
                    <div class="border-b border-slate-100 p-5">
                        <p class="admin-section-title">Danh mục hiện có</p>
                        <p class="mt-1 text-sm font-semibold text-slate-500">{{ categories.length }} danh mục</p>
                    </div>
                    <div v-if="loading" class="p-8 text-center font-bold text-slate-500">Đang tải...</div>
                    <div v-else-if="!categories.length" class="p-8 text-center font-bold text-slate-500">Chưa có danh mục.</div>
                    <div v-else class="divide-y divide-slate-100">
                        <div v-for="category in categories" :key="category.id" class="flex items-center gap-4 p-5">
                            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl text-xl" :style="{ backgroundColor: `${category.color}20`, color: category.color }">{{ category.icon || '⭐' }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="font-black text-slate-950">{{ category.name }}</p>
                            </div>
                            <button class="rounded-lg p-2 text-sky-700 hover:bg-sky-50" type="button" title="Sửa" @click="editCategory(category)"><Pencil class="h-4 w-4" /></button>
                            <button class="rounded-lg p-2 text-red-600 hover:bg-red-50" type="button" title="Xóa" @click="removeCategory(category)"><Trash2 class="h-4 w-4" /></button>
                        </div>
                    </div>
                </section>

                <form class="admin-card h-fit p-5" @submit.prevent="save">
                    <p class="admin-section-title">{{ editingId ? 'Chỉnh sửa' : 'Tạo mới' }}</p>
                    <h2 class="mt-1 text-xl font-black">{{ editingId ? 'Cập nhật danh mục' : 'Thêm danh mục' }}</h2>
                    <div class="mt-5 space-y-4">
                        <label class="field-label">Tên danh mục<input v-model="form.name" class="home-input" maxlength="100" required placeholder="Ví dụ: Học tập" /></label>
                        <div class="grid grid-cols-[1fr_4.5rem] gap-3">
                            <label class="field-label">Biểu tượng<input v-model="form.icon" class="home-input" maxlength="20" placeholder="📚" /></label>
                            <label class="field-label">Màu<input v-model="form.color" class="mt-2 h-12 w-full cursor-pointer rounded-xl border border-slate-200 bg-white p-1" type="color" /></label>
                        </div>
                        <p v-if="error" class="rounded-xl bg-red-50 p-3 text-sm font-bold text-red-600">{{ error }}</p>
                        <div class="flex gap-2">
                            <button class="flex min-h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-sky-600 px-4 text-sm font-black text-white hover:bg-sky-700 disabled:opacity-50" type="submit" :disabled="saving"><Plus v-if="!editingId" class="h-4 w-4" />{{ saving ? 'Đang lưu...' : (editingId ? 'Lưu thay đổi' : 'Thêm danh mục') }}</button>
                            <button v-if="editingId" class="rounded-xl px-4 text-sm font-black text-slate-600 ring-1 ring-slate-200" type="button" @click="resetForm">Hủy</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </ParentLayout>
</template>
