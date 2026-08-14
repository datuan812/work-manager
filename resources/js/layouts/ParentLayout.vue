<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { BarChart3, CalendarCheck, ChartNoAxesColumnIncreasing, ChevronRight, Gift, History, LayoutDashboard, LogOut, Medal, MonitorSmartphone, NotebookTabs, PanelLeftClose, PanelLeftOpen, Users } from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth.store'
import { useParentStore } from '../stores/parent.store'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const parent = useParentStore()
const sidebarCollapsed = ref(false)
const links = [
    { to: '/parent', label: 'Tổng quan', icon: LayoutDashboard },
    { to: '/parent/statistics', label: 'Thống kê', icon: ChartNoAxesColumnIncreasing },
    { to: '/parent/children', label: 'Hồ sơ bé', icon: Users },
    { to: '/parent/tasks', label: 'Nhiệm vụ', icon: NotebookTabs },
    { to: '/parent/task-calendar', label: 'Giao nhiệm vụ', icon: CalendarCheck },
    { to: '/parent/rewards', label: 'Phần thưởng', icon: Gift },
    { to: '/parent/achievements', label: 'Thành tựu', icon: Medal },
    { to: '/parent/task-history', label: 'Lịch sử việc làm', icon: History },
    { to: '/parent/reward-history', label: 'Lịch sử đổi thưởng', icon: History },
]
const currentLink = computed(() => [...links].sort((a, b) => b.to.length - a.to.length).find((link) => route.path === link.to || route.path.startsWith(`${link.to}/`)) ?? links[0])
const userInitial = computed(() => (auth.user?.name || 'A').trim().slice(0, 1).toUpperCase())

async function logout() {
    await auth.logout()
    router.push('/parent/login')
}
</script>

<template>
    <main
        class="min-h-screen bg-slate-100 text-slate-950 lg:grid"
        :class="sidebarCollapsed ? 'lg:grid-cols-[88px_1fr]' : 'lg:grid-cols-[288px_1fr]'"
    >
        <div v-if="parent.loading" class="fixed left-0 right-0 top-0 z-50 h-1 overflow-hidden bg-sky-100">
            <div class="h-full w-1/3 animate-[loading-slide_1.1s_ease-in-out_infinite] rounded-r-full bg-sky-600"></div>
        </div>

        <aside class="border-b border-slate-200 bg-slate-950 px-4 py-4 text-white transition-all lg:sticky lg:top-0 lg:flex lg:h-screen lg:flex-col lg:border-b-0 lg:border-r lg:border-slate-900 lg:px-5 lg:py-5">
            <div class="flex min-h-12 items-center gap-3">
                <RouterLink to="/parent" class="flex min-w-0 flex-1 items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-sky-500 text-lg font-bold text-white shadow-sm shadow-sky-950/40">K</span>
                <span class="min-w-0" :class="sidebarCollapsed ? 'lg:hidden' : ''">
                    <span class="block text-lg font-bold leading-5">KidTask</span>
                    <span class="mt-1 block text-xs font-bold uppercase tracking-wide text-slate-400">Parent Console</span>
                </span>
                </RouterLink>

            </div>

            <nav class="mt-5 flex gap-1 overflow-x-auto pb-1 lg:mt-8 lg:grid lg:gap-1 lg:overflow-y-auto hide-scrollbar lg:pb-0">
                <RouterLink
                    v-for="{ to, label, icon } in links"
                    :key="to"
                    :to="to"
                    class="group flex min-h-11 shrink-0 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold text-slate-300 transition hover:bg-white/8 hover:text-white lg:w-full"
                    :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''"
                    active-class="bg-white text-slate-950 shadow-sm hover:bg-white hover:text-slate-950"
                    :title="label"
                >
                    <component :is="icon" class="h-4 w-4 shrink-0" />
                    <span :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ label }}</span>
                    <ChevronRight class="ml-auto hidden h-4 w-4 text-slate-400 transition group-[.router-link-active]:text-slate-500 lg:block" :class="sidebarCollapsed ? 'lg:hidden' : ''" />
                </RouterLink>
            </nav>

            <div class="mt-auto hidden border-t border-white/10 pt-5 lg:block">
                  <button
                    type="button"
                    class=" mb-3 flex min-h-10 w-full items-center justify-center gap-2 rounded-xl border border-white/10 text-sm font-bold text-slate-300 transition bg-white/20 hover:bg-white/10 hover:text-white"
                    :title="sidebarCollapsed ? 'Mở rộng sidebar' : 'Thu gọn sidebar'"
                    @click="sidebarCollapsed = !sidebarCollapsed"
                >
                    <PanelLeftOpen v-if="sidebarCollapsed" class="h-4 w-4" />
                    <PanelLeftClose v-else class="h-4 w-4" />
                    <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Thu gọn</span>
                </button>
                <div class="rounded-xl bg-white/6 p-3 ring-1 ring-white/10" :class="sidebarCollapsed ? 'lg:px-2' : ''">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-white text-sm font-bold text-slate-950">{{ userInitial }}</span>
                        <div class="min-w-0" :class="sidebarCollapsed ? 'lg:hidden' : ''">
                            <p class="truncate text-sm font-bold">{{ auth.user?.name || 'KidTask Admin' }}</p>
                            <p class="text-xs font-bold text-slate-400">Parent account</p>
                        </div>
                    </div>
                    <button class="mt-3 flex min-h-10 w-full items-center justify-center gap-2 rounded-xl border border-white/10 text-sm font-bold text-slate-300 transition hover:bg-white/10 hover:text-white" :title="sidebarCollapsed ? 'Đăng xuất' : ''" @click="logout">
                        <LogOut class="h-4 w-4" />
                        <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Đăng xuất</span>
                    </button>
                </div>
            </div>
        </aside>

        <section class="min-w-0">
            <header class="sticky top-0 z-30 flex min-h-16 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-500">
                        <BarChart3 class="h-4 w-4 text-sky-600" />
                        Parent Dashboard
                    </div>
                    <p class="mt-0.5 truncate text-sm font-bold text-slate-950 sm:text-base">{{ currentLink.label }}</p>
                </div>

                <div class="flex items-center gap-2">
                    <RouterLink to="/" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl px-3 text-sm font-bold text-white ring-1 ring-slate-200 transition bg-sky-600 hover:bg-sky-700 ">
                        <MonitorSmartphone class="h-4 w-4" />
                        <span class="hidden sm:inline">Chế độ Trẻ em</span>
                    </RouterLink>
                    <button class="inline-flex min-h-10 items-center justify-center rounded-xl px-3 text-sm font-bold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50 hover:text-slate-950 lg:hidden" @click="logout">
                        <LogOut class="h-4 w-4" />
                    </button>
                </div>
            </header>

            <div class="mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
                <slot />
            </div>
        </section>
    </main>
</template>
