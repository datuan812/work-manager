<script setup>
import { onMounted } from 'vue'
import ParentLayout from '../../layouts/ParentLayout.vue'
import ProgressBar from '../../components/common/ProgressBar.vue'
import AvatarPhoto from '../../components/common/AvatarPhoto.vue'
import LoadingState from '../../components/common/LoadingState.vue'
import { useParentStore } from '../../stores/parent.store'

const parent = useParentStore()
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
            <article v-for="child in parent.dashboard?.children" :key="child.id" class="admin-card p-5">
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
    </ParentLayout>
</template>
