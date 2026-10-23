<script setup>
import { onMounted, reactive, ref } from 'vue'
import ParentLayout from '../../layouts/ParentLayout.vue'
import ConfirmDialog from '../../components/common/ConfirmDialog.vue'
import { api } from '../../services/api.service'
import { LockKeyhole, LockKeyholeOpen, SquarePen, Trash } from 'lucide-vue-next'

const users = ref([])
const families = ref([])
const loading = ref(true)
const familyForm = ref({ name: '', parent_name: '', code: '' })
const familyError = ref('')
const confirmDelete = reactive({ show: false, family: null })
const editingFamily = reactive({ id: null, name: '', code: '', saving: false, error: '' })

async function load() {
    const [userData, familyData] = await Promise.all([
        api('/api/admin/users'),
        api('/api/admin/families'),
    ])
    users.value = userData.data ?? []
    families.value = familyData ?? []
    loading.value = false
}

async function createFamily() {
    familyError.value = ''
    try {
        const data = await api('/api/admin/families', { method: 'POST', body: familyForm.value })
        families.value.unshift(data.family)
        users.value.unshift({ ...data.parent, family: data.family })
        familyForm.value = { name: '', parent_name: '', code: '' }
    } catch (error) {
        familyError.value = error.message
    }
}

function editFamily(family) {
    Object.assign(editingFamily, { id: family.id, name: family.name, code: family.code, saving: false, error: '' })
}

function cancelEditFamily() {
    editingFamily.id = null
    editingFamily.error = ''
}

async function saveFamily() {
    editingFamily.error = ''
    editingFamily.saving = true
    try {
        const family = await api(`/api/admin/families/${editingFamily.id}`, {
            method: 'PATCH',
            body: { name: editingFamily.name, code: editingFamily.code },
        })
        const index = families.value.findIndex((item) => item.id === family.id)
        if (index !== -1) families.value[index] = family
        cancelEditFamily()
    } catch (error) {
        editingFamily.error = error.message
    } finally {
        editingFamily.saving = false
    }
}

async function toggle(user) {
    await api(`/api/admin/users/${user.id}`, { method: 'PATCH', body: { is_active: !user.is_active } })
    user.is_active = !user.is_active
}

async function changeRole(user) {
    await api(`/api/admin/users/${user.id}`, { method: 'PATCH', body: { role: user.role } })
}

async function changeFamily(user) {
    await api(`/api/admin/users/${user.id}`, { method: 'PATCH', body: { family_id: user.family_id || null } })
    await load()
}

function requestDeleteFamily(family) {
    confirmDelete.family = family
    confirmDelete.show = true
}

async function deleteFamily() {
    const family = confirmDelete.family
    confirmDelete.family = null
    if (!family) return

    await api(`/api/admin/families/${family.id}`, { method: 'DELETE' })
    await load()
}

onMounted(load)
</script>

