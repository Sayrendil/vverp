<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    project: Object,
});

const form = useForm({
    name: props.project.name,
    description: props.project.description,
    icon: props.project.icon,
    color: props.project.color,
});

const submit = () => {
    form.put(route('projects.update', props.project.key));
};
</script>

<template>
    <AppLayout :title="`Редактировать ${project.name}`">
        <template #header>
            <div class="flex items-center space-x-3">
                <Link :href="route('projects.show', project.key)" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Редактировать проект
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ project.key }}
                    </p>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <!-- Ключ проекта (только для информации) -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="flex-1">
                                    <p class="text-sm text-blue-800 dark:text-blue-300 font-medium">
                                        Ключ проекта: <span class="font-mono">{{ project.key }}</span>
                                    </p>
                                    <p class="text-xs text-blue-700 dark:text-blue-400 mt-1">
                                        Ключ проекта нельзя изменить после создания
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Название проекта -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Название проекта *
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm"
                            />
                            <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">
                                {{ form.errors.name }}
                            </div>
                        </div>

                        <!-- Описание -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Описание
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="4"
                                class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm"
                            ></textarea>
                        </div>

                        <!-- Иконка и цвет -->
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Иконка (Emoji)
                                </label>
                                <input
                                    v-model="form.icon"
                                    type="text"
                                    maxlength="2"
                                    class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm text-2xl text-center"
                                />
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-center">
                                    Популярные: 🚀 📊 💼 🎯 ⚙️ 🔧
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Цвет
                                </label>
                                <div class="flex items-center space-x-2">
                                    <input
                                        v-model="form.color"
                                        type="color"
                                        class="h-10 w-20 border-gray-300 dark:border-gray-700 rounded cursor-pointer"
                                    />
                                    <input
                                        v-model="form.color"
                                        type="text"
                                        pattern="#[0-9A-Fa-f]{6}"
                                        class="flex-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm font-mono uppercase"
                                    />
                                </div>
                                <div v-if="form.errors.color" class="text-red-600 text-sm mt-1">
                                    {{ form.errors.color }}
                                </div>
                            </div>
                        </div>

                        <!-- Предпросмотр -->
                        <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Предпросмотр:</p>
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                                <div class="h-2" :style="{ backgroundColor: form.color }"></div>
                                <div class="p-4">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <span class="text-2xl">{{ form.icon }}</span>
                                        <div>
                                            <h3 class="font-bold text-gray-900 dark:text-gray-100">
                                                {{ form.name }}
                                            </h3>
                                            <p class="text-sm font-mono text-gray-500 dark:text-gray-400">
                                                {{ project.key }}
                                            </p>
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ form.description }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Кнопки -->
                        <div class="flex items-center justify-end space-x-3 pt-4">
                            <Link
                                :href="route('projects.show', project.key)"
                                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition"
                            >
                                Отмена
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 transition"
                            >
                                {{ form.processing ? 'Сохранение...' : 'Сохранить изменения' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
