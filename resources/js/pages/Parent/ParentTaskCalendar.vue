<script setup>
import { computed, onMounted, ref, watch } from "vue";
import ParentLayout from "../../layouts/ParentLayout.vue";
import BaseButton from "../../components/common/BaseButton.vue";
import { useParentStore } from "../../stores/parent.store";
import { useToastStore } from "../../stores/toast.store";
import { ChevronLeft, ChevronRight } from "lucide-vue-next";

const parent = useParentStore();
const toast = useToastStore();
const selectedDates = ref([]);
const selectedTaskIds = ref([]);
const selectedChildIds = ref([]);
const modalDate = ref(null);
const currentMonth = ref(monthDateFromKey(vietnamTodayKey()));
const quickRange = ref({
    start: monthStartKeyFromDate(currentMonth.value),
    end: vietnamTodayKey(),
    mode: "all",
});

const weekDays = ["T2", "T3", "T4", "T5", "T6", "T7", "CN"];
const quickModes = [
    ["all", "Tất cả ngày"],
    ["weekdays", "Thứ 2-6"],
    ["weekends", "Cuối tuần"],
];
const quickStartDisplay = computed({
    get: () => formatDate(quickRange.value.start),
    set: (value) => {
        quickRange.value = {
            ...quickRange.value,
            start: parseDisplayDate(value) || quickRange.value.start,
        };
    },
});
const quickEndDisplay = computed({
    get: () => formatDate(quickRange.value.end),
    set: (value) => {
        quickRange.value = {
            ...quickRange.value,
            end: parseDisplayDate(value) || quickRange.value.end,
        };
    },
});

const monthStartKey = computed(() =>
    dateKey(
        new Date(
            currentMonth.value.getFullYear(),
            currentMonth.value.getMonth(),
            1,
        ),
    ),
);
const monthEndKey = computed(() =>
    dateKey(
        new Date(
            currentMonth.value.getFullYear(),
            currentMonth.value.getMonth() + 1,
            0,
        ),
    ),
);
const monthLabel = computed(() =>
    new Intl.DateTimeFormat("vi-VN", {
        month: "long",
        year: "numeric",
        timeZone: "Asia/Ho_Chi_Minh",
    }).format(currentMonth.value),
);
const todayKey = computed(() => vietnamTodayKey());
const calendarParams = computed(() => ({
    start_date: monthStartKey.value,
    end_date: monthEndKey.value,
}));
const modalDates = computed(() =>
    selectedDates.value.length
        ? selectedDates.value
        : modalDate.value
          ? [modalDate.value]
          : [],
);
const modalAssignments = computed(() =>
    modalDates.value.flatMap((date) => parent.taskCalendar.by_date?.[date] ?? []),
);
const modalHasAssignments = computed(() => modalAssignments.value.length > 0);
const modalHasLockedDates = computed(() =>
    modalDates.value.some((date) => isLockedDate(date)),
);
const modalDateSummary = computed(() => {
    const dates = modalDates.value;

    if (!dates.length) return "";
    if (dates.length === 1) return formatDate(dates[0]);
    if (dates.length <= 4) return dates.map(formatDate).join(", ");

    const sortedDates = [...dates].sort();
    return `${formatDate(sortedDates[0])} - ${formatDate(sortedDates[sortedDates.length - 1])}`;
});
const modalDatePreview = computed(() => {
    const dates = modalDates.value;

    if (dates.length <= 4) return "";

    const sortedDates = [...dates].sort();
    const head = sortedDates.slice(0, 2).map(formatDate);
    const tail = sortedDates.slice(-2).map(formatDate);

    return [...head, "...", ...tail].join(", ");
});
const activeTaskTemplates = computed(() => {
    const templates = new Map();

    parent.tasks
        .filter((task) => task.is_active)
        .forEach((task) => {
            const key = taskTemplateKey(task);
            if (!templates.has(key)) {
                templates.set(key, task);
            }
        });

    return [...templates.values()];
});
const calendarDays = computed(() => {
    const year = currentMonth.value.getFullYear();
    const month = currentMonth.value.getMonth();
    const firstDate = new Date(year, month, 1);
    const totalDays = new Date(year, month + 1, 0).getDate();
    const leadingDays = (firstDate.getDay() + 6) % 7;
    const days = Array.from({ length: leadingDays }, (_, index) => ({
        key: `blank-${index}`,
        blank: true,
    }));

    for (let day = 1; day <= totalDays; day++) {
        const date = new Date(year, month, day);
        const key = dateKey(date);
        const assignments = parent.taskCalendar.by_date?.[key] ?? [];

        days.push({
            key,
            day,
            assignments,
            childCount: new Set(
                assignments.map((assignment) => assignment.user_id),
            ).size,
            hasAssignments: assignments.length > 0,
            isLocked: isLockedDate(key),
            isToday: key === todayKey.value,
            isSelected: selectedDates.value.includes(key),
        });
    }

    while (days.length % 7 !== 0) {
        days.push({ key: `blank-end-${days.length}`, blank: true });
    }

    return days;
});

