import { api } from './api.service'

export const authService = {
    login: (payload) => api('/api/auth/login', { method: 'POST', body: payload }),
    logout: () => api('/api/auth/logout', { method: 'POST' }),
    me: () => api('/api/auth/me'),
}
