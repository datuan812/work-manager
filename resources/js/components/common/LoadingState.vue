<script setup>
defineProps({
    title: { type: String, default: 'Đang tải dữ liệu' },
    message: { type: String, default: 'Vui lòng chờ trong giây lát.' },
    rows: { type: Number, default: 3 },
    variant: { type: String, default: 'admin' },
})
</script>

<template>
    <div
        class="animate-pulse"
        :class="variant === 'child' ? 'soft-panel rounded-[1.75rem] p-5' : 'admin-card p-5'"
        role="status"
        aria-live="polite"
    >
        <div class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-slate-100">
                <span class="h-5 w-5 animate-spin rounded-full border-2 border-slate-300 border-t-sky-500"></span>
            </span>
            <div>
                <p class="font-bold text-slate-900">{{ title }}</p>
                <p class="mt-1 text-sm font-semibold text-slate-500">{{ message }}</p>
            </div>
        </div>

        <div class="mt-5 grid gap-3">
            <div v-for="row in rows" :key="row" class="h-14 rounded-xl bg-slate-100"></div>
        </div>
    </div>
</template>
