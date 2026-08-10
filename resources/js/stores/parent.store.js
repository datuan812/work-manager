import { defineStore } from 'pinia'
import { parentService } from '../services/parent.service'

export const useParentStore = defineStore('parent', {
    state: () => ({ dashboard: null, children: [], tasks: [], categories: [], rewards: [], achievements: [], statistics: null, loading: false, loadingStates: {} }),
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
