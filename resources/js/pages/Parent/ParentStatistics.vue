<script setup>
import { computed, onMounted } from 'vue'
import { Award, CheckCircle2, Gift, ListChecks, Star, Target, TrendingUp, Users } from 'lucide-vue-next'
import ParentLayout from '../../layouts/ParentLayout.vue'
import LoadingState from '../../components/common/LoadingState.vue'
import { useParentStore } from '../../stores/parent.store'

const parent = useParentStore()
const stats = computed(() => parent.statistics ?? {})
const dailyTrend = computed(() => stats.value.daily_trend ?? [])
const childPerformance = computed(() => stats.value.child_performance ?? [])
const categoryStats = computed(() => stats.value.category_stats ?? [])
const topRewards = computed(() => stats.value.top_rewards ?? [])
const todayStatus = computed(() => stats.value.today_status ?? { completed: 0, pending: 0, skipped: 0 })
const todayTotal = computed(() => todayStatus.value.completed + todayStatus.value.pending + todayStatus.value.skipped)
const todayCompletion = computed(() => todayTotal.value ? Math.round((todayStatus.value.completed / todayTotal.value) * 100) : 0)
const maxDailyTotal = computed(() => Math.max(1, ...dailyTrend.value.map((item) => item.total || 0)))
const maxChildCompleted = computed(() => Math.max(1, ...childPerformance.value.map((child) => child.completed_tasks || 0)))
const maxCategoryCompleted = computed(() => Math.max(1, ...categoryStats.value.map((category) => category.completed || 0)))
const maxRewardTotal = computed(() => Math.max(1, ...topRewards.value.map((reward) => reward.total || 0)))
const donutStyle = computed(() => {
    const completed = todayTotal.value ? (todayStatus.value.completed / todayTotal.value) * 100 : 0
    const pending = todayTotal.value ? (todayStatus.value.pending / todayTotal.value) * 100 : 0

    return {
        background: `conic-gradient(#10b981 0 ${completed}%, #38bdf8 ${completed}% ${completed + pending}%, #f59e0b ${completed + pending}% 100%)`,
    }
})
const kpis = computed(() => [
    { label: 'Hồ sơ bé', value: stats.value.children_count ?? 0, hint: 'Tổng tài khoản trẻ em', icon: Users, tone: 'bg-sky-100 text-sky-700' },
    { label: 'Nhiệm vụ đang bật', value: stats.value.active_tasks ?? 0, hint: 'Template có thể giao', icon: ListChecks, tone: 'bg-violet-100 text-violet-700' },
    { label: 'Hoàn thành hôm nay', value: stats.value.completed_today ?? 0, hint: `${todayCompletion.value}% tiến độ trong ngày`, icon: CheckCircle2, tone: 'bg-emerald-100 text-emerald-700' },
    { label: 'Còn chờ hôm nay', value: stats.value.missed_today ?? 0, hint: 'Nhiệm vụ chưa hoàn tất', icon: Target, tone: 'bg-amber-100 text-amber-800' },
    { label: 'Tổng sao hiện có', value: stats.value.points_total ?? 0, hint: 'Số dư sao của các bé', icon: Star, tone: 'bg-rose-100 text-rose-700' },
])

function barHeight(value) {
    return `${Math.max(8, Math.round((Number(value || 0) / maxDailyTotal.value) * 100))}%`
}

function barWidth(value, max) {
    return `${Math.max(4, Math.round((Number(value || 0) / max) * 100))}%`
}

onMounted(() => parent.loadStatistics())
</script>

