<script setup>
import { computed, onMounted, reactive } from 'vue'
import { Gift, Search, Sparkles, Star, Users } from 'lucide-vue-next'
import ParentLayout from '../../layouts/ParentLayout.vue'
import BaseButton from '../../components/common/BaseButton.vue'
import LoadingState from '../../components/common/LoadingState.vue'
import PaginationControls from '../../components/common/PaginationControls.vue'
import { useParentStore } from '../../stores/parent.store'

const parent = useParentStore()
const filters = reactive({
    start_date: dateKey(daysAgo(30)),
    end_date: dateKey(new Date()),
    user_id: '',
    per_page: 25,
    page: 1,
})
const summaryCards = computed(() => [
    { label: 'Lượt đổi', value: parent.rewardHistory.summary?.total ?? 0, icon: Gift, tone: 'bg-sky-100 text-sky-700' },
    { label: 'Sao đã dùng', value: parent.rewardHistory.summary?.points_spent ?? 0, icon: Star, tone: 'bg-amber-100 text-amber-800' },
    { label: 'Bé đã đổi', value: parent.rewardHistory.summary?.children ?? 0, icon: Users, tone: 'bg-emerald-100 text-emerald-700' },
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

async function loadHistory(page = filters.page) {
    filters.page = page

    await parent.loadRewardHistory({
        ...filters,
        user_id: filters.user_id || '',
    })
}

function applyFilters() {
    return loadHistory(1)
}

onMounted(async () => {
    await Promise.all([parent.loadChildren(), loadHistory()])
})
</script>

<template>
    <ParentLayout>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="admin-section-title">Đổi thưởng</p>
                <h1 class="mt-1 text-3xl font-bold">Lịch sử đổi thưởng</h1>
                <p class="mt-2 max-w-2xl text-sm font-semibold text-slate-500">
                    Theo dõi các phần thưởng mà các bé đã đổi, thời gian đổi và số sao đã sử dụng.
                </p>
            </div>
        </div>

        <div class="mt-6 grid gap-3 md:grid-cols-3">
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
            <div class="grid gap-3 lg:grid-cols-[1fr_1fr_1fr_140px_auto] lg:items-end">
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
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Mỗi trang</span>
                    <select v-model="filters.per_page" class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-900">
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                </label>
                <BaseButton class="w-full" @click="applyFilters">
                    <Search class="h-4 w-4" />
                    Lọc
                </BaseButton>
            </div>
        </section>

        <LoadingState
            v-if="parent.loadingStates.rewardHistory && !parent.rewardHistory.items.length"
            class="mt-5"
            title="Đang tải lịch sử đổi thưởng"
            message="KidTask đang lấy các lượt đổi thưởng của các bé."
            :rows="6"
        />

        <section v-else class="admin-card mt-5 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Bé</th>
                            <th>Phần thưởng</th>
                            <th>Điểm đã dùng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in parent.rewardHistory.items" :key="item.id">
                            <td class="whitespace-nowrap text-sm font-semibold text-slate-500">
                                {{ formatDateTime(item.redeemed_at) }}
                            </td>
                            <td class="font-bold text-slate-900">{{ item.user?.name || '—' }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-amber-50 text-lg ring-1 ring-amber-100">
                                        {{ item.reward?.icon || '🎁' }}
                                    </span>
                                    <div>
                                        <p class="font-bold text-slate-950">{{ item.reward?.title || 'Phần thưởng đã xóa' }}</p>
                                        <p class="text-xs font-semibold text-slate-500">{{ item.reward?.description || 'Không có mô tả' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="font-bold text-amber-700">-{{ item.points_spent }} ⭐</td>
                        </tr>
                        <tr v-if="!parent.rewardHistory.items.length">
                            <td colspan="4" class="py-8 text-center">
                                <Sparkles class="mx-auto h-8 w-8 text-sky-500" />
                                <p class="mt-2 text-sm font-semibold text-slate-500">
                                    Chưa có lịch sử đổi thưởng trong khoảng đã chọn.
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <PaginationControls
                :meta="parent.rewardHistory.meta"
                :loading="parent.loadingStates.rewardHistory"
                @change="loadHistory"
            />
        </section>
    </ParentLayout>
</template>
