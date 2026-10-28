<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ArrowRight, Eye, EyeOff, Heart, LockKeyhole, ShieldCheck, X } from 'lucide-vue-next'
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
        <div class="family-page relative isolate min-h-screen overflow-hidden bg-sky-50 text-[#173b75]">
            <div class="absolute inset-0 -z-10 bg-gradient-to-b from-white/50 via-sky-50/40 to-white/70"></div>

            <section class="mx-auto flex min-h-screen w-full max-w-6xl flex-col justify-center px-5 py-10 sm:px-8">
                <div class="mb-10 flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                    <div class="max-w-[680px]">
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-sky-100
                            bg-white/90 px-4 py-2 text-sm font-extrabold text-sky-700 shadow-sm"
                        >
                            <Heart class="h-4 w-4 fill-rose-500 text-rose-500" />
                            FAMILY HOME • {{ auth.user?.family_name || auth.user?.family_code }}
                        </div>

                        <h1
                            class="mt-6 text-4xl font-black leading-[1.15] tracking-tight
                            text-[#14458f] sm:text-5xl"
                        >
                            Hôm nay ai sẽ
                            <span class="text-amber-500">chinh phục</span>
                            nhiệm vụ?
                        </h1>

                        <p class="mt-4 max-w-[570px] text-base font-medium leading-7 text-slate-700 sm:text-lg sm:leading-8">
                            Chọn hồ sơ của con để xem nhiệm vụ, tích điểm và mở phần thưởng trong ngày.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="inline-flex min-h-12 shrink-0 items-center justify-center gap-2 rounded-full
                        bg-[#1688f8] px-6 text-sm font-extrabold text-white shadow-lg shadow-sky-300/50
                        transition duration-200 hover:-translate-y-1 hover:bg-blue-600"
                        @click="openParentDashboard"
                    >
                        <LockKeyhole class="h-4 w-4" />
                        Khu vực phụ huynh
                    </button>
                </div>

                <!-- Danh sách bé -->
                <div
                    class="rounded-[28px] border border-white/80 bg-white/90 p-5
                    shadow-[0_25px_80px_rgba(53,113,180,.22)] backdrop-blur-xl sm:p-8"
                >
                    <LoadingState
                        v-if="childStore.loadingStates.children && !childStore.children.length"
                        title="Đang tải hồ sơ bé"
                        message="Đang chuẩn bị danh sách bé trong gia đình."
                        variant="child"
                    />
                    <ChildSelector v-else :children="childStore.children" @select="selectChild" />

                    <p
                        v-if="!childStore.loadingStates.children && !childStore.children.length"
                        class="rounded-2xl bg-sky-50 p-6 text-center text-sm font-bold text-slate-600"
                    >
                        Gia đình chưa có hồ sơ bé. Phụ huynh hãy vào dashboard để thêm bé.
                    </p>
                </div>

                <p class="mt-8 flex items-center justify-center gap-2 text-xs font-bold text-slate-600">
                    <ShieldCheck class="h-4 w-4 text-sky-600" />
                    Thông tin của gia đình được bảo mật
                </p>
            </section>

            <!-- Modal: mở hồ sơ bé -->
            <div
                v-if="selectedChild"
                class="fixed inset-0 z-50 flex items-center justify-center bg-[#0f2a5c]/50 px-4 backdrop-blur-sm"
            >
                <form
                    class="relative w-full max-w-sm rounded-[28px] border border-white/80 bg-white p-6
                    shadow-[0_25px_80px_rgba(53,113,180,.3)] sm:p-7"
                    @submit.prevent="unlockChild"
                >
                    <button
                        type="button"
                        class="absolute right-4 top-4 grid h-8 w-8 place-items-center rounded-full
                        bg-slate-100 text-slate-500 transition hover:bg-slate-200"
                        aria-label="Đóng"
                        @click="closeUnlock"
                    >
                        <X class="h-4 w-4" />
                    </button>

                    <span class="inline-flex items-center gap-1.5 rounded-full bg-sky-50 px-3 py-1 text-xs font-extrabold text-sky-600">
                        <LockKeyhole class="h-3.5 w-3.5" /> Mở hồ sơ bé
                    </span>
                    <h2 class="mt-3 text-2xl font-black text-[#14458f]">{{ selectedChild.name }}</h2>

                    <label class="mt-5 block">
                        <span class="mb-2 block text-sm font-bold text-slate-700">Mã PIN</span>
                        <div
                            class="relative flex items-center rounded-2xl border border-slate-200 bg-white transition
                            focus-within:border-sky-400 focus-within:ring-4 focus-within:ring-sky-100"
                        >
                            <input
                                v-model="unlockForm.pin"
                                autofocus
                                inputmode="numeric"
                                maxlength="6"
                                autocomplete="one-time-code"
                                class="h-14 w-full rounded-2xl bg-transparent px-12 text-center text-xl font-black
                                tracking-[0.35em] text-[#14458f] outline-none"
                                :type="showPin ? 'text' : 'password'"
                            />
                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-sky-500"
                                :aria-label="showPin ? 'Ẩn mã PIN' : 'Hiện mã PIN'"
                                @click="showPin = !showPin"
                            >
                                <Eye v-if="!showPin" class="h-5 w-5" />
                                <EyeOff v-else class="h-5 w-5" />
                            </button>
                        </div>
                        <span
                            v-if="unlockError"
                            role="alert"
                            class="mt-3 block rounded-xl bg-red-50 p-3 text-sm font-bold text-red-600"
                        >
                            {{ unlockError }}
                        </span>
                    </label>

                    <div class="mt-6 flex gap-2">
                        <BaseButton type="submit" :disabled="childStore.loadingStates.unlockChild">
                            {{ childStore.loadingStates.unlockChild ? 'Đang mở...' : 'Vào làm nhiệm vụ' }}
                            <ArrowRight class="ml-1 inline h-4 w-4" />
                        </BaseButton>
                        <BaseButton type="button" variant="secondary" @click="closeUnlock">Hủy</BaseButton>
                    </div>
                </form>
            </div>

            <!-- Modal: khu vực phụ huynh -->
            <div
                v-if="showParentAccess"
                class="fixed inset-0 z-50 flex items-center justify-center bg-[#0f2a5c]/50 px-4 backdrop-blur-sm"
            >
                <form
                    class="relative w-full max-w-sm rounded-[28px] border border-white/80 bg-white p-6
                    shadow-[0_25px_80px_rgba(53,113,180,.3)] sm:p-7"
                    @submit.prevent="verifyParentAccess"
                >
                    <button
                        type="button"
                        class="absolute right-4 top-4 grid h-8 w-8 place-items-center rounded-full
                        bg-slate-100 text-slate-500 transition hover:bg-slate-200"
                        aria-label="Đóng"
                        @click="closeParentAccess"
                    >
                        <X class="h-4 w-4" />
                    </button>

                    <span class="inline-flex items-center gap-1.5 rounded-full bg-sky-50 px-3 py-1 text-xs font-extrabold text-sky-600">
                        <ShieldCheck class="h-3.5 w-3.5" /> Khu vực phụ huynh
                    </span>
                    <h2 class="mt-3 text-2xl font-black text-[#14458f]">Nhập mã truy cập</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Nhập mã riêng mà bạn đã đặt trong dashboard phụ huynh.
                    </p>

                    <div
                        class="mt-5 rounded-2xl border border-slate-200 bg-white transition
                        focus-within:border-sky-400 focus-within:ring-4 focus-within:ring-sky-100"
                    >
                        <input
                            v-model="parentAccessForm.pin"
                            autofocus
                            type="password"
                            inputmode="numeric"
                            maxlength="6"
                            autocomplete="current-password"
                            class="h-14 w-full rounded-2xl bg-transparent px-4 text-center text-xl font-black
                            tracking-[0.35em] text-[#14458f] outline-none
                            placeholder:text-sm placeholder:font-medium placeholder:tracking-normal placeholder:text-slate-400"
                            placeholder="Mã 4-6 số"
                            required
                        />
                    </div>

                    <p
                        v-if="parentAccessError"
                        role="alert"
                        class="mt-3 rounded-xl bg-red-50 p-3 text-sm font-bold text-red-600"
                    >
                        {{ parentAccessError }}
                    </p>

                    <div class="mt-6 flex gap-2">
                        <BaseButton type="submit">
                            Vào khu vực phụ huynh <ArrowRight class="ml-1 inline h-4 w-4" />
                        </BaseButton>
                        <BaseButton type="button" variant="secondary" @click="closeParentAccess">Hủy</BaseButton>
                    </div>
                </form>
            </div>
        </div>
    </ChildLayout>
</template>

<style scoped>
.family-page button:focus-visible,
.family-page a:focus-visible {
    outline: 3px solid rgba(37, 136, 232, .5);
    outline-offset: 2px;
}
</style>