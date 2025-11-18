<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import KanbanColumn from '@/Components/Tasks/KanbanColumn.vue';

const props = defineProps({
    project: Object,
    statuses: Array,
    tasks: Object,
    members: Array,
    labels: Array,
});

const showCreateTaskModal = ref(false);
const showTaskDetailsModal = ref(false);
const selectedTask = ref(null);

// Форма создания задачи
const createTaskForm = useForm({
    project_id: props.project.id,
    title: '',
    description: '',
    type: 'task',
    priority: 'medium',
    assignee_id: null,
    due_date: null,
    story_points: null,
});

// Фильтры
const filters = ref({
    assignee: null,
    priority: null,
    search: '',
});

// Получить задачи для статуса
const getTasksForStatus = (statusId) => {
    const statusTasks = props.tasks[statusId] || [];

    // Применить фильтры
    return statusTasks.filter(task => {
        // Фильтр по исполнителю
        if (filters.value.assignee && task.assignee?.id !== filters.value.assignee) {
            return false;
        }

        // Фильтр по приоритету
        if (filters.value.priority && task.priority !== filters.value.priority) {
            return false;
        }

        // Поиск по названию
        if (filters.value.search && !task.title.toLowerCase().includes(filters.value.search.toLowerCase())) {
            return false;
        }

        return true;
    });
};

// Обработка клика по задаче
const handleTaskClick = (task) => {
    router.visit(route('tasks.show', task.id));
};

// Обработка Drag & Drop
const handleTaskDropped = (data) => {
    router.post(
        route('tasks.update-status', data.taskId),
        {
            status_id: data.targetStatusId,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                // Успешно перемещено
            },
        }
    );
};

// Создать задачу
const submitCreateTask = () => {
    createTaskForm.post(route('tasks.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateTaskModal.value = false;
            createTaskForm.reset();
        },
    });
};

// Открыть модалку создания
const openCreateTaskModal = () => {
    createTaskForm.reset();
    showCreateTaskModal.value = true;
};

// Сбросить фильтры
const resetFilters = () => {
    filters.value = {
        assignee: null,
        priority: null,
        search: '',
    };
};

// Проверка прав
const canCreateTask = computed(() => {
    const role = props.project.user_role;
    return ['owner', 'admin', 'member'].includes(role);
});
</script>

<template>
    <AppLayout :title="project.name">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <Link :href="route('projects.index')" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="text-2xl">{{ project.icon }}</span>
                            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                                {{ project.name }}
                            </h2>
                            <span class="text-sm font-mono text-gray-500 dark:text-gray-400">
                                {{ project.key }}
                            </span>
                        </div>
                        <p v-if="project.description" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            {{ project.description }}
                        </p>
                    </div>
                </div>

                <button
                    v-if="canCreateTask"
                    @click="openCreateTaskModal"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring focus:ring-blue-300 disabled:opacity-25 transition"
                >
                    ➕ Создать задачу
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Фильтры -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 mb-6">
                    <div class="flex items-center space-x-4">
                        <!-- Поиск -->
                        <div class="flex-1">
                            <input
                                v-model="filters.search"
                                type="text"
                                placeholder="🔍 Поиск задач..."
                                class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm"
                            />
                        </div>

                        <!-- Фильтр по исполнителю -->
                        <select
                            v-model="filters.assignee"
                            class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm"
                        >
                            <option :value="null">Все исполнители</option>
                            <option v-for="member in members" :key="member.id" :value="member.id">
                                {{ member.name }}
                            </option>
                        </select>

                        <!-- Фильтр по приоритету -->
                        <select
                            v-model="filters.priority"
                            class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm"
                        >
                            <option :value="null">Все приоритеты</option>
                            <option value="critical">🔴 Критический</option>
                            <option value="high">🟠 Высокий</option>
                            <option value="medium">🟡 Средний</option>
                            <option value="low">🟢 Низкий</option>
                        </select>

                        <!-- Кнопка сброса -->
                        <button
                            @click="resetFilters"
                            class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition"
                        >
                            Сбросить
                        </button>
                    </div>
                </div>

                <!-- Kanban доска -->
                <div class="flex space-x-4 overflow-x-auto pb-4">
                    <div
                        v-for="status in statuses"
                        :key="status.id"
                        class="flex-shrink-0 w-80"
                    >
                        <KanbanColumn
                            :status="status"
                            :tasks="getTasksForStatus(status.id)"
                            @task-clicked="handleTaskClick"
                            @task-dropped="handleTaskDropped"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Модалка создания задачи -->
        <div
            v-if="showCreateTaskModal"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Overlay -->
                <div
                    class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity"
                    @click="showCreateTaskModal = false"
                ></div>

                <!-- Modal -->
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <form @submit.prevent="submitCreateTask">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Создать задачу
                                </h3>
                                <button
                                    type="button"
                                    @click="showCreateTaskModal = false"
                                    class="text-gray-400 hover:text-gray-500"
                                >
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="space-y-4">
                                <!-- Название -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Название задачи *
                                    </label>
                                    <input
                                        v-model="createTaskForm.title"
                                        type="text"
                                        required
                                        class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm"
                                        placeholder="Краткое описание задачи"
                                    />
                                    <div v-if="createTaskForm.errors.title" class="text-red-600 text-sm mt-1">
                                        {{ createTaskForm.errors.title }}
                                    </div>
                                </div>

                                <!-- Описание -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Описание
                                    </label>
                                    <textarea
                                        v-model="createTaskForm.description"
                                        rows="4"
                                        class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm"
                                        placeholder="Подробное описание задачи (поддерживается Markdown)"
                                    ></textarea>
                                </div>

                                <!-- Тип и приоритет -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Тип задачи
                                        </label>
                                        <select
                                            v-model="createTaskForm.type"
                                            class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm"
                                        >
                                            <option value="task">📋 Задача</option>
                                            <option value="bug">🐛 Баг</option>
                                            <option value="feature">⭐ Функция</option>
                                            <option value="improvement">🔧 Улучшение</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Приоритет
                                        </label>
                                        <select
                                            v-model="createTaskForm.priority"
                                            class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm"
                                        >
                                            <option value="critical">🔴 Критический</option>
                                            <option value="high">🟠 Высокий</option>
                                            <option value="medium">🟡 Средний</option>
                                            <option value="low">🟢 Низкий</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Исполнитель и дедлайн -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Исполнитель
                                        </label>
                                        <select
                                            v-model="createTaskForm.assignee_id"
                                            class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm"
                                        >
                                            <option :value="null">Не назначен</option>
                                            <option v-for="member in members" :key="member.id" :value="member.id">
                                                {{ member.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Дедлайн
                                        </label>
                                        <input
                                            v-model="createTaskForm.due_date"
                                            type="date"
                                            class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm"
                                        />
                                    </div>
                                </div>

                                <!-- Story Points -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Story Points (1-13)
                                    </label>
                                    <input
                                        v-model.number="createTaskForm.story_points"
                                        type="number"
                                        min="1"
                                        max="13"
                                        class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm"
                                        placeholder="Оценка сложности"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                type="submit"
                                :disabled="createTaskForm.processing"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                            >
                                {{ createTaskForm.processing ? 'Создание...' : 'Создать задачу' }}
                            </button>
                            <button
                                type="button"
                                @click="showCreateTaskModal = false"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Отмена
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Кастомный scrollbar для горизонтального скролла */
.overflow-x-auto::-webkit-scrollbar {
    height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: rgba(243, 244, 246, 0.5);
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: rgba(156, 163, 175, 0.5);
    border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: rgba(156, 163, 175, 0.7);
}
</style>
