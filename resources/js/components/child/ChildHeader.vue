<script setup>
defineProps({
    points: {
        type: Number,
        default: 0,
    },
    rewardsOpen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["change-child", "show-history", "toggle-rewards"]);
</script>

<template>
    <header
        class="fixed w-full top-0 z-40 border-b border-white/60 bg-white/70 backdrop-blur-lg"
    >
        <div
            class="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-3 sm:px-6"
        >
            <button
                type="button"
                class="inline-flex min-h-11 items-center gap-2 rounded-full bg-sky-100 px-3 text-sm font-extrabold text-sky-700 shadow-sm ring-1 ring-sky-200/70 transition hover:-translate-y-0.5 hover:bg-sky-200/80 sm:px-4"
                @click="emit('change-child')"
            >
                <svg
                    class="h-4 w-4 shrink-0"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="3"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M15 18l-6-6 6-6" />
                </svg>
                <span class="hidden sm:inline">Đổi bé</span>
            </button>

            <div class="flex items-center gap-2 sm:gap-3">
                <button
                    type="button"
                    class="inline-flex min-h-11 items-center gap-2 rounded-full bg-white/80 px-3 text-sm font-extrabold text-slate-600 shadow-sm ring-1 ring-slate-200/80 transition hover:-translate-y-0.5 hover:bg-slate-50 sm:px-4"
                    @click="emit('show-history')"
                >
                    <svg
                        class="h-4 w-4 shrink-0"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 7v5l3 3" />
                    </svg>
                    <span class="hidden sm:inline">Lịch sử đổi thưởng</span>
                </button>

                <button
                    type="button"
                    class="inline-flex min-h-11 items-center gap-2 rounded-full bg-amber-300 px-3 text-sm font-extrabold text-slate-950 shadow-[0_10px_24px_rgba(245,158,11,0.30)] ring-1 ring-amber-200/80 transition hover:-translate-y-0.5 hover:bg-amber-200 sm:px-4"
                    @click="emit('toggle-rewards')"
                >
                    <span
                        class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-white/70 text-sm shadow-inner"
                        >🎁</span
                    >
                    <span class="hidden sm:inline"> Đổi thưởng </span>
                    <span
                        class="rounded-full bg-slate-950 px-2 py-0.5 text-xs text-white"
                        >{{ points }} ⭐</span
                    >
                </button>
            </div>
        </div>
    </header>
</template>
