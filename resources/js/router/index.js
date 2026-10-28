import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth.store'
import HomePage from '../pages/Home/HomePage.vue'
import ChildDashboard from '../pages/Child/ChildDashboard.vue'
import ParentDashboard from '../pages/Parent/ParentDashboard.vue'
import ParentChildren from '../pages/Parent/ParentChildren.vue'
import ParentTaskCalendar from '../pages/Parent/ParentTaskCalendar.vue'
import ParentTaskHistory from '../pages/Parent/ParentTaskHistory.vue'
import ParentTasks from '../pages/Parent/ParentTasks.vue'
import ParentRewards from '../pages/Parent/ParentRewards.vue'
import ParentRewardHistory from '../pages/Parent/ParentRewardHistory.vue'
import ParentStatistics from '../pages/Parent/ParentStatistics.vue'
import ParentAccessPin from '../pages/Parent/ParentAccessPin.vue'
import AdminUsers from '../pages/Admin/AdminUsers.vue'
import AdminCategories from '../pages/Admin/AdminCategories.vue'
import FamilyHome from '../pages/Family/FamilyHome.vue'
import { parentService } from '../services/parent.service'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', component: HomePage },
        { path: '/child/:id', component: ChildDashboard, props: true },
        { path: '/parent', component: ParentDashboard, meta: { requiresParent: true, requiresParentAccess: true } },
        { path: '/parent/children', component: ParentChildren, meta: { requiresParent: true, requiresParentAccess: true } },
        { path: '/parent/tasks', component: ParentTasks, meta: { requiresParent: true, requiresParentAccess: true } },
        { path: '/parent/task-calendar', component: ParentTaskCalendar, meta: { requiresParent: true, requiresParentAccess: true } },
        { path: '/parent/task-history', component: ParentTaskHistory, meta: { requiresParent: true, requiresParentAccess: true } },
        { path: '/parent/schedules', redirect: '/parent/task-calendar' },
        { path: '/parent/rewards', component: ParentRewards, meta: { requiresParent: true, requiresParentAccess: true } },
        { path: '/parent/reward-history', component: ParentRewardHistory, meta: { requiresParent: true, requiresParentAccess: true } },
        { path: '/parent/statistics', component: ParentStatistics, meta: { requiresParent: true, requiresParentAccess: true } },
        { path: '/parent/access-pin', component: ParentAccessPin, meta: { requiresParent: true } },
        { path: '/admin', component: AdminUsers, meta: { requiresAdmin: true } },
        { path: '/admin/categories', component: AdminCategories, meta: { requiresAdmin: true } },
        { path: '/family', component: FamilyHome, meta: { requiresParent: true } },
        { path: '/settings', redirect: '/parent' },
    ],
})

router.beforeEach(async (to) => {
    if (!to.meta.requiresParent && !to.meta.requiresAdmin) return true
    const auth = useAuthStore()
    await auth.fetchMe()
    if (!auth.isAuthenticated) return { path: '/', query: { redirect: to.fullPath } }
    if (to.meta.requiresAdmin && auth.user.role !== 'admin') return '/parent'
    if (to.meta.requiresParent && auth.user.role !== 'parent') return '/admin'
    if (to.meta.requiresParentAccess) {
        const access = await parentService.accessPinStatus()
        if (!access.has_pin) return '/parent/access-pin'
        if (!access.enabled) return true
        if (!access.granted) return '/family'
    }
    return true
})

export default router
