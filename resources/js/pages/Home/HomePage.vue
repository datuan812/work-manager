<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import ChildLayout from '../../layouts/ChildLayout.vue'
import ChildSelector from '../../components/child/ChildSelector.vue'
import LoadingState from '../../components/common/LoadingState.vue'
import { useChildStore } from '../../stores/child.store'

const router = useRouter()
const childStore = useChildStore()

onMounted(() => childStore.loadChildren())

function selectChild(child) {
    childStore.selectChild(child.id)
    router.push(`/child/${child.id}`)
}
</script>

<template>
    <ChildLayout>
        <section class="mx-auto flex min-h-screen w-full max-w-6xl flex-col justify-center px-4 py-8 sm:px-6">
            <div class="mb-8 grid gap-6 lg:grid-cols-[1.05fr_0.95fr] lg:items-end">
                <div>
                    <p class="inline-flex rounded-full bg-white/80 px-4 py-2 text-xs font-black uppercase tracking-wide text-sky-700 shadow-sm ring-1 ring-sky-100">KidTask</p>
                    <h1 class="mt-5 max-w-3xl text-4xl font-black leading-[1.02] text-slate-950 sm:text-5xl">
                        Hôm nay ai sẽ chinh phục nhiệm vụ?
                    </h1>
                    <p class="mt-4 max-w-xl text-base font-semibold leading-7 text-slate-600">
                        Chọn hồ sơ của con để xem nhiệm vụ, tích điểm và mở khóa phần thưởng trong ngày.
                    </p>
                </div>
                <div class="premium-panel rounded-[2rem] p-5">
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div class="rounded-2xl bg-sky-50 p-4">
                            <p class="text-2xl font-black">3</p>
                            <p class="mt-1 text-xs font-black uppercase text-slate-500">Mục tiêu</p>
                        </div>
                        <div class="rounded-2xl bg-amber-50 p-4">
                            <p class="text-2xl font-black">⭐</p>
                            <p class="mt-1 text-xs font-black uppercase text-slate-500">Tích điểm</p>
                        </div>
                        <div class="rounded-2xl bg-emerald-50 p-4">
                            <p class="text-2xl font-black">✓</p>
                            <p class="mt-1 text-xs font-black uppercase text-slate-500">Hoàn tất</p>
                        </div>
                    </div>
                </div>
            </div>
            <LoadingState v-if="childStore.loadingStates.children && !childStore.children.length" title="Đang tải hồ sơ bé" message="KidTask đang chuẩn bị danh sách để con chọn." variant="child" />
            <ChildSelector v-else :children="childStore.children" @select="selectChild" />
            <router-link to="/parent/login" class="mx-auto mt-8 inline-flex min-h-12 items-center rounded-full bg-slate-950 px-5 text-sm font-black text-white shadow-lg shadow-slate-300 transition hover:-translate-y-0.5 hover:bg-slate-800">
                🔐 Khu vực phụ huynh
            </router-link>
        </section>
    </ChildLayout>
</template>
