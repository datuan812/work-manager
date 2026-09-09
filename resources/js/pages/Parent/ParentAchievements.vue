<script setup>
import { onMounted, reactive } from 'vue'
import { SquarePen, Trash2 } from 'lucide-vue-next'
import ParentLayout from '../../layouts/ParentLayout.vue'
import BaseButton from '../../components/common/BaseButton.vue'
import BaseInput from '../../components/common/BaseInput.vue'
import ConfirmDialog from '../../components/common/ConfirmDialog.vue'
import LoadingState from '../../components/common/LoadingState.vue'
import { useParentStore } from '../../stores/parent.store'
import { useToastStore } from '../../stores/toast.store'

const parent = useParentStore()
const toast = useToastStore()
const confirmDelete = reactive({ show: false, id: null })
const form = reactive({ id: null, code: '', title: '', description: '', icon: '🏆', criteria: { type: 'completed_tasks', value: 1 }, is_active: true })
const errors = reactive({ code: '', title: '' })

function reset() {
    Object.assign(form, { id: null, code: '', title: '', description: '', icon: '🏆', criteria: { type: 'completed_tasks', value: 1 }, is_active: true })
    Object.assign(errors, { code: '', title: '' })
}

function criteriaFor(achievement) {
    if (achievement.criteria?.type) return achievement.criteria

    return {
        first_step: { type: 'completed_tasks', value: 1 },
        seven_day_streak: { type: 'streak_days', value: 7 },
        task_master: { type: 'completed_tasks', value: 100 },
        perfect_day: { type: 'perfect_day', value: 1 },
    }[achievement.code] || { type: 'completed_tasks', value: 1 }
}

function edit(achievement) {
    Object.assign(form, { ...achievement, description: achievement.description || '', icon: achievement.icon || '🏆', criteria: { ...criteriaFor(achievement) } })
    Object.assign(errors, { code: '', title: '' })
}

async function save() {
    Object.assign(errors, { code: '', title: '' })
    if (!form.code.trim()) errors.code = 'Vui lòng nhập mã thành tựu.'
    else if (!/^[a-z][a-z0-9_]*$/.test(form.code)) errors.code = 'Chỉ dùng chữ thường, số và dấu gạch dưới.'
    if (!form.title.trim()) errors.title = 'Vui lòng nhập tên thành tựu.'
    if (errors.code || errors.title) return

    try {
        const editing = Boolean(form.id)
        await parent.saveAchievement({ ...form, code: form.code.trim(), title: form.title.trim() })
        toast.show(editing ? 'Đã cập nhật thành tựu.' : 'Đã thêm thành tựu.')
        reset()
    } catch (error) {
        toast.show(error.message, 'error')
    }
}

function remove(id) {
    confirmDelete.id = id
    confirmDelete.show = true
}

async function confirmRemove() {
    const id = confirmDelete.id
    confirmDelete.id = null
    if (!id) return
    try {
        await parent.deleteAchievement(id)
        toast.show('Đã xóa thành tựu.')
        if (form.id === id) reset()
    } catch (error) {
        toast.show(error.message, 'error')
    }
}

onMounted(() => parent.loadAchievements())
</script>

