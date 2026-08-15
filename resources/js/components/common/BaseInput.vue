<script setup>
defineProps({
    modelValue: [String, Number, Boolean],
    label: String,
    type: { type: String, default: 'text' },
    placeholder: { type: String, default: '' },
    error: { type: String, default: '' },
})
defineEmits(['update:modelValue'])
</script>

<template>
    <label class="block">
        <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">{{ label }}</span>
        <input
            :type="type"
            :value="modelValue"
            :placeholder="placeholder"
            :aria-invalid="!!error"
            class="min-h-11 w-full rounded-xl border bg-white px-3 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:ring-3"
            :class="error ? 'border-red-300 focus:border-red-500 focus:ring-red-100' : 'border-slate-200 focus:border-sky-500 focus:ring-sky-100'"
            @input="$emit('update:modelValue', type === 'number' ? Number($event.target.value) : $event.target.value)"
        />
        <span v-if="error" class="mt-1 block text-xs font-semibold text-red-600">{{ error }}</span>
    </label>
</template>
