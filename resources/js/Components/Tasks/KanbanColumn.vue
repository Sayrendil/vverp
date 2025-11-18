<script setup>
import { computed } from 'vue';
import TaskCard from './TaskCard.vue';

const props = defineProps({
    status: Object,
    tasks: Array,
});

const emit = defineEmits(['task-clicked', 'task-dropped']);

const handleTaskClick = (task) => {
    emit('task-clicked', task);
};

const handleDragStart = (event, task) => {
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('taskId', task.id.toString());
    event.dataTransfer.setData('sourceStatusId', task.status_id.toString());
};

const handleDragOver = (event) => {
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';
};

const handleDrop = (event) => {
    event.preventDefault();
    const taskId = parseInt(event.dataTransfer.getData('taskId'));
    const sourceStatusId = parseInt(event.dataTransfer.getData('sourceStatusId'));

    if (sourceStatusId !== props.status.id) {
        emit('task-dropped', {
            taskId,
            targetStatusId: props.status.id,
        });
    }
};
</script>

<template>
    <div
        class="flex flex-col bg-gray-50 dark:bg-gray-900 rounded-lg p-4 min-h-[600px]"
        @dragover="handleDragOver"
        @drop="handleDrop"
    >
        <!-- Заголовок колонки -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
                <div
                    class="w-3 h-3 rounded-full mr-2"
                    :style="{ backgroundColor: status.color }"
                ></div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                    {{ status.name }}
                </h3>
                <span class="ml-2 px-2 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-400 bg-gray-200 dark:bg-gray-700 rounded-full">
                    {{ tasks.length }}
                </span>
            </div>
        </div>

        <!-- Задачи -->
        <div class="flex-1 space-y-3 overflow-y-auto">
            <div
                v-for="task in tasks"
                :key="task.id"
                draggable="true"
                @dragstart="handleDragStart($event, task)"
                @click="handleTaskClick(task)"
            >
                <TaskCard :task="task" />
            </div>

            <!-- Пустое состояние -->
            <div
                v-if="!tasks || tasks.length === 0"
                class="text-center text-gray-400 dark:text-gray-600 py-8"
            >
                <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <p class="text-sm">Нет задач</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Кастомный scrollbar для колонки */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: rgba(156, 163, 175, 0.5);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: rgba(156, 163, 175, 0.7);
}
</style>
