<script setup>
import { useRouter, RouterLink } from 'vue-router'
import { useAuthStore } from '../stores/auth.store'
import { useParentStore } from '../stores/parent.store'

const router = useRouter()
const auth = useAuthStore()
const parent = useParentStore()
const links = [
    ['/parent', 'Tổng quan'],
    ['/parent/children', 'Hồ sơ bé'],
    ['/parent/tasks', 'Nhiệm vụ'],
    ['/parent/rewards', 'Phần thưởng'],
    ['/parent/achievements', 'Thành tựu'],
    ['/parent/statistics', 'Thống kê'],
]

async function logout() {
    await auth.logout()
    router.push('/parent/login')
}
</script>

<template>
    <main class="min-h-screen bg-slate-50 text-slate-950 lg:grid lg:grid-cols-[272px_1fr]">
        <div v-if="parent.loading" class="fixed left-0 right-0 top-0 z-50 h-1 overflow-hidden bg-sky-100">
            <div class="h-full w-1/3 animate-[loading-slide_1.1s_ease-in-out_infinite] rounded-r-full bg-sky-600"></div>
        </div>
        <aside class="border-b border-slate-200 bg-white px-4 py-5 lg:min-h-screen lg:border-b-0 lg:border-r">
            <RouterLink to="/parent" class="flex items-center gap-3 text-xl font-black">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-sky-600 text-white">K</span>
                <span>
                    KidTask
                    <span class="block text-xs font-black uppercase text-slate-400">Parent Console</span>
                </span>
            </RouterLink>
            <nav class="mt-8 grid gap-1">
                <RouterLink
                    v-for="[to, label] in links"
                    :key="to"
                    :to="to"
                    class="rounded-xl px-3 py-2.5 text-sm font-extrabold text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                    active-class="bg-sky-50 text-sky-700 ring-1 ring-sky-100 hover:bg-sky-50 hover:text-sky-700"
                >
                    {{ label }}
                </RouterLink>
            </nav>
            <button class="mt-8 min-h-11 w-full rounded-xl border border-slate-200 text-sm font-black text-slate-600 transition hover:bg-slate-50 hover:text-slate-950" @click="logout">Đăng xuất</button>
        </aside>
        <section class="min-w-0">
            <header class="flex min-h-16 items-center justify-between border-b border-slate-200 bg-white px-5">
                <div>
                    <p class="text-xs font-black uppercase text-slate-500">Parent Mode</p>
                    <p class="font-black">{{ auth.user?.name || 'KidTask Admin' }}</p>
                </div>
                <RouterLink to="/" class="rounded-xl px-3 py-2 text-sm font-black text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50">Child Mode</RouterLink>
            </header>
            <div class="mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
                <slot />
            </div>
        </section>
    </main>
</template>
