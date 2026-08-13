<script setup>
defineProps({ label: String, value: [String, Number], hint: String })
</script>

<template>
    <div class="admin-card p-5">
        <p class="admin-section-title">{{ label }}</p>
        <p class="mt-3 text-3xl font-bold text-slate-950">{{ value }}</p>
        <p v-if="hint" class="mt-2 text-sm font-semibold text-slate-500">{{ hint }}</p>
    </div>
</template>
