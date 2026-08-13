<script setup>
import { computed, onMounted, reactive } from 'vue'
import { CheckCircle2, CircleDashed, Clock3, Search, SkipForward } from 'lucide-vue-next'
import ParentLayout from '../../layouts/ParentLayout.vue'
import BaseButton from '../../components/common/BaseButton.vue'
import LoadingState from '../../components/common/LoadingState.vue'
import { useParentStore } from '../../stores/parent.store'

const parent = useParentStore()
const filters = reactive({
    start_date: dateKey(daysAgo(30)),
    end_date: dateKey(new Date()),
    user_id: '',
    status: '',
    limit: 100,
})
const summaryCards = computed(() => [
    { label: 'Tổng việc', value: parent.taskHistory.summary?.total ?? 0, icon: CircleDashed, tone: 'bg-slate-100 text-slate-700' },
    { label: 'Hoàn thành', value: parent.taskHistory.summary?.completed ?? 0, icon: CheckCircle2, tone: 'bg-emerald-100 text-emerald-700' },
    { label: 'Đang chờ', value: parent.taskHistory.summary?.pending ?? 0, icon: Clock3, tone: 'bg-sky-100 text-sky-700' },
    { label: 'Bỏ qua', value: parent.taskHistory.summary?.skipped ?? 0, icon: SkipForward, tone: 'bg-amber-100 text-amber-800' },
])

function daysAgo(amount) {
    const date = new Date()
    date.setDate(date.getDate() - amount)
    return date
}

function dateKey(date) {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
}

function formatDate(value) {
    if (!value) return '—'

    const [year, month, day] = String(value).slice(0, 10).split('-').map(Number)
    if (!year || !month || !day) return value

    return new Intl.DateTimeFormat('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(new Date(year, month - 1, day))
}

function formatDateTime(value) {
    if (!value) return '—'

    return new Intl.DateTimeFormat('vi-VN', {
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(new Date(value))
}

function statusLabel(status) {
    return {
        completed: 'Hoàn thành',
        pending: 'Đang chờ',
        skipped: 'Bỏ qua',
    }[status] || status
}

function statusClass(status) {
    return {
        completed: 'bg-emerald-50 text-emerald-700 ring-emerald-100',
        pending: 'bg-sky-50 text-sky-700 ring-sky-100',
        skipped: 'bg-amber-50 text-amber-800 ring-amber-100',
    }[status] || 'bg-slate-100 text-slate-600 ring-slate-200'
}

async function loadHistory() {
    await parent.loadTaskHistory({
        ...filters,
        user_id: filters.user_id || '',
        status: filters.status || '',
    })
}

onMounted(async () => {
    await Promise.all([parent.loadChildren(), loadHistory()])
})
</script>

<template>
    <ParentLayout>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="admin-section-title">Theo dõi</p>
                <h1 class="mt-1 text-3xl font-bold">Lịch sử việc làm</h1>
                <p class="mt-2 max-w-2xl text-sm font-semibold text-slate-500">Xem lại các nhiệm vụ đã giao, hoàn thành, đang chờ hoặc đã bỏ qua của từng bé.</p>
            </div>
        </div>

        <div class="mt-6 grid gap-3 md:grid-cols-4">
            <article v-for="card in summaryCards" :key="card.label" class="admin-card p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ card.label }}</p>
                        <p class="mt-1 text-2xl font-bold text-slate-950">{{ card.value }}</p>
                    </div>
                    <span class="grid h-11 w-11 place-items-center rounded-xl" :class="card.tone">
                        <component :is="card.icon" class="h-5 w-5" />
                    </span>
                </div>
            </article>
        </div>

        <section class="admin-card mt-5 p-4">
            <div class="grid gap-3 lg:grid-cols-[1fr_1fr_1fr_160px_auto] lg:items-end">
                <label class="block">
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Từ ngày</span>
                    <input v-model="filters.start_date" type="date" class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-900" />
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Đến ngày</span>
                    <input v-model="filters.end_date" type="date" class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-900" />
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Bé</span>
                    <select v-model="filters.user_id" class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-900">
                        <option value="">Tất cả</option>
                        <option v-for="child in parent.children" :key="child.id" :value="child.id">{{ child.name }}</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Trạng thái</span>
                    <select v-model="filters.status" class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-900">
                        <option value="">Tất cả</option>
                        <option value="completed">Hoàn thành</option>
                        <option value="pending">Đang chờ</option>
                        <option value="skipped">Bỏ qua</option>
                    </select>
                </label>
                <BaseButton class="w-full" @click="loadHistory">
                    <Search class="h-4 w-4" />
                    Lọc
                </BaseButton>
            </div>
        </section>

        <LoadingState v-if="parent.loadingStates.taskHistory && !parent.taskHistory.items.length" class="mt-5" title="Đang tải lịch sử" message="KidTask đang lấy lịch sử việc làm của các bé." :rows="6" />

        <section v-else class="admin-card mt-5 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Ngày</th>
                            <th>Bé</th>
                            <th>Nhiệm vụ</th>
                            <th>Điểm</th>
                            <th>Trạng thái</th>
                            <th>Hoàn thành lúc</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in parent.taskHistory.items" :key="item.id">
                            <td class="font-bold text-slate-700">{{ formatDate(item.date) }}</td>
                            <td class="font-bold text-slate-900">{{ item.user?.name || '—' }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-slate-100 text-lg">{{ item.task?.icon || item.task?.category?.icon || '⭐' }}</span>
                                    <div>
                                        <p class="font-bold text-slate-950">{{ item.task?.title }}</p>
                                        <p class="text-xs font-semibold text-slate-500">{{ item.task?.category?.name || 'Không danh mục' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="font-bold text-amber-700">+{{ item.task?.points ?? 0 }}</td>
                            <td>
                                <span class="rounded-full px-3 py-1 text-xs font-bold ring-1" :class="statusClass(item.status)">
                                    {{ statusLabel(item.status) }}
                                </span>
                            </td>
                            <td class="text-sm font-semibold text-slate-500">{{ formatDateTime(item.completed_at) }}</td>
                        </tr>
                        <tr v-if="!parent.taskHistory.items.length">
                            <td colspan="6" class="py-8 text-center text-sm font-semibold text-slate-500">Chưa có lịch sử việc làm trong khoảng đã chọn.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </ParentLayout>
</template>
