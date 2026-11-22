<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { BarChart3, CalendarCheck, ChartNoAxesColumnIncreasing, ChevronRight, Gift, History, LayoutDashboard, LockKeyhole, LogOut, MonitorSmartphone, NotebookTabs, PanelLeftClose, PanelLeftOpen, Tags, Users } from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth.store'
import { useParentStore } from '../stores/parent.store'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const parent = useParentStore()
const sidebarCollapsed = ref(false)
const parentLinks = [
    { to: '/parent', label: 'Tổng quan', icon: LayoutDashboard },
    { to: '/parent/statistics', label: 'Thống kê', icon: ChartNoAxesColumnIncreasing },
    { to: '/parent/children', label: 'Hồ sơ bé', icon: Users },
    { to: '/parent/tasks', label: 'Nhiệm vụ', icon: NotebookTabs },
    { to: '/parent/task-calendar', label: 'Giao nhiệm vụ', icon: CalendarCheck },
    { to: '/parent/rewards', label: 'Phần thưởng', icon: Gift },
    { to: '/parent/task-history', label: 'Lịch sử việc làm', icon: History },
    { to: '/parent/reward-history', label: 'Lịch sử đổi thưởng', icon: History },
    { to: '/parent/access-pin', label: 'Mã khu vực phụ huynh', icon: LockKeyhole },
]
const links = computed(() => auth.user?.role === 'admin'
    ? [
        { to: '/admin/dashboard', label: 'Tổng quan vận hành', icon: LayoutDashboard },
        { to: '/admin', label: 'Người dùng và gia đình', icon: Users },
        { to: '/admin/categories', label: 'Danh mục nhiệm vụ', icon: Tags },
        { to: '/admin/task-templates', label: 'Thư viện nhiệm vụ mẫu', icon: NotebookTabs },
        { to: '/admin/audit-logs', label: 'Nhật ký thao tác', icon: History },
    ]
    : parentLinks)
const homeTarget = computed(() => auth.user?.role === 'admin' ? '/admin/dashboard' : '/parent')
const consoleTitle = computed(() => auth.user?.role === 'admin' ? 'Admin Console' : 'Parent Console')
const currentLink = computed(() => [...links.value].sort((a, b) => b.to.length - a.to.length).find((link) => route.path === link.to || route.path.startsWith(`${link.to}/`)) ?? links.value[0])
const userInitial = computed(() => (auth.user?.name || 'A').trim().slice(0, 1).toUpperCase())

async function logout() {
    await auth.logout()
    router.replace('/')
}
</script>

