<script setup>
import { computed, nextTick, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { BellRing, CalendarCheck, CheckCircle2, Star } from "lucide-vue-next";
import ChildLayout from "../../layouts/ChildLayout.vue";
import ChildHeader from "../../components/child/ChildHeader.vue";
import RewardHistoryModal from "../../components/child/RewardHistoryModal.vue";
import RewardsSheet from "../../components/child/RewardsSheet.vue";
import TaskCard from "../../components/child/TaskCard.vue";
import ProgressBar from "../../components/common/ProgressBar.vue";
import EmptyState from "../../components/common/EmptyState.vue";
import AvatarPhoto from "../../components/common/AvatarPhoto.vue";
import LoadingState from "../../components/common/LoadingState.vue";
import ConfirmDialog from "../../components/common/ConfirmDialog.vue";
import { useChildStore } from "../../stores/child.store";
import { useToastStore } from "../../stores/toast.store";

const route = useRoute();
const router = useRouter();
const childStore = useChildStore();
const toast = useToastStore();
const busyId = ref(null);
const showRewards = ref(false);
const showRewardHistory = ref(false);
const rewardToRedeem = ref(null);
const showSubmitConfirmation = ref(false);
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
            helper: "Có thể sửa nếu chưa hoàn thành",
            date: addDays(today, -1),
        },
        {
            key: "today",
            label: "Hôm nay",
            helper: "Chọn xong rồi hoàn thành",
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
const dueReminders = computed(() => {
    const now = new Date();
    const currentTime = `${String(now.getHours()).padStart(2, "0")}:${String(now.getMinutes()).padStart(2, "0")}`;

    return displayedTasks.value.filter((task) => task.reminder_time && task.status !== "completed" && task.reminder_time <= currentTime);
});
const submitDisabled = computed(
    () =>
        !dashboardReady.value ||
        !dayStatus.value.can_submit ||
        displayedTasks.value.length === 0 ||
        displayedProgress.value.completed === 0 ||
        childStore.loadingStates.taskDraft ||
        childStore.loadingStates.submitDailyTasks,
);
const submitHint = computed(() => {
    if (displayedTasks.value.length === 0) {
        return "Chưa có nhiệm vụ nào được giao cho ngày này.";
    }

    if (displayedProgress.value.completed === 0) {
        return "Con cần hoàn thành ít nhất một nhiệm vụ trong ngày trước khi chốt.";
    }

    if (selectedDay.value === "tomorrow") {
        return "Ngày mai chỉ xem trước, chưa thể tích hoặc hoàn thành.";
    }

    if (dayStatus.value.is_submitted) {
        return "Ngày này đã hoàn thành, bé chỉ có thể xem lại.";
    }

    return "Tích các việc đã làm, sau đó bấm hoàn thành để lưu điểm sao.";
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
    try {
        await Promise.all([
            childStore.loadDailyTasks(route.params.id, selectedDate.value),
            childStore.loadRewards(route.params.id),
        ]);
        syncDraft();
    } catch (error) {
        toast.show(error.message, "error");
        router.push("/family");
    }
}

async function selectDay(day) {
    const scrollTop = window.scrollY;
    selectedDay.value = day;
    await childStore.loadDailyTasks(route.params.id, selectedDate.value);
    syncDraft();
    await nextTick();
    requestAnimationFrame(() => window.scrollTo({ top: scrollTop, left: 0, behavior: "auto" }));
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
    if (submitDisabled.value) {
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
            amount > 0 ? `Đã hoàn thành nhiệm vụ! +${amount} ⭐` : "Đã hoàn thành nhiệm vụ",
        );
    } catch (error) {
        toast.show(error.message, "error");
    }
}

function requestSubmitDay() {
    if (submitDisabled.value) {
        return;
    }

    showSubmitConfirmation.value = true;
}

function requestRedeem(reward) {
    if (childStore.loadingStates.redeem) {
        return;
    }

    if (dashboard.value && dashboard.value.points < reward.required_points) {
        return;
    }

    rewardToRedeem.value = reward;
}

async function redeem() {
    const reward = rewardToRedeem.value;
    rewardToRedeem.value = null;

    if (!reward) return;

    try {
        await childStore.redeem(reward.id);
        await childStore.loadDailyTasks(route.params.id, selectedDate.value);
        syncDraft();
        toast.show(`Đã gửi yêu cầu đổi: ${reward.title}`);
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
            @change-child="router.push('/family')"
            @show-history="openRewardHistory"
            @toggle-rewards="toggleRewards"
        />

        <div class="child-page relative isolate min-h-screen overflow-hidden bg-sky-50 text-[#173b75]">

            <section class="mx-auto max-w-5xl overflow-hidden px-4 pb-10 pt-16 sm:px-6 sm:pt-20">
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
                            class="overflow-hidden rounded-[28px] border border-white/80 bg-white/95 p-6
                            shadow-[0_25px_80px_rgba(53,113,180,.22)] backdrop-blur-xl sm:p-8"
                        >
                            <div class="grid gap-6 lg:grid-cols-[1fr_auto] lg:items-center">
                                <div>
                                    <div
                                        class="inline-flex items-center gap-2 rounded-full border border-sky-100
                                        bg-white px-4 py-2 text-sm font-extrabold capitalize text-sky-700 shadow-sm"
                                    >
                                        <CalendarCheck class="h-4 w-4 text-sky-500" />
                                        {{ selectedOption.label }} · {{ selectedDateLabel }}
                                    </div>
                                    <h1
                                        class="mt-5 text-4xl font-black leading-[1.15] tracking-tight
                                        text-[#14458f] sm:text-5xl lg:text-[3.5rem]"
                                    >
                                        {{ dashboard.child.name }}
                                    </h1>
                                    <p class="mt-3 max-w-xl text-base font-medium leading-7 text-slate-700">
                                        Hoàn thành từng nhiệm vụ nhỏ để gom sao và giữ chuỗi ngày tốt.
                                    </p>
                                </div>
                                <AvatarPhoto
                                    :src="dashboard.child.avatar"
                                    :name="dashboard.child.name"
                                    size="xl"
                                />
                            </div>

                            <div class="mt-6 grid gap-3 sm:grid-cols-3">
                                <div class="flex items-center gap-4 rounded-2xl bg-sky-50 p-4 ring-1 ring-sky-100">
                                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-600">
                                        <CheckCircle2 class="h-6 w-6" />
                                    </span>
                                    <div>
                                        <p class="text-xs font-extrabold text-slate-500">Tiến độ</p>
                                        <p class="mt-0.5 text-2xl font-black text-[#14458f]">
                                            {{ displayedProgress.completed }}/{{ displayedProgress.total }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6">
                                <ProgressBar :value="displayedProgress.percent" />
                                <p class="mt-3 text-sm font-bold text-slate-600">
                                    {{ displayedProgress.percent }}% hoàn thành
                                    {{ selectedOption.label.toLowerCase() }}
                                </p>
                            </div>
                        </div>

                      <div
                            class="mt-5 grid gap-2 rounded-2xl border border-emerald-400/60 bg-gradient-to-r from-emerald-600 to-green-700
                            p-1.5 shadow-lg shadow-emerald-200/60 sm:grid-cols-3"
                            role="tablist"
                            aria-label="Chọn ngày"
                        >
                            <button
                                v-for="option in dateOptions"
                                :key="option.key"
                                type="button"
                                role="tab"
                                :aria-selected="selectedDay === option.key"
                                class="flex min-h-[66px] flex-col items-start justify-center rounded-xl px-4 py-3
                                text-left transition focus:outline-none focus:ring-4 focus:ring-emerald-200"
                                :class="
                                    selectedDay === option.key
                                        ? 'bg-white text-emerald-700 shadow-md ring-2 ring-amber-300'
                                        : 'text-white/90 hover:bg-white/15'
                                "
                                @click="selectDay(option.key)"
                            >
                                <span class="block text-sm font-extrabold">{{ option.label }}</span>
                                <span
                                    class="mt-1 block text-xs font-semibold"
                                    :class="selectedDay === option.key ? 'text-emerald-600' : 'text-emerald-100'"
                                >
                                    {{ option.helper }}
                                </span>
                            </button>
                        </div>

                        <!-- Tiêu đề danh sách + nút hoàn thành -->
                        <div class="mt-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/90 px-3 py-1 text-xs font-extrabold text-sky-600 shadow-sm">
                                    <Star class="h-3.5 w-3.5 fill-amber-400 text-amber-400" />
                                    Danh sách nhiệm vụ
                                </span>
                                <h2 class="mt-2 text-2xl font-black text-[#14458f] sm:text-3xl">
                                    Việc cần làm {{ selectedOption.label.toLowerCase() }}
                                </h2>
                                <p class="mt-2 max-w-xl text-sm font-medium leading-6 text-slate-600">
                                    {{ submitHint }}
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <p class="rounded-full border border-sky-100 bg-white/90 px-4 py-2 text-xs font-extrabold text-sky-700 shadow-sm">
                                    {{ displayedProgress.completed }} đã chọn
                                </p>
                                <button
                                    v-if="canEditTasks"
                                    type="button"
                                    class="inline-flex min-h-[48px] items-center justify-center rounded-full
                                    bg-gradient-to-r from-[#2d9bf0] to-[#0879e8] px-6 text-sm font-extrabold text-white
                                    shadow-lg shadow-sky-300/50 transition duration-200 hover:-translate-y-0.5
                                    focus:outline-none focus:ring-4 focus:ring-sky-200
                                    disabled:translate-y-0 disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="submitDisabled"
                                    @click="requestSubmitDay"
                                    :class="submitDisabled ? '!cursor-not-allowed' : ''"
                                >
                                    {{
                                        childStore.loadingStates.submitDailyTasks
                                            ? "Đang hoàn thành..."
                                            : "Hoàn thành nhiệm vụ"
                                    }}
                                </button>
                            </div>
                        </div>

                        <!-- Nhắc giờ -->
                        <div
                            v-if="dueReminders.length && canEditTasks"
                            class="mt-4 flex items-start gap-3 rounded-2xl border border-amber-100 bg-amber-50/95 p-4 shadow-sm"
                        >
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-amber-100 text-amber-500">
                                <BellRing class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="text-sm font-extrabold text-amber-900">
                                    Đến giờ làm {{ dueReminders.length }} nhiệm vụ rồi.
                                </p>
                                <p class="mt-1 text-sm font-semibold text-amber-800/80">
                                    {{ dueReminders.slice(0, 2).map((task) => `${task.reminder_time} · ${task.task.title}`).join(" · ") }}
                                </p>
                            </div>
                        </div>

                        <!-- Danh sách task -->
                        <div class="mt-5 grid gap-4 md:grid-cols-2">
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
                </div>
            </section>
        </div>

        <RewardsSheet
            :open="showRewards"
            :points="dashboard?.points ?? 0"
            :rewards="childStore.rewards?.rewards ?? []"
            :loading="childStore.loadingStates.rewards && !childStore.rewards"
            :redeeming="childStore.loadingStates.redeem"
            @close="showRewards = false"
            @redeem="requestRedeem"
        />

        <ConfirmDialog
            v-model="showSubmitConfirmation"
            title="Hoàn thành nhiệm vụ"
            :message="`Con đã chọn ${displayedProgress.completed}/${displayedProgress.total} nhiệm vụ. Con chắc chắn muốn hoàn thành chứ?`"
            confirm-text="Hoàn thành"
            cancel-text="Xem lại"
            variant="default"
            @confirm="submitDay"
        />

        <ConfirmDialog
            :model-value="!!rewardToRedeem"
            title="Đổi phần thưởng"
            :message="rewardToRedeem ? `Con sẽ dùng ${rewardToRedeem.required_points} ⭐ để đổi '${rewardToRedeem.title}'. Con đồng ý chứ?` : ''"
            confirm-text="Đổi thưởng"
            cancel-text="Xem lại"
            variant="default"
            :loading="childStore.loadingStates.redeem"
            @confirm="redeem"
            @cancel="rewardToRedeem = null"
        />

        <RewardHistoryModal
            :open="showRewardHistory"
            :history="childStore.rewardHistory"
            :loading="childStore.loadingStates.rewardHistory"
            @close="showRewardHistory = false"
        />
    </ChildLayout>
</template>

<style scoped>
.child-page button:focus-visible,
.child-page a:focus-visible {
    outline: 3px solid rgba(37, 136, 232, .5);
    outline-offset: 2px;
}
</style>