function vietnamTodayKey() {
    const parts = new Intl.DateTimeFormat("en", {
        day: "2-digit",
        month: "2-digit",
        timeZone: "Asia/Ho_Chi_Minh",
        year: "numeric",
    }).formatToParts(new Date());
    const values = Object.fromEntries(
        parts
            .filter((part) => part.type !== "literal")
            .map((part) => [part.type, part.value]),
    );

    return `${values.year}-${values.month}-${values.day}`;
}

function monthDateFromKey(key) {
    const [year, month] = key.split("-").map(Number);
    return new Date(year, month - 1, 1);
}

function dateFromKey(key) {
    const [year, month, day] = key.split("-").map(Number);
    return new Date(year, month - 1, day);
}

function monthStartKeyFromDate(date) {
    return dateKey(new Date(date.getFullYear(), date.getMonth(), 1));
}

function dateKey(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
}

function formatDate(key) {
    if (!key) return "";

    const [year, month, day] = key.split("-").map(Number);
    return new Intl.DateTimeFormat("vi-VN", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
    }).format(new Date(year, month - 1, day));
}

function parseDisplayDate(value) {
    const match = String(value).trim().match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);

    if (!match) return null;

    const [, day, month, year] = match;
    const date = new Date(Number(year), Number(month) - 1, Number(day));

    if (
        date.getFullYear() !== Number(year) ||
        date.getMonth() !== Number(month) - 1 ||
        date.getDate() !== Number(day)
    ) {
        return null;
    }

    return dateKey(date);
}

function changeMonth(amount) {
    currentMonth.value = new Date(
        currentMonth.value.getFullYear(),
        currentMonth.value.getMonth() + amount,
        1,
    );
    selectedDates.value = [];
    quickRange.value = {
        ...quickRange.value,
        start: dateKey(
            new Date(
                currentMonth.value.getFullYear(),
                currentMonth.value.getMonth(),
                1,
            ),
        ),
        end: dateKey(
            new Date(
                currentMonth.value.getFullYear(),
                currentMonth.value.getMonth() + 1,
                0,
            ),
        ),
    };
    closeModal();
}

function goToCurrentMonth() {
    currentMonth.value = monthDateFromKey(vietnamTodayKey());
    selectedDates.value = [];
    quickRange.value = {
        ...quickRange.value,
        start: monthStartKey.value,
        end: monthEndKey.value,
    };
    closeModal();
}

function toggleDate(key) {
    if (isLockedDate(key)) {
        toast.show("Không thể chọn ngày đã đến hạn hoặc đã qua.");
        return;
    }

    selectedDates.value = selectedDates.value.includes(key)
        ? selectedDates.value.filter((date) => date !== key)
        : [...selectedDates.value, key].sort();
}

function isLockedDate(key) {
    return key <= todayKey.value;
}

function datesInRange(startKey, endKey, mode = "all") {
    if (!startKey || !endKey) return [];

    const start = dateFromKey(startKey);
    const end = dateFromKey(endKey);

    if (start > end) return [];

    const dates = [];
    const cursor = new Date(start);

    while (cursor <= end) {
        const dayOfWeek = cursor.getDay();
        const matchesMode =
            mode === "all" ||
            (mode === "weekdays" && dayOfWeek >= 1 && dayOfWeek <= 5) ||
            (mode === "weekends" && [0, 6].includes(dayOfWeek));

        if (matchesMode && !isLockedDate(dateKey(cursor))) {
            dates.push(dateKey(cursor));
        }

        cursor.setDate(cursor.getDate() + 1);
    }

    return dates;
}

