<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import ChildLayout from "../../layouts/ChildLayout.vue";
import ChildHeader from "../../components/child/ChildHeader.vue";
import RewardsSheet from "../../components/child/RewardsSheet.vue";
import TaskCard from "../../components/child/TaskCard.vue";
import ProgressBar from "../../components/common/ProgressBar.vue";
import EmptyState from "../../components/common/EmptyState.vue";
import AvatarPhoto from "../../components/common/AvatarPhoto.vue";
import LoadingState from "../../components/common/LoadingState.vue";
import { useChildStore } from "../../stores/child.store";
import { useToastStore } from "../../stores/toast.store";

const route = useRoute();
const router = useRouter();
const childStore = useChildStore();
const toast = useToastStore();
const busyId = ref(null);
const showRewards = ref(false);

const today = new Intl.DateTimeFormat("vi-VN", {
    weekday: "long",
    day: "numeric",
    month: "long",
}).format(new Date());
const dashboard = computed(() => childStore.dashboard);

async function load() {
    childStore.selectChild(route.params.id);
    await Promise.all([
        childStore.loadToday(route.params.id),
        childStore.loadRewards(route.params.id),
    ]);
}

async function toggleTask(task) {
    busyId.value = task.id;
    try {
        const result = await childStore.toggleTask(task);
        const amount = result.points_awarded || 0;
        toast.show(
            amount > 0 ? `Tuyệt vời! +${amount} ⭐` : "Đã cập nhật nhiệm vụ",
        );
    } catch (error) {
        toast.show(error.message, "error");
    } finally {
        busyId.value = null;
    }
}

async function redeem(reward) {
    if (dashboard.value && dashboard.value.points < reward.required_points) {
        return;
    }
    try {
        await childStore.redeem(reward.id);
        toast.show(`Đã đổi: ${reward.title}`);
    } catch (error) {
        toast.show(error.message, "error");
    }
}

function toggleRewards() {
    showRewards.value = !showRewards.value;
}

function goToHistory() {
    router.push(`/child/${route.params.id}/history`);
}

onMounted(load);
</script>

