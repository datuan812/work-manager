<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import ChildLayout from "../../layouts/ChildLayout.vue";
import ChildHeader from "../../components/child/ChildHeader.vue";
import RewardHistoryModal from "../../components/child/RewardHistoryModal.vue";
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
const showRewardHistory = ref(false);
const selectedDay = ref("today");
const draftCompletedIds = ref(new Set());

const dayFormatter = new Intl.DateTimeFormat("vi-VN", {
    weekday: "long",
    day: "numeric",
    month: "long",
});
const dashboard = computed(() => childStore.dashboard);
const dateOptions = computed(() => {
    const today = new Date();

    return [
        {
            key: "yesterday",
            label: "Hôm qua",
            helper: "Có thể sửa nếu chưa chốt",
            date: addDays(today, -1),
        },
        {
            key: "today",
            label: "Hôm nay",
            helper: "Chọn xong rồi chốt",
            date: today,
        },
        {
            key: "tomorrow",
            label: "Ngày mai",
            helper: "Chỉ xem trước",
            date: addDays(today, 1),
        },
    ];
});
const selectedOption = computed(
    () =>
        dateOptions.value.find((option) => option.key === selectedDay.value) ??
        dateOptions.value[1],
);
const selectedDate = computed(() => formatDate(selectedOption.value.date));
const selectedDateLabel = computed(() =>
    dayFormatter.format(selectedOption.value.date),
);
const dashboardReady = computed(
    () => dashboard.value?.date === selectedDate.value,
);
const dayStatus = computed(
    () =>
        dashboard.value?.day_status ?? {
            key: selectedDay.value,
            is_submitted: false,
            can_edit: false,
            can_submit: false,
        },
);
const canEditTasks = computed(() => Boolean(dayStatus.value.can_edit));
const isReadonly = computed(() => !canEditTasks.value);
const displayedTasks = computed(() =>
    (dashboard.value?.tasks ?? []).map((task) => ({
        ...task,
        status: canEditTasks.value
            ? draftCompletedIds.value.has(task.id)
                ? "completed"
                : "pending"
            : task.status,
    })),
);
const displayedProgress = computed(() => {
    const total = displayedTasks.value.length;
    const completed = displayedTasks.value.filter(
        (task) => task.status === "completed",
    ).length;

    return {
        total,
        completed,
        percent: total ? Math.round((completed / total) * 100) : 0,
    };
});
const submitHint = computed(() => {
    if (selectedDay.value === "tomorrow") {
        return "Ngày mai chỉ xem trước, chưa thể tích hoặc chốt.";
    }

    if (dayStatus.value.is_submitted) {
        return "Ngày này đã chốt, bé chỉ có thể xem lại.";
    }

    return "Tích các việc đã làm, sau đó bấm chốt để lưu điểm sao.";
});

function addDays(date, amount) {
    const next = new Date(date);
    next.setDate(next.getDate() + amount);
    return next;
}

function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");

    return `${year}-${month}-${day}`;
}

function syncDraft() {
    if (canEditTasks.value && Array.isArray(dashboard.value?.draft_completed_task_ids)) {
        draftCompletedIds.value = new Set(dashboard.value.draft_completed_task_ids);
        return;
    }

    draftCompletedIds.value = new Set(
        (dashboard.value?.tasks ?? [])
            .filter((task) => task.status === "completed")
            .map((task) => task.id),
    );
}

async function load() {
    childStore.selectChild(route.params.id);
    await Promise.all([
        childStore.loadDailyTasks(route.params.id, selectedDate.value),
        childStore.loadRewards(route.params.id),
    ]);
    syncDraft();
}

async function selectDay(day) {
    selectedDay.value = day;
    await childStore.loadDailyTasks(route.params.id, selectedDate.value);
    syncDraft();
}

async function toggleTask(task) {
    if (!canEditTasks.value) {
        return;
    }

    busyId.value = task.id;
    const previous = new Set(draftCompletedIds.value);
    try {
        const next = new Set(draftCompletedIds.value);
        if (next.has(task.id)) {
            next.delete(task.id);
        } else {
            next.add(task.id);
        }
        draftCompletedIds.value = next;
        await childStore.saveDailyTaskDraft(selectedDate.value, Array.from(next));
        syncDraft();
    } catch (error) {
        draftCompletedIds.value = previous;
        toast.show(error.message, "error");
    } finally {
        busyId.value = null;
    }
}

async function submitDay() {
    if (!canEditTasks.value) {
        return;
    }

    try {
        const result = await childStore.submitDailyTasks(
            selectedDate.value,
            Array.from(draftCompletedIds.value),
        );
        syncDraft();
        const amount = result.points_awarded || 0;
        toast.show(
            amount > 0 ? `Đã chốt nhiệm vụ! +${amount} ⭐` : "Đã chốt nhiệm vụ",
        );
    } catch (error) {
        toast.show(error.message, "error");
    }
}

async function redeem(reward) {
    if (dashboard.value && dashboard.value.points < reward.required_points) {
        return;
    }
    try {
        await childStore.redeem(reward.id);
        await childStore.loadDailyTasks(route.params.id, selectedDate.value);
        syncDraft();
        toast.show(`Đã đổi: ${reward.title}`);
    } catch (error) {
        toast.show(error.message, "error");
    }
}

function toggleRewards() {
    showRewards.value = !showRewards.value;
}

