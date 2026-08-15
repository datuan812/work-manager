<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import ParentLayout from "../../layouts/ParentLayout.vue";
import BaseButton from "../../components/common/BaseButton.vue";
import ConfirmDialog from "../../components/common/ConfirmDialog.vue";
import TaskAssignModal from "../../components/parent/TaskAssignModal.vue";
import { useParentStore } from "../../stores/parent.store";
import { useToastStore } from "../../stores/toast.store";
import { Check, CalendarDays, ChevronLeft, ChevronRight, RotateCcw } from "lucide-vue-next";

const parent = useParentStore();
const toast = useToastStore();
const selectedDates = ref([]);
const selectedTaskIds = ref([]);
const selectedChildIds = ref([]);
const modalDate = ref(null);
const currentMonth = ref(monthDateFromKey(vietnamTodayKey()));
const quickRange = ref({
    start: monthStartKeyFromDate(currentMonth.value),
    end: monthEndKeyFromDate(currentMonth.value),
    mode: "all",
});
const quickStartInput = ref(formatDateInput(quickRange.value.start));
const quickEndInput = ref(formatDateInput(quickRange.value.end));
const quickStartPicker = ref(null);
const quickEndPicker = ref(null);
const quickSelectApplied = ref(false);
const confirmDelete = reactive({ show: false, assignment: null });

const weekDays = ["T2", "T3", "T4", "T5", "T6", "T7", "CN"];
const quickModes = [
    ["all", "Tất cả ngày"],
    ["weekdays", "Thứ 2-6"],
    ["weekends", "Cuối tuần"],
];

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
const filteredDateKeys = computed(
    () =>
        new Set(
            datesInRange(
                quickRange.value.start,
                quickRange.value.end,
                quickRange.value.mode,
                false,
            ),
        ),
);
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
            isInFilter: filteredDateKeys.value.has(key),
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

function monthEndKeyFromDate(date) {
    return dateKey(new Date(date.getFullYear(), date.getMonth() + 1, 0));
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

function isValidDateParts(day, month, year) {
    const date = new Date(year, month - 1, day);
    return (
        date.getFullYear() === year &&
        date.getMonth() === month - 1 &&
        date.getDate() === day
    );
}

function normalizeDate(value) {
    if (!value) return "";

    const date = String(value).trim();

    if (/^\d{4}-\d{2}-\d{2}/.test(date)) {
        return date.slice(0, 10);
    }

    const match = date.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);

    if (match) {
        const [, day, month, year] = match;
        if (!isValidDateParts(Number(day), Number(month), Number(year))) return "";

        return `${year}-${month.padStart(2, "0")}-${day.padStart(2, "0")}`;
    }

    return "";
}

function formatDateInput(value) {
    if (!value) return "";

    const [year, month, day] = String(value).slice(0, 10).split("-");
    if (!year || !month || !day) return "";

    return `${day.padStart(2, "0")}/${month.padStart(2, "0")}/${year}`;
}

function openQuickStartPicker() {
    if (typeof quickStartPicker.value?.showPicker === "function") {
        quickStartPicker.value.showPicker();
        return;
    }
    quickStartPicker.value?.click();
}
function openQuickEndPicker() {
    if (typeof quickEndPicker.value?.showPicker === "function") {
        quickEndPicker.value.showPicker();
        return;
    }
    quickEndPicker.value?.click();
}
function pickQuickStartDate(event) {
    quickRange.value.start = event.target.value;
    quickStartInput.value = formatDateInput(event.target.value);
}
function pickQuickEndDate(event) {
    quickRange.value.end = event.target.value;
    quickEndInput.value = formatDateInput(event.target.value);
}
function syncQuickStartDate() {
    const normalized = normalizeDate(quickStartInput.value);
    if (!normalized) {
        quickStartInput.value = formatDateInput(quickRange.value.start);
        return;
    }

    quickRange.value.start = normalized;
    quickStartInput.value = formatDateInput(normalized);
}
function syncQuickEndDate() {
    const normalized = normalizeDate(quickEndInput.value);
    if (!normalized) {
        quickEndInput.value = formatDateInput(quickRange.value.end);
        return;
    }

    quickRange.value.end = normalized;
    quickEndInput.value = formatDateInput(normalized);
}

function changeMonth(amount) {
    currentMonth.value = new Date(
        currentMonth.value.getFullYear(),
        currentMonth.value.getMonth() + amount,
        1,
    );
    resetQuickRangeToMonth();
    closeModal();
}

function goToCurrentMonth() {
    currentMonth.value = monthDateFromKey(vietnamTodayKey());
    resetQuickRangeToMonth();
    closeModal();
}