<template>
    <ParentLayout>
        <div class="mx-auto max-w-6xl">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="admin-section-title">System administration</p>
                    <h1 class="mt-2 text-3xl font-black">Quản lý người dùng</h1>
                    <p class="mt-2 font-semibold text-slate-500">Quản lý toàn bộ parent và child của các gia đình trong hệ thống.</p>
                </div>
            </div>

            <section class="admin-card mt-8 p-5">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="admin-section-title">Families</p>
                        <h2 class="mt-1 text-xl font-black">Tạo mã gia đình mới</h2>
                        <p class="mt-1 text-sm font-semibold text-slate-500">Hệ thống tự tạo luôn một tài khoản parent để đăng nhập bằng mã này.</p>
                    </div>
                    <form class="flex flex-wrap gap-2" @submit.prevent="createFamily">
                        <input v-model="familyForm.name" class="home-input !mt-0 !min-h-11 !w-48" placeholder="Tên gia đình" required />
                        <input v-model="familyForm.parent_name" class="home-input !mt-0 !min-h-11 !w-48" placeholder="Tên phụ huynh" />
                        <input v-model="familyForm.code" class="home-input !mt-0 !min-h-11 !w-40 uppercase" placeholder="Mã tùy chọn" />
                        <button class="rounded-xl bg-sky-600 px-4 py-2 text-sm font-black text-white" type="submit">Tạo family</button>
                    </form>
                </div>
                <p v-if="familyError" class="mt-3 text-sm font-bold text-red-600">{{ familyError }}</p>
                <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div v-for="family in families" :key="family.id" class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                        <form v-if="editingFamily.id === family.id" class="mb-3 space-y-2" @submit.prevent="saveFamily">
                            <input v-model="editingFamily.name" class="home-input !mt-0 !min-h-10" placeholder="Tên gia đình" required />
                            <input v-model="editingFamily.code" class="home-input !mt-0 !min-h-10 uppercase" placeholder="Mã gia đình" required />
                            <p v-if="editingFamily.error" class="text-xs font-bold text-red-600">{{ editingFamily.error }}</p>
                            <div class="flex gap-2"><button class="rounded-lg bg-sky-600 px-3 py-2 text-xs font-black text-white" type="submit" :disabled="editingFamily.saving">Lưu</button><button class="rounded-lg px-3 py-2 text-xs font-black text-slate-600 ring-1 ring-slate-200" type="button" @click="cancelEditFamily">Hủy</button></div>
                        </form>
                        <div class="flex items-center justify-between gap-2">
                            <p class="font-bold">{{ family.name }}</p>
                            <div>
                                <button v-if="editingFamily.id !== family.id" class="float-right text-xs font-black text-sky-700" type="button" @click="editFamily(family)"><SquarePen /></button>
                                <button class="!text-xs font-black text-red-600" type="button" @click="requestDeleteFamily(family)"><Trash /></button>
                            </div>
                        </div>
                        <p class="mt-1 text-lg font-black tracking-wider text-sky-700">{{ family.code }}</p>
                        <p class="mt-1 text-xs font-semibold text-slate-500">{{ family.users_count }} tài khoản</p>
                    </div>
                </div>
            </section>

            <div class="admin-card mt-8 overflow-x-auto">
                <table class="admin-table">
                    <thead><tr><th>Người dùng</th><th>Vai trò</th><th>Gia đình</th><th>Trạng thái</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="user in users" :key="user.id">
                            <td><p class="font-bold text-slate-900">{{ user.name }}</p><p class="text-xs text-slate-500">{{ user.email || 'Tài khoản trẻ em' }}</p></td>
                            <td><select v-model="user.role" class="rounded-lg border border-slate-200 px-2 py-1 text-xs font-black uppercase" @change="changeRole(user)"><option value="admin">admin</option><option value="parent">parent</option><option value="child">child</option></select></td>
                            <td><select v-model="user.family_id" class="rounded-lg border border-slate-200 px-2 py-1 text-xs font-semibold" @change="changeFamily(user)"><option :value="null">Chưa gán</option><option v-for="family in families" :key="family.id" :value="family.id">{{ family.code }} · {{ family.name }}</option></select></td>
                            <td><span :class="user.is_active ? 'text-emerald-600' : 'text-red-500'" class="font-bold">{{ user.is_active ? 'Đang hoạt động' : 'Đã khóa' }}</span></td>
                            <td class="text-right">
                                <button
                                    class="text-sm font-black text-sky-700"
                                    @click="toggle(user)"
                                    :title="user.is_active ? 'Khóa tài khoản' : 'Mở khóa tài khoản'"
                                >
                                    <LockKeyhole v-if="user.is_active" class="w-6 h-6 inline-block" />
                                    <LockKeyholeOpen v-else class="w-6 h-6 inline-block" />
                                </button>
                            </td>                        
                        </tr>
                        <tr v-if="!loading && !users.length"><td colspan="5" class="py-12 text-center font-bold text-slate-500">Chưa có người dùng.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <ConfirmDialog
            v-model="confirmDelete.show"
            title="Xóa mã gia đình"
            :message="confirmDelete.family ? `Xóa family ${confirmDelete.family.code}? Parent, child và toàn bộ dữ liệu của family này sẽ bị xóa.` : ''"
            confirm-text="Xóa"
            cancel-text="Hủy"
            @confirm="deleteFamily"
            @cancel="confirmDelete.family = null"
        />
    </ParentLayout>
</template>
