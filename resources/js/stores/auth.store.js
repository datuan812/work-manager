import { defineStore } from 'pinia'
import { authService } from '../services/auth.service'

export const useAuthStore = defineStore('auth', {
    state: () => ({ user: null, checked: false, loading: false }),
    getters: {
        isAuthenticated: (state) => Boolean(state.user),
    },
    actions: {
        async fetchMe() {
            if (this.checked) return this.user
            this.loading = true
            try {
                const data = await authService.me()
                this.user = data.user
            } catch {
                this.user = null
            } finally {
                this.checked = true
                this.loading = false
            }
            return this.user
        },
        async login(payload) {
            const data = await authService.login(payload)
            this.user = data.user
            this.checked = true
        },
        async logout() {
            await authService.logout()
            this.user = null
            this.checked = true
        },
    },
})