function applyQuickRange(replace = true) {
    const dates = datesInRange(
        quickRange.value.start,
        quickRange.value.end,
        quickRange.value.mode,
    );

    if (!dates.length) {
        toast.show("Khoảng ngày chưa hợp lệ hoặc chỉ gồm ngày đã khóa.");
        return;
    }

    selectedDates.value = replace
        ? dates
        : [...new Set([...selectedDates.value, ...dates])].sort();
}

function selectCurrentMonth() {
    quickRange.value = {
        ...quickRange.value,
        start: monthStartKey.value,
        end: monthEndKey.value,
        mode: "all",
    };
    applyQuickRange();
}

function clearSelectedDates() {
    selectedDates.value = [];
}

function taskTemplateKey(task) {
    return [
        task?.title,
        task?.category_id || "",
        task?.points,
        task?.icon || "",
    ].join("|");
}

function toggleTask(taskId) {
    selectedTaskIds.value = selectedTaskIds.value.includes(taskId)
        ? selectedTaskIds.value.filter((id) => id !== taskId)
        : [...selectedTaskIds.value, taskId];
}

function toggleChild(childId) {
    selectedChildIds.value = selectedChildIds.value.includes(childId)
        ? selectedChildIds.value.filter((id) => id !== childId)
        : [...selectedChildIds.value, childId];
}

function openModal(key) {
    modalDate.value = key;
    const dates = selectedDates.value.length ? selectedDates.value : [key];
    const assignments = dates.flatMap(
        (date) => parent.taskCalendar.by_date?.[date] ?? [],
    );
    const templateMap = new Map(
        activeTaskTemplates.value.map((task) => [taskTemplateKey(task), task.id]),
    );
    const templateIds = new Set();

    assignments.forEach((assignment) => {
        const templateId = templateMap.get(taskTemplateKey(assignment.task));

        if (templateId) {
            templateIds.add(templateId);
        }
    });

    selectedTaskIds.value = [...templateIds];
    selectedChildIds.value = [
        ...new Set(assignments.map((assignment) => assignment.user_id)),
    ];
}

function closeModal() {
    modalDate.value = null;
    selectedTaskIds.value = [];
    selectedChildIds.value = [];
}

async function loadCalendar() {
    await parent.loadTaskCalendar(calendarParams.value);
}

async function assignSelectedTasks() {
    if (!modalDates.value.length) {
        return;
    }

    if (modalHasLockedDates.value) {
        toast.show("Không thể thay đổi nhiệm vụ trong ngày đã đến hạn hoặc đã qua.");
        return;
    }

    if (
        !modalHasAssignments.value &&
        (!selectedTaskIds.value.length || !selectedChildIds.value.length)
    ) {
        toast.show("Vui lòng chọn nhiệm vụ và bé nhận nhiệm vụ.");
        return;
    }

    const templateMap = new Map(
        activeTaskTemplates.value.map((task) => [taskTemplateKey(task), task.id]),
    );
    const deleteIds = modalAssignments.value
        .filter((assignment) => {
            const templateId = templateMap.get(taskTemplateKey(assignment.task));

            return (
                !templateId ||
                !selectedTaskIds.value.includes(templateId) ||
                !selectedChildIds.value.includes(assignment.user_id)
            );
        })
        .map((assignment) => assignment.id);
    const assignPayload =
        selectedTaskIds.value.length && selectedChildIds.value.length
            ? {
                  user_ids: selectedChildIds.value,
                  task_ids: selectedTaskIds.value,
                  dates: modalDates.value,
              }
            : null;

    await parent.saveTaskAssignmentChanges(
        {
            deleteIds,
            assignPayload,
        },
        calendarParams.value,
    );

    toast.show("Đã lưu thay đổi");
    selectedDates.value = [];
    closeModal();
}

async function removeAssignment(assignment) {
    const date = assignment.date?.slice(0, 10);

    if (date && isLockedDate(date)) {
        toast.show("Không thể thay đổi nhiệm vụ trong ngày đã đến hạn hoặc đã qua.");
        return;
    }

    if (!window.confirm("Bạn có chắc muốn xóa nhiệm vụ khỏi ngày này?")) return;

    await parent.deleteTaskAssignment(assignment.id, calendarParams.value);
    toast.show("Đã xóa nhiệm vụ khỏi ngày này");
}

watch(currentMonth, loadCalendar);

onMounted(async () => {
    await Promise.all([parent.loadChildren(), parent.loadTasks()]);
    await loadCalendar();
});
</script>

