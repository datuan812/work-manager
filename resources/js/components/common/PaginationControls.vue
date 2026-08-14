<script setup>
import { computed } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

const props = defineProps({
    meta: {
        type: Object,
        default: () => ({
            current_page: 1,
            last_page: 1,
            per_page: 25,
            total: 0,
            from: 0,
            to: 0,
        }),
    },
    loading: { type: Boolean, default: false },
})

const emit = defineEmits(['change'])

const currentPage = computed(() => Number(props.meta?.current_page || 1))
const lastPage = computed(() => Number(props.meta?.last_page || 1))
const total = computed(() => Number(props.meta?.total || 0))
const from = computed(() => Number(props.meta?.from || 0))
const to = computed(() => Number(props.meta?.to || 0))
const pages = computed(() => {
    const page = currentPage.value
    const last = lastPage.value
    const start = Math.max(1, page - 2)
    const end = Math.min(last, page + 2)

    return Array.from({ length: end - start + 1 }, (_, index) => start + index)
})

function goTo(page) {
    if (props.loading || page < 1 || page > lastPage.value || page === currentPage.value) {
        return
    }

    emit('change', page)
}
</script>

<template>
    <div
        v-if="total > 0"
        class="flex flex-col gap-3 border-t border-slate-100 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <p class="text-sm font-semibold text-slate-500">
            Hiển thị <span class="font-bold text-slate-900">{{ from }}</span>-<span class="font-bold text-slate-900">{{ to }}</span>
            trong <span class="font-bold text-slate-900">{{ total }}</span> dòng
        </p>

        <div class="flex items-center gap-1">
            <button
                type="button"
                class="grid h-9 w-9 place-items-center rounded-xl text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="loading || currentPage <= 1"
                aria-label="Trang trước"
                @click="goTo(currentPage - 1)"
            >
                <ChevronLeft class="h-4 w-4" />
            </button>

            <button
                v-if="pages[0] > 1"
                type="button"
                class="hidden h-9 min-w-9 rounded-xl px-3 text-sm font-bold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50 sm:inline-flex sm:items-center sm:justify-center"
                :disabled="loading"
                @click="goTo(1)"
            >
                1
            </button>
            <span v-if="pages[0] > 2" class="hidden px-2 text-sm font-bold text-slate-400 sm:inline">...</span>

            <button
                v-for="page in pages"
                :key="page"
                type="button"
                class="h-9 min-w-9 rounded-xl px-3 text-sm font-bold ring-1 transition"
                :class="
                    page === currentPage
                        ? 'bg-sky-600 text-white ring-sky-600'
                        : 'text-slate-600 ring-slate-200 hover:bg-slate-50'
                "
                :disabled="loading || page === currentPage"
                @click="goTo(page)"
            >
                {{ page }}
            </button>

            <span v-if="pages[pages.length - 1] < lastPage - 1" class="hidden px-2 text-sm font-bold text-slate-400 sm:inline">...</span>
            <button
                v-if="pages[pages.length - 1] < lastPage"
                type="button"
                class="hidden h-9 min-w-9 rounded-xl px-3 text-sm font-bold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50 sm:inline-flex sm:items-center sm:justify-center"
                :disabled="loading"
                @click="goTo(lastPage)"
            >
                {{ lastPage }}
            </button>

            <button
                type="button"
                class="grid h-9 w-9 place-items-center rounded-xl text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="loading || currentPage >= lastPage"
                aria-label="Trang sau"
                @click="goTo(currentPage + 1)"
            >
                <ChevronRight class="h-4 w-4" />
            </button>
        </div>
    </div>
</template>
