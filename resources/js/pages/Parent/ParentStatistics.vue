<script setup>
import { onMounted } from 'vue'
import ParentLayout from '../../layouts/ParentLayout.vue'
import MetricCard from '../../components/parent/MetricCard.vue'
import LoadingState from '../../components/common/LoadingState.vue'
import { useParentStore } from '../../stores/parent.store'

const parent = useParentStore()
onMounted(() => parent.loadStatistics())
</script>

<template>
    <ParentLayout>
        <div>
            <p class="admin-section-title">Báo cáo nhanh</p>
            <h1 class="mt-1 text-3xl font-bold">Thống kê</h1>
        </div>

        <LoadingState v-if="parent.loadingStates.statistics && !parent.statistics" class="mt-6" title="Đang tải thống kê" message="KidTask đang tính toán các chỉ số tổng quan." :rows="3" />

        <div v-else class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <MetricCard label="Children" :value="parent.statistics?.children_count ?? 0" hint="Tổng hồ sơ bé" />
            <MetricCard label="Active tasks" :value="parent.statistics?.active_tasks ?? 0" hint="Nhiệm vụ đang bật" />
            <MetricCard label="Completed today" :value="parent.statistics?.completed_today ?? 0" hint="Đã hoàn thành hôm nay" />
            <MetricCard label="Pending today" :value="parent.statistics?.missed_today ?? 0" hint="Còn chờ xử lý" />
            <MetricCard label="Point balance" :value="parent.statistics?.points_total ?? 0" hint="Tổng điểm hiện có" />
        </div>
    </ParentLayout>
</template>
