<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { CheckCircle2, CalendarDays, CircleDashed, Clock3, RotateCcw, SkipForward } from 'lucide-vue-next'
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
    status: '',
    per_page: 25,
    page: 1,
})
const startDateInput = ref(formatDateInput(filters.start_date))
const endDateInput = ref(formatDateInput(filters.end_date))
const startDatePicker = ref(null)
const endDatePicker = ref(null)
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

    await parent.loadTaskHistory({
        ...filters,
        user_id: filters.user_id || '',
        status: filters.status || '',
    })
}

function resetFilters() {
    Object.assign(filters, {
        start_date: dateKey(daysAgo(30)),
        end_date: dateKey(new Date()),
        user_id: '',
        status: '',
        per_page: 25,
        page: 1,
    })
    startDateInput.value = formatDateInput(filters.start_date)
    endDateInput.value = formatDateInput(filters.end_date)
}

watch(
    () => [filters.start_date, filters.end_date, filters.user_id, filters.status, filters.per_page],
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
                <label class="block">
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Trạng thái</span>
                    <select v-model="filters.status" class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-900">
                        <option value="">Tất cả</option>
                        <option value="completed">Hoàn thành</option>
                        <option value="pending">Đang chờ</option>
                        <option value="skipped">Bỏ qua</option>
                    </select>
                </label>
                <BaseButton variant="secondary" class="w-full" @click="resetFilters">
                    <RotateCcw class="h-4 w-4" />
                    Làm mới lọc
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
            <PaginationControls
                :meta="parent.taskHistory.meta"
                :loading="parent.loadingStates.taskHistory"
                show-per-page
                :per-page="filters.per_page"
                @change="loadHistory"
                @update:per-page="(value) => { filters.per_page = value }"
            />
        </section>
    </ParentLayout>
</template>
