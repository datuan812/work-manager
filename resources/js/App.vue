<script setup>
import { computed, ref, watch } from 'vue'

const storageKey = 'kids-daily-tasks'

const defaultChildren = [
    {
        id: 1,
        name: 'Minh Anh',
        age: 7,
        color: '#1f8a70',
        tasks: [
            { id: 101, title: 'Dọn đồ chơi', time: 'Sáng', points: 10, done: false },
            { id: 102, title: 'Học bài 30 phút', time: 'Chiều', points: 15, done: false },
            { id: 103, title: 'Đánh răng trước khi ngủ', time: 'Tối', points: 10, done: true },
        ],
    },
    {
        id: 2,
        name: 'Gia Huy',
        age: 5,
        color: '#d97706',
        tasks: [
            { id: 201, title: 'Xếp quần áo vào tủ', time: 'Sáng', points: 10, done: false },
            { id: 202, title: 'Tập viết chữ', time: 'Chiều', points: 15, done: false },
            { id: 203, title: 'Rửa tay trước khi ăn', time: 'Tối', points: 8, done: false },
        ],
    },
]

const loadChildren = () => {
    try {
        const saved = JSON.parse(localStorage.getItem(storageKey))
        return Array.isArray(saved) && saved.length ? saved : defaultChildren
    } catch {
        return defaultChildren
    }
}

const children = ref(loadChildren())
const activeChildId = ref(children.value[0]?.id)
const newTaskTitle = ref('')
const newTaskTime = ref('Sáng')
const newTaskPoints = ref(10)

const today = new Intl.DateTimeFormat('vi-VN', {
    weekday: 'long',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
}).format(new Date())

const activeChild = computed(() => children.value.find((child) => child.id === activeChildId.value) ?? children.value[0])

const completedTasks = computed(() => activeChild.value?.tasks.filter((task) => task.done).length ?? 0)
const totalTasks = computed(() => activeChild.value?.tasks.length ?? 0)
const completionPercent = computed(() => {
    if (!totalTasks.value) {
        return 0
    }

    return Math.round((completedTasks.value / totalTasks.value) * 100)
})

const earnedPoints = computed(() =>
    activeChild.value?.tasks.reduce((total, task) => total + (task.done ? task.points : 0), 0) ?? 0,
)

const totalPoints = computed(() =>
    activeChild.value?.tasks.reduce((total, task) => total + task.points, 0) ?? 0,
)

const toggleTask = (taskId) => {
    const task = activeChild.value?.tasks.find((item) => item.id === taskId)

    if (task) {
        task.done = !task.done
    }
}

const addTask = () => {
    const title = newTaskTitle.value.trim()

    if (!title || !activeChild.value) {
        return
    }

    activeChild.value.tasks.push({
        id: Date.now(),
        title,
        time: newTaskTime.value,
        points: Number(newTaskPoints.value) || 5,
        done: false,
    })

    newTaskTitle.value = ''
    newTaskPoints.value = 10
}

const resetDay = () => {
    activeChild.value?.tasks.forEach((task) => {
        task.done = false
    })
}

watch(
    children,
    (value) => {
        localStorage.setItem(storageKey, JSON.stringify(value))
    },
    { deep: true },
)
</script>

