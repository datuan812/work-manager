import { defineStore } from 'pinia'
import { parentService } from '../services/parent.service'

export const useParentStore = defineStore('parent', {
    state: () => ({
        dashboard: null,
        children: [],
        tasks: [],
        categories: [],
        taskCalendar: { assignments: [], by_date: {} },
        taskHistory: { items: [], summary: { total: 0, completed: 0, pending: 0, incomplete: 0 }, filters: {}, meta: { current_page: 1, last_page: 1, per_page: 25, total: 0, from: 0, to: 0 } },
        rewardHistory: { items: [], summary: { total: 0, points_spent: 0, children: 0 }, filters: {}, meta: { current_page: 1, last_page: 1, per_page: 25, total: 0, from: 0, to: 0 } },
        rewards: [],
        achievements: [],
        statistics: null,
        loading: false,
        loadingStates: {},
    }),
    actions: {
        async withLoading(key, callback) {
            this.loading = true
            this.loadingStates = { ...this.loadingStates, [key]: true }
            try {
                return await callback()
            } finally {
                const next = { ...this.loadingStates, [key]: false }
                this.loadingStates = next
                this.loading = Object.values(next).some(Boolean)
            }
        },
        async loadDashboard() {
            return this.withLoading('dashboard', async () => {
                this.dashboard = await parentService.dashboard()
            })
        },
        async loadChildren() {
            return this.withLoading('children', async () => {
                this.children = await parentService.children()
            })
        },
        async saveChild(payload) {
            return this.withLoading('saveChild', async () => {
                payload.id ? await parentService.updateChild(payload.id, payload) : await parentService.createChild(payload)
                await this.loadChildren()
                await this.loadDashboard()
            })
        },
        async deleteChild(id) {
            return this.withLoading('deleteChild', async () => {
                await parentService.deleteChild(id)
                await this.loadChildren()
                await this.loadDashboard()
            })
        },
        async loadTasks() {
            return this.withLoading('tasks', async () => {
                const data = await parentService.tasks()
                this.tasks = data.tasks
                this.categories = data.categories
            })
        },
        async saveTask(payload) {
            return this.withLoading('saveTask', async () => {
                payload.id ? await parentService.updateTask(payload.id, payload) : await parentService.createTask(payload)
                await this.loadTasks()
            })
        },
        async deleteTask(id) {
            return this.withLoading('deleteTask', async () => {
                await parentService.deleteTask(id)
                await this.loadTasks()
            })
        },
        async loadTaskCalendar(params) {
            return this.withLoading('taskCalendar', async () => {
                this.taskCalendar = await parentService.taskCalendar(params)
            })
        },
        async loadTaskHistory(params) {
            return this.withLoading('taskHistory', async () => {
                this.taskHistory = await parentService.taskHistory(params)
            })
        },
        async loadRewardHistory(params) {
            return this.withLoading('rewardHistory', async () => {
                this.rewardHistory = await parentService.rewardHistory(params)
            })
        },
        async assignTasks(payload, calendarParams = null) {
            return this.withLoading('assignTasks', async () => {
                await parentService.assignTasks(payload)
                if (calendarParams) {
                    await this.loadTaskCalendar(calendarParams)
                }
            })
        },
        async deleteTaskAssignment(id, calendarParams = null) {
            return this.withLoading('deleteTaskAssignment', async () => {
                await parentService.deleteTaskAssignment(id)
                if (calendarParams) {
                    await this.loadTaskCalendar(calendarParams)
                }
            })
        },
        async saveTaskAssignmentChanges({ deleteIds = [], assignPayload = null }, calendarParams = null) {
            return this.withLoading('saveTaskAssignmentChanges', async () => {
                await Promise.all(deleteIds.map((id) => parentService.deleteTaskAssignment(id)))

                if (assignPayload?.task_ids?.length && assignPayload?.user_ids?.length && assignPayload?.dates?.length) {
                    await parentService.assignTasks(assignPayload)
                }

                if (calendarParams) {
                    await this.loadTaskCalendar(calendarParams)
                }
            })
        },
        async loadRewards() {
            return this.withLoading('rewards', async () => {
                this.rewards = await parentService.rewards()
            })
        },
        async saveReward(payload) {
            return this.withLoading('saveReward', async () => {
                payload.id ? await parentService.updateReward(payload.id, payload) : await parentService.createReward(payload)
                await this.loadRewards()
            })
        },
        async deleteReward(id) {
            return this.withLoading('deleteReward', async () => {
                await parentService.deleteReward(id)
                await this.loadRewards()
            })
        },
        async loadAchievements() {
            return this.withLoading('achievements', async () => {
                this.achievements = await parentService.achievements()
            })
        },
        async loadStatistics() {
            return this.withLoading('statistics', async () => {
                this.statistics = await parentService.statistics()
            })
        },
    },
})
