<script setup>
import { useRoute, useRouter } from 'vue-router'
import { reactive, ref } from 'vue'
import { useAuthStore } from '../../stores/auth.store'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const form = reactive({ email: 'parent@gmail.com', password: '12345678' })
const error = ref('')

async function submit() {
    error.value = ''
    try {
        await auth.login(form)
        router.push(route.query.redirect || (auth.user.role === 'admin' ? '/admin' : '/parent'))
    } catch (exception) {
        error.value = exception.message
    }
}
</script>

<template>
    <main class="grid min-h-screen place-items-center bg-sky-50 p-5">
        <form class="w-full max-w-md rounded-3xl bg-white p-8 shadow-xl" @submit.prevent="submit">
            <img src="/build/images/kidtask-logo.png" alt="KidTask" class="mx-auto mb-8 w-56" />
            <h1 class="text-2xl font-black text-slate-900">Đăng nhập bằng tài khoản</h1>
            <p class="mt-2 text-sm font-semibold text-slate-500">Dùng mã gia đình tại trang chủ để đăng nhập nhanh.</p>
            <input v-model="form.email" class="home-input mt-6" type="email" placeholder="Email" required />
            <input v-model="form.password" class="home-input mt-3" type="password" placeholder="Mật khẩu" required />
            <p v-if="error" class="mt-3 rounded-xl bg-red-50 p-3 text-sm font-bold text-red-600">{{ error }}</p>
            <button class="mt-5 min-h-12 w-full rounded-xl bg-sky-600 font-black text-white" type="submit">Đăng nhập</button>
            <router-link to="/" class="mt-4 block text-center text-sm font-bold text-sky-700">Quay lại trang chủ</router-link>
        </form>
    </main>
</template>
