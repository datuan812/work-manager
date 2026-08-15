<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import ParentLayout from '../../layouts/ParentLayout.vue'
import BaseButton from '../../components/common/BaseButton.vue'
import BaseInput from '../../components/common/BaseInput.vue'
import AvatarPhoto from '../../components/common/AvatarPhoto.vue'
import LoadingState from '../../components/common/LoadingState.vue'
import ConfirmDialog from "../../components/common/ConfirmDialog.vue";
import { useParentStore } from '../../stores/parent.store'
import { useToastStore } from '../../stores/toast.store'
import { CalendarDays, SquarePen, Trash2 } from 'lucide-vue-next'

const parent = useParentStore()
const toast = useToastStore()
const confirmDelete = reactive({ show: false, id: null });
const fileInput = ref(null)
const datePicker = ref(null)
const form = reactive({ id: null, name: '', avatar: '', avatar_file: null, avatar_preview: '', date_of_birth: '', is_active: true })
const dateOfBirthInput = ref('')
const errors = reactive({ name: '', date_of_birth: '', avatar_file: '' })
const previewAvatar = computed(() => form.avatar_preview || form.avatar)

function normalizeDate(value) {
    if (!value) return ''

    const date = String(value).trim()

    if (/^\d{4}-\d{2}-\d{2}/.test(date)) {
        return date.slice(0, 10)
    }

    const match = date.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/)

    if (match) {
        const [, day, month, year] = match
        if (!isValidDateParts(Number(day), Number(month), Number(year))) return ''

        return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`
    }

    return ''
}

function formatDateInput(value) {
    if (!value) return ''

    const [year, month, day] = String(value).slice(0, 10).split('-')
    if (!year || !month || !day) return ''

    return `${day.padStart(2, '0')}/${month.padStart(2, '0')}/${year}`
}

function isValidDateParts(day, month, year) {
    const date = new Date(year, month - 1, day)

    return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day
}

function clearErrors() {
    Object.assign(errors, { name: '', date_of_birth: '', avatar_file: '' })
}

function validateForm() {
    clearErrors()

    if (!form.name.trim()) {
        errors.name = 'Vui lòng nhập tên bé.'
    }

    const normalizedDate = normalizeDate(dateOfBirthInput.value)
    if (!dateOfBirthInput.value.trim()) {
        errors.date_of_birth = 'Vui lòng chọn hoặc nhập ngày sinh.'
    } else if (!normalizedDate) {
        errors.date_of_birth = 'Ngày sinh cần đúng định dạng dd/mm/yyyy.'
    } else if (new Date(`${normalizedDate}T00:00:00`) > new Date()) {
        errors.date_of_birth = 'Ngày sinh không được lớn hơn hôm nay.'
    } else {
        form.date_of_birth = normalizedDate
        dateOfBirthInput.value = formatDateInput(normalizedDate)
    }

    if (form.avatar_file && !form.avatar_file.type.startsWith('image/')) {
        errors.avatar_file = 'Ảnh đại diện phải là file hình ảnh.'
    } else if (form.avatar_file && form.avatar_file.size > 2 * 1024 * 1024) {
        errors.avatar_file = 'Ảnh đại diện không được vượt quá 2MB.'
    }

    return !Object.values(errors).some(Boolean)
}

function edit(child) {
    Object.assign(form, { id: child.id, name: child.name, avatar: child.avatar || '', avatar_file: null, avatar_preview: '', date_of_birth: normalizeDate(child.date_of_birth) || '', is_active: child.is_active })
    dateOfBirthInput.value = formatDateInput(form.date_of_birth)
    clearErrors()
    if (fileInput.value) fileInput.value.value = ''
}
function reset() {
    Object.assign(form, { id: null, name: '', avatar: '', avatar_file: null, avatar_preview: '', date_of_birth: '', is_active: true })
    dateOfBirthInput.value = ''
    clearErrors()
    if (fileInput.value) fileInput.value.value = ''
}
function chooseAvatar(event) {
    const file = event.target.files?.[0] || null
    form.avatar_file = file
    form.avatar_preview = file ? URL.createObjectURL(file) : ''
    errors.avatar_file = ''
}
function openDatePicker() {
    if (typeof datePicker.value?.showPicker === 'function') {
        datePicker.value.showPicker()
        return
    }

    datePicker.value?.click()
}
function chooseDateOfBirth(event) {
    form.date_of_birth = event.target.value
    dateOfBirthInput.value = formatDateInput(event.target.value)
    errors.date_of_birth = ''
}
function syncDateOfBirth() {
    const normalizedDate = normalizeDate(dateOfBirthInput.value)
    if (!normalizedDate) return

    form.date_of_birth = normalizedDate
    dateOfBirthInput.value = formatDateInput(normalizedDate)
    errors.date_of_birth = ''
}
async function save() {
    if (!validateForm()) {
        toast.show('Vui lòng kiểm tra lại thông tin hồ sơ bé.', 'error')
        return
    }

    const isEditing = Boolean(form.id)
    await parent.saveChild(form)
    toast.show(isEditing ? 'Cập nhật hồ sơ bé thành công.' : 'Thêm hồ sơ bé thành công.')
    reset()
}

function remove(id) {
    confirmDelete.id = id;
    confirmDelete.show = true;
}
async function confirmRemove() {
    const id = confirmDelete.id;
    confirmDelete.id = null;
    if (!id) return;

    await parent.deleteChild(id);
    toast.show("Đã xóa hồ sơ bé thành công.");
}

function formatDate(value) {
    if (!value) return '—'

    const [year, month, day] = String(value).slice(0, 10).split('-').map(Number)
    if (!year || !month || !day) return value

    return new Intl.DateTimeFormat('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(new Date(year, month - 1, day))
}
onMounted(() => parent.loadChildren())
</script>

<template>
    <ParentLayout>
        <div>
            <p class="admin-section-title">Quản lý hồ sơ</p>
            <h1 class="mt-1 text-3xl font-bold">Hồ sơ bé</h1>
        </div>

        <div class="mt-6 grid gap-5 lg:grid-cols-[380px_1fr]">
            <form class="admin-card p-5" @submit.prevent="save">
                <div class="mb-5">
                    <h2 class="text-lg font-bold">{{ form.id ? 'Cập nhật hồ sơ' : 'Thêm bé mới' }}</h2>
                </div>
                <BaseInput v-model="form.name" label="Tên" :error="errors.name" @update:modelValue="errors.name = ''" />
                <div class="mt-4 flex items-center gap-4 rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-4">
                    <AvatarPhoto :src="previewAvatar" :name="form.name" size="lg" />
                    <label class="min-w-0 flex-1">
                        <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Ảnh đại diện</span>
                        <input ref="fileInput" type="file" accept="image/*" class="block w-full text-sm font-semibold text-slate-600 file:mr-3 file:rounded-full file:border-0 file:bg-slate-950 file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-slate-800" @change="chooseAvatar" />
                        <span v-if="errors.avatar_file" class="mt-2 block text-xs font-semibold text-red-600">{{ errors.avatar_file }}</span>
                    </label>
                </div>
                <div class="mt-4">
                    <label class="block">
                        <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Ngày sinh</span>
                        <div class="relative">
                            <input
                                v-model="dateOfBirthInput"
                                inputmode="numeric"
                                placeholder="dd/mm/yyyy"
                                :aria-invalid="!!errors.date_of_birth"
                                class="min-h-11 w-full rounded-xl border bg-white px-3 pr-12 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:ring-3"
                                :class="errors.date_of_birth ? 'border-red-300 focus:border-red-500 focus:ring-red-100' : 'border-slate-200 focus:border-sky-500 focus:ring-sky-100'"
                                @blur="syncDateOfBirth"
                                @input="errors.date_of_birth = ''"
                            />
                            <input ref="datePicker" :value="form.date_of_birth" type="date" class="pointer-events-none absolute inset-0 opacity-0" tabindex="-1" @input="chooseDateOfBirth" />
                            <button type="button" title="Chọn ngày sinh" class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-sky-700" @click="openDatePicker">
                                <CalendarDays class="h-4 w-4" />
                            </button>
                        </div>
                        <span v-if="errors.date_of_birth" class="mt-1 block text-xs font-semibold text-red-600">{{ errors.date_of_birth }}</span>
                    </label>
                </div>
                <label class="mt-4 flex min-h-11 items-center gap-2 text-sm font-bold"><input v-model="form.is_active" type="checkbox" /> Hiển thị hồ sơ bé</label>
                <div class="mt-5 flex gap-2"><BaseButton type="submit">{{ form.id ? 'Cập nhật' : 'Tạo mới' }}</BaseButton><BaseButton variant="secondary" @click="reset">Làm mới</BaseButton></div>
            </form>
            <LoadingState v-if="parent.loadingStates.children && !parent.children.length" title="Đang tải hồ sơ bé" message="Danh sách hồ sơ sẽ hiện sau khi tải xong." :rows="5" />

            <div v-else class="admin-card overflow-hidden">
                <table class="admin-table">
                    <thead><tr><th>Tên</th><th>Ngày sinh</th><th>Trạng thái</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="child in parent.children" :key="child.id">
                            <td>
                                <div class="flex items-center gap-3 font-bold">
                                    <AvatarPhoto :src="child.avatar" :name="child.name" size="sm" />
                                    {{ child.name }}
                                </div>
                            </td>
                            <td class="text-slate-600">{{ formatDate(child.date_of_birth) }}</td>
                            <td><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ child.is_active ? 'Hoạt động' : 'Không hoạt động' }}</span></td>
                            <td class="text-right"><button title="Chỉnh sửa" class="font-bold text-sky-700" @click="edit(child)"><SquarePen /></button><button title="Xóa" class="ml-4 font-bold text-red-700" @click="remove(child.id)"><Trash2 /></button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <ConfirmDialog
            v-model="confirmDelete.show"
            title="Xóa hồ sơ bé"
            message="Bạn có chắc muốn xóa hồ sơ bé này? Hành động này không thể hoàn tác."
            confirm-text="Xóa"
            cancel-text="Hủy"
            @confirm="confirmRemove"
        />
    </ParentLayout>
</template>
