import { api } from './api.service'

export const childService = {
    list: () => api('/api/children'),
    today: (childId) => api(`/api/children/${childId}/today`),
    complete: (dailyTaskId) => api(`/api/daily-tasks/${dailyTaskId}/complete`, { method: 'PATCH' }),
    uncomplete: (dailyTaskId) => api(`/api/daily-tasks/${dailyTaskId}/uncomplete`, { method: 'PATCH' }),
    rewards: (childId) => api(`/api/children/${childId}/rewards`),
    redeem: (childId, rewardId) => api(`/api/children/${childId}/rewards/${rewardId}/redeem`, { method: 'POST' }),
}