<template>
    <ParentLayout>
        <div>
            <p class="admin-section-title">Milestones</p>
            <h1 class="mt-1 text-3xl font-bold">Thành tựu</h1>
        </div>

        <div class="mt-6 grid gap-5 xl:grid-cols-[410px_1fr]">
            <div class="self-start xl:sticky xl:top-20">
                <form class="admin-card p-5" @submit.prevent="save">
                    <h2 class="text-lg font-bold">{{ form.id ? 'Cập nhật thành tựu' : 'Tạo thành tựu' }}</h2>
                    <div class="mt-4 grid grid-cols-[1fr_90px] gap-3">
                        <BaseInput v-model="form.title" label="Tên thành tựu" :error="errors.title" @update:model-value="errors.title = ''" />
                        <BaseInput v-model="form.icon" label="Icon" />
                    </div>
                    <div class="mt-4">
                        <BaseInput v-model="form.code" label="Mã thành tựu" placeholder="first_step" :error="errors.code" @update:model-value="errors.code = ''" />
                        <p class="mt-1 text-xs font-semibold text-slate-500">Dùng chữ thường, số và dấu gạch dưới; mã không được trùng.</p>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <label class="block">
                            <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Điều kiện mở khóa</span>
                            <select v-model="form.criteria.type" class="min-h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-semibold">
                                <option value="completed_tasks">Hoàn thành số nhiệm vụ</option>
                                <option value="streak_days">Chuỗi ngày liên tiếp</option>
                                <option value="perfect_day">Hoàn thành trọn ngày</option>
                            </select>
                        </label>
                        <BaseInput v-if="form.criteria.type !== 'perfect_day'" v-model="form.criteria.value" type="number" label="Mốc cần đạt" />
                        <p v-else class="self-end pb-3 text-sm font-semibold text-slate-500">Hoàn thành tất cả nhiệm vụ trong ngày.</p>
                    </div>
                    <label class="mt-4 block">
                        <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Mô tả</span>
                        <textarea v-model="form.description" rows="3" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold outline-none focus:border-sky-500 focus:ring-3 focus:ring-sky-100"></textarea>
                    </label>
                    <label class="mt-4 flex min-h-11 items-center gap-2 text-sm font-bold"><input v-model="form.is_active" type="checkbox" /> Hiển thị thành tựu</label>
                    <div class="mt-5 flex gap-2">
                        <BaseButton type="submit">{{ form.id ? 'Cập nhật' : 'Tạo mới' }}</BaseButton>
                        <BaseButton type="button" variant="secondary" @click="reset">Làm mới</BaseButton>
                    </div>
                </form>
            </div>

            <div>
                <LoadingState v-if="parent.loadingStates.achievements && !parent.achievements.length" title="Đang tải thành tựu" message="KidTask đang lấy danh sách milestone." :rows="4" />

        <div v-else class="grid gap-4 md:grid-cols-2">
            <article v-for="achievement in parent.achievements" :key="achievement.id" class="admin-card p-5">
                <div class="flex items-start justify-between gap-4">
                    <p class="grid h-12 w-12 place-items-center rounded-xl bg-sky-50 text-2xl">{{ achievement.icon }}</p>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ achievement.users_count }} unlocked</span>
                        <button type="button" class="text-sky-700" title="Chỉnh sửa" @click="edit(achievement)"><SquarePen class="h-5 w-5" /></button>
                        <button type="button" class="text-red-700" title="Xóa" @click="remove(achievement.id)"><Trash2 class="h-5 w-5" /></button>
                    </div>
                </div>
                <h2 class="mt-4 font-bold">{{ achievement.title }}</h2>
                <p class="mt-1 text-sm font-semibold leading-6 text-slate-500">{{ achievement.description || 'Chưa có mô tả.' }}</p>
                <div class="mt-3 flex items-center justify-between gap-3 text-xs font-bold">
                    <code class="rounded bg-slate-100 px-2 py-1 text-slate-600">{{ achievement.code }}</code>
                    <span :class="achievement.is_active ? 'text-emerald-700' : 'text-slate-400'">{{ achievement.is_active ? 'Đang bật' : 'Đang tắt' }}</span>
                </div>
            </article>
                <p v-if="!parent.achievements.length" class="py-10 text-center text-sm font-semibold text-slate-500">Chưa có thành tựu nào.</p>
            </div>
        </div>
        </div>

        <ConfirmDialog v-model="confirmDelete.show" title="Xóa thành tựu" message="Bạn có chắc muốn xóa thành tựu này? Thành tựu đã mở khóa cũng sẽ bị xóa." confirm-text="Xóa" cancel-text="Hủy" @confirm="confirmRemove" />
    </ParentLayout>
</template>
