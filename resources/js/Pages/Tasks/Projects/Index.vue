<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    projects: Array,
});
</script>

<template>
    <AppLayout title="Проекты">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    📁 Проекты
                </h2>
                <Link
                    v-if="$page.props.auth.user.role === 'admin'"
                    :href="route('projects.create')"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring focus:ring-blue-300 disabled:opacity-25 transition"
                >
                    ➕ Создать проект
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Пустое состояние -->
                <div v-if="!projects || projects.length === 0" class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-12 text-center">
                    <div class="text-6xl mb-4">📋</div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                        Нет проектов
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Создайте первый проект для начала работы с задачами
                    </p>
                    <Link
                        v-if="$page.props.auth.user.role === 'admin'"
                        :href="route('projects.create')"
                        class="inline-flex items-center px-6 py-3 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition"
                    >
                        Создать проект
                    </Link>
                </div>

                <!-- Список проектов -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link
                        v-for="project in projects"
                        :key="project.id"
                        :href="route('projects.show', project.key)"
                        class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg hover:shadow-2xl transition-shadow duration-300 transform hover:-translate-y-1"
                    >
                        <!-- Цветная полоска сверху -->
                        <div class="h-2" :style="{ backgroundColor: project.color }"></div>

                        <div class="p-6">
                            <!-- Иконка и название -->
                            <div class="flex items-start mb-4">
                                <div class="text-4xl mr-3">{{ project.icon }}</div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 truncate">
                                        {{ project.name }}
                                    </h3>
                                    <p class="text-sm font-mono text-gray-500 dark:text-gray-400">
                                        {{ project.key }}
                                    </p>
                                </div>
                            </div>

                            <!-- Описание -->
                            <p v-if="project.description" class="text-gray-600 dark:text-gray-400 text-sm mb-4 line-clamp-2">
                                {{ project.description }}
                            </p>

                            <!-- Статистика -->
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center space-x-4">
                                    <div class="flex items-center text-gray-600 dark:text-gray-400">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        <span>{{ project.tasks_count }} задач</span>
                                    </div>
                                    <div class="flex items-center text-gray-600 dark:text-gray-400">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        <span>{{ project.members_count }}</span>
                                    </div>
                                </div>

                                <!-- Роль пользователя -->
                                <span
                                    class="px-2 py-1 text-xs font-semibold rounded-full"
                                    :class="{
                                        'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200': project.user_role === 'owner',
                                        'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': project.user_role === 'admin',
                                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': project.user_role === 'member',
                                        'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200': project.user_role === 'viewer',
                                    }"
                                >
                                    {{
                                        project.user_role === 'owner' ? 'Владелец' :
                                        project.user_role === 'admin' ? 'Админ' :
                                        project.user_role === 'member' ? 'Участник' :
                                        'Наблюдатель'
                                    }}
                                </span>
                            </div>

                            <!-- Владелец -->
                            <div v-if="project.owner" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                                    <span class="mr-2">👤 Владелец:</span>
                                    <span class="font-medium">{{ project.owner.name }}</span>
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