<template>
    <ParentLayout>
        <div
            class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
        >
            <div>
                <p class="admin-section-title">Lịch giao nhiệm vụ</p>
                <h1 class="mt-1 text-3xl font-bold">
                    Giao nhiệm vụ
                </h1>
            </div>
            <div
                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white p-1 shadow-sm"
            >
                <BaseButton
                    variant="ghost"
                    class="!h-9 !rounded-lg !px-3 text-slate-600 !font-bold hover:bg-slate-100"
                    @click="changeMonth(-1)"
                >
                    <ChevronLeft class="mr-1.5 h-4 w-4" />
                    Tháng trước
                </BaseButton>

                <BaseButton
                    variant="ghost"
                    class="!h-9 !rounded-lg !bg-slate-100 !px-4 !font-bold !text-slate-800 hover:!bg-slate-200"
                    @click="goToCurrentMonth"
                >
                    Hiện tại
                </BaseButton>

                <BaseButton
                    variant="ghost"
                    class="!h-9 !rounded-lg !px-3 text-slate-600 !font-bold hover:bg-slate-100"
                    @click="changeMonth(1)"
                >
                    Tháng sau
                    <ChevronRight class="ml-1.5 h-4 w-4" />
                </BaseButton>
            </div>
        </div>

        <section class="admin-card mt-6 overflow-hidden">
            <div
                class="flex flex-col gap-3 border-b border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p
                        class="text-xs font-bold uppercase tracking-wide text-slate-500"
                    >
                        Tháng đang xem
                    </p>
                    <h2 class="text-xl mt-1 font-semibold capitalize">
                        {{ monthLabel }}
                    </h2>
                </div>
                <div
                    class="rounded-xl bg-slate-50 px-3 py-2 text-sm font-bold text-slate-600"
                >
                    {{ selectedDates.length }} ngày được chọn
                </div>
            </div>

            <div
                class="grid gap-3 border-b border-slate-200 bg-white p-4 lg:grid-cols-6 lg:items-end"
            >
                <label class="block">
                    <span
                        class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500"
                        >Từ ngày</span
                    >
                    <input
                        v-model="quickStartDisplay"
                        type="text"
                        inputmode="numeric"
                        placeholder="dd/mm/yyyy"
                        class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-900"
                    />
                </label>
                <label class="block">
                    <span
                        class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500"
                        >Đến ngày</span
                    >
                    <input
                        v-model="quickEndDisplay"
                        type="text"
                        inputmode="numeric"
                        placeholder="dd/mm/yyyy"
                        class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-900"
                    />
                </label>
                <label class="block">
                    <span
                        class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500"
                        >Kiểu ngày</span
                    >
                    <select
                        v-model="quickRange.mode"
                        class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-900"
                    >
                        <option
                            v-for="[value, label] in quickModes"
                            :key="value"
                            :value="value"
                        >
                            {{ label }}
                        </option>
                    </select>
                </label>
                <BaseButton @click="applyQuickRange()">Lọc</BaseButton>
                <BaseButton
                    variant="secondary"
                    @click="selectCurrentMonth"
                    >Chọn cả tháng</BaseButton
                >
                <BaseButton
                    variant="danger"
                    class="!bg-red-100 !text-red-500 !border-red-300 hover:!bg-red-200"
                    @click="clearSelectedDates"
                >
                    ❌ Bỏ chọn
                </BaseButton>
            </div>

            <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50">
                <div
                    v-for="day in weekDays"
                    :key="day"
                    class="px-2 py-3 text-center text-xs font-bold uppercase text-slate-500"
                >
                    {{ day }}
                </div>
            </div>

            <div
                v-if="
                    parent.loadingStates.taskCalendar &&
                    !parent.taskCalendar.assignments.length
                "
                class="grid grid-cols-7"
            >
                <div
                    v-for="cell in 35"
                    :key="cell"
                    class="min-h-32 border-b border-r border-slate-100 p-2"
                >
                    <div
                        class="h-8 w-8 animate-pulse rounded-full bg-slate-100"
                    ></div>
                    <div
                        class="mt-3 h-7 animate-pulse rounded-lg bg-slate-100"
                    ></div>
                </div>
            </div>

            <div v-else class="grid grid-cols-7">
                <div
                    v-for="day in calendarDays"
                    :key="day.key"
                    class="min-h-40 border-b border-r border-slate-100 p-2 text-left transition"
                    :class="{
                        'bg-slate-50/70': day.blank,
                        'cursor-pointer bg-sky-50 ring-2 ring-inset ring-sky-200 hover:bg-sky-100':
                            !day.blank && day.isSelected,
                        'cursor-pointer bg-gray-200 text-slate-400 hover:bg-slate-100':
                            !day.blank && !day.isSelected && day.isLocked,
                        'cursor-pointer bg-amber-50/80 hover:bg-amber-100/70':
                            !day.blank &&
                            !day.isSelected &&
                            !day.isLocked &&
                            day.hasAssignments,
                        'cursor-pointer bg-white hover:bg-slate-50':
                            !day.blank &&
                            !day.isSelected &&
                            !day.isLocked &&
                            !day.hasAssignments,
                    }"
                    @click="!day.blank && openModal(day.key)"
                >
                    <template v-if="!day.blank">
                        <div class="flex items-center justify-between gap-2">
                            <span
                                class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold"
                                :class="
                                    day.isToday
                                        ? 'bg-sky-600 text-white'
                                        : day.isSelected
                                          ? 'bg-sky-100 text-sky-800'
                                          : 'text-slate-700'
                                "
                            >
                                {{ day.day }}
                            </span>
                            <input
                                type="checkbox"
                                class="h-5 w-5 rounded border-slate-300 text-sky-600 focus:ring-sky-200"
                                :checked="day.isSelected"
                                :disabled="day.isLocked"
                                @click.stop
                                @change="toggleDate(day.key)"
                            />
                        </div>

                        <div class="mt-3 grid gap-1">
                            <div
                                v-if="day.isLocked"
                                class="rounded-lg bg-slate-200 px-2 py-1 text-xs font-bold text-slate-600"
                            >
                                Đã khóa
                            </div>
                            <div
                                class="rounded-lg px-2 py-1 text-xs font-bold"
                                :class="
                                    day.hasAssignments
                                        ? 'bg-amber-200/80 text-amber-900 ring-1 ring-amber-300'
                                        : 'bg-slate-100 text-slate-600'
                                "
                            >
                                {{ day.assignments.length }} nhiệm vụ
                                <span v-if="day.childCount">
                                    · {{ day.childCount }} bé</span
                                >
                            </div>
                            <div class="max-h-28 space-y-1 overflow-y-auto pr-1">
                                <div
                                    v-for="assignment in day.assignments"
                                    :key="assignment.id"
                                    class="group flex min-h-8 items-center gap-1 rounded-lg bg-white px-2 py-1 ring-1 ring-amber-200"
                                    :title="assignment.task?.title"
                                >
                                    <span class="shrink-0">{{
                                        assignment.task?.icon
                                    }}</span>
                                    <span
                                        class="min-w-0 flex-1 truncate text-xs font-bold text-slate-700"
                                        >{{ assignment.task?.title }}</span
                                    >
                                    <button
                                        type="button"
                                        class="shrink-0 text-xs font-bold text-red-600 opacity-0 transition group-hover:opacity-100 disabled:text-slate-400 disabled:opacity-100"
                                        :disabled="
                                            assignment.status === 'completed' ||
                                            day.isLocked
                                        "
                                        @click.stop="removeAssignment(assignment)"
                                    >
                                        Xóa
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </section>

        <div
            v-if="modalDate"
            class="fixed inset-0 z-50 grid place-items-center bg-slate-950/40 p-4"
            @click.self="closeModal"
        >
            <section
                class="max-h-[92vh] overflow-y-auto w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-2xl"
            >
                <div
                    class="flex items-start justify-between gap-4 border-b border-slate-200 p-5"
                >
                    <div>
                        <p class="admin-section-title">Giao nhiệm vụ</p>
                        <h2 class="mt-1 text-2xl font-bold">
                            {{ modalDates.length }} ngày
                        </h2>
                        <p class="mt-1 text-sm font-bold text-slate-600">
                            {{ modalDateSummary }}
                        </p>
                        <p
                            v-if="modalDatePreview"
                            class="mt-1 text-xs font-semibold text-slate-400"
                        >
                            {{ modalDatePreview }}
                        </p>
                        <p
                            v-if="modalHasLockedDates"
                            class="mt-2 rounded-lg bg-slate-100 px-3 py-2 text-xs font-bold text-slate-600"
                        >
                            Ngày đã đến hạn hoặc đã qua chỉ được xem, không thể
                            thay đổi.
                        </p>
                    </div>
                    <button
                        class="rounded-xl px-2 py-2 text-sm font-bold text-slate-500 bg-gray-100 hover:bg-slate-200"
                        type="button"
                        @click="closeModal"
                    >
                        ❌
                    </button>
                </div>

                <div class="p-5">
                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="font-bold">Nhiệm vụ</h3>
                            <span
                                class="rounded-lg bg-sky-50 px-2 py-1 text-xs font-bold text-sky-700"
                                >{{ selectedTaskIds.length }} chọn</span
                            >
                        </div>
                        <div class="mt-3 grid grid-cols-3 gap-2">
                            <label
                                v-for="task in activeTaskTemplates"
                                :key="task.id"
                                class="flex min-h-14 items-center gap-3 rounded-xl border px-3 py-2"
                                :class="
                                    selectedTaskIds.includes(task.id)
                                        ? 'border-sky-300 bg-sky-50'
                                        : 'border-slate-200'
                                "
                            >
                                <input
                                    type="checkbox"
                                    class="h-5 w-5 rounded border-slate-300 text-sky-600 focus:ring-sky-200"
                                    :checked="selectedTaskIds.includes(task.id)"
                                    :disabled="modalHasLockedDates"
                                    @change="toggleTask(task.id)"
                                />
                                <span
                                    class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-slate-100 text-lg"
                                    >{{ task.icon }}</span
                                >
                                <span class="min-w-0 flex-1">
                                    <span
                                        class="block truncate text-sm font-bold text-slate-950"
                                        >{{ task.title }}</span
                                    >
                                    <span
                                        class="block truncate text-xs font-bold text-slate-500"
                                        >{{
                                            task.category?.name ||
                                            "Không danh mục"
                                        }}
                                        · +{{ task.points }}</span
                                    >
                                </span>
                            </label>
                            <div
                                v-if="!activeTaskTemplates.length"
                                class="rounded-xl border border-dashed border-slate-200 p-4 text-sm font-semibold text-slate-500"
                            >
                                Chưa có nhiệm vụ đang bật.
                            </div>
                        </div>
                    </div>

                    <div>
                        <div
                            class="flex items-center justify-between gap-3 mt-5"
                        >
                            <h3 class="font-bold">Bé nhận nhiệm vụ</h3>
                            <span
                                class="rounded-lg bg-amber-50 px-2 py-1 text-xs font-bold text-amber-700"
                                >{{ selectedChildIds.length }} chọn</span
                            >
                        </div>
                        <div class="mt-3 grid grid-cols-4 gap-2">
                            <label
                                v-for="child in parent.children"
                                :key="child.id"
                                class="flex min-h-14 items-center gap-3 rounded-xl border px-3 py-2"
                                :class="
                                    selectedChildIds.includes(child.id)
                                        ? 'border-amber-300 bg-amber-50'
                                        : 'border-slate-200'
                                "
                            >
                                <input
                                    type="checkbox"
                                    class="h-5 w-5 rounded border-slate-300 text-amber-500 focus:ring-amber-100"
                                    :checked="
                                        selectedChildIds.includes(child.id)
                                    "
                                    :disabled="modalHasLockedDates"
                                    @change="toggleChild(child.id)"
                                />
                                <span
                                    class="min-w-0 flex-1 truncate text-sm font-bold text-slate-950"
                                    >{{ child.name }}</span
                                >
                            </label>
                            <div
                                v-if="!parent.children.length"
                                class="rounded-xl border border-dashed border-slate-200 p-4 text-sm font-semibold text-slate-500"
                            >
                                Chưa có hồ sơ bé.
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="flex flex-col-reverse gap-2 border-t border-slate-200 p-5 sm:flex-row sm:justify-end"
                >
                    <BaseButton variant="secondary" @click="closeModal"
                        >Hủy</BaseButton
                    >
                    <BaseButton
                        :disabled="
                            modalHasLockedDates ||
                            (!modalHasAssignments &&
                                (!selectedTaskIds.length ||
                                    !selectedChildIds.length))
                        "
                        @click="assignSelectedTasks"
                        >Lưu thay đổi</BaseButton
                    >
                </div>
            </section>
        </div>
    </ParentLayout>
</template>