<template>
    <main
        class="console-layout min-h-screen bg-slate-100 text-slate-950 lg:grid"
        :class="{ 'sidebar-collapsed': sidebarCollapsed }"
    >
        <div v-if="parent.loading" class="fixed left-0 right-0 top-0 z-50 h-1 overflow-hidden bg-sky-100">
            <div class="h-full w-1/3 animate-[loading-slide_1.1s_ease-in-out_infinite] rounded-r-full bg-sky-600"></div>
        </div>

        <aside class="min-w-0 border-b border-slate-200 bg-slate-950 px-4 py-4 text-white lg:sticky lg:top-0 lg:flex lg:h-screen lg:flex-col lg:border-b-0 lg:border-r lg:border-slate-900 lg:px-5 lg:py-5">
            <div class="flex min-h-12 items-center gap-3">
                <RouterLink :to="homeTarget" class="flex min-w-0 flex-1 items-center gap-3">
                <img
                    src="/public/images/kidtask-favicon.png"
                    alt="KidTask"
                    class="h-11 w-11 shrink-0 rounded-xl object-cover shadow-sm shadow-sky-950/40"
                />
                <span class="sidebar-caption min-w-0 lg:overflow-hidden lg:whitespace-nowrap">
                    <span class="block text-lg font-bold leading-5">KidTask</span>
                    <span class="mt-1 block text-xs font-bold uppercase tracking-wide text-slate-400">{{ consoleTitle }}</span>
                </span>
                </RouterLink>

            </div>

            <nav class="mt-5 flex gap-1 overflow-x-auto pb-1 lg:mt-8 lg:grid lg:gap-1 lg:overflow-x-hidden lg:overflow-y-auto lg:pb-0">
                <RouterLink
                    v-for="{ to, label, icon } in links"
                    :key="to"
                    :to="to"
                    class="group flex min-h-11 shrink-0 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold text-slate-300 transition hover:bg-white/8 hover:text-white lg:w-full lg:min-w-0 lg:overflow-hidden lg:px-[15px]"
                    active-class="bg-white text-slate-950 shadow-sm hover:bg-white hover:text-slate-950"
                    :title="label"
                >
                    <component :is="icon" class="h-4 w-4 shrink-0" />
                    <span class="sidebar-caption lg:min-w-0 lg:flex-1 lg:truncate">{{ label }}</span>
                    <ChevronRight class="sidebar-caption ml-auto hidden h-4 w-4 shrink-0 text-slate-400 group-[.router-link-active]:text-slate-500 lg:block" aria-hidden="true" />
                </RouterLink>
            </nav>

            <div class="mt-auto hidden border-t border-white/10 pt-5 lg:block">
                <button
                    type="button"
                    class="sidebar-action mb-3 flex min-h-10 w-full items-center justify-center overflow-hidden rounded-xl border border-white/10 bg-white/20 text-sm font-bold text-slate-300 hover:bg-white/10 hover:text-white"
                    :title="sidebarCollapsed ? 'Mở rộng sidebar' : 'Thu gọn sidebar'"
                    :aria-label="sidebarCollapsed ? 'Mở rộng sidebar' : 'Thu gọn sidebar'"
                    :aria-expanded="!sidebarCollapsed"
                    @click="sidebarCollapsed = !sidebarCollapsed"
                >
                    <PanelLeftOpen v-if="sidebarCollapsed" class="h-4 w-4 shrink-0" aria-hidden="true" />
                    <PanelLeftClose v-else class="h-4 w-4 shrink-0" aria-hidden="true" />
                    <span class="sidebar-caption sidebar-action-label" aria-hidden="true">Thu gọn</span>
                </button>
                <div class="sidebar-account rounded-xl bg-white/6 p-3 ring-1 ring-white/10">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-white text-sm font-bold text-slate-950">{{ userInitial }}</span>
                        <div class="sidebar-caption min-w-0 overflow-hidden whitespace-nowrap">
                            <p class="truncate text-sm font-bold">{{ auth.user?.name || 'KidTask Admin' }}</p>
                            <p class="text-xs font-bold text-slate-400">
                                {{ auth.user?.role === 'admin' ? 'Admin account' : 'Parent account' }}
                            </p>
                        </div>
                    </div>
                    <button type="button" class="sidebar-action mt-3 flex min-h-10 w-full items-center justify-center overflow-hidden rounded-xl border border-white/10 text-sm font-bold text-slate-300 hover:bg-white/10 hover:text-white" :title="sidebarCollapsed ? 'Đăng xuất' : ''" aria-label="Đăng xuất" @click="logout">
                        <LogOut class="h-4 w-4 shrink-0" aria-hidden="true" />
                        <span class="sidebar-caption sidebar-action-label" aria-hidden="true">Đăng xuất</span>
                    </button>
                </div>
            </div>
        </aside>

        <section class="min-w-0">
            <header class="sticky top-0 z-30 flex min-h-16 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-500">
                        <BarChart3 class="h-4 w-4 text-sky-600" />
                        {{ auth.user?.role === 'admin' ? 'Admin Dashboard' : 'Parent Dashboard' }}
                    </div>
                    <p class="mt-0.5 truncate text-sm font-bold text-slate-950 sm:text-base">{{ currentLink.label }}</p>
                </div>

                <div class="flex items-center gap-2">
                    <RouterLink v-if="auth.user?.role !== 'admin'" to="/family" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl px-3 text-sm font-bold text-white ring-1 ring-slate-200 transition bg-sky-600 hover:bg-sky-700 ">
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

<style scoped>
@media (min-width: 64rem) {
    .console-layout {
        --sidebar-duration: 280ms;
        --sidebar-easing: cubic-bezier(0.22, 1, 0.36, 1);
        grid-template-columns: 288px minmax(0, 1fr);
        transition: grid-template-columns var(--sidebar-duration) var(--sidebar-easing);
    }

    .console-layout.sidebar-collapsed {
        grid-template-columns: 88px minmax(0, 1fr);
    }

    .sidebar-caption {
        transition:
            opacity 160ms ease-out 55ms,
            transform var(--sidebar-duration) var(--sidebar-easing);
    }

    .sidebar-collapsed .sidebar-caption {
        opacity: 0;
        transform: translateX(-6px);
        transition-delay: 0ms;
    }

    .sidebar-account {
        transition: padding-inline var(--sidebar-duration) var(--sidebar-easing);
    }

    .sidebar-collapsed .sidebar-account {
        padding-inline: 3px;
    }

    .sidebar-action {
        column-gap: 8px;
        transition:
            column-gap var(--sidebar-duration) var(--sidebar-easing),
            background-color 150ms ease,
            color 150ms ease;
    }

    .sidebar-action-label {
        max-width: 7rem;
        overflow: hidden;
        white-space: nowrap;
        transition:
            max-width var(--sidebar-duration) var(--sidebar-easing),
            opacity 160ms ease-out 55ms,
            transform var(--sidebar-duration) var(--sidebar-easing);
    }

    .sidebar-collapsed .sidebar-action {
        column-gap: 0;
    }

    .sidebar-collapsed .sidebar-action-label {
        max-width: 0;
    }
}

@media (prefers-reduced-motion: reduce) {
    .console-layout,
    .sidebar-caption,
    .sidebar-account,
    .sidebar-action,
    .sidebar-action-label,
    .sidebar-collapsed .sidebar-caption {
        transition: none;
    }
}
</style>
