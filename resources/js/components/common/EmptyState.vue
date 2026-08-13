<script setup>
defineProps({ title: String, message: String })
</script>

<template>
    <div class="soft-panel rounded-[1.5rem] border-dashed p-8 text-center">
        <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-sky-100 text-2xl">✦</div>
        <p class="mt-4 text-lg font-bold text-slate-900">{{ title }}</p>
        <p class="mx-auto mt-2 max-w-md text-sm font-semibold leading-6 text-slate-500">{{ message }}</p>
    </div>
</template>
