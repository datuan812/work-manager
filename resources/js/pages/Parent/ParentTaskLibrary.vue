<script setup>
import { onMounted, ref } from 'vue'
import ParentLayout from '../../layouts/ParentLayout.vue'
import PaginationControls from '../../components/common/PaginationControls.vue'
import { api } from '../../services/api.service'
import { useToastStore } from '../../stores/toast.store'

const toast = useToastStore()
const result = ref(null)
const search = ref('')
const loading = ref(false)
const importId = ref(null)
const error = ref('')

function ageLabel(template) {
    if (template.min_age !== null && template.max_age !== null) return template.min_age + '–' + template.max_age + ' tuổi'
    if (template.min_age !== null) return 'Từ ' + template.min_age + ' tuổi'
    if (template.max_age !== null) return 'Đến ' + template.max_age + ' tuổi'
    return null
}

async function load(page = 1) {
    loading.value = true
    error.value = ''
    try {
        const query = new URLSearchParams({ page, search: search.value.trim() })
        result.value = await api('/api/parent/task-templates?' + query)
    } catch (failure) {
        error.value = failure.message
    } finally {
        loading.value = false
    }
}

async function importTemplate(template) {
    importId.value = template.id
    try {
        const task = await api('/api/parent/task-templates/' + template.id + '/import', { method: 'POST' })
        template.imported_task_id = task.id
        toast.show('Đã thêm mẫu vào kho nhiệm vụ của gia đình.')
    } catch (failure) {
        toast.show(failure.message, 'error')
    } finally {
        importId.value = null
    }
}

onMounted(() => load())
</script>

<template>
    <ParentLayout>
        <div class="mx-auto max-w-6xl">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black">Thư viện nhiệm vụ mẫu</h1>
                    <p class="mt-2 text-sm text-slate-600">Thêm một mẫu vào kho gia đình để chỉnh sửa và giao cho bé.</p>
                </div>
                <RouterLink to="/parent/tasks" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-sky-700">Về kho nhiệm vụ</RouterLink>
            </div>
            <form class="mt-6 flex max-w-md items-end gap-2" @submit.prevent="load()">
                <label class="flex-1 text-sm font-bold">Tìm theo tên<input v-model="search" class="home-input" type="search" maxlength="100" /></label>
                <button class="min-h-11 rounded-xl bg-sky-600 px-4 text-sm font-bold text-white disabled:opacity-50" :disabled="loading">Tìm</button>
            </form>
            <p v-if="error" class="mt-4 text-sm font-bold text-red-600" role="alert">{{ error }}</p>
            <p v-if="loading" class="mt-4 text-sm text-slate-500">Đang tải thư viện…</p>
            <p v-if="result && !result.data.length" class="admin-card mt-5 p-5 text-sm text-slate-500">Chưa tìm thấy mẫu phù hợp.</p>
            <div v-if="result" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <article v-for="template in result.data" :key="template.id" class="admin-card flex min-w-0 flex-col p-5">
                    <h2 class="break-words text-lg font-black">{{ template.icon || template.category?.icon || '⭐' }} {{ template.title }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ template.category?.name }} · {{ template.points }} điểm<span v-if="ageLabel(template)"> · {{ ageLabel(template) }}</span></p>
                    <p v-if="template.description" class="mt-3 break-words text-sm text-slate-700">{{ template.description }}</p>
                    <ol v-if="template.steps?.length" class="mt-3 list-decimal space-y-1 pl-5 text-sm text-slate-700">
                        <li v-for="(step, index) in template.steps" :key="index" class="break-words">{{ step }}</li>
                    </ol>
                    <div class="mt-auto pt-4">
                        <button type="button" class="rounded-xl bg-sky-600 px-4 py-2 text-sm font-bold text-white disabled:bg-slate-100 disabled:text-slate-600" :disabled="Boolean(template.imported_task_id) || importId !== null" @click="importTemplate(template)">
                            {{ template.imported_task_id ? 'Đã có trong kho' : importId === template.id ? 'Đang thêm…' : 'Thêm vào kho' }}
                        </button>
                    </div>
                </article>
            </div>
            <PaginationControls v-if="result" class="mt-5" :meta="result" :loading="loading" @change="load" />
        </div>
    </ParentLayout>
</template>