<template>
    <ParentLayout>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="admin-section-title">Báo cáo nhanh</p>
                <h1 class="mt-1 text-3xl font-bold">Thống kê</h1>
                <p class="mt-2 max-w-2xl text-sm font-semibold text-slate-500">
                    Theo dõi nhịp hoàn thành nhiệm vụ, điểm sao, đổi thưởng và hiệu suất của từng bé.
                </p>
            </div>
            <div class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-bold text-slate-600 ring-1 ring-slate-200">
                <TrendingUp class="h-4 w-4 text-sky-600" />
                14 ngày gần nhất
            </div>
        </div>

        <LoadingState
            v-if="parent.loadingStates.statistics && !parent.statistics"
            class="mt-6"
            title="Đang tải thống kê"
            message="KidTask đang tính toán các chỉ số tổng quan."
            :rows="6"
        />

        <template v-else>
            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <article v-for="kpi in kpis" :key="kpi.label" class="admin-card p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ kpi.label }}</p>
                            <p class="mt-3 text-3xl font-bold text-slate-950">{{ kpi.value }}</p>
                            <p class="mt-2 text-sm font-semibold text-slate-500">{{ kpi.hint }}</p>
                        </div>
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl" :class="kpi.tone">
                            <component :is="kpi.icon" class="h-5 w-5" />
                        </span>
                    </div>
                </article>
            </div>

            <div class="mt-5 grid gap-5 xl:grid-cols-[1.7fr_1fr]">
                <section class="admin-card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="admin-section-title">Tiến độ</p>
                            <h2 class="mt-1 text-xl font-bold text-slate-950">Nhiệm vụ theo ngày</h2>
                        </div>
                        <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 ring-1 ring-sky-100">
                            Hoàn thành / Tổng
                        </span>
                    </div>

                    <div class="mt-6 flex h-72 items-end gap-2 overflow-x-auto pb-2">
                        <div
                            v-for="day in dailyTrend"
                            :key="day.date"
                            class="flex h-full min-w-12 flex-1 flex-col items-center justify-end gap-2"
                        >
                            <div class="flex h-full w-full items-end justify-center rounded-xl bg-slate-50 px-2">
                                <div class="flex w-full max-w-8 flex-col justify-end overflow-hidden rounded-t-xl bg-slate-200" :style="{ height: barHeight(day.total) }">
                                    <div class="bg-emerald-500" :style="{ height: `${day.completion_percent}%` }"></div>
                                </div>
                            </div>
                            <div class="text-center">
                                <p class="text-xs font-bold text-slate-900">{{ day.completed }}/{{ day.total }}</p>
                                <p class="text-[11px] font-semibold text-slate-500">{{ day.label }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="admin-card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="admin-section-title">Hôm nay</p>
                            <h2 class="mt-1 text-xl font-bold text-slate-950">Trạng thái nhiệm vụ</h2>
                        </div>
                        <CheckCircle2 class="h-5 w-5 text-emerald-600" />
                    </div>

                    <div class="mt-7 flex items-center justify-center">
                        <div class="relative h-44 w-44">
                            <svg
                                class="h-full w-full -rotate-90"
                                viewBox="0 0 120 120"
                            >
                                <circle
                                    cx="60"
                                    cy="60"
                                    r="50"
                                    fill="none"
                                    stroke="#e2e8f0"
                                    stroke-width="10"
                                />

                                <circle
                                    cx="60"
                                    cy="60"
                                    r="50"
                                    fill="none"
                                    stroke="#22c55e"
                                    stroke-width="10"
                                    stroke-linecap="round"
                                    :stroke-dasharray="2 * Math.PI * 50"
                                    :stroke-dashoffset="
                                        2 * Math.PI * 50 * (1 - todayCompletion / 100)
                                    "
                                />
                            </svg>

                            <div
                                class="absolute inset-0 flex flex-col items-center justify-center"
                            >
                                <p class="text-4xl font-extrabold leading-none text-slate-950">
                                    {{ todayCompletion }}%
                                </p>

                                <p
                                    class="mt-2 text-xs font-bold uppercase tracking-wide text-slate-500"
                                >
                                    Hoàn thành
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-2">
                        <div class="flex items-center justify-between rounded-xl bg-emerald-50 px-3 py-2 text-sm font-bold text-emerald-700">
                            <span>Hoàn thành</span>
                            <span>{{ todayStatus.completed }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-xl bg-sky-50 px-3 py-2 text-sm font-bold text-sky-700">
                            <span>Đang chờ</span>
                            <span>{{ todayStatus.pending }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-xl bg-amber-50 px-3 py-2 text-sm font-bold text-amber-800">
                            <span>Bỏ qua</span>
                            <span>{{ todayStatus.skipped }}</span>
                        </div>
                    </div>
                </section>
            </div>

            <div class="mt-5 grid gap-5 xl:grid-cols-2">
                <section class="admin-card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="admin-section-title">Bé</p>
                            <h2 class="mt-1 text-xl font-bold text-slate-950">Bảng hiệu suất</h2>
                        </div>
                        <Award class="h-5 w-5 text-amber-600" />
                    </div>

                    <div class="mt-5 grid gap-4">
                        <article v-for="child in childPerformance" :key="child.id" class="rounded-xl border border-slate-100 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate font-bold text-slate-950">{{ child.name }}</p>
                                    <p class="mt-1 text-xs font-semibold text-slate-500">
                                        {{ child.points }} sao · {{ child.reward_redemptions }} lượt đổi
                                    </p>
                                </div>
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
                                    {{ child.completed_tasks }} việc
                                </span>
                            </div>
                            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-emerald-500" :style="{ width: barWidth(child.completed_tasks, maxChildCompleted) }"></div>
                            </div>
                        </article>
                        <p v-if="!childPerformance.length" class="py-6 text-center text-sm font-semibold text-slate-500">
                            Chưa có dữ liệu của bé.
                        </p>
                    </div>
                </section>

                <section class="admin-card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="admin-section-title">Danh mục</p>
                            <h2 class="mt-1 text-xl font-bold text-slate-950">Hoàn thành theo nhóm việc</h2>
                        </div>
                        <ListChecks class="h-5 w-5 text-violet-600" />
                    </div>

                    <div class="mt-5 grid gap-4">
                        <article v-for="category in categoryStats" :key="category.name" class="rounded-xl bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-bold text-slate-950">{{ category.icon }} {{ category.name }}</p>
                                <p class="text-sm font-bold text-slate-600">{{ category.completed }}/{{ category.total }}</p>
                            </div>
                            <div class="mt-3 h-3 overflow-hidden rounded-full bg-white ring-1 ring-slate-100">
                                <div class="h-full rounded-full bg-violet-500" :style="{ width: barWidth(category.completed, maxCategoryCompleted) }"></div>
                            </div>
                            <p class="mt-2 text-xs font-semibold text-slate-500">{{ category.completion_percent }}% hoàn thành</p>
                        </article>
                        <p v-if="!categoryStats.length" class="py-6 text-center text-sm font-semibold text-slate-500">
                            Chưa có dữ liệu danh mục.
                        </p>
                    </div>
                </section>
            </div>

            <section class="admin-card mt-5 p-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="admin-section-title">Đổi thưởng</p>
                        <h2 class="mt-1 text-xl font-bold text-slate-950">Phần thưởng được đổi nhiều nhất</h2>
                    </div>
                    <Gift class="h-5 w-5 text-rose-600" />
                </div>

                <div class="mt-5 grid gap-3 lg:grid-cols-5">
                    <article v-for="reward in topRewards" :key="reward.id" class="rounded-xl border border-slate-100 p-4">
                        <div class="flex items-center gap-3">
                            <span class="grid h-11 w-11 place-items-center rounded-xl bg-rose-50 text-xl ring-1 ring-rose-100">
                                {{ reward.icon || '🎁' }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate font-bold text-slate-950">{{ reward.title }}</p>
                                <p class="text-xs font-semibold text-slate-500">{{ reward.points_spent }} sao</p>
                            </div>
                        </div>
                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-rose-500" :style="{ width: barWidth(reward.total, maxRewardTotal) }"></div>
                        </div>
                        <p class="mt-2 text-sm font-bold text-rose-700">{{ reward.total }} lượt đổi</p>
                    </article>
                    <p v-if="!topRewards.length" class="py-6 text-center text-sm font-semibold text-slate-500 lg:col-span-5">
                        Chưa có phần thưởng nào được đổi.
                    </p>
                </div>
            </section>
        </template>
    </ParentLayout>
</template>
