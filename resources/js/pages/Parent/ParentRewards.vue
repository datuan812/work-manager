<script setup>
import { onMounted, reactive } from 'vue'
import ParentLayout from '../../layouts/ParentLayout.vue'
import BaseButton from '../../components/common/BaseButton.vue'
import BaseInput from '../../components/common/BaseInput.vue'
import LoadingState from '../../components/common/LoadingState.vue'
import { useParentStore } from '../../stores/parent.store'
import { useToastStore } from '../../stores/toast.store'
import { SquarePen, Trash2 } from 'lucide-vue-next'

const parent = useParentStore()
const toast = useToastStore()
const form = reactive({ id: null, title: '', description: '', icon: '🎁', required_points: 100, is_active: true })
function reset() { Object.assign(form, { id: null, title: '', description: '', icon: '🎁', required_points: 100, is_active: true }) }
function edit(reward) { Object.assign(form, reward) }
async function save() { await parent.saveReward(form); toast.show('Đã lưu reward'); reset() }
async function remove(id) {
    if (!window.confirm('Bạn có chắc muốn xóa phần thưởng này?')) return

    await parent.deleteReward(id)
    toast.show('Đã xóa reward')
}
onMounted(() => parent.loadRewards())
</script>

<template>
    <ParentLayout>
        <div>
            <p class="admin-section-title">Động lực</p>
            <h1 class="mt-1 text-3xl font-bold">Phần thưởng</h1>
        </div>

        <div class="mt-6 grid gap-5 lg:grid-cols-[380px_1fr]">
            <div class="self-start xl:sticky xl:top-20">
                <form class="admin-card h-fit p-5" @submit.prevent="save">
                    <div class="mb-5">
                        <h2 class="text-lg font-bold">{{ form.id ? 'Cập nhật phần thưởng' : 'Tạo phần thưởng' }}</h2>
                        <p class="mt-1 text-sm font-semibold text-slate-500">Quy đổi điểm thành phần thưởng cụ thể cho bé.</p>
                    </div>
                    <BaseInput v-model="form.title" label="Tên phần thưởng" />
                    <div class="mt-4 grid grid-cols-2 gap-3"><BaseInput v-model="form.icon" label="Icon" /><BaseInput v-model="form.required_points" label="Điểm cần thiết" type="number" /></div>
                    <BaseInput v-model="form.description" class="mt-4" label="Mô tả" />
                    <label class="mt-4 flex min-h-11 items-center gap-2 text-sm font-bold"><input v-model="form.is_active" type="checkbox" /> Hiển thị</label>
                    <div class="mt-5 flex gap-2"><BaseButton type="submit">{{ form.id ? 'Cập nhật' : 'Tạo mới' }}</BaseButton><BaseButton variant="secondary" @click="reset">Xóa</BaseButton></div>
                </form>
            </div>
            <LoadingState v-if="parent.loadingStates.rewards && !parent.rewards.length" title="Đang tải phần thưởng" message="Danh sách phần thưởng đang được cập nhật." :rows="4" />

            <div v-else class="grid gap-3 md:grid-cols-2">
                <article v-for="reward in parent.rewards" :key="reward.id" class="admin-card p-5">
                    <div class="flex items-start justify-between gap-4">
                        <span class="grid h-12 w-12 place-items-center rounded-xl bg-amber-50 text-2xl">{{ reward.icon }}</span>
                        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">{{ reward.required_points }} điểm</span>
                    </div>
                    <h2 class="mt-4 font-bold">{{ reward.title }}</h2>
                    <p class="mt-1 text-sm font-semibold leading-6 text-slate-500">{{ reward.description }}</p>
                    <div class="mt-4"><button class="text-sm font-bold text-sky-700" title="Chỉnh sửa" @click="edit(reward)"><SquarePen /></button><button title="Xóa" class="ml-3 text-sm font-bold text-red-700" @click="remove(reward.id)"><Trash2 /></button></div>
                </article>
            </div>
        </div>
    </ParentLayout>
</template>
