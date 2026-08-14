<script setup>
import { computed, onMounted } from 'vue'
import { Flame, ListChecks, Star, Trophy, Users } from 'lucide-vue-next'
import ParentLayout from '../../layouts/ParentLayout.vue'
import ProgressBar from '../../components/common/ProgressBar.vue'
import AvatarPhoto from '../../components/common/AvatarPhoto.vue'
import LoadingState from '../../components/common/LoadingState.vue'
import { useParentStore } from '../../stores/parent.store'

const parent = useParentStore()
const children = computed(() => parent.dashboard?.children ?? [])
const rankedChildren = computed(() =>
    [...children.value].sort((a, b) =>
        b.completion_percent - a.completion_percent ||
        b.today_completed - a.today_completed ||
        b.points - a.points,
    ),
)
const topChild = computed(() => rankedChildren.value[0] ?? null)
const totalCompleted = computed(() => children.value.reduce((sum, child) => sum + Number(child.today_completed || 0), 0))
const totalTasks = computed(() => children.value.reduce((sum, child) => sum + Number(child.today_total || 0), 0))
const averageCompletion = computed(() => totalTasks.value ? Math.round((totalCompleted.value / totalTasks.value) * 100) : 0)
const maxPoints = computed(() => Math.max(1, ...children.value.map((child) => Number(child.points || 0))))
const maxStreak = computed(() => Math.max(1, ...children.value.map((child) => Number(child.streak || 0))))

function barWidth(value, max = 100) {
    return `${Math.max(Number(value || 0) ? 6 : 0, Math.round((Number(value || 0) / max) * 100))}%`
}

onMounted(() => parent.loadDashboard())
</script>

