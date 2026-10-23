import { api } from './api.service'

export const authService = {
    login: async (payload) => refreshCsrf(await api('/api/auth/login', { method: 'POST', body: payload })),
    familyLogin: async (payload) => refreshCsrf(await api('/api/auth/family-login', { method: 'POST', body: payload })),
    logout: () => api('/api/auth/logout', { method: 'POST' }),
    me: () => api('/api/auth/me'),
}

function refreshCsrf(data) {
    if (data?.csrf_token) document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', data.csrf_token)
    return data
}
