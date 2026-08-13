<script setup>
import { onMounted } from 'vue'
import ParentLayout from '../../layouts/ParentLayout.vue'
import LoadingState from '../../components/common/LoadingState.vue'
import { useParentStore } from '../../stores/parent.store'

const parent = useParentStore()
onMounted(() => parent.loadAchievements())
</script>

<template>
    <ParentLayout>
        <div>
            <p class="admin-section-title">Milestones</p>
            <h1 class="mt-1 text-3xl font-bold">Thành tựu</h1>
        </div>

        <LoadingState v-if="parent.loadingStates.achievements && !parent.achievements.length" class="mt-6" title="Đang tải thành tựu" message="KidTask đang lấy danh sách milestone." :rows="4" />

        <div v-else class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article v-for="achievement in parent.achievements" :key="achievement.id" class="admin-card p-5">
                <div class="flex items-start justify-between gap-4">
                    <p class="grid h-12 w-12 place-items-center rounded-xl bg-sky-50 text-2xl">{{ achievement.icon }}</p>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ achievement.users_count }} unlocked</span>
                </div>
                <h2 class="mt-4 font-bold">{{ achievement.title }}</h2>
                <p class="mt-1 text-sm font-semibold leading-6 text-slate-500">{{ achievement.description }}</p>
            </article>
        </div>
    </ParentLayout>
</template>
