<script setup>
import { onMounted, ref } from 'vue'
import ConfirmDialog from '../common/ConfirmDialog.vue'
import { api } from '../../services/api.service'

const props = defineProps({ family: { type: Object, required: true } })
const emit = defineEmits(['updated', 'close'])
const parents = ref([])
const parentId = ref('')
const verified = ref(false)
const loadingParents = ref(true)
const busy = ref(false)
const confirmation = ref(false)
const choice = ref(null)
const error = ref('')
const status = ref('')
const resetLink = ref('')
const actions = {
    rotate: { endpoint: 'rotate-code', label: 'Cấp lại mã gia đình', message: 'Mã cũ sẽ hết hiệu lực và các thiết bị trong gia đình sẽ phải đăng nhập lại.' },
    revoke: { endpoint: 'revoke-sessions', label: 'Thu hồi tất cả phiên', message: 'Các thiết bị trong gia đình sẽ phải đăng nhập lại. Mã gia đình hiện tại vẫn dùng được.' },
}
const url = (path) => '/api/admin/families/' + props.family.id + '/' + path

onMounted(async () => {
    try {
        parents.value = await api(url('parents'))
        if (parents.value.length === 1) parentId.value = parents.value[0].id
    } catch (failure) {
        error.value = failure.message
    } finally {
        loadingParents.value = false
    }
})

function ask(action) {
    choice.value = actions[action]
    confirmation.value = true
}

async function runSupportAction() {
    if (!choice.value) return
    const action = choice.value
    busy.value = true
    error.value = status.value = resetLink.value = ''
    try {
        const response = await api(url(action.endpoint), { method: 'POST' })
        if (response) emit('updated', response)
        status.value = response ? 'Đã cấp mã mới và thu hồi các phiên cũ.' : 'Đã thu hồi các phiên và liên kết đặt lại đang chờ.'
    } catch (failure) {
        error.value = failure.message
    } finally {
        busy.value = false
        choice.value = null
    }
}

async function issueReset() {
    busy.value = true
    error.value = status.value = resetLink.value = ''
    try {
        const data = await api(url('pin-resets'), {
            method: 'POST', body: { user_id: parentId.value, identity_verified: verified.value },
        })
        resetLink.value = new URL('/reset-parent-pin#token=' + data.token, window.location.origin).href
        verified.value = false
        status.value = 'Liên kết dùng một lần, có hiệu lực 30 phút. Gửi qua kênh liên lạc đã xác minh.'
    } catch (failure) {
        error.value = failure.message
    } finally {
        busy.value = false
    }
}

async function copyLink() {
    try {
        await navigator.clipboard.writeText(resetLink.value)
        status.value = 'Đã sao chép. Gửi liên kết qua kênh liên lạc đã xác minh trong vòng 30 phút.'
    } catch {
        status.value = 'Hãy chọn và sao chép liên kết trong ô bên dưới.'
    }
}
</script>

<template>
    <section class="mt-5 rounded-2xl border border-sky-200 bg-sky-50/60 p-5">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-lg font-black">Hỗ trợ {{ family.name }}</h2>
            <button type="button" class="text-sm font-bold text-slate-600" :disabled="busy" @click="$emit('close')">Đóng</button>
        </div>
        <div class="mt-4 flex flex-wrap gap-3">
            <button type="button" class="rounded-xl border border-sky-200 bg-white px-4 py-2 text-sm font-bold text-sky-700 disabled:opacity-50" :disabled="busy" @click="ask('rotate')">Cấp lại mã gia đình</button>
            <button type="button" class="rounded-xl border border-red-200 bg-white px-4 py-2 text-sm font-bold text-red-700 disabled:opacity-50" :disabled="busy" @click="ask('revoke')">Thu hồi tất cả phiên</button>
        </div>
        <form class="mt-5 grid gap-3 border-t border-sky-200 pt-4" @submit.prevent="issueReset">
            <h3 class="font-black">Đặt lại PIN phụ huynh</h3>
            <p v-if="loadingParents" class="text-sm text-slate-600">Đang tải danh sách phụ huynh…</p>
            <p v-else-if="!parents.length" class="text-sm text-slate-600">Không có tài khoản phụ huynh đang hoạt động.</p>
            <template v-else>
                <label class="max-w-sm text-sm font-bold">
                    Tài khoản cần hỗ trợ
                    <select v-model="parentId" class="home-input" required>
                        <option value="" disabled>Chọn phụ huynh</option>
                        <option v-for="parent in parents" :key="parent.id" :value="parent.id">{{ parent.name }} (#{{ parent.id }})</option>
                    </select>
                </label>
                <label class="flex items-start gap-2 text-sm font-semibold"><input v-model="verified" class="mt-1" type="checkbox" required /> Tôi đã xác minh người yêu cầu có quyền với tài khoản này.</label>
                <button type="submit" class="justify-self-start rounded-xl bg-sky-600 px-4 py-2 text-sm font-bold text-white disabled:opacity-50" :disabled="busy || !verified || !parentId">Tạo liên kết đặt lại</button>
            </template>
        </form>
        <p v-if="error" class="mt-4 text-sm font-bold text-red-700" role="alert">{{ error }}</p>
        <p v-if="status" class="mt-4 text-sm font-semibold text-emerald-700" role="status">{{ status }}</p>
        <label v-if="resetLink" class="mt-3 block text-sm font-bold">
            Liên kết đặt lại
            <input :value="resetLink" class="home-input" readonly @focus="$event.target.select()" />
        </label>
        <button v-if="resetLink" type="button" class="mt-2 text-sm font-bold text-sky-700" @click="copyLink">Sao chép liên kết</button>
        <ConfirmDialog v-model="confirmation" :title="choice?.label" :message="choice?.message" confirm-text="Xác nhận" @confirm="runSupportAction" />
    </section>
</template>
