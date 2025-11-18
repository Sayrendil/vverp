<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    task: Object,
    project: Object,
    statuses: Array,
    members: Array,
    labels: Array,
});

// Формы
const commentForm = useForm({
    content: '',
});

const attachmentForm = useForm({
    file: null,
});

// Refs
const fileInput = ref(null);

// Computed
const typeIcon = computed(() => {
    const icons = { task: '📋', bug: '🐛', feature: '⭐', improvement: '🔧' };
    return icons[props.task.type] || '📋';
});

const priorityIcon = computed(() => {
    const icons = { critical: '🔴', high: '🟠', medium: '🟡', low: '🟢' };
    return icons[props.task.priority] || '🟢';
});

// Методы
const updateStatus = (statusId) => {
    router.post(route('tasks.update-status', props.task.id), {
        status_id: statusId,
    }, {
        preserveScroll: true,
    });
};

const updateAssignee = (assigneeId) => {
    router.post(route('tasks.update-assignee', props.task.id), {
        assignee_id: assigneeId,
    }, {
        preserveScroll: true,
    });
};

const updatePriority = (priority) => {
    router.post(route('tasks.update-priority', props.task.id), {
        priority: priority,
    }, {
        preserveScroll: true,
    });
};

const submitComment = () => {
    commentForm.post(route('tasks.comments.store', props.task.id), {
        preserveScroll: true,
        onSuccess: () => {
            commentForm.reset();
        },
    });
};

const deleteComment = (commentId) => {
    if (confirm('Удалить комментарий?')) {
        router.delete(route('tasks.comments.destroy', commentId), {
            preserveScroll: true,
        });
    }
};

const uploadAttachment = () => {
    if (fileInput.value?.files[0]) {
        attachmentForm.file = fileInput.value.files[0];
        attachmentForm.post(route('tasks.attachments.store', props.task.id), {
            preserveScroll: true,
            onSuccess: () => {
                attachmentForm.reset();
                fileInput.value.value = '';
            },
        });
    }
};

const deleteAttachment = (attachmentId) => {
    if (confirm('Удалить вложение?')) {
        router.delete(route('tasks.attachments.destroy', attachmentId), {
            preserveScroll: true,
        });
    }
};

const deleteTask = () => {
    if (confirm('Вы уверены? Это действие нельзя отменить.')) {
        router.delete(route('tasks.destroy', props.task.id), {
            onSuccess: () => {
                router.visit(route('projects.show', props.project.key));
            },
        });
    }
};
</script>

