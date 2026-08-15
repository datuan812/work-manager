<script setup>
import { Teleport, Transition } from "vue";
import { AlertTriangle } from "lucide-vue-next";

defineProps({
    modelValue: { type: Boolean, default: false },
    title: { type: String, default: "Xác nhận" },
    message: { type: String, default: "Bạn có chắc chắn muốn thực hiện hành động này?" },
    confirmText: { type: String, default: "Xóa" },
    cancelText: { type: String, default: "Hủy" },
    variant: { type: String, default: "danger" }, // danger | default
});

const emit = defineEmits(["update:modelValue", "confirm", "cancel"]);

function close() {
    emit("update:modelValue", false);
    emit("cancel");
}
function confirm() {
    emit("update:modelValue", false);
    emit("confirm");
}
</script>

<template>
    <Teleport to="body">
        <Transition name="fade">
            <div
                v-if="modelValue"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4 backdrop-blur-sm"
                @click.self="close"
            >
                <Transition name="pop" appear>
                    <div
                        v-if="modelValue"
                        class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl"
                        role="alertdialog"
                        aria-modal="true"
                    >
                        <div class="flex items-start gap-4">
                            <div
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-full"
                                :class="variant === 'danger' ? 'bg-red-100 text-red-600' : 'bg-sky-100 text-sky-600'"
                            >
                                <AlertTriangle class="h-5 w-5" />
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-base font-bold text-slate-900">{{ title }}</h3>
                                <p class="mt-1 text-sm font-medium text-slate-500">{{ message }}</p>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end gap-2">
                            <button
                                type="button"
                                class="min-h-10 rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-600 transition hover:bg-slate-50"
                                @click="close"
                            >
                                {{ cancelText }}
                            </button>
                            <button
                                type="button"
                                class="min-h-10 rounded-xl px-4 text-sm font-bold text-white transition"
                                :class="variant === 'danger' ? 'bg-red-600 hover:bg-red-700' : 'bg-sky-600 hover:bg-sky-700'"
                                @click="confirm"
                            >
                                {{ confirmText }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
.pop-enter-active {
    transition: all 0.18s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.pop-leave-active {
    transition: all 0.12s ease;
}
.pop-enter-from,
.pop-leave-to {
    opacity: 0;
    transform: scale(0.95) translateY(4px);
}
</style>
