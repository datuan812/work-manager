<script setup>
import { computed, onMounted, ref } from 'vue'
import ParentLayout from '../../layouts/ParentLayout.vue'
import { api } from '../../services/api.service'

const data = ref(null)
const loading = ref(false)
const error = ref('')
const percent = (count, total) => total ? Math.round(count / total * 100) + '%' : '—'

const metrics = computed(() => {
    if (!data.value) return []
    const report = data.value
    return [
        { label: 'Gia đình đã giao việc', value: report.families_with_tasks + ' / ' + report.families_total, note: percent(report.families_with_tasks, report.families_total) + ' số gia đình có nhiệm vụ đã giao' },
        { label: 'Gia đình hoạt động tuần này', value: report.active_this_week, note: 'Từ ' + report.week_start },
        { label: 'Gia đình quay lại tuần này', value: report.returned_this_week + ' / ' + report.active_previous_week, note: percent(report.returned_this_week, report.active_previous_week) + ' số gia đình đã hoạt động tuần trước' },
    ]
})

async function load() {
    loading.value = true
    error.value = ''
    try {
        data.value = await api('/api/admin/dashboard')
    } catch (failure) {
        error.value = failure.message
    } finally {
        loading.value = false
    }
}

onMounted(load)
</script>

<template>
    <ParentLayout>
        <div class="mx-auto max-w-6xl">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black">Tổng quan vận hành</h1>
                    <p class="mt-2 text-sm text-slate-600">Theo dõi việc giao nhiệm vụ, hoạt động theo tuần và lượt sử dụng mẫu.</p>
                </div>
                <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-sky-700 disabled:opacity-50" :disabled="loading" @click="load">Làm mới</button>
            </div>

            <p v-if="error" role="alert" class="mt-4 text-sm font-bold text-red-600">{{ error }}</p>
            <p v-if="loading && !data" class="mt-4 text-sm text-slate-500">Đang tải số liệu…</p>
            <template v-if="data">
                <div class="mt-6 grid gap-4 md:grid-cols-3">
                    <article v-for="metric in metrics" :key="metric.label" class="admin-card p-5">
                        <h2 class="text-sm font-semibold text-slate-600">{{ metric.label }}</h2>
                        <p class="mt-3 text-3xl font-black text-slate-900">{{ metric.value }}</p>
                        <p class="mt-2 text-xs text-slate-600">{{ metric.note }}</p>
                    </article>
                </div>
                <p class="mt-3 text-xs text-slate-500">Hoạt động được ghi nhận từ khi bật tính năng, theo tuần bắt đầu thứ Hai (giờ Việt Nam).</p>

                <section class="admin-card mt-6 p-5">
                    <h2 class="text-xl font-black">Mẫu được nhiều gia đình dùng</h2>
                    <p v-if="!data.popular_templates.length" class="mt-3 text-sm text-slate-500">Chưa có mẫu nào được sao chép.</p>
                    <ul v-else class="mt-3 divide-y divide-slate-100">
                        <li v-for="template in data.popular_templates" :key="template.id" class="flex items-center justify-between gap-3 py-3 text-sm">
                            <span class="font-semibold">{{ template.title }}</span>
                            <span class="shrink-0 font-bold text-sky-700">{{ template.families_count }} gia đình</span>
                        </li>
                    </ul>
                </section>
            </template>
        </div>
    </ParentLayout>
</template>
