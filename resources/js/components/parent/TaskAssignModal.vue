<script setup>
import { computed } from "vue";
import BaseButton from "../common/BaseButton.vue";

const props = defineProps({
    open: { type: Boolean, default: false },
    dateSummary: { type: String, default: "" },
    datePreview: { type: String, default: "" },
    dateCount: { type: Number, default: 0 },
    hasLockedDates: { type: Boolean, default: false },
    hasAssignments: { type: Boolean, default: false },
    tasks: { type: Array, default: () => [] },
    selectedTaskIds: { type: Array, default: () => [] },
    children: { type: Array, default: () => [] },
    selectedChildIds: { type: Array, default: () => [] },
});

const emit = defineEmits([
    "close",
    "toggle-task",
    "toggle-child",
    "select-all-tasks",
    "clear-tasks",
    "save",
]);

const allTasksSelected = computed(
    () =>
        props.tasks.length > 0 &&
        props.selectedTaskIds.length === props.tasks.length,
);

const canSave = computed(
    () =>
        !props.hasLockedDates &&
        (props.hasAssignments ||
            (props.selectedTaskIds.length && props.selectedChildIds.length)),
);

function toggleSelectAllTasks() {
    emit(allTasksSelected.value ? "clear-tasks" : "select-all-tasks");
}
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 grid place-items-center bg-slate-950/40 p-4"
        @click.self="emit('close')"
    >
        <section
            class="max-h-[92vh] overflow-y-auto w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-2xl"
        >
            <div
                class="flex items-start justify-between gap-4 border-b border-slate-200 p-5"
            >
                <div>
                    <p class="admin-section-title">Giao nhiệm vụ</p>
                    <h2 class="mt-1 text-2xl font-bold">{{ dateCount }} ngày</h2>
                    <!-- <p class="mt-1 text-sm font-bold text-slate-600">
                        {{ dateSummary }}
                    </p> -->
                    <p
                        v-if="datePreview"
                        class="mt-1 text-xs font-semibold text-slate-400"
                    >
                        {{ datePreview }}
                    </p>
                    <p
                        v-if="hasLockedDates"
                        class="mt-2 rounded-lg bg-slate-100 px-3 py-2 text-xs font-bold text-slate-600"
                    >
                        Ngày đã đến hạn hoặc đã qua chỉ được xem, không thể
                        thay đổi.
                    </p>
                </div>
                <button
                    class="rounded-xl px-2 py-2 text-sm font-bold text-slate-500 bg-gray-100 hover:bg-slate-200"
                    type="button"
                    @click="emit('close')"
                >
                    ❌
                </button>
            </div>

            <div class="p-5">
                <div>
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="font-bold">Bé nhận nhiệm vụ</h3>
                        <span
                            class="rounded-lg bg-amber-50 px-2 py-1 text-xs font-bold text-amber-700"
                            >{{ selectedChildIds.length }} chọn</span
                        >
                    </div>
                    <div class="mt-3 grid grid-cols-4 gap-2">
                        <label
                            v-for="child in children"
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
                                :checked="selectedChildIds.includes(child.id)"
                                :disabled="hasLockedDates"
                                @change="emit('toggle-child', child.id)"
                            />
                            <span
                                class="min-w-0 flex-1 truncate text-sm font-bold text-slate-950"
                                >{{ child.name }}</span
                            >
                        </label>
                        <div
                            v-if="!children.length"
                            class="rounded-xl border border-dashed border-slate-200 p-4 text-sm font-semibold text-slate-500"
                        >
                            Chưa có hồ sơ bé.
                        </div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between gap-3 mt-5">
                        <h3 class="font-bold">Nhiệm vụ</h3>
                        <div class="flex items-center gap-2">
                            <span
                                class="rounded-lg bg-sky-50 px-2 py-1 text-xs font-bold text-sky-700"
                                >{{ selectedTaskIds.length }} chọn</span
                            >
                            <button
                                type="button"
                                class="rounded-lg px-2 py-1 text-xs font-bold text-sky-700 underline decoration-sky-300 underline-offset-2 transition hover:text-sky-900 disabled:cursor-not-allowed disabled:text-slate-400 disabled:no-underline"
                                :disabled="hasLockedDates || !tasks.length"
                                @click="toggleSelectAllTasks"
                            >
                                {{
                                    allTasksSelected
                                        ? "Bỏ chọn tất cả"
                                        : "Chọn tất cả"
                                }}
                            </button>
                        </div>
                    </div>
                    <div class="mt-3 grid grid-cols-3 gap-2">
                        <label
                            v-for="task in tasks"
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
                                :disabled="hasLockedDates"
                                @change="emit('toggle-task', task.id)"
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
                                        task.category?.name || "Không danh mục"
                                    }}
                                    · +{{ task.points }}</span
                                >
                            </span>
                        </label>
                        <div
                            v-if="!tasks.length"
                            class="rounded-xl border border-dashed border-slate-200 p-4 text-sm font-semibold text-slate-500"
                        >
                            Chưa có nhiệm vụ đang bật.
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="flex flex-col-reverse gap-2 border-t border-slate-200 p-5 sm:flex-row sm:justify-end"
            >
                <BaseButton variant="secondary" @click="emit('close')"
                    >Hủy</BaseButton
                >
                <BaseButton :disabled="!canSave" @click="emit('save')"
                    >Giao</BaseButton
                >
            </div>
        </section>
    </div>
</template>
