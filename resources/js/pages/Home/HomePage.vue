<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import ChildLayout from '../../layouts/ChildLayout.vue'
import ChildSelector from '../../components/child/ChildSelector.vue'
import LoadingState from '../../components/common/LoadingState.vue'
import BaseButton from '../../components/common/BaseButton.vue'
import { useChildStore } from '../../stores/child.store'
import { useToastStore } from '../../stores/toast.store'

const router = useRouter()
const childStore = useChildStore()
const toast = useToastStore()
const selectedChild = ref(null)
const unlockForm = reactive({ pin: '' })
const unlockError = ref('')
const showPin = ref(false)

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
    showPin.value = false
}

async function unlockChild() {
    if (!selectedChild.value) return

    unlockError.value = ''

    if (!/^\d{4,6}$/.test(unlockForm.pin)) {
        unlockError.value = 'Nhập mã PIN 4-6 số.'
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
</script>

<template>
    <ChildLayout>
        <section class="mx-auto flex min-h-screen w-full max-w-6xl flex-col justify-center px-4 py-8 sm:px-6">
            <div class="mb-8 text-center">
                <p class="inline-flex rounded-full bg-white/80 px-4 py-2 text-xs font-black uppercase tracking-wide text-sky-700 shadow-sm ring-1 ring-sky-100">KidTask</p>
                <h1 class="mt-5 text-4xl font-black leading-[1.02] text-slate-950 sm:text-5xl">
                    Hôm nay ai sẽ chinh phục nhiệm vụ?
                </h1>
                <p class="mt-4 text-base font-semibold leading-7 text-slate-600">
                    Chọn hồ sơ của con để xem nhiệm vụ, tích điểm và mở khóa phần thưởng trong ngày.
                </p>
            </div>
            <LoadingState v-if="childStore.loadingStates.children && !childStore.children.length" title="Đang tải hồ sơ bé" message="KidTask đang chuẩn bị danh sách để con chọn." variant="child" />
            <ChildSelector v-else :children="childStore.children" @select="selectChild" />
            <router-link to="/parent" class="mx-auto mt-8 inline-flex min-h-12 items-center rounded-full bg-slate-950 px-5 text-sm font-black text-white shadow-lg shadow-slate-300 transition hover:-translate-y-0.5 hover:bg-slate-800">
                🔐 Khu vực phụ huynh
            </router-link>
        </section>

        <div v-if="selectedChild" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4 backdrop-blur-sm">
            <form class="w-full max-w-sm rounded-[1.5rem] bg-white p-6 shadow-2xl" @submit.prevent="unlockChild">
                <p class="text-xs font-black uppercase text-sky-700">Mở hồ sơ bé</p>
                <h2 class="mt-2 text-2xl font-black text-slate-950">{{ selectedChild.name }}</h2>
                <label class="mt-5 block">
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Mã PIN</span>
                    <div class="relative">
                        <input
                            v-model="unlockForm.pin"
                            autofocus
                            inputmode="numeric"
                            maxlength="6"
                            autocomplete="one-time-code"
                            class="min-h-12 w-full rounded-xl border border-slate-200 px-12 text-center text-xl font-black tracking-[0.35em] text-slate-900 outline-none focus:border-sky-500 focus:ring-4 focus:ring-sky-100"
                            :type="showPin ? 'text' : 'password'"
                        />
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-xl text-slate-500 transition hover:text-sky-700 focus:outline-none focus-visible:text-sky-700"
                            :aria-label="showPin ? 'Ẩn mã PIN' : 'Hiện mã PIN'"
                            :title="showPin ? 'Ẩn mã PIN' : 'Hiện mã PIN'"
                            @click="showPin = !showPin"
                        >
                            {{ showPin ? '🙈' : '👁️' }}
                        </button>
                    </div>
                    <span v-if="unlockError" class="mt-2 block text-sm font-semibold text-red-600">{{ unlockError }}</span>
                </label>
                <div class="mt-6 flex gap-2">
                    <BaseButton type="submit" :disabled="childStore.loadingStates.unlockChild">
                        {{ childStore.loadingStates.unlockChild ? 'Đang mở...' : 'Vào làm nhiệm vụ' }}
                    </BaseButton>
                    <BaseButton type="button" variant="secondary" @click="closeUnlock">Hủy</BaseButton>
                </div>
            </form>
        </div>
    </ChildLayout>
</template>