<template>
    <ParentLayout>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="admin-section-title">Dashboard</p>
                <h1 class="mt-1 text-3xl font-bold">Tổng quan hôm nay</h1>
            </div>
        </div>

        <LoadingState v-if="parent.loadingStates.dashboard && !parent.dashboard" class="mt-6" title="Đang tải dashboard" message="KidTask đang tổng hợp tiến độ hôm nay." :rows="4" />

        <div v-else class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article v-for="child in children" :key="child.id" class="admin-card p-5">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <AvatarPhoto :src="child.avatar" :name="child.name" size="sm" />
                        <h2 class="text-xl font-bold">{{ child.name }}</h2>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-bold" :class="child.is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100' : 'bg-slate-100 text-slate-500'">
                        {{ child.is_active ? 'Hoạt động' : 'Không hoạt động' }}
                    </span>
                </div>
                <p class="mt-5 text-sm font-bold text-slate-600">{{ child.today_completed }} / {{ child.today_total }} nhiệm vụ hoàn thành</p>
                <ProgressBar class="mt-2" :value="child.completion_percent" />
                <div class="mt-4 flex justify-between text-sm font-bold text-slate-600">
                    <span>{{ child.streak }} ngày streak</span>
                    <span>⭐ {{ child.points }}</span>
                </div>
            </article>
        </div>

        <section v-if="children.length" class="admin-card mt-6 overflow-hidden">
            <div class="border-b border-slate-100 p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="admin-section-title">So sánh</p>
                        <h2 class="mt-1 text-2xl font-bold text-slate-950">So sánh giữa các bé</h2>
                        <p class="mt-2 text-sm font-semibold text-slate-500">
                            Xếp hạng theo tiến độ hôm nay, điểm sao và chuỗi ngày duy trì.
                        </p>
                    </div>
                    <div v-if="topChild" class="rounded-xl bg-amber-50 px-4 py-3 ring-1 ring-amber-100">
                        <p class="text-xs font-bold uppercase text-amber-700">Dẫn đầu hôm nay</p>
                        <p class="mt-1 text-sm font-bold text-slate-950">{{ topChild.name }} · {{ topChild.completion_percent }}%</p>
                    </div>
                </div>

                <div class="mt-5 grid gap-3 md:grid-cols-4">
                    <article class="rounded-xl bg-sky-50 p-4 ring-1 ring-sky-100">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold uppercase text-slate-500">Tiến độ chung</p>
                                <p class="mt-1 text-2xl font-bold text-slate-950">{{ averageCompletion }}%</p>
                            </div>
                            <Trophy class="h-5 w-5 text-sky-700" />
                        </div>
                    </article>
                    <article class="rounded-xl bg-emerald-50 p-4 ring-1 ring-emerald-100">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold uppercase text-slate-500">Đã hoàn thành</p>
                                <p class="mt-1 text-2xl font-bold text-slate-950">{{ totalCompleted }}/{{ totalTasks }}</p>
                            </div>
                            <ListChecks class="h-5 w-5 text-emerald-700" />
                        </div>
                    </article>
                    <article class="rounded-xl bg-rose-50 p-4 ring-1 ring-rose-100">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold uppercase text-slate-500">Tổng sao</p>
                                <p class="mt-1 text-2xl font-bold text-slate-950">{{ children.reduce((sum, child) => sum + Number(child.points || 0), 0) }}</p>
                            </div>
                            <Star class="h-5 w-5 text-rose-700" />
                        </div>
                    </article>
                    <article class="rounded-xl bg-violet-50 p-4 ring-1 ring-violet-100">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold uppercase text-slate-500">Số bé</p>
                                <p class="mt-1 text-2xl font-bold text-slate-950">{{ children.length }}</p>
                            </div>
                            <Users class="h-5 w-5 text-violet-700" />
                        </div>
                    </article>
                </div>
            </div>

            <div class="grid gap-5 p-5 xl:grid-cols-[1.4fr_1fr]">
                <div class="space-y-4">
                    <article
                        v-for="(child, index) in rankedChildren"
                        :key="child.id"
                        class="rounded-xl border border-slate-100 p-4"
                    >
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-slate-100 text-sm font-bold text-slate-700">
                                    #{{ index + 1 }}
                                </span>
                                <AvatarPhoto :src="child.avatar" :name="child.name" size="sm" />
                                <div class="min-w-0">
                                    <p class="truncate font-bold text-slate-950">{{ child.name }}</p>
                                    <p class="text-xs font-semibold text-slate-500">
                                        {{ child.today_completed }}/{{ child.today_total }} nhiệm vụ hôm nay
                                    </p>
                                </div>
                            </div>
                            <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 ring-1 ring-sky-100">
                                {{ child.completion_percent }}%
                            </span>
                        </div>
                        <div class="mt-4 h-3 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-sky-600" :style="{ width: barWidth(child.completion_percent) }"></div>
                        </div>
                    </article>
                </div>

                <div class="grid gap-4 content-start">
                    <article class="rounded-xl bg-slate-50 p-4">
                        <div class="flex items-center gap-2">
                            <Star class="h-4 w-4 text-amber-600" />
                            <h3 class="font-bold text-slate-950">Điểm sao</h3>
                        </div>
                        <div class="mt-4 grid gap-3">
                            <div v-for="child in rankedChildren" :key="`points-${child.id}`">
                                <div class="flex justify-between text-xs font-bold text-slate-600">
                                    <span>{{ child.name }}</span>
                                    <span>{{ child.points }} ⭐</span>
                                </div>
                                <div class="mt-1 h-2 overflow-hidden rounded-full bg-white ring-1 ring-slate-100">
                                    <div class="h-full rounded-full bg-amber-400" :style="{ width: barWidth(child.points, maxPoints) }"></div>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="rounded-xl bg-slate-50 p-4">
                        <div class="flex items-center gap-2">
                            <Flame class="h-4 w-4 text-orange-600" />
                            <h3 class="font-bold text-slate-950">Streak</h3>
                        </div>
                        <div class="mt-4 grid gap-3">
                            <div v-for="child in rankedChildren" :key="`streak-${child.id}`">
                                <div class="flex justify-between text-xs font-bold text-slate-600">
                                    <span>{{ child.name }}</span>
                                    <span>{{ child.streak }} ngày</span>
                                </div>
                                <div class="mt-1 h-2 overflow-hidden rounded-full bg-white ring-1 ring-slate-100">
                                    <div class="h-full rounded-full bg-orange-500" :style="{ width: barWidth(child.streak, maxStreak) }"></div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>
    </ParentLayout>
</template>
