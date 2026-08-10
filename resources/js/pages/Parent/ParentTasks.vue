<script setup>
import { computed, onMounted, reactive } from 'vue'
import ParentLayout from '../../layouts/ParentLayout.vue'
import BaseButton from '../../components/common/BaseButton.vue'
import BaseInput from '../../components/common/BaseInput.vue'
import LoadingState from '../../components/common/LoadingState.vue'
import { useParentStore } from '../../stores/parent.store'
import { useToastStore } from '../../stores/toast.store'

const parent = useParentStore()
const toast = useToastStore()
const form = reactive({
    id: null,
    user_id: '',
    category_id: '',
    title: '',
    description: '',
    icon: '⭐',
    points: 10,
    is_active: true,
    schedule: { repeat_type: 'daily', start_date: new Date().toISOString().slice(0, 10), days_of_week: [], time_of_day: null },
})
const children = computed(() => parent.children)

function reset() {
    Object.assign(form, { id: null, user_id: children.value[0]?.id || '', category_id: parent.categories[0]?.id || '', title: '', description: '', icon: '⭐', points: 10, is_active: true, schedule: { repeat_type: 'daily', start_date: new Date().toISOString().slice(0, 10), days_of_week: [], time_of_day: null } })
}
function edit(task) {
    Object.assign(form, { ...task, user_id: task.user_id, category_id: task.category_id || '', schedule: { ...task.schedule, time_of_day: task.schedule?.time_of_day?.slice(0, 5) || null } })
}
async function save() {
    await parent.saveTask({ ...form, category_id: form.category_id || null })
    toast.show('Đã lưu task')
    reset()
}
async function remove(id) {
    await parent.deleteTask(id)
    toast.show('Đã xóa task')
}
onMounted(async () => {
    await Promise.all([parent.loadChildren(), parent.loadTasks()])
    reset()
})
</script>

<template>
    <ParentLayout>
        <div>
            <p class="admin-section-title">Lịch sinh hoạt</p>
            <h1 class="mt-1 text-3xl font-black">Nhiệm vụ & lịch lặp</h1>
        </div>

        <div class="mt-6 grid gap-5 xl:grid-cols-[410px_1fr]">
            <form class="admin-card p-5" @submit.prevent="save">
                <div class="mb-5">
                    <h2 class="text-lg font-black">{{ form.id ? 'Cập nhật nhiệm vụ' : 'Tạo nhiệm vụ' }}</h2>
                    <p class="mt-1 text-sm font-semibold text-slate-500">Thiết lập việc cần làm, điểm thưởng và chu kỳ lặp.</p>
                </div>
                <label class="block"><span class="mb-1 block text-xs font-bold uppercase text-slate-500">Child</span><select v-model="form.user_id" class="min-h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-semibold"><option v-for="child in children" :key="child.id" :value="child.id">{{ child.name }}</option></select></label>
                <div class="mt-4 grid grid-cols-2 gap-3"><BaseInput v-model="form.title" label="Title" /><BaseInput v-model="form.icon" label="Icon" /></div>
                <div class="mt-4 grid grid-cols-2 gap-3"><BaseInput v-model="form.points" label="Points" type="number" /><label class="block"><span class="mb-1 block text-xs font-bold uppercase text-slate-500">Category</span><select v-model="form.category_id" class="min-h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-semibold"><option v-for="category in parent.categories" :key="category.id" :value="category.id">{{ category.icon }} {{ category.name }}</option></select></label></div>
                <label class="mt-4 block"><span class="mb-1 block text-xs font-bold uppercase text-slate-500">Repeat</span><select v-model="form.schedule.repeat_type" class="min-h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-semibold"><option value="once">Once</option><option value="daily">Daily</option><option value="weekly">Weekly</option><option value="custom_days">Custom days</option></select></label>
                <BaseInput v-model="form.schedule.start_date" class="mt-4" label="Start date" type="date" />
                <label class="mt-4 flex min-h-11 items-center gap-2 text-sm font-bold"><input v-model="form.is_active" type="checkbox" /> Active</label>
                <div class="mt-5 flex gap-2"><BaseButton type="submit">{{ form.id ? 'Update' : 'Create' }}</BaseButton><BaseButton variant="secondary" @click="reset">Clear</BaseButton></div>
            </form>
            <LoadingState v-if="parent.loadingStates.tasks && !parent.tasks.length" title="Đang tải nhiệm vụ" message="KidTask đang lấy nhiệm vụ và danh mục." :rows="6" />

            <div v-else class="grid gap-3">
                <article v-for="task in parent.tasks" :key="task.id" class="admin-card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-3">
                                <span class="grid h-11 w-11 place-items-center rounded-xl bg-slate-100 text-xl">{{ task.icon }}</span>
                                <div>
                                    <h2 class="font-black">{{ task.title }}</h2>
                                    <p class="text-sm font-semibold text-slate-500">{{ task.user?.name }} · {{ task.category?.name || 'No category' }} · {{ task.schedule?.repeat_type }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="shrink-0 text-right"><p class="font-black text-amber-700">+{{ task.points }} ⭐</p><button class="text-sm font-black text-sky-700" @click="edit(task)">Edit</button><button class="ml-3 text-sm font-black text-red-700" @click="remove(task.id)">Delete</button></div>
                    </div>
                </article>
            </div>
        </div>
    </ParentLayout>
</template>
