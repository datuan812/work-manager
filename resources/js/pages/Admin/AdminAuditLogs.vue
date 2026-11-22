<script setup>
import { onMounted, ref } from 'vue'
import ParentLayout from '../../layouts/ParentLayout.vue'
import PaginationControls from '../../components/common/PaginationControls.vue'
import { api } from '../../services/api.service'

const actions = {
    'family.created': 'Tạo gia đình',
    'family.updated': 'Đổi tên gia đình',
    'family.deleted': 'Xóa gia đình',
    'family.code_rotated': 'Cấp lại mã gia đình',
    'family.sessions_revoked': 'Thu hồi phiên gia đình',
    'parent.pin_reset_issued': 'Tạo liên kết đặt lại PIN',
    'parent.pin_reset_completed': 'Hoàn tất đặt lại PIN',
    'user.updated': 'Sửa thông tin người dùng',
    'user.role_changed': 'Đổi vai trò',
    'user.family_changed': 'Đổi gia đình',
    'user.locked': 'Khóa tài khoản',
    'user.unlocked': 'Mở khóa tài khoản',
    'category.created': 'Tạo danh mục',
    'category.updated': 'Cập nhật danh mục',
    'category.deleted': 'Xóa danh mục',
    'template.created': 'Tạo nhiệm vụ mẫu',
    'template.updated': 'Cập nhật nhiệm vụ mẫu',
    'template.deleted': 'Xóa nhiệm vụ mẫu',
}
const action = ref('')
const result = ref(null)
const loading = ref(false)
const error = ref('')
const dateFormatter = new Intl.DateTimeFormat('vi-VN', { dateStyle: 'short', timeStyle: 'short', timeZone: 'Asia/Ho_Chi_Minh' })

async function load(page = 1) {
    loading.value = true
    error.value = ''
    try {
        const query = new URLSearchParams({ page, action: action.value })
        result.value = await api('/api/admin/audit-logs?' + query)
    } catch (failure) {
        error.value = failure.message
    } finally {
        loading.value = false
    }
}

onMounted(() => load())
</script>

<template>
    <ParentLayout>
        <div class="mx-auto max-w-6xl">
            <h1 class="text-3xl font-black">Nhật ký thao tác</h1>
            <label class="mt-5 block max-w-xs text-sm font-bold">
                Lọc theo thao tác
                <select v-model="action" class="home-input" :disabled="loading" @change="load()">
                    <option value="">Tất cả</option>
                    <option v-for="(label, key) in actions" :key="key" :value="key">{{ label }}</option>
                </select>
            </label>
            <p v-if="error" class="mt-4 text-sm font-bold text-red-600" role="alert">{{ error }}</p>
            <p v-if="loading" class="mt-4 text-sm text-slate-500">Đang tải nhật ký…</p>
            <div v-if="result" class="admin-card mt-5 overflow-x-auto">
                <table class="admin-table">
                    <thead><tr><th>Thời điểm</th><th>Người thực hiện</th><th>Thao tác</th><th>Đối tượng</th></tr></thead>
                    <tbody>
                        <tr v-for="entry in result.data" :key="entry.id">
                            <td class="whitespace-nowrap">{{ dateFormatter.format(new Date(entry.created_at)) }}</td>
                            <td>{{ entry.actor_name }}<span v-if="entry.actor_id" class="ml-1 text-xs text-slate-500">#{{ entry.actor_id }}</span></td>
                            <td class="font-semibold">
                                {{ actions[entry.action] || entry.action }}
                                <p v-if="entry.details?.field === 'role' || entry.details?.field === 'family_id'" class="text-xs font-normal text-slate-500">{{ entry.details.before ?? '—' }} → {{ entry.details.after ?? '—' }}</p>
                            </td>
                            <td>{{ entry.subject_name }} <span class="text-xs text-slate-500">#{{ entry.subject_id }}</span></td>
                        </tr>
                        <tr v-if="!result.data.length"><td colspan="4" class="py-8 text-center text-slate-500">Chưa có thao tác nào.</td></tr>
                    </tbody>
                </table>
            </div>
            <PaginationControls v-if="result" class="mt-4" :meta="result" :loading="loading" @change="load" />
        </div>
    </ParentLayout>
</template>
