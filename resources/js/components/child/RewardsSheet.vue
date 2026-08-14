<script setup>
import { Teleport, Transition } from "vue";

defineProps({
    open: { type: Boolean, default: false },
    points: { type: Number, default: 0 },
    rewards: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(["close", "redeem"]);
</script>

<template>
    <Teleport to="body">
        <Transition name="sheet-fade">
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/40 backdrop-blur-sm sm:items-center"
                @click.self="emit('close')"
            >
                <Transition name="sheet-slide" appear>
                    <div
                        v-if="open"
                        class="max-h-[85vh] w-full overflow-y-auto rounded-t-[2rem] bg-white p-5 shadow-2xl sm:max-w-lg sm:rounded-[2rem] sm:p-6"
                    >
                        <div class="mx-auto mb-4 h-1.5 w-12 rounded-full bg-slate-200 sm:hidden" />

                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-lg font-extrabold text-slate-900">
                                Phần thưởng
                            </h2>
                            <div class="flex items-center gap-2">
                                <span
                                    class="rounded-full bg-amber-100 px-3 py-1 text-xs font-extrabold text-amber-700"
                                    >{{ points }} ⭐</span
                                >
                                <button
                                    type="button"
                                    class="grid h-8 w-8 place-items-center rounded-full bg-slate-100 text-slate-500 transition hover:bg-slate-200"
                                    aria-label="Đóng"
                                    @click="emit('close')"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>

                        <div v-if="loading" class="mt-4 grid gap-3">
                            <div
                                v-for="n in 3"
                                :key="n"
                                class="h-16 animate-pulse rounded-2xl bg-slate-100"
                            />
                        </div>

                        <div v-else class="mt-4 grid gap-3">
                            <button
                                v-for="reward in rewards"
                                :key="reward.id"
                                type="button"
                                :disabled="points < reward.required_points"
                                class="rounded-2xl border p-4 text-left transition"
                                :class="
                                    points < reward.required_points
                                        ? 'cursor-not-allowed border-slate-100 bg-slate-50 opacity-60'
                                        : 'border-slate-200 bg-white hover:-translate-y-0.5 hover:border-amber-300 hover:bg-amber-50'
                                "
                                @click="emit('redeem', reward)"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <span class="font-bold text-slate-900"
                                        >{{ reward.icon }} {{ reward.title }}</span
                                    >
                                    <span
                                        class="shrink-0 rounded-full px-2.5 py-1 text-xs font-extrabold"
                                        :class="
                                            points < reward.required_points
                                                ? 'bg-slate-200 text-slate-500'
                                                : 'bg-amber-100 text-amber-700'
                                        "
                                        >{{ reward.required_points }} ⭐</span
                                    >
                                </div>
                                <p
                                    v-if="points < reward.required_points"
                                    class="mt-1 text-xs font-semibold text-slate-500"
                                >
                                    Còn thiếu {{ reward.required_points - points }} ⭐ nữa
                                </p>
                            </button>

                            <p
                                v-if="!rewards.length"
                                class="py-6 text-center text-sm font-medium text-slate-500"
                            >
                                Chưa có phần thưởng nào.
                            </p>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.sheet-fade-enter-active,
.sheet-fade-leave-active {
    transition: opacity 0.2s ease;
}
.sheet-fade-enter-from,
.sheet-fade-leave-to {
    opacity: 0;
}

.sheet-slide-enter-active,
.sheet-slide-leave-active {
    transition: transform 0.25s cubic-bezier(0.32, 0.72, 0, 1);
}
.sheet-slide-enter-from,
.sheet-slide-leave-to {
    transform: translateY(100%);
}
@media (min-width: 640px) {
    .sheet-slide-enter-from,
    .sheet-slide-leave-to {
        transform: translateY(16px) scale(0.98);
    }
}

@media (prefers-reduced-motion: reduce) {
    .sheet-fade-enter-active,
    .sheet-fade-leave-active,
    .sheet-slide-enter-active,
    .sheet-slide-leave-active {
        transition: none;
    }
}
</style>
