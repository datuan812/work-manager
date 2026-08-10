<script setup>
defineProps({
    variant: { type: String, default: 'primary' },
    type: { type: String, default: 'button' },
    disabled: Boolean,
})
</script>

<template>
    <button
        :type="type"
        :disabled="disabled"
        class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-black transition focus:outline-none focus:ring-3 focus:ring-sky-200 disabled:cursor-not-allowed disabled:opacity-55"
        :class="{
            'bg-sky-600 text-white shadow-sm hover:bg-sky-700': variant === 'primary',
            'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50': variant === 'secondary',
            'bg-red-600 text-white hover:bg-red-700': variant === 'danger',
            'bg-amber-400 text-slate-950 hover:bg-amber-300': variant === 'kid',
        }"
    >
        <slot />
    </button>
</template>
