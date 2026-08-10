<script setup>
defineProps({ value: { type: Number, default: 0 } })
</script>

<template>
    <div class="h-4 overflow-hidden rounded-full bg-white/70 ring-1 ring-slate-200/80" aria-hidden="true">
        <div
            class="h-full rounded-full bg-gradient-to-r from-emerald-400 via-sky-400 to-amber-300 shadow-[0_8px_18px_rgba(14,165,233,0.28)] transition-all duration-500"
            :style="{ width: `${Math.min(value, 100)}%` }"
        ></div>
    </div>
</template>
