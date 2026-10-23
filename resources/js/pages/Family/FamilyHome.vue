<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ArrowRight, Eye, EyeOff, LockKeyhole, ShieldCheck } from 'lucide-vue-next'
import ChildLayout from '../../layouts/ChildLayout.vue'
import ChildSelector from '../../components/child/ChildSelector.vue'
import LoadingState from '../../components/common/LoadingState.vue'
import BaseButton from '../../components/common/BaseButton.vue'
import { useChildStore } from '../../stores/child.store'
import { useAuthStore } from '../../stores/auth.store'
import { useToastStore } from '../../stores/toast.store'
import { parentService } from '../../services/parent.service'

const router = useRouter()
const childStore = useChildStore()
const auth = useAuthStore()
const toast = useToastStore()
const selectedChild = ref(null)
const unlockForm = reactive({ pin: '' })
const unlockError = ref('')
const showPin = ref(false)
const showParentAccess = ref(false)
const parentAccessForm = reactive({ pin: '' })
const parentAccessError = ref('')

onMounted(() => childStore.loadChildren())

function selectChild(child) {
    selectedChild.value = child
    unlockForm.pin = ''
    unlockError.value = ''
    showPin.value = false
}

function closeUnlock() {
    selectedChild.value = null
    unlockForm.pin = ''
    unlockError.value = ''
}

async function unlockChild() {
    if (!selectedChild.value) return
    unlockError.value = ''
    if (!/^\d{4,6}$/.test(unlockForm.pin)) {
        unlockError.value = 'Mã PIN cần có 4-6 số.'
        return
    }
    try {
        await childStore.unlockChild(selectedChild.value.id, unlockForm.pin)
        router.push(`/child/${selectedChild.value.id}`)
    } catch (error) {
        unlockError.value = error.message
        toast.show(error.message, 'error')
    }
}

async function openParentDashboard() {
    parentAccessError.value = ''
    parentAccessForm.pin = ''
    const status = await parentService.accessPinStatus()
    if (!status.has_pin) {
        router.push('/parent/access-pin')
        return
    }
    if (!status.enabled) {
        router.push('/parent')
        return
    }
    showParentAccess.value = true
}

function closeParentAccess() {
    showParentAccess.value = false
    parentAccessForm.pin = ''
    parentAccessError.value = ''
}

async function verifyParentAccess() {
    parentAccessError.value = ''
    try {
        await parentService.verifyAccessPin(parentAccessForm.pin)
        closeParentAccess()
        router.push('/parent')
    } catch (error) {
        parentAccessError.value = error.message
    }
}
</script>

<template>
    <ChildLayout>
        <section class="mx-auto flex min-h-screen w-full max-w-6xl flex-col justify-center px-4 py-8 sm:px-6">
            <div class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div><p class="inline-flex rounded-full bg-white/80 px-4 py-2 text-xs font-black uppercase tracking-wide text-sky-700 shadow-sm ring-1 ring-sky-100">Family Home · {{ auth.user?.family_code }}</p><h1 class="mt-5 text-4xl font-black leading-[1.02] text-slate-950 sm:text-5xl">Hôm nay ai sẽ chinh phục nhiệm vụ?</h1><p class="mt-4 text-base font-semibold leading-7 text-slate-600">Chọn hồ sơ của con để xem nhiệm vụ, tích điểm và mở phần thưởng trong ngày.</p></div>
                <button type="button" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-full bg-slate-950 px-5 text-sm font-black text-white shadow-lg shadow-slate-300 transition hover:-translate-y-0.5 hover:bg-slate-800" @click="openParentDashboard"><LockKeyhole class="h-4 w-4" /> Khu vực phụ huynh</button>
            </div>
            <LoadingState v-if="childStore.loadingStates.children && !childStore.children.length" title="Đang tải hồ sơ bé" message="Đang chuẩn bị danh sách bé trong gia đình." variant="child" />
            <ChildSelector v-else :children="childStore.children" @select="selectChild" />
            <p v-if="!childStore.loadingStates.children && !childStore.children.length" class="rounded-2xl bg-white/80 p-6 text-center font-bold text-slate-600">Gia đình chưa có hồ sơ bé. Phụ huynh hãy vào dashboard để thêm bé.</p>
            <p class="mt-8 flex items-center justify-center gap-2 text-xs font-semibold text-slate-500"><ShieldCheck class="h-4 w-4 text-sky-600" /> Thông tin của gia đình được bảo mật</p>
        </section>

        <div v-if="selectedChild" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4 backdrop-blur-sm">
            <form class="w-full max-w-sm rounded-[1.5rem] bg-white p-6 shadow-2xl" @submit.prevent="unlockChild"><p class="text-xs font-black uppercase text-sky-700">Mở hồ sơ bé</p><h2 class="mt-2 text-2xl font-black text-slate-950">{{ selectedChild.name }}</h2><label class="mt-5 block"><span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Mã PIN</span><div class="relative"><input v-model="unlockForm.pin" autofocus inputmode="numeric" maxlength="6" autocomplete="one-time-code" class="min-h-12 w-full rounded-xl border border-slate-200 px-12 text-center text-xl font-black tracking-[0.35em] text-slate-900 outline-none focus:border-sky-500 focus:ring-4 focus:ring-sky-100" :type="showPin ? 'text' : 'password'" /><button type="button" class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-slate-500" @click="showPin = !showPin"><Eye v-if="!showPin" class="h-5 w-5" /><EyeOff v-else class="h-5 w-5" /></button></div><span v-if="unlockError" class="mt-2 block text-sm font-semibold text-red-600">{{ unlockError }}</span></label><div class="mt-6 flex gap-2"><BaseButton type="submit" :disabled="childStore.loadingStates.unlockChild">{{ childStore.loadingStates.unlockChild ? 'Đang mở...' : 'Vào làm nhiệm vụ' }} <ArrowRight class="ml-1 inline h-4 w-4" /></BaseButton><BaseButton type="button" variant="secondary" @click="closeUnlock">Hủy</BaseButton></div></form>
        </div>

        <div v-if="showParentAccess" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4 backdrop-blur-sm">
            <form class="w-full max-w-sm rounded-[1.5rem] bg-white p-6 shadow-2xl" @submit.prevent="verifyParentAccess">
                <p class="text-xs font-black uppercase text-sky-700">Khu vực phụ huynh</p>
                <h2 class="mt-2 text-2xl font-black text-slate-950">Nhập mã truy cập</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Nhập mã riêng mà bạn đã đặt trong dashboard phụ huynh.</p>
                <input v-model="parentAccessForm.pin" autofocus type="password" inputmode="numeric" maxlength="6" autocomplete="current-password" class="mt-5 min-h-12 w-full rounded-xl border border-slate-200 px-4 text-center text-xl font-black tracking-[0.35em] text-slate-900 outline-none focus:border-sky-500 focus:ring-4 focus:ring-sky-100" placeholder="Mã 4-6 số" required />
                <p v-if="parentAccessError" class="mt-2 text-sm font-semibold text-red-600">{{ parentAccessError }}</p>
                <div class="mt-6 flex gap-2">
                    <BaseButton type="submit">Vào khu vực phụ huynh <ArrowRight class="ml-1 inline h-4 w-4" /></BaseButton>
                    <BaseButton type="button" variant="secondary" @click="closeParentAccess">Hủy</BaseButton>
                </div>
            </form>
        </div>
    </ChildLayout>
</template>
