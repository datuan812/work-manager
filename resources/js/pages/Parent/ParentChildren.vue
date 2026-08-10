<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import ParentLayout from '../../layouts/ParentLayout.vue'
import BaseButton from '../../components/common/BaseButton.vue'
import BaseInput from '../../components/common/BaseInput.vue'
import AvatarPhoto from '../../components/common/AvatarPhoto.vue'
import LoadingState from '../../components/common/LoadingState.vue'
import { useParentStore } from '../../stores/parent.store'
import { useToastStore } from '../../stores/toast.store'

const parent = useParentStore()
const toast = useToastStore()
const fileInput = ref(null)
const form = reactive({ id: null, name: '', avatar: '', avatar_file: null, avatar_preview: '', date_of_birth: '', is_active: true })
const previewAvatar = computed(() => form.avatar_preview || form.avatar)

function edit(child) {
    Object.assign(form, { id: child.id, name: child.name, avatar: child.avatar || '', avatar_file: null, avatar_preview: '', date_of_birth: child.date_of_birth || '', is_active: child.is_active })
    if (fileInput.value) fileInput.value.value = ''
}
function reset() {
    Object.assign(form, { id: null, name: '', avatar: '', avatar_file: null, avatar_preview: '', date_of_birth: '', is_active: true })
    if (fileInput.value) fileInput.value.value = ''
}
function chooseAvatar(event) {
    const file = event.target.files?.[0] || null
    form.avatar_file = file
    form.avatar_preview = file ? URL.createObjectURL(file) : ''
}
async function save() {
    await parent.saveChild(form)
    toast.show('Đã lưu child')
    reset()
}
async function remove(id) {
    await parent.deleteChild(id)
    toast.show('Đã xóa child')
}
onMounted(() => parent.loadChildren())
</script>

<template>
    <ParentLayout>
        <div>
            <p class="admin-section-title">Quản lý hồ sơ</p>
            <h1 class="mt-1 text-3xl font-black">Hồ sơ bé</h1>
        </div>

        <div class="mt-6 grid gap-5 lg:grid-cols-[380px_1fr]">
            <form class="admin-card p-5" @submit.prevent="save">
                <div class="mb-5">
                    <h2 class="text-lg font-black">{{ form.id ? 'Cập nhật hồ sơ' : 'Thêm bé mới' }}</h2>
                    <p class="mt-1 text-sm font-semibold text-slate-500">Ảnh đại diện sẽ hiển thị ở Child Mode.</p>
                </div>
                <BaseInput v-model="form.name" label="Name" />
                <div class="mt-4 flex items-center gap-4 rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-4">
                    <AvatarPhoto :src="previewAvatar" :name="form.name" size="lg" />
                    <label class="min-w-0 flex-1">
                        <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Ảnh đại diện</span>
                        <input ref="fileInput" type="file" accept="image/*" class="block w-full text-sm font-semibold text-slate-600 file:mr-3 file:rounded-full file:border-0 file:bg-slate-950 file:px-4 file:py-2 file:text-sm file:font-black file:text-white hover:file:bg-slate-800" @change="chooseAvatar" />
                    </label>
                </div>
                <div class="mt-4">
                    <BaseInput v-model="form.date_of_birth" label="Birth date" type="date" />
                </div>
                <label class="mt-4 flex min-h-11 items-center gap-2 text-sm font-bold"><input v-model="form.is_active" type="checkbox" /> Active</label>
                <div class="mt-5 flex gap-2"><BaseButton type="submit">{{ form.id ? 'Update' : 'Create' }}</BaseButton><BaseButton variant="secondary" @click="reset">Clear</BaseButton></div>
            </form>
            <LoadingState v-if="parent.loadingStates.children && !parent.children.length" title="Đang tải hồ sơ bé" message="Danh sách hồ sơ sẽ hiện sau khi tải xong." :rows="5" />

            <div v-else class="admin-card overflow-hidden">
                <table class="admin-table">
                    <thead><tr><th>Child</th><th>DOB</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="child in parent.children" :key="child.id">
                            <td>
                                <div class="flex items-center gap-3 font-black">
                                    <AvatarPhoto :src="child.avatar" :name="child.name" size="sm" />
                                    {{ child.name }}
                                </div>
                            </td>
                            <td class="text-slate-600">{{ child.date_of_birth || '—' }}</td>
                            <td><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ child.is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-right"><button class="font-black text-sky-700" @click="edit(child)">Edit</button><button class="ml-4 font-black text-red-700" @click="remove(child.id)">Delete</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </ParentLayout>
</template>
