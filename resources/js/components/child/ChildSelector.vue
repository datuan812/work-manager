<script setup>
import AvatarPhoto from '../common/AvatarPhoto.vue'

defineProps({ children: { type: Array, default: () => [] } })
defineEmits(['select'])
</script>

<template>
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <button
            v-for="child in children"
            :key="child.id"
            type="button"
            class="group relative min-h-48 overflow-hidden rounded-[1.75rem] border border-white/80 bg-white/80 p-5 text-left shadow-[0_18px_50px_rgba(15,23,42,0.08)] backdrop-blur transition hover:-translate-y-1 hover:border-amber-300 hover:shadow-[0_26px_70px_rgba(15,23,42,0.14)] focus:outline-none focus:ring-4 focus:ring-amber-200"
            @click="$emit('select', child)"
        >
            <span class="absolute -right-8 -top-10 h-32 w-32 rounded-full bg-amber-100/70 transition group-hover:scale-110"></span>
            <AvatarPhoto class="relative" :src="child.avatar" :name="child.name" size="lg" />
            <span class="relative mt-5 block text-2xl font-black">{{ child.name }}</span>
            <span class="relative mt-2 inline-flex rounded-full bg-slate-950 px-3 py-1 text-xs font-black uppercase text-white">Bắt đầu</span>
        </button>
    </div>
</template>