<template>
    <ChildLayout>
        <ChildHeader
            :points="dashboard?.points ?? 0"
            :rewards-open="showRewards"
            @change-child="router.push('/')"
            @show-history="goToHistory"
            @toggle-rewards="toggleRewards"
        />

        <section
            class="mx-auto max-w-5xl px-4 pb-6 pt-16 sm:px-6 sm:pt-20 overflow-hidden"
        >
            <LoadingState
                v-if="childStore.loadingStates.dashboard && !dashboard"
                class="mt-6"
                title="Đang tải nhiệm vụ hôm nay"
                message="KidTask đang lấy tiến độ, điểm và danh sách nhiệm vụ."
                variant="child"
                :rows="5"
            />

            <div v-else-if="dashboard" class="mt-6">
                <section class="min-w-0">
                    <div
                        class="premium-panel overflow-hidden rounded-[2rem] p-6 sm:p-7"
                    >
                        <div
                            class="grid gap-6 lg:grid-cols-[1fr_auto] lg:items-center"
                        >
                            <div>
                                <p
                                    class="inline-flex rounded-full bg-sky-100 px-3 py-1 text-xs font-extrabold uppercase text-sky-700"
                                >
                                    Hôm nay · {{ today }}
                                </p>
                                <h1
                                    class="mt-4 text-4xl font-extrabold leading-tight text-slate-900 sm:text-6xl"
                                >
                                    {{ dashboard.child.name }}
                                </h1>
                                <p
                                    class="mt-3 max-w-xl text-sm font-medium leading-6 text-slate-600"
                                >
                                    Hoàn thành từng nhiệm vụ nhỏ để gom sao và
                                    giữ chuỗi ngày tốt.
                                </p>
                            </div>
                            <AvatarPhoto
                                :src="dashboard.child.avatar"
                                :name="dashboard.child.name"
                                size="xl"
                            />
                        </div>

                        <div class="mt-6 grid gap-3 sm:grid-cols-3">
                            <div
                                class="rounded-2xl bg-sky-50 p-4 ring-1 ring-sky-100"
                            >
                                <p
                                    class="text-xs font-bold uppercase text-slate-600"
                                >
                                    Tiến độ
                                </p>
                                <p class="mt-2 text-2xl font-extrabold text-slate-900">
                                    {{ dashboard.progress.completed }}/{{
                                        dashboard.progress.total
                                    }}
                                </p>
                            </div>
                            <div
                                class="rounded-2xl bg-amber-50 p-4 ring-1 ring-amber-100"
                            >
                                <p
                                    class="text-xs font-bold uppercase text-slate-600"
                                >
                                    Điểm sao
                                </p>
                                <p class="mt-2 text-2xl font-extrabold text-slate-900">
                                    {{ dashboard.points }} ⭐
                                </p>
                            </div>
                            <div
                                class="rounded-2xl bg-emerald-50 p-4 ring-1 ring-emerald-100"
                            >
                                <p
                                    class="text-xs font-bold uppercase text-slate-600"
                                >
                                    Streak
                                </p>
                                <p class="mt-2 text-2xl font-extrabold text-slate-900">
                                    🔥 {{ dashboard.streak }}
                                </p>
                            </div>
                        </div>
                        <div class="mt-6">
                            <ProgressBar :value="dashboard.progress.percent" />
                            <p
                                class="mt-3 text-sm font-semibold text-slate-600"
                            >
                                {{ dashboard.progress.percent }}% hoàn thành hôm
                                nay
                            </p>
                        </div>
                    </div>

                    <div class="mt-7 flex items-end justify-between gap-4">
                        <div>
                            <p
                                class="text-xs font-bold uppercase text-sky-700"
                            >
                                Danh sách nhiệm vụ
                            </p>
                            <h2 class="mt-1 text-2xl font-extrabold text-slate-900">
                                Việc cần làm hôm nay
                            </h2>
                        </div>
                        <p
                            class="rounded-full bg-white/80 px-3 py-2 text-xs font-bold text-slate-600 shadow-sm"
                        >
                            {{ dashboard.progress.completed }} đã xong
                        </p>
                    </div>

                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <TaskCard
                            v-for="dailyTask in dashboard.tasks"
                            :key="dailyTask.id"
                            :daily-task="dailyTask"
                            :busy="busyId === dailyTask.id"
                            @toggle="toggleTask"
                        />
                    </div>
                    <EmptyState
                        v-if="!dashboard.tasks.length"
                        class="mt-5"
                        title="Chưa có nhiệm vụ hôm nay"
                        message="Phụ huynh có thể thêm nhiệm vụ trong Parent Mode."
                    />
                </section>

                <section class="mt-6 space-y-5">
                    <div class="soft-panel rounded-[1.75rem] p-5">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-lg font-extrabold text-slate-900">
                                Thành tựu
                            </h2>
                            <span
                                class="rounded-full bg-sky-100 px-3 py-1 text-xs font-extrabold text-sky-700"
                                >{{ dashboard.achievements.length }}</span
                            >
                        </div>
                        <div class="mt-4 grid gap-3">
                            <div
                                v-for="achievement in dashboard.achievements"
                                :key="achievement.id"
                                class="rounded-2xl bg-white/80 p-4 ring-1 ring-slate-100"
                            >
                                <p class="font-bold text-slate-900">
                                    {{ achievement.icon }}
                                    {{ achievement.title }}
                                </p>
                                <p
                                    class="mt-1 text-sm font-medium leading-6 text-slate-600"
                                >
                                    {{ achievement.description }}
                                </p>
                            </div>
                            <p
                                v-if="!dashboard.achievements.length"
                                class="text-sm font-medium text-slate-600"
                            >
                                Hoàn thành nhiệm vụ đầu tiên để mở khóa.
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </section>

        <RewardsSheet
            :open="showRewards"
            :points="dashboard?.points ?? 0"
            :rewards="childStore.rewards?.rewards ?? []"
            :loading="childStore.loadingStates.rewards && !childStore.rewards"
            @close="showRewards = false"
            @redeem="redeem"
        />
    </ChildLayout>
</template>
