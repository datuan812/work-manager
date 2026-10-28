<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import {
    ArrowRight, BrainCircuit, CalendarCheck, CheckCircle2, ClipboardList, Facebook, Heart,
    Home, LockKeyhole, Mail, MessageCircleMore, Phone, ShieldCheck, Smile, Star,
    TrendingUp, UserPlus, UserRound, Users, UsersRound, Youtube,
} from 'lucide-vue-next'
import { useAuthStore } from '../../stores/auth.store'

const router = useRouter()
const auth = useAuthStore()
const activeTab = ref('family')
const error = ref('')
const loading = ref(false)
const form = reactive({ family_code: '', email: '', password: '' })

async function login() {
    error.value = ''
    loading.value = true
    try {
        if (activeTab.value === 'family') await auth.familyLogin({ family_code: form.family_code })
        else await auth.login({ email: form.email, password: form.password })
        router.push(auth.user.role === 'admin' ? '/admin' : '/family')
    } catch (e) {
        error.value = e.message
    } finally {
        loading.value = false
    }
}

const activeSection = ref('top')

const scrollTo = (id) => {
    activeSection.value = id

    const element = document.getElementById(id)
    if (!element) return

    element.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
    })
}

const nav = [
    { label: 'Trang chủ', id: 'top' },
    { label: 'Giới thiệu', id: 'why' },
    { label: 'Tính năng', id: 'features' },
    { label: 'Hướng dẫn', id: 'steps' },
    { label: 'Bảng giá', id: 'pricing' },
    { label: 'Liên hệ', id: 'cta' },
]

const heroChecks = ['Dễ sử dụng', 'Kết nối gia đình', 'Theo dõi tiến độ', 'Phát triển kỹ năng']

const features = [
    { icon: ClipboardList, title: 'Giao việc dễ dàng', text: 'Tạo nhiệm vụ hằng ngày phù hợp với độ tuổi và khả năng của trẻ.', tone: 'bg-emerald-100 text-emerald-600' },
    { icon: CalendarCheck, title: 'Theo dõi tiến độ', text: 'Nắm bắt tình hình hoàn thành nhiệm vụ, xem lịch sử và báo cáo chi tiết.', tone: 'bg-sky-100 text-sky-600' },
    { icon: Star, title: 'Rèn luyện thói quen', text: 'Giúp con hình thành thói quen tốt và phát triển kỹ năng sống mỗi ngày.', tone: 'bg-amber-100 text-amber-500' },
    { icon: UsersRound, title: 'Kết nối gia đình', text: 'Cùng con tạo nên môi trường sinh hoạt lành mạnh, yêu thương và tích cực.', tone: 'bg-indigo-100 text-indigo-500' },
    { icon: ShieldCheck, title: 'An toàn & bảo mật', text: 'Dữ liệu gia đình được bảo mật, chỉ thành viên được phép mới có thể truy cập.', tone: 'bg-green-100 text-green-600' },
]

const steps = [
    { icon: Home, title: 'Tạo gia đình', text: 'Đăng ký tài khoản gia đình và nhận mã gia đình.', color: 'bg-sky-500' },
    { icon: ClipboardList, title: 'Giao nhiệm vụ', text: 'Thiết lập các việc làm hằng ngày, phù hợp với con.', color: 'bg-emerald-500' },
    { icon: TrendingUp, title: 'Theo dõi tiến độ', text: 'Xem kết quả, nhận thông báo và cùng con phát triển.', color: 'bg-violet-500' },
]

const reasons = [
    { icon: Heart, title: 'Tiết kiệm thời gian', text: 'Không cần nhắc nhở nhiều lần, vẫn biết con đang làm gì.', tone: 'bg-emerald-100 text-emerald-600' },
    { icon: Smile, title: 'Tạo động lực cho con', text: 'Mỗi nhiệm vụ hoàn thành là một niềm vui nhỏ.', tone: 'bg-amber-100 text-amber-500' },
    { icon: BrainCircuit, title: 'Phát triển kỹ năng', text: 'Rèn luyện tính tự lập, trách nhiệm và kỹ năng sống.', tone: 'bg-violet-100 text-violet-600' },
    { icon: Users, title: 'Gắn kết gia đình', text: 'Hiểu và đồng hành cùng con mỗi ngày.', tone: 'bg-sky-100 text-sky-600' },
]