async function openRewardHistory() {
    showRewardHistory.value = true;
    try {
        await childStore.loadRewardHistory(route.params.id);
    } catch (error) {
        toast.show(error.message, "error");
    }
}

onMounted(load);
</script>

<template>
    <ChildLayout>
        <ChildHeader
            :points="dashboard?.points ?? 0"
            :rewards-open="showRewards"
            @change-child="router.push('/')"
            @show-history="openRewardHistory"
            @toggle-rewards="toggleRewards"
        />

        <section
            class="mx-auto max-w-5xl px-4 pb-6 pt-16 sm:px-6 sm:pt-20 overflow-hidden"
        >
            <LoadingState
                v-if="childStore.loadingStates.dashboard && !dashboardReady"
                class="mt-6"
                :title="`Đang tải nhiệm vụ ${selectedOption.label.toLowerCase()}`"
                message="KidTask đang lấy tiến độ, điểm và danh sách nhiệm vụ."
                variant="child"
                :rows="5"
            />

            <div v-else-if="dashboardReady" class="mt-6">
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
                                    {{ selectedOption.label }} ·
                                    {{ selectedDateLabel }}
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
                                <p
                                    class="mt-2 text-2xl font-extrabold text-slate-900"
                                >
                                    {{ displayedProgress.completed }}/{{
                                        displayedProgress.total
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
                                <p
                                    class="mt-2 text-2xl font-extrabold text-slate-900"
                                >
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
                                <p
                                    class="mt-2 text-2xl font-extrabold text-slate-900"
                                >
                                    🔥 {{ dashboard.streak }}
                                </p>
                            </div>
                        </div>
                        <div class="mt-6">
                            <ProgressBar :value="displayedProgress.percent" />
                            <p
                                class="mt-3 text-sm font-semibold text-slate-600"
                            >
                                {{ displayedProgress.percent }}% hoàn thành
                                {{ selectedOption.label.toLowerCase() }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="mt-5 grid gap-3 rounded-[1.5rem] bg-white/80 p-3 shadow-sm ring-1 ring-slate-100 sm:grid-cols-3"
                    >
                        <button
                            v-for="option in dateOptions"
                            :key="option.key"
                            type="button"
                            class="rounded-[1.15rem] px-4 py-3 text-left transition focus:outline-none focus:ring-4 focus:ring-sky-200"
                            :class="
                                selectedDay === option.key
                                    ? 'bg-green-700 text-white shadow-lg shadow-slate-200'
                                    : 'bg-slate-50 text-slate-700 hover:bg-sky-50'
                            "
                            @click="selectDay(option.key)"
                        >
                            <span class="block text-sm font-extrabold">
                                {{ option.label }}
                            </span>
                            <span
                                class="mt-1 block text-xs font-semibold"
                                :class="
                                    selectedDay === option.key
                                        ? 'text-slate-200'
                                        : 'text-slate-500'
                                "
                            >
                                {{ option.helper }}
                            </span>
                        </button>
                    </div>

                    <div
                        class="mt-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
                    >
                        <div>
                            <p class="text-xs font-bold uppercase text-sky-700">
                                Danh sách nhiệm vụ
                            </p>
                            <h2
                                class="mt-1 text-2xl font-extrabold text-slate-900"
                            >
                                Việc cần làm
                                {{ selectedOption.label.toLowerCase() }}
                            </h2>
                            <p
                                class="mt-2 text-sm font-semibold text-slate-600"
                            >
                                {{ submitHint }}
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <p
                                class="rounded-full bg-white/80 px-3 py-2 text-xs font-bold text-slate-600 shadow-sm"
                            >
                                {{ displayedProgress.completed }} đã chọn
                            </p>
                            <button
                                v-if="canEditTasks"
                                type="button"
                                class="rounded-full bg-emerald-500 px-5 py-3 text-sm font-extrabold text-white shadow-lg shadow-emerald-200 transition hover:bg-emerald-600 focus:outline-none focus:ring-4 focus:ring-emerald-200 disabled:cursor-wait disabled:opacity-70"
                                :disabled="
                                    childStore.loadingStates.submitDailyTasks
                                "
                                @click="submitDay"
                            >
                                {{
                                    childStore.loadingStates.submitDailyTasks
                                        ? "Đang chốt..."
                                        : "Chốt nhiệm vụ"
                                }}
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <TaskCard
                            v-for="dailyTask in displayedTasks"
                            :key="dailyTask.id"
                            :daily-task="dailyTask"
                            :busy="busyId === dailyTask.id"
                            :readonly="isReadonly"
                            :status-label="
                                dailyTask.status === 'completed'
                                    ? canEditTasks
                                        ? 'Đã chọn'
                                        : 'Hoàn thành'
                                    : dailyTask.status === 'incomplete'
                                      ? 'Chưa hoàn thành'
                                    : isReadonly
                                      ? 'Chỉ xem'
                                      : ''
                            "
                            @toggle="toggleTask"
                        />
                    </div>
                    <EmptyState
                        v-if="!displayedTasks.length"
                        class="mt-5"
                        :title="`Chưa có nhiệm vụ ${selectedOption.label.toLowerCase()}`"
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

        <RewardHistoryModal
            :open="showRewardHistory"
            :history="childStore.rewardHistory"
            :loading="childStore.loadingStates.rewardHistory"
            @close="showRewardHistory = false"
        />
    </ChildLayout>
</template>
