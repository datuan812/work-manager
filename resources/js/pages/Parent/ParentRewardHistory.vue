<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { CalendarDays, Gift, RotateCcw, Sparkles, Star, Users } from 'lucide-vue-next'
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
const startDateInput = ref(formatDateInput(filters.start_date))
const endDateInput = ref(formatDateInput(filters.end_date))
const startDatePicker = ref(null)
const endDatePicker = ref(null)
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

function isValidDateParts(day, month, year) {
    const date = new Date(year, month - 1, day)
    return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day
}

function normalizeDate(value) {
    if (!value) return ''

    const date = String(value).trim()

    if (/^\d{4}-\d{2}-\d{2}/.test(date)) {
        return date.slice(0, 10)
    }

    const match = date.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/)

    if (match) {
        const [, day, month, year] = match
        if (!isValidDateParts(Number(day), Number(month), Number(year))) return ''

        return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`
    }

    return ''
}

function formatDateInput(value) {
    if (!value) return ''

    const [year, month, day] = String(value).slice(0, 10).split('-')
    if (!year || !month || !day) return ''

    return `${day.padStart(2, '0')}/${month.padStart(2, '0')}/${year}`
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

function openStartDatePicker() {
    if (typeof startDatePicker.value?.showPicker === 'function') {
        startDatePicker.value.showPicker()
        return
    }
    startDatePicker.value?.click()
}
function openEndDatePicker() {
    if (typeof endDatePicker.value?.showPicker === 'function') {
        endDatePicker.value.showPicker()
        return
    }
    endDatePicker.value?.click()
}
function pickStartDate(event) {
    filters.start_date = event.target.value
    startDateInput.value = formatDateInput(event.target.value)
}
function pickEndDate(event) {
    filters.end_date = event.target.value
    endDateInput.value = formatDateInput(event.target.value)
}
function syncStartDate() {
    const normalized = normalizeDate(startDateInput.value)
    if (!normalized) {
        startDateInput.value = formatDateInput(filters.start_date)
        return
    }

    filters.start_date = normalized
    startDateInput.value = formatDateInput(normalized)
}
function syncEndDate() {
    const normalized = normalizeDate(endDateInput.value)
    if (!normalized) {
        endDateInput.value = formatDateInput(filters.end_date)
        return
    }

    filters.end_date = normalized
    endDateInput.value = formatDateInput(normalized)
}

async function loadHistory(page = filters.page) {
    filters.page = page

    await parent.loadRewardHistory({
        ...filters,
        user_id: filters.user_id || '',
    })
}

function resetFilters() {
    Object.assign(filters, {
        start_date: dateKey(daysAgo(30)),
        end_date: dateKey(new Date()),
        user_id: '',
        per_page: 25,
        page: 1,
    })
    startDateInput.value = formatDateInput(filters.start_date)
    endDateInput.value = formatDateInput(filters.end_date)
}

watch(
    () => [filters.start_date, filters.end_date, filters.user_id, filters.per_page],
    () => loadHistory(1),
)

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
            <div class="grid gap-3 lg:grid-cols-[1fr_1fr_1fr_auto] lg:items-end">
                <label class="block">
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Từ ngày</span>
                    <div class="relative">
                        <input
                            v-model="startDateInput"
                            inputmode="numeric"
                            placeholder="dd/mm/yyyy"
                            class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 pr-12 text-sm font-semibold text-slate-900 outline-none transition focus:border-sky-500 focus:ring-3 focus:ring-sky-100"
                            @blur="syncStartDate"
                        />
                        <input ref="startDatePicker" :value="filters.start_date" type="date" class="pointer-events-none absolute inset-0 opacity-0" tabindex="-1" @input="pickStartDate" />
                        <button type="button" title="Chọn ngày" class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-sky-700" @click="openStartDatePicker">
                            <CalendarDays class="h-4 w-4" />
                        </button>
                    </div>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Đến ngày</span>
                    <div class="relative">
                        <input
                            v-model="endDateInput"
                            inputmode="numeric"
                            placeholder="dd/mm/yyyy"
                            class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 pr-12 text-sm font-semibold text-slate-900 outline-none transition focus:border-sky-500 focus:ring-3 focus:ring-sky-100"
                            @blur="syncEndDate"
                        />
                        <input ref="endDatePicker" :value="filters.end_date" type="date" class="pointer-events-none absolute inset-0 opacity-0" tabindex="-1" @input="pickEndDate" />
                        <button type="button" title="Chọn ngày" class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-sky-700" @click="openEndDatePicker">
                            <CalendarDays class="h-4 w-4" />
                        </button>
                    </div>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Bé</span>
                    <select v-model="filters.user_id" class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-900">
                        <option value="">Tất cả</option>
                        <option v-for="child in parent.children" :key="child.id" :value="child.id">{{ child.name }}</option>
                    </select>
                </label>
                <BaseButton variant="secondary" class="w-full" @click="resetFilters">
                    <RotateCcw class="h-4 w-4" />
                    Làm mới lọc
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
                show-per-page
                :per-page="filters.per_page"
                @change="loadHistory"
                @update:per-page="(value) => { filters.per_page = value }"
            />
        </section>
    </ParentLayout>
</template>