const plans = [
    {
        icon: Home,
        name: 'Gói Gia đình',
        price: 'Miễn phí',
        period: '',
        desc: 'Đủ dùng cho gia đình bắt đầu làm quen với KidTask.',
        features: ['1 gia đình, tối đa 2 bé', 'Giao nhiệm vụ hằng ngày', 'Theo dõi tiến độ cơ bản', 'Đổi thưởng bằng điểm sao'],
        cta: 'Bắt đầu miễn phí',
        tone: 'bg-sky-100 text-sky-600',
        featured: false,
    },
    {
        icon: Star,
        name: 'Gói Nâng cao',
        price: '99.000đ',
        period: '/ tháng',
        desc: 'Dành cho gia đình muốn theo dõi sâu và có báo cáo chi tiết.',
        features: ['Không giới hạn số bé', 'Nhắc việc theo giờ', 'Báo cáo & lịch sử chi tiết', 'Tùy chỉnh phần thưởng', 'Hỗ trợ ưu tiên qua Zalo'],
        cta: 'Đăng ký gói Nâng cao',
        tone: 'bg-amber-100 text-amber-500',
        featured: true,
    },
    {
        icon: UsersRound,
        name: 'Gói Lớp học',
        price: 'Liên hệ',
        period: '',
        desc: 'Dành cho trường mầm non, trung tâm và nhóm nhiều gia đình.',
        features: ['Quản lý nhiều gia đình', 'Tài khoản giáo viên', 'Báo cáo theo lớp', 'Hỗ trợ triển khai'],
        cta: 'Liên hệ tư vấn',
        tone: 'bg-indigo-100 text-indigo-500',
        featured: false,
    },
]

const quickLinks = [
    ['Trang chủ', 'top'], ['Hướng dẫn', 'steps'],
    ['Giới thiệu', 'why'], ['Bảng giá', 'pricing'],  
    ['Tính năng', 'features'], ['Liên hệ', 'cta'],
]
</script>

