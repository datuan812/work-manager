<script setup>
import { storeToRefs } from 'pinia'
import { useToastStore } from '../../stores/toast.store'
import { CheckCircle2, XCircle } from 'lucide-vue-next'

const toast = useToastStore()
const { visible, message, type } = storeToRefs(toast)
</script>

<template>
    <div
        v-if="visible"
        class="fixed left-1/2 top-5 z-50 flex min-w-[280px] max-w-[calc(100vw-2rem)] -translate-x-1/2 items-start gap-3 rounded-2xl border bg-white px-4 py-3 text-sm font-bold text-slate-800 shadow-2xl shadow-slate-900/15"
        :class="type === 'error' ? 'border-red-100 ring-1 ring-red-100' : 'border-emerald-100 ring-1 ring-emerald-100'"
        role="status"
    >
        <span
            class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
            :class="type === 'error' ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600'"
        >
            <XCircle v-if="type === 'error'" class="h-5 w-5" />
            <CheckCircle2 v-else class="h-5 w-5" />
        </span>
        <span class="pt-1 leading-5">{{ message }}</span>
    </div>
</template>
