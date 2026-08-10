<script setup>
import { storeToRefs } from 'pinia'
import { useToastStore } from '../../stores/toast.store'

const toast = useToastStore()
const { visible, message, type } = storeToRefs(toast)
</script>

<template>
    <div
        v-if="visible"
        class="fixed bottom-5 left-1/2 z-50 min-w-[260px] -translate-x-1/2 rounded-lg px-4 py-3 text-sm font-semibold shadow-xl"
        :class="type === 'error' ? 'bg-red-600 text-white' : 'bg-slate-950 text-white'"
        role="status"
    >
        {{ message }}
    </div>
</template>
