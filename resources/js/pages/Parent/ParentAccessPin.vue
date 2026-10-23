<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import ParentLayout from '../../layouts/ParentLayout.vue'
import { parentService } from '../../services/parent.service'

const router = useRouter()
const form = reactive({ pin: '', confirmation: '' })
const state = reactive({ hasPin: false, enabled: true, loading: false, toggling: false, error: '', success: '' })

async function load() {
    const data = await parentService.accessPinStatus()
    state.hasPin = data.has_pin
    state.enabled = data.enabled
}

async function toggleAccessPin() {
    state.error = ''
    state.success = ''
    state.toggling = true
    try {
        const data = await parentService.toggleAccessPin(!state.enabled)
        state.enabled = data.enabled
        state.success = state.enabled ? 'Đã bật yêu cầu mã.' : 'Đã tắt yêu cầu mã.'
    } catch (error) {
        state.error = error.message
    } finally {
        state.toggling = false
    }
}

async function save() {
    state.error = ''
    state.success = ''
    if (!/^\d{4,6}$/.test(form.pin)) {
        state.error = 'Mã cần có từ 4 đến 6 chữ số.'
        return
    }
    if (form.pin !== form.confirmation) {
        state.error = 'Hai mã chưa trùng khớp.'
        return
    }

    state.loading = true
    try {
        await parentService.setAccessPin(form.pin)
        state.hasPin = true
        form.pin = ''
        form.confirmation = ''
        state.success = 'Đã lưu mã khu vực phụ huynh.'
    } catch (error) {
        state.error = error.message
    } finally {
        state.loading = false
    }
}

onMounted(load)
</script>

<template>
    <ParentLayout>
        <div class="mx-auto max-w-2xl">
            <p class="admin-section-title">Security</p>
            <h1 class="mt-1 text-3xl font-black">Mã khu vực phụ huynh</h1>
            <p class="mt-2 font-semibold text-slate-500">Đặt một mã riêng để bảo vệ khu vực quản lý phụ huynh khi chuyển từ giao diện gia đình.</p>

            <section class="admin-card mt-6 p-6 sm:p-8">
                <div class="flex items-start gap-4">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-sky-100 text-2xl">🔐</span>
                    <div>
                        <h2 class="text-xl font-black">{{ state.hasPin ? 'Đổi mã truy cập' : 'Tạo mã truy cập' }}</h2>
                        <p class="mt-1 text-sm font-semibold text-slate-500">Mã này khác với mã gia đình và mã PIN của bé.</p>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-between gap-4 rounded-2xl bg-slate-50 p-4">
                    <div>
                        <p class="font-black text-slate-900">{{ state.enabled ? 'Đang bật mã bảo vệ' : 'Đang tắt mã bảo vệ' }}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-500">Tắt mã để vào khu vực phụ huynh trực tiếp.</p>
                    </div>
                    <button
                        type="button"
                        role="switch"
                        :aria-checked="state.enabled"
                        :disabled="state.toggling || !state.hasPin"
                        class="relative h-7 w-12 shrink-0 rounded-full transition disabled:cursor-not-allowed disabled:opacity-50"
                        :class="state.enabled ? 'bg-sky-600' : 'bg-slate-300'"
                        @click="toggleAccessPin"
                    >
                        <span class="absolute top-1 h-5 w-5 rounded-full bg-white shadow transition" :class="state.enabled ? 'left-6' : 'left-1'" />
                    </button>
                </div>
                <p v-if="!state.hasPin" class="mt-2 text-xs font-semibold text-amber-700">Hãy tạo mã trước khi bật chức năng bảo vệ.</p>

                <form class="mt-6 space-y-4" @submit.prevent="save">
                    <label class="block">
                        <span class="mb-1 block text-xs font-black uppercase tracking-wide text-slate-500">Mã mới</span>
                        <input v-model="form.pin" class="home-input" type="password" inputmode="numeric" maxlength="6" autocomplete="new-password" placeholder="Nhập 4-6 chữ số" required />
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-black uppercase tracking-wide text-slate-500">Nhập lại mã</span>
                        <input v-model="form.confirmation" class="home-input" type="password" inputmode="numeric" maxlength="6" autocomplete="new-password" placeholder="Nhập lại mã" required />
                    </label>
                    <p v-if="state.error" class="rounded-xl bg-red-50 p-3 text-sm font-bold text-red-600">{{ state.error }}</p>
                    <p v-if="state.success" class="rounded-xl bg-emerald-50 p-3 text-sm font-bold text-emerald-700">{{ state.success }}</p>
                    <button class="min-h-12 w-full rounded-xl bg-sky-600 px-4 text-sm font-black text-white transition hover:bg-sky-700 disabled:cursor-wait disabled:opacity-60" type="submit" :disabled="state.loading">
                        {{ state.loading ? 'Đang lưu...' : 'Lưu mã truy cập' }}
                    </button>
                </form>
            </section>
        </div>
    </ParentLayout>
</template>
