import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth.store'
import HomePage from '../pages/Home/HomePage.vue'
import ChildDashboard from '../pages/Child/ChildDashboard.vue'
import ParentLogin from '../pages/Parent/ParentLogin.vue'
import ParentDashboard from '../pages/Parent/ParentDashboard.vue'
import ParentChildren from '../pages/Parent/ParentChildren.vue'
import ParentTaskCalendar from '../pages/Parent/ParentTaskCalendar.vue'
import ParentTaskHistory from '../pages/Parent/ParentTaskHistory.vue'
import ParentTasks from '../pages/Parent/ParentTasks.vue'
import ParentRewards from '../pages/Parent/ParentRewards.vue'
import ParentAchievements from '../pages/Parent/ParentAchievements.vue'
import ParentStatistics from '../pages/Parent/ParentStatistics.vue'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', component: HomePage },
        { path: '/child/:id', component: ChildDashboard, props: true },
        { path: '/parent/login', component: ParentLogin },
        { path: '/parent', component: ParentDashboard, meta: { requiresParent: true } },
        { path: '/parent/children', component: ParentChildren, meta: { requiresParent: true } },
        { path: '/parent/tasks', component: ParentTasks, meta: { requiresParent: true } },
        { path: '/parent/task-calendar', component: ParentTaskCalendar, meta: { requiresParent: true } },
        { path: '/parent/task-history', component: ParentTaskHistory, meta: { requiresParent: true } },
        { path: '/parent/schedules', redirect: '/parent/task-calendar' },
        { path: '/parent/rewards', component: ParentRewards, meta: { requiresParent: true } },
        { path: '/parent/achievements', component: ParentAchievements, meta: { requiresParent: true } },
        { path: '/parent/statistics', component: ParentStatistics, meta: { requiresParent: true } },
        { path: '/settings', redirect: '/parent' },
    ],
})

router.beforeEach(async (to) => {
    if (!to.meta.requiresParent) return true
    const auth = useAuthStore()
    await auth.fetchMe()
    return auth.isAuthenticated ? true : { path: '/parent/login', query: { redirect: to.fullPath } }
})

export default router
