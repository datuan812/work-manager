import { api } from './api.service'

function childFormData(payload, method = null) {
    const formData = new FormData()

    if (method) {
        formData.append('_method', method)
    }

    ;['name', 'date_of_birth'].forEach((key) => {
        if (payload[key] !== undefined && payload[key] !== null) {
            formData.append(key, payload[key])
        }
    })

    if (payload.is_active !== undefined && payload.is_active !== null) {
        formData.append('is_active', payload.is_active ? '1' : '0')
    }

    if (payload.avatar_file) {
        formData.append('avatar_file', payload.avatar_file)
    }

    return formData
}

export const parentService = {
    dashboard: () => api('/api/parent'),
    statistics: () => api('/api/parent/statistics'),
    children: () => api('/api/parent/children'),
    createChild: (payload) => api('/api/parent/children', { method: 'POST', body: childFormData(payload) }),
    updateChild: (id, payload) => api(`/api/parent/children/${id}`, { method: 'POST', body: childFormData(payload, 'PUT') }),
    deleteChild: (id) => api(`/api/parent/children/${id}`, { method: 'DELETE' }),
    tasks: () => api('/api/parent/tasks'),
    createTask: (payload) => api('/api/parent/tasks', { method: 'POST', body: payload }),
    updateTask: (id, payload) => api(`/api/parent/tasks/${id}`, { method: 'PUT', body: payload }),
    deleteTask: (id) => api(`/api/parent/tasks/${id}`, { method: 'DELETE' }),
    taskCalendar: (params) => api(`/api/parent/task-calendar?${new URLSearchParams(params)}`),
    taskHistory: (params) => api(`/api/parent/task-history?${new URLSearchParams(params)}`),
    rewardHistory: (params) => api(`/api/parent/reward-history?${new URLSearchParams(params)}`),
    assignTasks: (payload) => api('/api/parent/task-calendar', { method: 'POST', body: payload }),
    deleteTaskAssignment: (id) => api(`/api/parent/task-calendar/${id}`, { method: 'DELETE' }),
    rewards: () => api('/api/parent/rewards'),
    createReward: (payload) => api('/api/parent/rewards', { method: 'POST', body: payload }),
    updateReward: (id, payload) => api(`/api/parent/rewards/${id}`, { method: 'PUT', body: payload }),
    deleteReward: (id) => api(`/api/parent/rewards/${id}`, { method: 'DELETE' }),
    achievements: () => api('/api/parent/achievements'),
}
