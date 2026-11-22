<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../../services/api.service'
import { useAuthStore } from '../../stores/auth.store'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const token = computed(() => new URLSearchParams(route.hash.slice(1)).get('token') ?? '')
const validLink = computed(() => /^[0-9a-f]{64}$/.test(token.value))
const pin = ref('')
const confirmation = ref('')
const busy = ref(false)
const success = ref(false)
const error = ref('')

async function submit() {
    if (pin.value !== confirmation.value) {
        error.value = 'Hai mã PIN chưa trùng khớp.'
        return
    }
    busy.value = true
    error.value = ''
    try {
        await api('/api/auth/reset-parent-pin', { method: 'POST', body: { token: token.value, pin: pin.value, pin_confirmation: confirmation.value } })
        success.value = true
        pin.value = confirmation.value = ''
        auth.$patch({ user: null, checked: false })
        await router.replace({ path: route.path })
    } catch (failure) {
        error.value = failure.message
    } finally {
        busy.value = false
    }
}
</script>

<template>
    <main class="grid min-h-screen place-items-center bg-sky-50 px-4 py-10">
        <section class="admin-card w-full max-w-md p-6 sm:p-8">
            <h1 class="text-2xl font-black">Đặt lại PIN phụ huynh</h1>
            <template v-if="success">
                <p class="mt-4 text-sm text-emerald-700" role="status">Đã lưu PIN mới. Vui lòng đăng nhập lại để tiếp tục.</p>
                <RouterLink to="/" class="mt-5 inline-block rounded-xl bg-sky-600 px-4 py-2 text-sm font-bold text-white">Về trang đăng nhập</RouterLink>
            </template>
            <p v-else-if="!validLink" class="mt-4 text-sm text-red-700">Liên kết không hợp lệ. Hãy liên hệ bộ phận hỗ trợ KidTask để được cấp lại.</p>
            <form v-else class="mt-5 space-y-4" @submit.prevent="submit">
                <label class="block text-sm font-bold">PIN mới<input v-model="pin" class="home-input" type="password" inputmode="numeric" pattern="[0-9]{4,6}" maxlength="6" autocomplete="new-password" required /></label>
                <label class="block text-sm font-bold">Nhập lại PIN<input v-model="confirmation" class="home-input" type="password" inputmode="numeric" pattern="[0-9]{4,6}" maxlength="6" autocomplete="new-password" required /></label>
                <p class="text-xs text-slate-600">PIN có từ 4 đến 6 chữ số. Gia đình sẽ cần đăng nhập lại sau khi đổi.</p>
                <p v-if="error" role="alert" class="text-sm font-bold text-red-600">{{ error }}</p>
                <button class="w-full rounded-xl bg-sky-600 px-4 py-3 text-sm font-bold text-white disabled:opacity-50" :disabled="busy">{{ busy ? 'Đang lưu…' : 'Lưu PIN mới' }}</button>
            </form>
        </section>
    </main>
</template>
