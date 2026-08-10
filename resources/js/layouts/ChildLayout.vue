<script setup>
import { useChildStore } from '../stores/child.store'

const childStore = useChildStore()
</script>

<template>
    <main class="child-shell min-h-screen overflow-hidden text-slate-950">
        <div v-if="childStore.loading" class="fixed left-0 right-0 top-0 z-50 h-1 overflow-hidden bg-sky-100">
            <div class="h-full w-1/3 animate-[loading-slide_1.1s_ease-in-out_infinite] rounded-r-full bg-sky-500"></div>
        </div>
        <slot />
    </main>
</template>
