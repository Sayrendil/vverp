<script setup>
import { computed } from 'vue';

const props = defineProps({
    task: Object,
});

const typeIcon = computed(() => {
    const icons = {
        'task': '📋',
        'bug': '🐛',
        'feature': '⭐',
        'improvement': '🔧',
    };
    return icons[props.task.type] || '📋';
});

const priorityIcon = computed(() => {
    const icons = {
        'critical': '🔴',
        'high': '🟠',
        'medium': '🟡',
        'low': '🟢',
    };
    return icons[props.task.priority] || '🟢';
});

const priorityColor = computed(() => {
    const colors = {
        'critical': 'border-red-500',
        'high': 'border-orange-500',
        'medium': 'border-yellow-500',
        'low': 'border-green-500',
    };
    return colors[props.task.priority] || 'border-gray-500';
});

const subtasksProgress = computed(() => {
    if (!props.task.subtasks_count) return 0;
    return Math.round((props.task.completed_subtasks_count / props.task.subtasks_count) * 100);
});
</script>

<template>
    <div
        class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm hover:shadow-md transition-shadow cursor-move border-l-4"
        :class="[priorityColor]"
    >
        <!-- Заголовок -->
        <div class="flex items-start justify-between mb-2">
            <span class="text-xs font-mono text-gray-500 dark:text-gray-400">
                {{ task.key }}
            </span>
            <div class="flex items-center space-x-1">
                <span class="text-sm" :title="task.type">{{ typeIcon }}</span>
                <span class="text-sm" :title="task.priority">{{ priorityIcon }}</span>
            </div>
        </div>

        <!-- Название задачи -->
        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3 line-clamp-2">
            {{ task.title }}
        </h4>

        <!-- Метки -->
        <div v-if="task.labels && task.labels.length" class="flex flex-wrap gap-1 mb-3">
            <span
                v-for="label in task.labels"
                :key="label.id"
                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium text-white"
                :style="{ backgroundColor: label.color }"
            >
                {{ label.name }}
            </span>
        </div>

        <!-- Футер -->
        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
            <div class="flex items-center space-x-3">
                <!-- Подзадачи -->
                <div v-if="task.subtasks_count" class="flex items-center space-x-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    <span>{{ task.completed_subtasks_count }}/{{ task.subtasks_count }}</span>
                    <div class="w-12 h-1 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden ml-1">
                        <div class="h-full bg-green-500" :style="{ width: subtasksProgress + '%' }"></div>
                    </div>
                </div>

                <!-- Комментарии -->
                <div v-if="task.comments_count" class="flex items-center space-x-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    <span>{{ task.comments_count }}</span>
                </div>

                <!-- Вложения -->
                <div v-if="task.attachments_count" class="flex items-center space-x-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                    </svg>
                    <span>{{ task.attachments_count }}</span>
                </div>
            </div>

            <!-- Исполнитель -->
            <div v-if="task.assignee" class="flex items-center" :title="task.assignee.name">
                <img
                    v-if="task.assignee.profile_photo_url"
                    :src="task.assignee.profile_photo_url"
                    :alt="task.assignee.name"
                    class="w-6 h-6 rounded-full border-2 border-white dark:border-gray-700"
                />
                <div v-else class="w-6 h-6 rounded-full bg-blue-500 text-white flex items-center justify-center text-xs font-bold">
                    {{ task.assignee.name.charAt(0).toUpperCase() }}
                </div>
            </div>
        </div>

        <!-- Дедлайн (если есть и просрочен) -->
        <div v-if="task.is_overdue" class="mt-2 flex items-center text-xs text-red-600 dark:text-red-400">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Просрочено: {{ task.due_date }}</span>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
