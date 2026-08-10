<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import BaseButton from '../../components/common/BaseButton.vue'
import BaseInput from '../../components/common/BaseInput.vue'
import { useAuthStore } from '../../stores/auth.store'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const form = reactive({ email: 'parent@example.com', password: 'password' })
const error = ref('')

async function submit() {
    error.value = ''
    try {
        await auth.login(form)
        router.push(route.query.redirect || '/parent')
    } catch (e) {
        error.value = e.message
    }
}
</script>

<template>
    <main class="min-h-screen overflow-hidden bg-slate-950 text-white">
        <section class="relative mx-auto grid min-h-screen w-full max-w-7xl gap-8 px-4 py-6 sm:px-6 lg:grid-cols-[1fr_460px] lg:items-center lg:py-10">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_14%_12%,rgba(56,189,248,0.26),transparent_30rem),radial-gradient(circle_at_88%_18%,rgba(251,191,36,0.22),transparent_24rem),linear-gradient(135deg,#020617_0%,#0f172a_56%,#111827_100%)]"></div>

            <div class="relative flex min-h-[42vh] flex-col justify-between rounded-[2rem] border border-white/10 bg-white/[0.06] p-6 shadow-[0_30px_90px_rgba(0,0,0,0.25)] backdrop-blur lg:min-h-[calc(100vh-5rem)] lg:p-8">
                <router-link to="/" class="inline-flex w-fit items-center gap-3 rounded-full bg-white/10 px-4 py-2 text-sm font-black text-white ring-1 ring-white/10 transition hover:bg-white/15">
                    <span class="grid h-8 w-8 place-items-center rounded-full bg-amber-300 text-slate-950">K</span>
                    KidTask
                </router-link>

                <div class="my-10 max-w-2xl">
                    <h1 class="mt-5 text-4xl font-black leading-[1.04] sm:text-6xl">
                        Quản lý nhiệm vụ của con rõ ràng hơn mỗi ngày.
                    </h1>
                    <p class="mt-5 max-w-xl text-base font-semibold leading-7 text-slate-300">
                        Theo dõi tiến độ, thiết lập phần thưởng và giữ cho lịch sinh hoạt của bé nhẹ nhàng nhưng có động lực.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/10">
                        <p class="text-2xl font-black">✓</p>
                        <p class="mt-2 text-xs font-black uppercase text-slate-300">Task hằng ngày</p>
                    </div>
                    <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/10">
                        <p class="text-2xl font-black">⭐</p>
                        <p class="mt-2 text-xs font-black uppercase text-slate-300">Điểm thưởng</p>
                    </div>
                    <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/10">
                        <p class="text-2xl font-black">📈</p>
                        <p class="mt-2 text-xs font-black uppercase text-slate-300">Theo dõi</p>
                    </div>
                </div>
            </div>

            <div class="relative flex items-center">
                <form class="w-full rounded-[2rem] border border-white/80 bg-white p-6 text-slate-950 shadow-[0_30px_90px_rgba(0,0,0,0.26)] sm:p-8" @submit.prevent="submit">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase text-sky-600">KidTask Parent</p>
                            <h2 class="mt-2 text-3xl font-black">Đăng nhập</h2>
                            <p class="mt-2 text-sm font-semibold leading-6 text-slate-500">Vào khu vực phụ huynh để quản lý nhiệm vụ, điểm và phần thưởng.</p>
                        </div>
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-amber-100 text-xl shadow-inner">🔐</span>
                    </div>

                    <div class="mt-7 space-y-4">
                        <BaseInput v-model="form.email" label="Email" type="email" />
                        <BaseInput v-model="form.password" label="Mật khẩu" type="password" />
                    </div>

                    <p v-if="error" class="mt-4 rounded-2xl bg-red-50 p-4 text-sm font-bold leading-6 text-red-700 ring-1 ring-red-100">{{ error }}</p>

                    <BaseButton type="submit" class="mt-6 w-full rounded-2xl shadow-lg shadow-slate-200">Đăng nhập</BaseButton>
                </form>
            </div>
        </section>
    </main>
</template>
