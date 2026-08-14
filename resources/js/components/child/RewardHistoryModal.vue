<script setup>
import { computed } from "vue";
import { Clock3, Gift, Sparkles, X } from "lucide-vue-next";

const props = defineProps({
    open: { type: Boolean, default: false },
    history: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(["close"]);

const totalPoints = computed(() =>
    props.history.reduce((sum, item) => sum + Number(item.points_spent || 0), 0),
);

function formatDate(value) {
    if (!value) {
        return "Vừa xong";
    }

    return new Intl.DateTimeFormat("vi-VN", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    }).format(new Date(value));
}
</script>

<template>
    <Teleport to="body">
        <Transition name="history-fade">
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/40 backdrop-blur-sm sm:items-center"
                @click.self="emit('close')"
            >
                <Transition name="history-slide" appear>
                    <section
                        v-if="open"
                        class="max-h-[88vh] w-full overflow-hidden rounded-t-[2rem] bg-white shadow-2xl sm:max-w-xl sm:rounded-[2rem]"
                        aria-modal="true"
                        role="dialog"
                        aria-labelledby="reward-history-title"
                    >
                        <div class="mx-auto mt-3 h-1.5 w-12 rounded-full bg-slate-200 sm:hidden" />

                        <div class="border-b border-slate-100 p-5 sm:p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 text-sky-700">
                                        <span class="grid h-9 w-9 place-items-center rounded-full bg-sky-100">
                                            <Gift class="h-5 w-5" />
                                        </span>
                                        <p class="text-xs font-extrabold uppercase">
                                            Lịch sử đổi thưởng
                                        </p>
                                    </div>
                                    <h2
                                        id="reward-history-title"
                                        class="mt-3 text-2xl font-extrabold leading-tight text-slate-900"
                                    >
                                        Những món quà đã đổi
                                    </h2>
                                </div>

                                <button
                                    type="button"
                                    class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-slate-100 text-slate-500 transition hover:bg-slate-200"
                                    aria-label="Đóng lịch sử đổi thưởng"
                                    @click="emit('close')"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>

                            <div class="mt-5 grid grid-cols-2 gap-3">
                                <div class="rounded-2xl bg-amber-50 p-4 ring-1 ring-amber-100">
                                    <p class="text-xs font-bold uppercase text-slate-600">
                                        Đã đổi
                                    </p>
                                    <p class="mt-1 text-2xl font-extrabold text-slate-900">
                                        {{ history.length }}
                                    </p>
                                </div>
                                <div class="rounded-2xl bg-emerald-50 p-4 ring-1 ring-emerald-100">
                                    <p class="text-xs font-bold uppercase text-slate-600">
                                        Sao đã dùng
                                    </p>
                                    <p class="mt-1 text-2xl font-extrabold text-slate-900">
                                        {{ totalPoints }} ⭐
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="max-h-[52vh] overflow-y-auto p-5 sm:p-6">
                            <div v-if="loading" class="grid gap-3">
                                <div
                                    v-for="n in 4"
                                    :key="n"
                                    class="h-20 animate-pulse rounded-2xl bg-slate-100"
                                />
                            </div>

                            <div v-else-if="history.length" class="grid gap-3">
                                <article
                                    v-for="item in history"
                                    :key="item.id"
                                    class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm"
                                >
                                    <div class="flex items-start gap-3">
                                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-amber-50 text-2xl ring-1 ring-amber-100">
                                            {{ item.reward?.icon || "🎁" }}
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-start justify-between gap-3">
                                                <h3 class="font-extrabold leading-6 text-slate-900">
                                                    {{ item.reward?.title || "Phần thưởng đã xóa" }}
                                                </h3>
                                                <span class="shrink-0 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-extrabold text-amber-700">
                                                    -{{ item.points_spent }} ⭐
                                                </span>
                                            </div>
                                            <p
                                                v-if="item.reward?.description"
                                                class="mt-1 text-sm font-medium leading-6 text-slate-600"
                                            >
                                                {{ item.reward.description }}
                                            </p>
                                            <p class="mt-2 inline-flex items-center gap-1.5 text-xs font-bold text-slate-500">
                                                <Clock3 class="h-3.5 w-3.5" />
                                                {{ formatDate(item.redeemed_at) }}
                                            </p>
                                        </div>
                                    </div>
                                </article>
                            </div>

                            <div
                                v-else
                                class="rounded-2xl bg-sky-50 p-6 text-center ring-1 ring-sky-100"
                            >
                                <Sparkles class="mx-auto h-9 w-9 text-sky-500" />
                                <h3 class="mt-3 font-extrabold text-slate-900">
                                    Chưa đổi phần thưởng nào
                                </h3>
                                <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">
                                    Gom thêm sao rồi chọn món quà đầu tiên nhé.
                                </p>
                            </div>
                        </div>
                    </section>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.history-fade-enter-active,
.history-fade-leave-active {
    transition: opacity 0.2s ease;
}
.history-fade-enter-from,
.history-fade-leave-to {
    opacity: 0;
}

.history-slide-enter-active,
.history-slide-leave-active {
    transition: transform 0.25s cubic-bezier(0.32, 0.72, 0, 1);
}
.history-slide-enter-from,
.history-slide-leave-to {
    transform: translateY(100%);
}
@media (min-width: 640px) {
    .history-slide-enter-from,
    .history-slide-leave-to {
        transform: translateY(16px) scale(0.98);
    }
}

@media (prefers-reduced-motion: reduce) {
    .history-fade-enter-active,
    .history-fade-leave-active,
    .history-slide-enter-active,
    .history-slide-leave-active {
        transition: none;
    }
}
</style>
