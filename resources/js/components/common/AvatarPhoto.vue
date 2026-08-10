<script setup>
import { computed } from 'vue'

const props = defineProps({
    src: String,
    name: { type: String, default: '' },
    size: { type: String, default: 'md' },
})

const imageSrc = computed(() => {
    if (!props.src) return ''

    return /^(https?:|data:image\/|blob:|\/storage\/|\/images\/)/.test(props.src) ? props.src : ''
})

</script>

<template>
    <span
        class="inline-grid shrink-0 place-items-center overflow-hidden rounded-2xl bg-gradient-to-br from-sky-100 to-amber-100 font-black text-slate-700 shadow-inner ring-1 ring-white/80"
        :class="{
            'h-10 w-10 text-sm': size === 'sm',
            'h-16 w-16 text-xl': size === 'md',
            'h-20 w-20 text-2xl': size === 'lg',
            'h-28 w-28 text-4xl sm:h-36 sm:w-36': size === 'xl',
        }"
    >
        <img v-if="imageSrc" :src="imageSrc" :alt="name" class="h-full w-full object-cover" />
        <span v-else>{{ name?.slice(0, 1)?.toUpperCase() || 'T' }}</span>
    </span>
</template>
