<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import ParentLayout from '../../layouts/ParentLayout.vue'
import BaseButton from '../../components/common/BaseButton.vue'
import BaseInput from '../../components/common/BaseInput.vue'
import AvatarPhoto from '../../components/common/AvatarPhoto.vue'
import LoadingState from '../../components/common/LoadingState.vue'
import ConfirmDialog from "../../components/common/ConfirmDialog.vue";
import { useParentStore } from '../../stores/parent.store'
import { useToastStore } from '../../stores/toast.store'
import { useAuthStore } from '../../stores/auth.store'
import { CalendarDays, Eye, EyeOff, SquarePen, Trash2 } from 'lucide-vue-next'

const parent = useParentStore()
const router = useRouter()
const auth = useAuthStore()
const toast = useToastStore()
const confirmDelete = reactive({ show: false, id: null });
const fileInput = ref(null)
const datePicker = ref(null)
const showPin = ref(false)
const form = reactive({ id: null, name: '', avatar: '', avatar_file: null, avatar_preview: '', date_of_birth: '', access_pin: '', is_active: true })
const dateOfBirthInput = ref('')
const errors = reactive({ name: '', access_pin: '', avatar_file: '', date_of_birth: '' })
const previewAvatar = computed(() => form.avatar_preview || form.avatar)
const parentFamilyCode = computed(() => auth.user?.family_code || `Family #${auth.user?.family_id || '—'}`)

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

function isValidDateParts(day, month, year) {
    const date = new Date(year, month - 1, day)

    return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day
}

function formatDateInput(value) {
    if (!value) return ''

    const [year, month, day] = String(value).slice(0, 10).split('-')
    if (!year || !month || !day) return ''

    return `${day.padStart(2, '0')}/${month.padStart(2, '0')}/${year}`
}

function clearErrors() {
    Object.assign(errors, { name: '', access_pin: '', avatar_file: '', date_of_birth: '' })
}

function validateForm() {
    clearErrors()

    if (!form.name.trim()) {
        errors.name = 'Vui lòng nhập tên bé.'
    }

    if (dateOfBirthInput.value.trim() && !form.date_of_birth) {
        errors.date_of_birth = 'Vui lòng nhập ngày sinh theo dạng dd/mm/yyyy.'
    } else if (form.date_of_birth && new Date(`${form.date_of_birth}T00:00:00`) > new Date()) {
        errors.date_of_birth = 'Ngày sinh không được lớn hơn hôm nay.'
    }

    if (!form.id && !/^\d{4,6}$/.test(form.access_pin)) {
        errors.access_pin = 'Mã PIN cần có 4-6 số.'
    } else if (form.id && form.access_pin && !/^\d{4,6}$/.test(form.access_pin)) {
        errors.access_pin = 'Mã PIN mới cần có 4-6 số.'
    }

    if (form.avatar_file && !form.avatar_file.type.startsWith('image/')) {
        errors.avatar_file = 'Ảnh đại diện phải là file hình ảnh.'
    } else if (form.avatar_file && form.avatar_file.size > 2 * 1024 * 1024) {
        errors.avatar_file = 'Ảnh đại diện không được vượt quá 2MB.'
    }

    return !Object.values(errors).some(Boolean)
}

function edit(child) {
    Object.assign(form, { id: child.id, name: child.name, avatar: child.avatar || '', avatar_file: null, avatar_preview: '', date_of_birth: normalizeDate(child.date_of_birth) || '', access_pin: '', is_active: child.is_active })
    dateOfBirthInput.value = formatDateInput(form.date_of_birth)
    showPin.value = false
    clearErrors()
    if (fileInput.value) fileInput.value.value = ''
}
function openChild(child) {
    router.push(`/child/${child.id}`)
}
function reset() {
    Object.assign(form, { id: null, name: '', avatar: '', avatar_file: null, avatar_preview: '', date_of_birth: '', access_pin: '', is_active: true })
    dateOfBirthInput.value = ''
    showPin.value = false
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
    const inputValue = dateOfBirthInput.value.trim()
    const normalizedDate = normalizeDate(inputValue)
    form.date_of_birth = normalizedDate
    dateOfBirthInput.value = normalizedDate ? formatDateInput(normalizedDate) : inputValue
    errors.date_of_birth = !normalizedDate && inputValue
        ? 'Vui lòng nhập ngày sinh theo dạng dd/mm/yyyy.'
        : ''
}
async function save() {
    syncDateOfBirth()

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
                <div class="mt-4">
                    <label class="block">
                        <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">
                            {{ form.id ? 'Đổi mã PIN' : 'Mã PIN cho bé' }}
                        </span>
                        <div class="relative">
                            <input
                                v-model="form.access_pin"
                                :type="showPin ? 'text' : 'password'"
                                :placeholder="form.id ? 'Nhập mã PIN' : '4-6 số'"
                                inputmode="numeric"
                                maxlength="6"
                                :aria-invalid="!!errors.access_pin"
                                class="min-h-11 w-full rounded-xl border bg-white px-3 pr-12 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:ring-3"
                                :class="errors.access_pin ? 'border-red-300 focus:border-red-500 focus:ring-red-100' : 'border-slate-200 focus:border-sky-500 focus:ring-sky-100'"
                                @input="errors.access_pin = ''"
                            />
                            <button
                                type="button"
                                :title="showPin ? 'Ẩn mã PIN' : 'Hiện mã PIN'"
                                class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-sky-700"
                                @click="showPin = !showPin"
                            >
                                <EyeOff v-if="showPin" class="h-4 w-4" />
                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <span v-if="errors.access_pin" class="mt-1 block text-xs font-semibold text-red-600">{{ errors.access_pin }}</span>
                    </label>
                </div>
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
                                class="min-h-11 w-full rounded-xl border bg-white px-3 pr-12 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-500 focus:ring-3 focus:ring-sky-100"
                                :class="errors.date_of_birth ? 'border-red-300 focus:border-red-500 focus:ring-red-100' : 'border-slate-200'"
                                @blur="syncDateOfBirth"
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
                    <thead><tr><th>Tên bé</th><th>Ngày sinh</th><th>Trạng thái</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="child in parent.children" :key="child.id">
                            <td>
                                <div class="flex items-center gap-3 font-bold">
                                    <AvatarPhoto :src="child.avatar" :name="child.name" size="sm" />
                                    {{ child.name }}
                                </div>
                            </td>
                            <td class="text-slate-600">{{ formatDate(child.date_of_birth) }}</td>
                            <td><span class="rounded-full bg-green-300 px-3 py-1 text-xs font-bold text-slate-600">{{ child.is_active ? 'Hoạt động' : 'Không hoạt động' }}</span></td>
                            <td class="text-right"><button v-if="child.is_active" title="Mở khu vực nhiệm vụ" class="font-bold text-emerald-700" @click="openChild(child)"><Eye /></button><button title="Chỉnh sửa" class="ml-4 font-bold text-sky-700" @click="edit(child)"><SquarePen /></button><button title="Xóa" class="ml-4 font-bold text-red-700" @click="remove(child.id)"><Trash2 /></button></td>
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
