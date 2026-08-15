import { api } from './api.service'

export const childService = {
    list: () => api('/api/children'),
    today: (childId) => api(`/api/children/${childId}/today`),
    dailyTasks: (childId, date) => api(`/api/children/${childId}/daily-tasks?${new URLSearchParams({ date })}`),
    saveDailyTaskDraft: (childId, payload) => api(`/api/children/${childId}/daily-tasks/draft`, { method: 'PATCH', body: payload }),
    submitDailyTasks: (childId, payload) => api(`/api/children/${childId}/daily-tasks/submit`, { method: 'POST', body: payload }),
    complete: (dailyTaskId) => api(`/api/daily-tasks/${dailyTaskId}/complete`, { method: 'PATCH' }),
    uncomplete: (dailyTaskId) => api(`/api/daily-tasks/${dailyTaskId}/uncomplete`, { method: 'PATCH' }),
    rewards: (childId) => api(`/api/children/${childId}/rewards`),
    rewardHistory: (childId) => api(`/api/children/${childId}/reward-history`),
    redeem: (childId, rewardId) => api(`/api/children/${childId}/rewards/${rewardId}/redeem`, { method: 'POST' }),
}