<template>
    <div id="top" class="home-page min-h-screen bg-white text-[#173b75]">
        <header class="sticky top-0 z-40 border-b border-sky-100/70 bg-white/90 backdrop-blur">
            <div class="mx-auto flex h-[72px] max-w-[1440px] items-center justify-between gap-6 px-5 lg:px-8">
                <button type="button" class="shrink-0" aria-label="KidTask" @click="scrollTo('top')">
                    <img src="/public/images/kidtask-logo.png" alt="KidTask" class="h-11 w-auto object-contain sm:h-12" />
                </button>
               
                <nav
                    class="hidden items-center gap-8 lg:flex"
                    aria-label="Điều hướng chính"
                >
                    <button
                        v-for="item in nav"
                        :key="item.id"
                        type="button"
                        class="nav-link"
                        :class="{ 'nav-link-active': activeSection === item.id }"
                        :aria-current="activeSection === item.id ? 'location' : undefined"
                        @click="scrollTo(item.id)"
                    >
                        {{ item.label }}
                    </button>
                </nav>

                <div class="flex items-center gap-3">
                    <a href="https://zalo.me/0384082003" target="_blank" rel="noopener" class="hidden items-center gap-2 rounded-full bg-[#2588e8] px-5 py-2.5 text-sm font-extrabold text-white shadow-md shadow-sky-200 transition hover:bg-[#1775d0] sm:inline-flex">
                        <MessageCircleMore class="h-4 w-4" /> Tư vấn sử dụng
                    </a>
                    <a href="tel:0384082003" class="grid h-10 w-10 place-items-center rounded-full bg-[#2588e8] text-white shadow-md shadow-sky-200 transition hover:bg-[#1775d0]" aria-label="Gọi hotline">
                        <Phone class="h-4 w-4" />
                    </a>
                </div>
            </div>
        </header>

        <section
            id="home"
            class="relative isolate min-h-[calc(100vh-72px)] overflow-hidden bg-sky-50"
        >
            <img
                src="/public/images/banner-hero.png"
                alt=""
                aria-hidden="true"
                class="absolute inset-0 -z-20 h-full w-full object-cover object-center"
            />

            <div class="mx-auto max-w-[1440px] px-5 pb-12 pt-8 lg:px-10 lg:pb-20">
                <div
                    class="grid items-center gap-10
                    lg:grid-cols-[1.15fr_0.85fr] lg:gap-8"
                >
                    <div class="relative z-10 max-w-[680px]">
                        <div
                            class="inline-flex items-center gap-2 rounded-full
                            border border-sky-100 bg-white/90 px-4 py-2
                            text-sm font-extrabold text-sky-700 shadow-sm"
                        >
                            <Heart class="h-4 w-4 fill-rose-500 text-rose-500" />
                            Giải pháp quản lý việc làm cho trẻ
                        </div>

                        <h1
                            class="mt-6 text-4xl font-black leading-[1.15]
                            tracking-tight text-[#14458f]
                            sm:text-5xl lg:text-[3.5rem]"
                        >
                            Giúp con hình thành <br>
                            <span class="text-amber-500">
                                thói quen tốt
                            </span>
                            mỗi ngày
                        </h1>

                        <p
                            class="mt-5 max-w-[570px] text-base font-medium
                            leading-7 text-slate-700 sm:text-lg sm:leading-8"
                        >
                            KidTask giúp cha mẹ giao nhiệm vụ, theo dõi tiến độ
                            và đồng hành cùng con trong từng hoạt động hằng ngày.
                            Biến những việc nhỏ thành thói quen lớn!
                        </p>

                        <ul class="mt-6 flex flex-wrap gap-x-5 gap-y-3">
                            <li
                                v-for="item in heroChecks"
                                :key="item"
                                class="inline-flex items-center gap-2
                                text-sm font-bold text-slate-700"
                            >
                                <CheckCircle2
                                    class="h-5 w-5 shrink-0 fill-emerald-500 text-white"
                                />
                                {{ item }}
                            </li>
                        </ul>

                        <a
                            href="https://zalo.me/0384082003"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-8 inline-flex items-center gap-3 rounded-full
                            bg-[#1688f8] px-7 py-4 text-sm font-extrabold
                            text-white shadow-lg shadow-sky-300/50
                            transition duration-200 hover:-translate-y-1
                            hover:bg-blue-600 sm:text-base"
                        >
                            <MessageCircleMore class="h-5 w-5" />
                            Tư vấn sử dụng KidTask
                            <ArrowRight class="h-5 w-5" />
                        </a>

                        <p class="mt-4 text-sm font-medium text-slate-600">
                            Đồng hành cùng con từ những việc nhỏ nhất.
                        </p>
                    </div>

                    <div class="relative z-10 mx-auto w-full max-w-[490px]">
                        <div
                            class="rounded-[28px] border border-white/80
                            bg-white/95 p-5 shadow-[0_25px_80px_rgba(53,113,180,.22)]
                            backdrop-blur-xl sm:p-7"
                        >
                            <div
                                class="grid grid-cols-2 gap-2 rounded-2xl
                                bg-slate-100 p-1.5"
                                role="tablist"
                                aria-label="Chọn hình thức đăng nhập"
                            >
                                <button
                                    type="button"
                                    role="tab"
                                    :aria-selected="activeTab === 'family'"
                                    @click="activeTab = 'family'; error = ''"
                                    :class="activeTab === 'family'
                                        ? 'border border-sky-100 bg-white text-sky-600 shadow-sm'
                                        : 'text-slate-500 hover:bg-white/60'"
                                    class="flex min-h-[66px] flex-col items-center
                                    justify-center gap-1.5 rounded-xl px-2
                                    text-sm font-extrabold transition"
                                >
                                    <Users class="h-5 w-5" />
                                    Nhập mã gia đình
                                </button>

                                <button
                                    type="button"
                                    role="tab"
                                    :aria-selected="activeTab === 'admin'"
                                    @click="activeTab = 'admin'; error = ''"
                                    :class="activeTab === 'admin'
                                        ? 'border border-sky-100 bg-white text-sky-600 shadow-sm'
                                        : 'text-slate-500 hover:bg-white/60'"
                                    class="flex min-h-[66px] flex-col items-center
                                    justify-center gap-1.5 rounded-xl px-2
                                    text-sm font-extrabold transition"
                                >
                                    <LockKeyhole class="h-5 w-5" />
                                    Đăng nhập Admin
                                </button>
                            </div>

                            <div class="mb-6 mt-7">
                                <h2 class="text-2xl font-black text-[#14458f]">
                                    {{
                                        activeTab === 'family'
                                            ? 'Nhập mã gia đình'
                                            : 'Đăng nhập Admin'
                                    }}
                                </h2>

                                <p class="mt-2 text-sm leading-6 text-slate-500">
                                    {{
                                        activeTab === 'family'
                                            ? 'Nhập mã để truy cập không gian quản lý của gia đình bạn.'
                                            : 'Đăng nhập dành riêng cho quản trị viên hệ thống.'
                                    }}
                                </p>
                            </div>

                            <form class="space-y-4" @submit.prevent="login">
                                <!-- FAMILY CODE -->
                                <template v-if="activeTab === 'family'">
                                    <label
                                        for="family_code"
                                        class="mb-2 block text-sm font-bold text-slate-700"
                                    >
                                        Mã gia đình
                                    </label>

                                    <div
                                        class="flex items-center gap-3 rounded-2xl
                                        border border-slate-200 bg-white px-4
                                        transition focus-within:border-sky-400
                                        focus-within:ring-4 focus-within:ring-sky-100"
                                    >
                                        <UserRound
                                            class="h-5 w-5 shrink-0 text-sky-500"
                                        />

                                        <input
                                            id="family_code"
                                            v-model="form.family_code"
                                            type="text"
                                            maxlength="32"
                                            autocomplete="off"
                                            placeholder="Ví dụ: GD123456"
                                            required
                                            class="h-14 min-w-0 flex-1 bg-transparent
                                            text-sm font-bold uppercase
                                            tracking-wider text-slate-700
                                            outline-none placeholder:normal-case
                                            placeholder:tracking-normal
                                            placeholder:text-slate-400"
                                        />
                                    </div>
                                </template>

                                <template v-else>
                                    <label
                                        for="admin_email"
                                        class="block text-sm font-bold text-slate-700"
                                    >
                                        Email quản trị
                                    </label>

                                    <div
                                        class="flex items-center gap-3 rounded-2xl
                                        border border-slate-200 px-4
                                        focus-within:border-sky-400
                                        focus-within:ring-4 focus-within:ring-sky-100"
                                    >
                                        <Mail class="h-5 w-5 text-sky-500" />

                                        <input
                                            id="admin_email"
                                            v-model="form.email"
                                            type="email"
                                            autocomplete="username"
                                            placeholder="admin@example.com"
                                            required
                                            class="h-14 min-w-0 flex-1 bg-transparent
                                            text-sm outline-none"
                                        />
                                    </div>

                                    <label
                                        for="admin_password"
                                        class="block text-sm font-bold text-slate-700"
                                    >
                                        Mật khẩu
                                    </label>

                                    <div
                                        class="flex items-center gap-3 rounded-2xl
                                        border border-slate-200 px-4
                                        focus-within:border-sky-400
                                        focus-within:ring-4 focus-within:ring-sky-100"
                                    >
                                        <LockKeyhole class="h-5 w-5 text-sky-500" />

                                        <input
                                            id="admin_password"
                                            v-model="form.password"
                                            type="password"
                                            autocomplete="current-password"
                                            placeholder="Nhập mật khẩu"
                                            required
                                            class="h-14 min-w-0 flex-1 bg-transparent
                                            text-sm outline-none"
                                        />
                                    </div>
                                </template>

                                <p
                                    v-if="error"
                                    role="alert"
                                    class="rounded-xl bg-red-50 p-3 text-sm
                                    font-bold text-red-600"
                                >
                                    {{ error }}
                                </p>

                                <button
                                    type="submit"
                                    :disabled="loading"
                                    class="flex min-h-[54px] w-full items-center
                                    justify-center gap-2 rounded-2xl
                                    bg-gradient-to-r from-[#2d9bf0] to-[#0879e8]
                                    px-5 py-3 text-base font-extrabold text-white
                                    shadow-lg shadow-sky-200 transition
                                    hover:-translate-y-0.5 hover:shadow-sky-300
                                    disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {{
                                        loading
                                            ? 'Đang xử lý...'
                                            : activeTab === 'family'
                                                ? 'Vào trang gia đình'
                                                : 'Đăng nhập Admin'
                                    }}

                                    <ArrowRight class="h-5 w-5" />
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="features" class="mx-auto max-w-[1440px] scroll-mt-24 px-5 py-14 lg:px-8">
            <div class="text-center">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-sky-50 px-3 py-1 text-xs font-extrabold text-sky-600"><Star class="h-3.5 w-3.5 fill-amber-400 text-amber-400" /> Tính năng nổi bật</span>
                <h2 class="mt-3 text-3xl font-black text-[#14458f]">KidTask – Đồng hành cùng con mỗi ngày</h2>
            </div>
            <div class="mt-10 grid gap-8 sm:grid-cols-2 lg:grid-cols-5">
                <article v-for="f in features" :key="f.title" class="text-center">
                    <span class="mx-auto grid h-[72px] w-[72px] place-items-center rounded-full" :class="f.tone"><component :is="f.icon" class="h-8 w-8" /></span>
                    <h3 class="mt-4 text-base font-black">{{ f.title }}</h3>
                    <p class="mx-auto mt-2 max-w-[220px] text-sm font-medium leading-6 text-slate-500">{{ f.text }}</p>
                </article>
            </div>
        </section>

        <!-- STEPS -->
        <section id="steps" class="mx-auto max-w-[1440px] scroll-mt-24 px-5 lg:px-8">
            <div class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-sky-50 to-blue-50 p-6 sm:p-8 lg:pr-[340px]">
                <span class="inline-flex items-center gap-1.5 text-xs font-extrabold text-sky-600"><Home class="h-3.5 w-3.5" /> Cách thức hoạt động</span>
                <h2 class="mt-1 text-3xl font-black text-[#14458f]">Chỉ với 3 bước đơn giản</h2>
                <div class="mt-6 grid items-stretch gap-3 md:grid-cols-[1fr_auto_1fr_auto_1fr]">
                    
                <template v-for="(s, i) in steps" :key="s.title">
                    <div
                        class="group relative rounded-2xl border border-sky-50 bg-white/80 p-5 text-center shadow-sm
                            transition-all duration-300 ease-out
                            hover:-translate-y-2 hover:border-sky-200 hover:bg-white
                            hover:shadow-xl hover:shadow-sky-100/70"
                    >
                        <span
                            class="absolute left-4 top-4 grid h-7 w-7 place-items-center rounded-full
                                text-sm font-black text-white transition-transform duration-300
                                group-hover:scale-110"
                            :class="s.color"
                        >
                            {{ i + 1 }}
                        </span>

                        <component
                            :is="s.icon"
                            class="mx-auto h-12 w-12 text-sky-600 transition-all duration-300
                                group-hover:-translate-y-1 group-hover:scale-110 group-hover:text-sky-500"
                            stroke-width="1.6"
                        />

                        <h3
                            class="mt-3 text-base font-black transition-colors duration-300
                                group-hover:text-sky-600"
                        >
                            {{ s.title }}
                        </h3>

                        <p class="mt-1.5 text-sm font-medium leading-6 text-slate-500">
                            {{ s.text }}
                        </p>
                    </div>

                    <ArrowRight
                        v-if="i < steps.length - 1"
                        class="hidden h-6 w-6 self-center text-sky-400 transition-all
                            duration-300 hover:translate-x-1 hover:text-sky-600 md:block"
                    />
                </template>

                </div>
                <img src="/public/images/anh3.png" alt="Bé gái ôm mèo" class="pointer-events-none mx-auto mt-6 w-56 object-contain lg:absolute lg:bottom-[25px] lg:right-4 lg:mt-0 lg:w-[310px]" />
            </div>
        </section>

        <!-- WHY -->
        <section id="why" class="mx-auto grid max-w-[1440px] scroll-mt-24 items-center gap-8 px-5 py-14 lg:grid-cols-[0.9fr_1.1fr] lg:px-8">
            <img src="/public/images/anh1.png" alt="Mẹ và bé cùng học" class="w-full rounded-3xl object-cover shadow-[0_18px_50px_rgba(53,113,180,.15)]" />
            <div>
                <span class="inline-flex items-center gap-1.5 text-xs font-extrabold text-amber-500"><Heart class="h-3.5 w-3.5 fill-amber-400" /> Dành cho phụ huynh</span>
                <h2 class="mt-1 text-3xl font-black text-[#14458f]">Vì sao nên chọn KidTask?</h2>
                <p class="mt-3 max-w-lg text-sm font-medium leading-6 text-slate-500">KidTask không chỉ là công cụ quản lý, mà còn là người bạn đồng hành cùng con trên hành trình trưởng thành.</p>
                <div class="mt-7 grid gap-x-8 gap-y-6 sm:grid-cols-2">
                    <div v-for="r in reasons" :key="r.title" class="flex items-start gap-4">
                        <span class="grid h-14 w-14 shrink-0 place-items-center rounded-full" :class="r.tone"><component :is="r.icon" class="h-7 w-7" /></span>
                        <div>
                            <h3 class="text-base font-black">{{ r.title }}</h3>
                            <p class="mt-1 text-sm font-medium leading-6 text-slate-500">{{ r.text }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- PRICING -->
        <section id="pricing" class="mx-auto max-w-[1440px] scroll-mt-24 px-5 pb-14 lg:px-8">
            <div class="text-center">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-sky-50 px-3 py-1 text-xs font-extrabold text-sky-600">
                    <Star class="h-3.5 w-3.5 fill-amber-400 text-amber-400" /> Bảng giá
                </span>
                <h2 class="mt-3 text-3xl font-black text-[#14458f]">Chọn gói phù hợp với gia đình bạn</h2>
                <p class="mx-auto mt-3 max-w-xl text-sm font-medium leading-6 text-slate-500">
                    Bắt đầu miễn phí, nâng cấp khi gia đình cần thêm tính năng.
                </p>
            </div>

            <div class="mt-10 grid items-stretch gap-6 lg:grid-cols-3">
                <article
                    v-for="p in plans"
                    :key="p.name"
                    class="relative flex flex-col rounded-[28px] p-6 transition duration-300 hover:-translate-y-2 sm:p-8"
                    :class="p.featured
                        ? 'bg-gradient-to-br from-[#14458f] to-[#0879e8] text-white shadow-[0_25px_80px_rgba(53,113,180,.35)]'
                        : 'border border-sky-100 bg-white text-[#173b75] shadow-[0_18px_50px_rgba(53,113,180,.12)]'"
                >
                    <span
                        v-if="p.featured"
                        class="absolute -top-3 left-1/2 inline-flex -translate-x-1/2 items-center gap-1 rounded-full bg-amber-400 px-4 py-1 text-xs font-black text-[#14458f] shadow-md"
                    >
                        <Star class="h-3.5 w-3.5 fill-[#14458f]" /> Phổ biến nhất
                    </span>

                    <span class="grid h-14 w-14 place-items-center rounded-full" :class="p.featured ? 'bg-white/15 text-white' : p.tone">
                        <component :is="p.icon" class="h-7 w-7" />
                    </span>

                    <h3 class="mt-5 text-xl font-black">{{ p.name }}</h3>
                    <p class="mt-2 text-sm font-medium leading-6" :class="p.featured ? 'text-sky-100' : 'text-slate-500'">
                        {{ p.desc }}
                    </p>

                    <p class="mt-5 flex items-end gap-1">
                        <span class="text-4xl font-black" :class="p.featured ? 'text-white' : 'text-[#14458f]'">{{ p.price }}</span>
                        <span v-if="p.period" class="pb-1 text-sm font-bold" :class="p.featured ? 'text-sky-200' : 'text-slate-500'">{{ p.period }}</span>
                    </p>

                    <ul class="mt-6 flex-1 space-y-3">
                        <li v-for="f in p.features" :key="f" class="flex items-start gap-2.5 text-sm font-bold">
                            <CheckCircle2
                                class="mt-0.5 h-5 w-5 shrink-0"
                                :class="p.featured ? 'fill-emerald-400 text-[#14458f]' : 'fill-emerald-500 text-white'"
                            />
                            <span :class="p.featured ? 'text-white' : 'text-slate-700'">{{ f }}</span>
                        </li>
                    </ul>

                    <a
                        href="https://zalo.me/0384082003"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-8 inline-flex min-h-[52px] items-center justify-center gap-2 rounded-full px-6 text-sm font-extrabold transition hover:-translate-y-0.5"
                        :class="p.featured
                            ? 'bg-white text-[#14458f] shadow-lg shadow-sky-900/20 hover:bg-sky-50'
                            : 'bg-[#1688f8] text-white shadow-lg shadow-sky-300/50 hover:bg-blue-600'"
                    >
                        {{ p.cta }}
                        <ArrowRight class="h-4 w-4" />
                    </a>
                </article>
            </div>
        </section>

        <section id="cta" class="mx-auto max-w-[1440px] scroll-mt-24 px-5 pb-14 lg:px-8">
            <div class="relative isolate overflow-hidden rounded-3xl shadow-[0_18px_50px_rgba(53,113,180,.15)]">
                <img
                    src="/public/images/anh2.png"
                    alt=""
                    aria-hidden="true"
                    class="absolute inset-0 h-full w-full object-cover object-center"
                />
                <div class="absolute inset-0 bg-gradient-to-r from-emerald-50/40 via-lime-50/20 to-transparent"></div>
                <div class="relative z-10 flex min-h-[120px] flex-col items-start gap-5 px-6 py-6 sm:px-8 md:flex-row md:items-center lg:px-10">
                    <div class="max-w-2xl">
                        <h2 class="text-lg font-black text-[#14458f] sm:text-xl">
                            Bắt đầu hành trình cùng KidTask ngay hôm nay!
                        </h2>
                        <p class="mt-1 text-sm font-medium text-slate-600">
                            Đơn giản, tiện lợi và
                            <b class="text-sky-600">hoàn toàn miễn phí</b>
                            cho gia đình.
                        </p>
                    </div>

                    <div class="flex shrink-0 flex-wrap gap-3">
                         <a href="https://zalo.me/0384082003" target="_blank" rel="noopener"
                            class="inline-flex items-center justify-center gap-2 rounded-full bg-sky-500 px-5 py-3 text-sm font-extrabold text-white shadow-md shadow-sky-200 transition hover:-translate-y-0.5 hover:bg-sky-600"
                        >
                            <Users class="h-4 w-4" />
                            Đăng ký gia đình
                        </a>

                        <a
                            href="tel:0384082003"
                            class="inline-flex items-center justify-center gap-2 rounded-full border border-sky-300 bg-white/90 px-5 py-3 text-sm font-extrabold text-sky-600 transition hover:bg-sky-50"
                        >
                            <Phone class="h-4 w-4" />
                            Liên hệ tư vấn
                        </a>
                    </div>

                </div>
            </div>
        </section>


        <!-- FOOTER -->
        <footer id="footer" class="bg-[#0f2a5c] text-slate-200">
            <div class="mx-auto grid max-w-[1440px] gap-10 px-5 py-12 sm:grid-cols-2 lg:grid-cols-[1.3fr_1fr_1fr_0.7fr] lg:px-8">
                <div>
                    <img src="/public/images/kidtask-logo.png" alt="KidTask" class="h-14 w-auto rounded-xl bg-white/95 p-1.5 object-contain" />
                    <p class="mt-3 max-w-[260px] text-xs font-medium text-slate-400">Cùng con xây dựng thói quen tốt mỗi ngày.</p>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-white">Liên kết nhanh</h3>
                    <ul class="mt-3 grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
                        <li v-for="[label, id] in quickLinks" :key="label"><button type="button" class="transition hover:text-white" @click="scrollTo(id)">{{ label }}</button></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-white">Hỗ trợ</h3>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li class="flex items-center gap-2"><Phone class="h-4 w-4 text-sky-300" /> Hotline: 0384 082 003</li>
                        <li class="flex items-center gap-2"><Mail class="h-4 w-4 text-sky-300" /> Email: kidtask@gmail.com</li>
                        <li class="flex items-center gap-2"><MessageCircleMore class="h-4 w-4 text-sky-300" /> Zalo: 0384 082 003</li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-white">Theo dõi chúng tôi</h3>
                    <div class="mt-3 flex gap-3">
                        <a href="#" class="grid h-9 w-9 place-items-center rounded-full bg-white/10 transition hover:bg-white/20" aria-label="Facebook"><Facebook class="h-4 w-4" /></a>
                        <a href="#" class="grid h-9 w-9 place-items-center rounded-full bg-white/10 transition hover:bg-white/20" aria-label="YouTube"><Youtube class="h-4 w-4" /></a>
                    </div>
                </div>
            </div>
            <div class="border-t border-white/10">
                <div class="mx-auto flex max-w-[1440px] flex-col gap-2 px-5 py-4 text-xs text-slate-400 sm:flex-row sm:justify-between lg:px-8">
                    <span>© 2025 KidTask. Tất cả quyền được bảo lưu.</span>
                    <span>Điều khoản sử dụng &nbsp;|&nbsp; Chính sách bảo mật</span>
                </div>
            </div>
        </footer>

        <a href="https://zalo.me/0384082003" target="_blank" rel="noopener" class="fixed bottom-5 right-5 z-50 grid h-12 w-12 place-items-center rounded-full bg-[#2588e8] text-white shadow-xl shadow-sky-300 transition hover:scale-105" aria-label="Chat tư vấn">
            <MessageCircleMore class="h-6 w-6" />
        </a>
    </div>
</template>

<style scoped>
.hero-bg {
    background:
        radial-gradient(circle at 10% 20%, rgba(186, 230, 253, .55), transparent 40%),
        radial-gradient(circle at 80% 0%, rgba(254, 240, 138, .35), transparent 35%),
        linear-gradient(180deg, #e8f5ff 0%, #f6fbff 70%, #ffffff 100%);
}

.nav-link {
    position: relative;
    padding: 10px 0;
    color: #475569;
    font-size: 14px;
    font-weight: 700;
    transition: color 0.25s ease;
}

.nav-link::after {
    content: "";
    position: absolute;
    left: 0;
    bottom: 0;
    width: 100%;
    height: 2px;
    border-radius: 999px;
    background: #2588e8;
    transform: scaleX(0);
    transform-origin: center;
    transition: transform 0.25s ease;
}

.nav-link:hover,
.nav-link-active {
    color: #2588e8;
}

.nav-link:hover::after,
.nav-link-active::after {
    transform: scaleX(1);
}

.tab-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 8px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 800;
    color: #64748b;
    transition: all .15s;
}
.tab-active {
    background: #fff;
    color: #1f78dd;
    box-shadow: 0 2px 10px rgba(53, 113, 180, .15);
}
.input-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 14px;
    height: 48px;
    border: 1.5px solid #dbe7f5;
    border-radius: 12px;
    background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
.input-wrap:focus-within {
    border-color: #2588e8;
    box-shadow: 0 0 0 4px rgba(37, 136, 232, .12);
}
.input-field {
    width: 100%;
    background: transparent;
    outline: none;
    font-size: 14px;
    font-weight: 600;
    color: #173b75;
}
.input-field::placeholder { color: #94a3b8; }
button:focus-visible, a:focus-visible {
    outline: 3px solid rgba(37, 136, 232, .5);
    outline-offset: 2px;
}
</style>