function resetQuickRangeToMonth() {
    selectedDates.value = [];
    quickSelectApplied.value = false;
    quickRange.value = {
        ...quickRange.value,
        start: monthStartKey.value,
        end: monthEndKey.value,
    };
    quickStartInput.value = formatDateInput(quickRange.value.start);
    quickEndInput.value = formatDateInput(quickRange.value.end);
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
    return key < todayKey.value;
}

function datesInRange(startKey, endKey, mode = "all", excludeLocked = true) {
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
        const key = dateKey(cursor);

        if (matchesMode && (!excludeLocked || !isLockedDate(key))) {
            dates.push(key);
        }

        cursor.setDate(cursor.getDate() + 1);
    }

    return dates;
}

function toggleQuickSelect() {
    if (quickSelectApplied.value) {
        selectedDates.value = [];
        quickSelectApplied.value = false;
        quickRange.value = {
            ...quickRange.value,
            start: monthStartKey.value,
            end: monthEndKey.value,
            mode: "all",
        };
        quickStartInput.value = formatDateInput(quickRange.value.start);
        quickEndInput.value = formatDateInput(quickRange.value.end);
        return;
    }

    const dates = datesInRange(
        quickRange.value.start,
        quickRange.value.end,
        quickRange.value.mode,
        true,
    );

    if (!dates.length) {
        toast.show("Khoảng ngày chưa hợp lệ hoặc chỉ gồm ngày đã khóa.");
        return;
    }

    selectedDates.value = dates;
    quickSelectApplied.value = true;
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

function selectAllTasks() {
    selectedTaskIds.value = activeTaskTemplates.value.map((task) => task.id);
}

function clearTaskSelection() {
    selectedTaskIds.value = [];
}

function openModal(key = null) {
    const dates = selectedDates.value.length
        ? selectedDates.value
        : key
          ? [key]
          : [];

    if (!dates.length) {
        toast.show("Vui lòng chọn ít nhất một ngày.");
        return;
    }

    modalDate.value = dates[0];

    const assignments = dates.flatMap(
        (date) => parent.taskCalendar.by_date?.[date] ?? [],
    );

    const templateMap = new Map(
        activeTaskTemplates.value.map((task) => [
            taskTemplateKey(task),
            task.id,
        ]),
    );

    const templateIds = new Set();

    assignments.forEach((assignment) => {
        const templateId = templateMap.get(
            taskTemplateKey(assignment.task),
        );

        if (templateId) {
            templateIds.add(templateId);
        }
    });

    selectedTaskIds.value = [...templateIds];

    selectedChildIds.value = [
        ...new Set(
            assignments.map((assignment) => assignment.user_id),
        ),
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

    try {
        await parent.saveTaskAssignmentChanges(
            {
                deleteIds,
                assignPayload,
            },
            calendarParams.value,
        );

        toast.show("Đã giao nhiệm vụ thành công.");
        selectedDates.value = [];
        quickSelectApplied.value = false;
        closeModal();
    } catch (error) {
        toast.show(error.message, "error");
    }
}

async function removeAssignment(assignment) {
    confirmDelete.assignment = assignment;
    confirmDelete.show = true;
}

async function confirmRemoveAssignment() {
    const assignment = confirmDelete.assignment;
    confirmDelete.assignment = null;

    if (!assignment) return;

    try {
        await parent.deleteTaskAssignment(assignment.id, calendarParams.value);
        toast.show("Đã xóa nhiệm vụ khỏi ngày này");
    } catch (error) {
        toast.show(error.message, "error");
    }
}

watch(currentMonth, loadCalendar);
watch(
    () => [quickRange.value.start, quickRange.value.end, quickRange.value.mode],
    () => {
        quickSelectApplied.value = false;
    },
);

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
                class="grid gap-3 border-b border-slate-200 bg-white p-4 lg:grid-cols-5 lg:items-end"
            >
                <label class="block">
                    <span
                        class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500"
                        >Từ ngày</span
                    >
                    <div class="relative">
                        <input
                            v-model="quickStartInput"
                            inputmode="numeric"
                            placeholder="dd/mm/yyyy"
                            class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 pr-12 text-sm font-semibold text-slate-900 outline-none transition focus:border-sky-500 focus:ring-3 focus:ring-sky-100"
                            @blur="syncQuickStartDate"
                        />
                        <input
                            ref="quickStartPicker"
                            :value="quickRange.start"
                            type="date"
                            class="pointer-events-none absolute inset-0 opacity-0"
                            tabindex="-1"
                            @input="pickQuickStartDate"
                        />
                        <button
                            type="button"
                            title="Chọn ngày"
                            class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-sky-700"
                            @click="openQuickStartPicker"
                        >
                            <CalendarDays class="h-4 w-4" />
                        </button>
                    </div>
                </label>
                <label class="block">
                    <span
                        class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500"
                        >Đến ngày</span
                    >
                    <div class="relative">
                        <input
                            v-model="quickEndInput"
                            inputmode="numeric"
                            placeholder="dd/mm/yyyy"
                            class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 pr-12 text-sm font-semibold text-slate-900 outline-none transition focus:border-sky-500 focus:ring-3 focus:ring-sky-100"
                            @blur="syncQuickEndDate"
                        />
                        <input
                            ref="quickEndPicker"
                            :value="quickRange.end"
                            type="date"
                            class="pointer-events-none absolute inset-0 opacity-0"
                            tabindex="-1"
                            @input="pickQuickEndDate"
                        />
                        <button
                            type="button"
                            title="Chọn ngày"
                            class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-sky-700"
                            @click="openQuickEndPicker"
                        >
                            <CalendarDays class="h-4 w-4" />
                        </button>
                    </div>
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
                <BaseButton
                    :variant="quickSelectApplied ? 'secondary' : 'primary'"
                    @click="toggleQuickSelect"
                >
                    <component :is="quickSelectApplied ? RotateCcw : Check" class="h-4 w-4" />
                    {{ quickSelectApplied ? "Làm mới lọc" : "Chọn tất cả" }}
                </BaseButton>
                <BaseButton
                    variant="primary"
                    :disabled="!selectedDates.length"
                    :class="!selectedDates.length ? '!cursor-not-allowed' : 'cursor-pointer'"
                    @click="openModal()"
                >
                    <Check class="mr-1.5 h-4 w-4" />
                    Giao nhiệm vụ
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
                        'cursor-not-allowed bg-slate-50 opacity-40':
                            !day.blank && !day.isInFilter,
                        'cursor-pointer bg-sky-50 ring-2 ring-inset ring-sky-200 hover:bg-sky-100':
                            !day.blank && day.isInFilter && day.isSelected,
                        'cursor-pointer bg-gray-200 text-slate-400 hover:bg-slate-100':
                            !day.blank &&
                            day.isInFilter &&
                            !day.isSelected &&
                            day.isLocked,
                        'cursor-pointer bg-amber-50/80 hover:bg-amber-100/70':
                            !day.blank &&
                            day.isInFilter &&
                            !day.isSelected &&
                            !day.isLocked &&
                            day.hasAssignments,
                        'cursor-pointer bg-white hover:bg-slate-50':
                            !day.blank &&
                            day.isInFilter &&
                            !day.isSelected &&
                            !day.isLocked &&
                            !day.hasAssignments,
                    }"
                    @click="!day.blank && day.isInFilter && openModal(day.key)"
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
                                :disabled="day.isLocked || !day.isInFilter"
                                @click.stop
                                @change="toggleDate(day.key)"
                            />
                        </div>

                        <div v-if="day.isInFilter" class="mt-3 grid gap-1">
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
                                        class="shrink-0 text-xs font-bold text-red-600 opacity-0 transition group-hover:opacity-100"
                                        @click.stop="removeAssignment(assignment)"
                                    >
                                        Xóa
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div
                            v-else
                            class="mt-3 text-center text-[11px] font-semibold text-slate-400"
                        >
                            Ngoài bộ lọc
                        </div>
                    </template>
                </div>
            </div>
        </section>

        <TaskAssignModal
            :open="!!modalDate"
            :date-summary="modalDateSummary"
            :date-preview="modalDatePreview"
            :date-count="modalDates.length"
            :has-locked-dates="modalHasLockedDates"
            :has-assignments="modalHasAssignments"
            :tasks="activeTaskTemplates"
            :selected-task-ids="selectedTaskIds"
            :children="parent.children"
            :selected-child-ids="selectedChildIds"
            @close="closeModal"
            @toggle-task="toggleTask"
            @toggle-child="toggleChild"
            @select-all-tasks="selectAllTasks"
            @clear-tasks="clearTaskSelection"
            @save="assignSelectedTasks"
        />

        <ConfirmDialog
            v-model="confirmDelete.show"
            title="Xóa nhiệm vụ"
            message="Bạn có chắc muốn xóa nhiệm vụ khỏi ngày này?"
            confirm-text="Xóa"
            cancel-text="Hủy"
            @confirm="confirmRemoveAssignment"
        />
    </ParentLayout>
</template>