<template>
    <main class="min-h-screen bg-[#f7f4ed] text-[#24302b]">
        <section class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
            <header class="flex flex-col gap-4 rounded-lg border border-[#ded7ca] bg-white p-5 shadow-sm md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase text-[#1f8a70]">Bảng việc hôm nay</p>
                    <h1 class="mt-2 text-3xl font-bold leading-tight text-[#17201c]">Quản lý công việc cho con</h1>
                    <p class="mt-2 max-w-2xl text-sm text-[#66736d]">
                        Phụ huynh setup sẵn các việc cần làm, bé chỉ cần tích vào những việc đã hoàn thành trong ngày.
                    </p>
                </div>

                <div class="rounded-lg border border-[#e5ded2] bg-[#fbfaf7] px-4 py-3 text-sm">
                    <p class="font-semibold text-[#17201c]">{{ today }}</p>
                    <p class="mt-1 text-[#66736d]">{{ completedTasks }}/{{ totalTasks }} việc đã xong</p>
                </div>
            </header>

            <div class="grid gap-6 lg:grid-cols-[280px_1fr]">
                <aside class="rounded-lg border border-[#ded7ca] bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-bold">Danh sách bé</h2>
                        <span class="rounded-full bg-[#eef7f3] px-3 py-1 text-xs font-semibold text-[#1f8a70]">
                            {{ children.length }} bé
                        </span>
                    </div>

                    <div class="mt-4 space-y-3">
                        <button
                            v-for="child in children"
                            :key="child.id"
                            type="button"
                            class="flex w-full items-center gap-3 rounded-lg border p-3 text-left transition hover:border-[#1f8a70]"
                            :class="child.id === activeChild?.id ? 'border-[#1f8a70] bg-[#eef7f3]' : 'border-[#e7e1d8] bg-white'"
                            @click="activeChildId = child.id"
                        >
                            <span
                                class="grid h-11 w-11 shrink-0 place-items-center rounded-full text-sm font-bold text-white"
                                :style="{ backgroundColor: child.color }"
                            >
                                {{ child.name.slice(0, 1) }}
                            </span>
                            <span>
                                <span class="block font-semibold text-[#17201c]">{{ child.name }}</span>
                                <span class="text-sm text-[#66736d]">{{ child.age }} tuổi</span>
                            </span>
                        </button>
                    </div>
                </aside>

                <section class="space-y-6">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="rounded-lg border border-[#ded7ca] bg-white p-4 shadow-sm">
                            <p class="text-sm text-[#66736d]">Tiến độ</p>
                            <p class="mt-2 text-3xl font-bold">{{ completionPercent }}%</p>
                            <div class="mt-3 h-2 rounded-full bg-[#ece6db]">
                                <div
                                    class="h-2 rounded-full bg-[#1f8a70] transition-all"
                                    :style="{ width: `${completionPercent}%` }"
                                ></div>
                            </div>
                        </div>
                        <div class="rounded-lg border border-[#ded7ca] bg-white p-4 shadow-sm">
                            <p class="text-sm text-[#66736d]">Điểm hôm nay</p>
                            <p class="mt-2 text-3xl font-bold">{{ earnedPoints }}/{{ totalPoints }}</p>
                            <p class="mt-2 text-sm text-[#66736d]">Dùng để thưởng sao hoặc phần quà nhỏ.</p>
                        </div>
                        <div class="rounded-lg border border-[#ded7ca] bg-white p-4 shadow-sm">
                            <p class="text-sm text-[#66736d]">Trạng thái</p>
                            <p class="mt-2 text-xl font-bold">
                                {{ completionPercent === 100 ? 'Hoàn thành ngày' : 'Đang thực hiện' }}
                            </p>
                            <button
                                type="button"
                                class="mt-3 rounded-md border border-[#ded7ca] px-3 py-2 text-sm font-semibold text-[#34413b] hover:bg-[#fbfaf7]"
                                @click="resetDay"
                            >
                                Đặt lại hôm nay
                            </button>
                        </div>
                    </div>

                    <div class="rounded-lg border border-[#ded7ca] bg-white p-4 shadow-sm">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-xl font-bold">Việc cần làm của {{ activeChild?.name }}</h2>
                                <p class="text-sm text-[#66736d]">Chạm vào từng dòng để đánh dấu hoàn thành.</p>
                            </div>
                        </div>

                        <div class="mt-4 space-y-3">
                            <button
                                v-for="task in activeChild?.tasks"
                                :key="task.id"
                                type="button"
                                class="flex w-full items-center gap-3 rounded-lg border p-4 text-left transition"
                                :class="task.done ? 'border-[#1f8a70] bg-[#eef7f3]' : 'border-[#e7e1d8] bg-white hover:border-[#d97706]'"
                                @click="toggleTask(task.id)"
                            >
                                <span
                                    class="grid h-9 w-9 shrink-0 place-items-center rounded-md border text-lg font-bold"
                                    :class="task.done ? 'border-[#1f8a70] bg-[#1f8a70] text-white' : 'border-[#cfc6b8] text-transparent'"
                                >
                                    ✓
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block font-semibold" :class="task.done ? 'text-[#1f8a70]' : 'text-[#17201c]'">
                                        {{ task.title }}
                                    </span>
                                    <span class="text-sm text-[#66736d]">{{ task.time }} · {{ task.points }} điểm</span>
                                </span>
                            </button>
                        </div>
                    </div>

                    <form class="rounded-lg border border-[#ded7ca] bg-white p-4 shadow-sm" @submit.prevent="addTask">
                        <h2 class="text-lg font-bold">Thêm việc mới</h2>
                        <div class="mt-4 grid gap-3 md:grid-cols-[1fr_140px_120px_auto]">
                            <input
                                v-model="newTaskTitle"
                                type="text"
                                class="rounded-md border border-[#d8d0c3] px-3 py-2 outline-none focus:border-[#1f8a70] focus:ring-2 focus:ring-[#d8eee6]"
                                placeholder="Ví dụ: Gấp chăn sau khi ngủ dậy"
                            />
                            <select
                                v-model="newTaskTime"
                                class="rounded-md border border-[#d8d0c3] px-3 py-2 outline-none focus:border-[#1f8a70] focus:ring-2 focus:ring-[#d8eee6]"
                            >
                                <option>Sáng</option>
                                <option>Chiều</option>
                                <option>Tối</option>
                            </select>
                            <input
                                v-model="newTaskPoints"
                                type="number"
                                min="1"
                                class="rounded-md border border-[#d8d0c3] px-3 py-2 outline-none focus:border-[#1f8a70] focus:ring-2 focus:ring-[#d8eee6]"
                            />
                            <button
                                type="submit"
                                class="rounded-md bg-[#d97706] px-4 py-2 font-semibold text-white hover:bg-[#b96005]"
                            >
                                + Thêm
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </section>
    </main>
</template>