<template>
    <AppLayout :title="task.key">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <Link :href="route('projects.show', project.key)" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="text-lg">{{ typeIcon }}</span>
                            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                                {{ task.key }}
                            </h2>
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <button
                        @click="deleteTask"
                        class="inline-flex items-center px-3 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition"
                    >
                        🗑️ Удалить
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Левая колонка - контент -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Заголовок задачи -->
                        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                                {{ task.title }}
                            </h1>

                            <!-- Описание -->
                            <div v-if="task.description" class="prose dark:prose-invert max-w-none">
                                <div class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ task.description }}</div>
                            </div>
                            <div v-else class="text-gray-500 dark:text-gray-400 italic">
                                Описание отсутствует
                            </div>
                        </div>

                        <!-- Подзадачи -->
                        <div v-if="task.subtasks && task.subtasks.length" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                Подзадачи
                            </h3>
                            <div class="space-y-2">
                                <Link
                                    v-for="subtask in task.subtasks"
                                    :key="subtask.id"
                                    :href="route('tasks.show', subtask.id)"
                                    class="flex items-center p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                                >
                                    <span class="mr-2">
                                        {{ subtask.status.is_final ? '✅' : '⏺️' }}
                                    </span>
                                    <span class="flex-1 text-gray-900 dark:text-gray-100">
                                        {{ subtask.key }} - {{ subtask.title }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ subtask.status.name }}
                                    </span>
                                </Link>
                            </div>
                        </div>

                        <!-- Вложения -->
                        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                📎 Вложения
                            </h3>

                            <!-- Список вложений -->
                            <div v-if="task.attachments && task.attachments.length" class="space-y-2 mb-4">
                                <div
                                    v-for="attachment in task.attachments"
                                    :key="attachment.id"
                                    class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded-lg"
                                >
                                    <div class="flex items-center space-x-3">
                                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                        </svg>
                                        <div>
                                            <a
                                                :href="attachment.url"
                                                target="_blank"
                                                class="text-blue-600 dark:text-blue-400 hover:underline"
                                            >
                                                {{ attachment.file_name }}
                                            </a>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ attachment.file_size }} • {{ attachment.user.name }} • {{ attachment.created_at }}
                                            </p>
                                        </div>
                                    </div>
                                    <button
                                        @click="deleteAttachment(attachment.id)"
                                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Форма загрузки -->
                            <div class="flex items-center space-x-2">
                                <input
                                    ref="fileInput"
                                    type="file"
                                    class="flex-1 text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900 dark:file:text-blue-300"
                                />
                                <button
                                    @click="uploadAttachment"
                                    :disabled="attachmentForm.processing"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 transition"
                                >
                                    {{ attachmentForm.processing ? 'Загрузка...' : 'Загрузить' }}
                                </button>
                            </div>
                        </div>

                        <!-- Комментарии -->
                        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                💬 Комментарии ({{ task.comments.length }})
                            </h3>

                            <!-- Список комментариев -->
                            <div v-if="task.comments && task.comments.length" class="space-y-4 mb-6">
                                <div
                                    v-for="comment in task.comments"
                                    :key="comment.id"
                                    class="border-l-4 border-blue-500 pl-4 py-2"
                                >
                                    <div class="flex items-start justify-between mb-2">
                                        <div class="flex items-center space-x-2">
                                            <img
                                                v-if="comment.user.profile_photo_url"
                                                :src="comment.user.profile_photo_url"
                                                :alt="comment.user.name"
                                                class="w-8 h-8 rounded-full"
                                            />
                                            <div v-else class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center text-sm font-bold">
                                                {{ comment.user.name.charAt(0).toUpperCase() }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ comment.user.name }}
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ comment.created_at }}
                                                </p>
                                            </div>
                                        </div>
                                        <button
                                            @click="deleteComment(comment.id)"
                                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ comment.content }}</p>
                                </div>
                            </div>

                            <!-- Форма добавления комментария -->
                            <form @submit.prevent="submitComment">
                                <textarea
                                    v-model="commentForm.content"
                                    rows="3"
                                    placeholder="Добавить комментарий..."
                                    class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm mb-2"
                                ></textarea>
                                <button
                                    type="submit"
                                    :disabled="!commentForm.content || commentForm.processing"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 transition"
                                >
                                    {{ commentForm.processing ? 'Отправка...' : 'Добавить комментарий' }}
                                </button>
                            </form>
                        </div>

                        <!-- История изменений -->
                        <div v-if="task.activities && task.activities.length" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                📝 История изменений
                            </h3>
                            <div class="space-y-3">
                                <div
                                    v-for="activity in task.activities"
                                    :key="activity.id"
                                    class="flex items-start space-x-3 text-sm"
                                >
                                    <span class="text-gray-500 dark:text-gray-400">{{ activity.created_at }}</span>
                                    <p class="flex-1 text-gray-700 dark:text-gray-300">
                                        {{ activity.description }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Правая колонка - метаданные -->
                    <div class="space-y-6">
                        <!-- Статус -->
                        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Статус
                            </label>
                            <select
                                :value="task.status_id"
                                @change="updateStatus($event.target.value)"
                                class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm"
                            >
                                <option v-for="status in statuses" :key="status.id" :value="status.id">
                                    {{ status.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Исполнитель -->
                        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Исполнитель
                            </label>
                            <select
                                :value="task.assignee?.id"
                                @change="updateAssignee($event.target.value || null)"
                                class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm"
                            >
                                <option :value="null">Не назначен</option>
                                <option v-for="member in members" :key="member.id" :value="member.id">
                                    {{ member.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Приоритет -->
                        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Приоритет
                            </label>
                            <select
                                :value="task.priority"
                                @change="updatePriority($event.target.value)"
                                class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm"
                            >
                                <option value="critical">🔴 Критический</option>
                                <option value="high">🟠 Высокий</option>
                                <option value="medium">🟡 Средний</option>
                                <option value="low">🟢 Низкий</option>
                            </select>
                        </div>

                        <!-- Метки -->
                        <div v-if="task.labels && task.labels.length" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Метки
                            </label>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="label in task.labels"
                                    :key="label.id"
                                    class="inline-flex items-center px-2 py-1 rounded text-xs font-medium text-white"
                                    :style="{ backgroundColor: label.color }"
                                >
                                    {{ label.name }}
                                </span>
                            </div>
                        </div>

                        <!-- Метаинформация -->
                        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 space-y-3 text-sm">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Создал:</span>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ task.reporter.name }}</p>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Создана:</span>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ task.created_at }}</p>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Обновлена:</span>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ task.updated_at }}</p>
                            </div>
                            <div v-if="task.due_date">
                                <span class="text-gray-500 dark:text-gray-400">Дедлайн:</span>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ task.due_date }}</p>
                            </div>
                            <div v-if="task.story_points">
                                <span class="text-gray-500 dark:text-gray-400">Story Points:</span>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ task.story_points }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
