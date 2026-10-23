<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ArrowRight, LockKeyhole, ShieldCheck, Sparkles, Users, UserRound } from 'lucide-vue-next'
import { useAuthStore } from '../../stores/auth.store'

const router = useRouter()
const auth = useAuthStore()
const activeTab = ref('family')
const error = ref('')
const form = reactive({ family_code: '', email: '', password: '' })

async function login() {
    error.value = ''
    try {
        if (activeTab.value === 'family') await auth.familyLogin({ family_code: form.family_code })
        else await auth.login({ email: form.email, password: form.password })
        router.push(auth.user.role === 'admin' ? '/admin' : '/family')
    } catch (e) { error.value = e.message }
}
</script>

<template>
    <main class="home-shell min-h-screen overflow-hidden text-[#173b75]">
        <div class="mx-auto grid min-h-screen max-w-[1500px] items-center gap-8 px-5 py-8 lg:grid-cols-[1.02fr_0.98fr] lg:px-14">
            <section class="relative flex min-h-[600px] flex-col justify-center py-8 lg:min-h-[720px]">
                <div class="absolute -left-24 top-16 h-56 w-56 rounded-full bg-sky-200/40 blur-3xl"></div>
                <img src="/public/images/kidtask-logo.png" alt="KidTask" class="relative w-64 object-contain sm:w-80" />
                <div class="relative mt-12 max-w-2xl">
                    <p class="mb-4 inline-flex items-center gap-2 rounded-full bg-white/70 px-4 py-2 text-sm font-bold text-sky-700 shadow-sm ring-1 ring-sky-100"><Sparkles class="h-4 w-4 text-amber-400" /> Mỗi ngày một bước tiến bộ</p>
                    <h1 class="text-5xl font-black leading-[1.02] tracking-tight text-[#14458f] sm:text-6xl">Quản lý hoạt động việc làm của trẻ mỗi ngày</h1>
                    <p class="mt-6 max-w-xl text-lg font-semibold leading-8 text-slate-600">KidTask giúp gia đình theo dõi, giao việc và ghi nhận những tiến bộ nhỏ mỗi ngày của trẻ.</p>
                </div>
                <div class="relative mt-12 grid max-w-xl grid-cols-2 gap-4 sm:grid-cols-4">
                    <div v-for="item in [{icon:'✓', text:'Giao việc hàng ngày', color:'bg-emerald-100 text-emerald-600'}, {icon:'★', text:'Theo dõi tiến độ', color:'bg-amber-100 text-amber-500'}, {icon:'♥', text:'Tạo thói quen tốt', color:'bg-rose-100 text-rose-500'}, {icon:'●', text:'Kết nối gia đình', color:'bg-indigo-100 text-indigo-500'}]" :key="item.text" class="text-center"><span class="mx-auto grid h-14 w-14 place-items-center rounded-full text-2xl font-black shadow-inner" :class="item.color">{{ item.icon }}</span><span class="mt-3 block text-sm font-black leading-5">{{ item.text }}</span></div>
                </div>
            </section>
            <section class="relative rounded-[2rem] border border-white/80 bg-white/90 p-5 shadow-[0_25px_80px_rgba(53,113,180,.18)] backdrop-blur sm:p-8">
                <div class="grid grid-cols-2 gap-2 rounded-2xl bg-slate-50 p-2"><button class="tab-button" :class="activeTab === 'family' ? 'tab-active' : ''" @click="activeTab = 'family'"><Users class="h-5 w-5" /> Vào trang gia đình</button><button class="tab-button" :class="activeTab === 'admin' ? 'tab-active' : ''" @click="activeTab = 'admin'"><LockKeyhole class="h-5 w-5" /> Đăng nhập Admin</button></div>
                <div class="mt-9 flex items-start gap-4"><span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-sky-100 text-sky-600"><UserRound class="h-6 w-6" /></span><div><h2 class="text-2xl font-black text-[#173b75]">{{ activeTab === 'family' ? 'Nhập mã gia đình' : 'Đăng nhập Admin' }}</h2><p class="mt-1 text-sm font-semibold text-slate-500">{{ activeTab === 'family' ? 'Nhập mã để vào tài khoản quản lý của gia đình bạn.' : 'Dành cho quản trị viên hệ thống.' }}</p></div></div>
                <form class="mt-8 space-y-4" @submit.prevent="login"><label v-if="activeTab === 'family'" class="field-label">Mã gia đình<input v-model="form.family_code" class="home-input uppercase tracking-[.18em]" placeholder="Ví dụ: GD123456" maxlength="32" required /></label><template v-else><label class="field-label">Email tài khoản<input v-model="form.email" class="home-input" type="email" placeholder="you@example.com" required /></label><label class="field-label">Mật khẩu<input v-model="form.password" class="home-input" type="password" placeholder="Nhập mật khẩu" required /></label></template><p v-if="error" class="rounded-xl bg-red-50 p-3 text-sm font-bold text-red-600">{{ error }}</p><button class="flex min-h-14 w-full items-center justify-center gap-3 rounded-2xl bg-[#2588e8] text-base font-black text-white shadow-lg shadow-sky-200 transition hover:-translate-y-0.5 hover:bg-[#1775d0]" type="submit">{{ activeTab === 'family' ? 'Vào trang gia đình' : 'Đăng nhập Admin' }} <ArrowRight class="h-5 w-5" /></button></form>
            </section>
        </div>
    </main>
</template>
