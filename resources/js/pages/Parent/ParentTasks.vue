<script setup>
import { onMounted, reactive } from "vue";
import ParentLayout from "../../layouts/ParentLayout.vue";
import BaseButton from "../../components/common/BaseButton.vue";
import BaseInput from "../../components/common/BaseInput.vue";
import LoadingState from "../../components/common/LoadingState.vue";
import { useParentStore } from "../../stores/parent.store";
import { useToastStore } from "../../stores/toast.store";
import { SquarePen, Trash2 } from "lucide-vue-next";

const parent = useParentStore();
const toast = useToastStore();
const form = reactive({
    id: null,
    category_id: "",
    title: "",
    description: "",
    icon: "⭐",
    points: 10,
    is_active: true,
});

function reset() {
    Object.assign(form, {
        id: null,
        category_id: parent.categories[0]?.id || "",
        title: "",
        description: "",
        icon: "⭐",
        points: 10,
        is_active: true,
    });
}
function edit(task) {
    Object.assign(form, { ...task, category_id: task.category_id || "" });
}
async function save() {
    const { schedule, user, ...payload } = form;
    await parent.saveTask({
        ...payload,
        user_id: null,
        category_id: form.category_id || null,
    });
    toast.show("Đã lưu task");
    reset();
}
async function remove(id) {
    if (!window.confirm("Bạn có chắc muốn xóa nhiệm vụ này?")) return;

    await parent.deleteTask(id);
    toast.show("Đã xóa task");
}
onMounted(async () => {
    await parent.loadTasks();
    reset();
});
</script>

<template>
    <ParentLayout>
        <div>
            <p class="admin-section-title">Kho nhiệm vụ</p>
            <h1 class="mt-1 text-3xl font-bold">Danh sách nhiệm vụ</h1>
        </div>

        <div class="mt-6 grid gap-5 xl:grid-cols-[410px_1fr]">
            <div class="self-start xl:sticky xl:top-20">
                <form class="admin-card h-fit p-5" @submit.prevent="save">
                    <div class="mb-5">
                        <h2 class="text-lg font-bold">
                            {{ form.id ? "Cập nhật nhiệm vụ" : "Tạo nhiệm vụ" }}
                        </h2>
                        <p class="mt-1 text-sm font-semibold text-slate-500">
                            Tạo nhiệm vụ mẫu để giao trên lịch theo ngày.
                        </p>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <BaseInput v-model="form.title" label="Tên nhiệm vụ" /><BaseInput
                            v-model="form.icon"
                            label="Icon"
                        />
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <BaseInput
                            v-model="form.points"
                            label="Điểm thưởng"
                            type="number"
                        /><label class="block"
                            ><span
                                class="mb-1 block text-xs font-bold uppercase text-slate-500"
                                >Danh mục</span
                            ><select
                                v-model="form.category_id"
                                class="min-h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-semibold"
                            >
                                <option value="">Không danh mục</option>
                                <option
                                    v-for="category in parent.categories"
                                    :key="category.id"
                                    :value="category.id"
                                >
                                    {{ category.icon }} {{ category.name }}
                                </option>
                            </select></label
                        >
                    </div>
                    <label
                        class="mt-4 flex min-h-11 items-center gap-2 text-sm font-bold"
                        ><input v-model="form.is_active" type="checkbox" />
                        Hiển thị</label
                    >
                    <div class="mt-5 flex gap-2">
                        <BaseButton type="submit">{{
                            form.id ? "Cập nhật" : "Tạo mới"
                        }}</BaseButton
                        ><BaseButton variant="secondary" @click="reset"
                            >Xóa</BaseButton
                        >
                    </div>
                </form>
            </div>
            <LoadingState
                v-if="parent.loadingStates.tasks && !parent.tasks.length"
                title="Đang tải nhiệm vụ"
                message="KidTask đang lấy nhiệm vụ và danh mục."
                :rows="6"
            />

            <div v-else class="grid gap-3">
                <article
                    v-for="task in parent.tasks"
                    :key="task.id"
                    class="admin-card p-4"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-3">
                                <span
                                    class="grid h-11 w-11 place-items-center rounded-xl bg-slate-100 text-xl"
                                    >{{ task.icon }}</span
                                >
                                <div>
                                    <h2 class="font-bold">{{ task.title }}</h2>
                                    <p
                                        class="text-sm font-semibold text-slate-500"
                                    >
                                        {{
                                            task.category?.name || "No category"
                                        }}
                                    </p>
                                    <span :class="task.is_active ? 'bg-green-500' : 'bg-orange-500'" class="text-white px-2 py-1 text-xs rounded-full mt-1">{{ task.is_active ? 'Hoạt động' : 'Không hoạt động' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="font-bold text-amber-700 mb-2">
                                +{{ task.points }} ⭐
                            </p>
                            <button title="Chỉnh sửa"
                                class="text-sm font-bold text-sky-700"
                                @click="edit(task)"
                            >
                                <SquarePen /></button
                            ><button title="Xóa"
                                class="ml-3 text-sm font-bold text-red-700"
                                @click="remove(task.id)"
                            >
                                <Trash2 />
                            </button>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </ParentLayout>
</template>
