import { defineStore } from 'pinia'
import { childService } from '../services/child.service'

export const useChildStore = defineStore('child', {
    state: () => ({
        children: [],
        selectedChildId: Number(sessionStorage.getItem('selectedChildId')) || null,
        dashboard: null,
        rewards: null,
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
        async loadChildren() {
            return this.withLoading('children', async () => {
                this.children = await childService.list()
            })
        },
        selectChild(id) {
            this.selectedChildId = Number(id)
            sessionStorage.setItem('selectedChildId', String(id))
        },
        async loadToday(id = this.selectedChildId) {
            return this.withLoading('dashboard', async () => {
                this.dashboard = await childService.today(id)
            })
        },
        async toggleTask(dailyTask) {
            return this.withLoading('taskAction', async () => {
                const done = dailyTask.status === 'completed'
                const result = done ? await childService.uncomplete(dailyTask.id) : await childService.complete(dailyTask.id)
                await this.loadToday(this.selectedChildId)
                return result
            })
        },
        async loadRewards(id = this.selectedChildId) {
            return this.withLoading('rewards', async () => {
                this.rewards = await childService.rewards(id)
            })
        },
        async redeem(rewardId) {
            return this.withLoading('redeem', async () => {
                const result = await childService.redeem(this.selectedChildId, rewardId)
                await this.loadRewards(this.selectedChildId)
                await this.loadToday(this.selectedChildId)
                return result
            })
        },
    },
})
