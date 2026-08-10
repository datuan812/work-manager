<script setup>
defineProps({
    modelValue: [String, Number, Boolean],
    label: String,
    type: { type: String, default: 'text' },
})
defineEmits(['update:modelValue'])
</script>

<template>
    <label class="block">
        <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">{{ label }}</span>
        <input
            :type="type"
            :value="modelValue"
            class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-500 focus:ring-3 focus:ring-sky-100"
            @input="$emit('update:modelValue', type === 'number' ? Number($event.target.value) : $event.target.value)"
        />
    </label>
</template>
