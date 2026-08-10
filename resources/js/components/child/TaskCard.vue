<script setup>
defineProps({ dailyTask: { type: Object, required: true }, busy: Boolean })
defineEmits(['toggle'])
</script>

<template>
    <button
        type="button"
        class="group grid min-h-40 w-full grid-cols-[1fr_auto] gap-4 overflow-hidden rounded-[1.5rem] border p-5 text-left shadow-[0_16px_46px_rgba(15,23,42,0.08)] transition hover:-translate-y-0.5 hover:shadow-[0_24px_62px_rgba(15,23,42,0.13)] focus:outline-none focus:ring-4 focus:ring-sky-200 disabled:opacity-70"
        :class="dailyTask.status === 'completed' ? 'border-emerald-200 bg-emerald-50/90' : 'border-white/80 bg-white/[0.82] backdrop-blur'"
        :disabled="busy"
        @click="$emit('toggle', dailyTask)"
    >
        <span class="min-w-0">
            <span class="grid h-14 w-14 place-items-center rounded-2xl bg-sky-50 text-4xl shadow-inner group-hover:scale-105">
                {{ dailyTask.task.icon || dailyTask.task.category?.icon || '⭐' }}
            </span>
            <span class="mt-4 block text-xl font-black leading-tight text-slate-950">{{ dailyTask.task.title }}</span>
            <span class="mt-2 inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-black uppercase text-slate-500">
                {{ dailyTask.status === 'completed' ? 'Hoàn thành' : dailyTask.task.category?.name || 'Nhiệm vụ' }}
            </span>
        </span>
        <span class="flex flex-col items-end justify-between gap-4">
            <span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-black text-amber-800 shadow-sm">+{{ dailyTask.task.points }} ⭐</span>
            <span
                class="grid h-12 w-12 place-items-center rounded-full border-2 text-xl font-black transition"
                :class="dailyTask.status === 'completed' ? 'border-emerald-500 bg-emerald-500 text-white shadow-lg shadow-emerald-200' : 'border-slate-300 bg-white/70 text-transparent group-hover:border-sky-300'"
            >✓</span>
        </span>
    </button>
</template>
