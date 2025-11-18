<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const form = useForm({
    name: '',
    key: '',
    description: '',
    icon: '📁',
    color: '#3498db',
});

const submit = () => {
    form.post(route('projects.store'));
};

const generateKey = () => {
    // Генерировать ключ из названия (только заглавные буквы)
    const key = form.name
        .toUpperCase()
        .replace(/[^A-ZА-ЯЁ]/g, '')
        .slice(0, 10);
    form.key = key;
};
</script>

<template>
    <AppLayout title="Создать проект">
        <template #header>
            <div class="flex items-center space-x-3">
                <Link :href="route('projects.index')" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Создать новый проект
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <!-- Название проекта -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Название проекта *
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                @input="generateKey"
                                class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm"
                                placeholder="Например: VVERP Development"
                            />
                            <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">
                                {{ form.errors.name }}
                            </div>
                        </div>

                        <!-- Ключ проекта -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Ключ проекта *
                            </label>
                            <input
                                v-model="form.key"
                                type="text"
                                required
                                maxlength="10"
                                pattern="[A-Z]+"
                                class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm font-mono uppercase"
                                placeholder="VVERP"
                            />
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Только заглавные латинские буквы, до 10 символов. Используется в ключах задач (например: VVERP-15)
                            </p>
                            <div v-if="form.errors.key" class="text-red-600 text-sm mt-1">
                                {{ form.errors.key }}
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
                                placeholder="Краткое описание проекта и его целей"
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
                                    placeholder="📁"
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
                                        placeholder="#3498db"
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
                                        <span class="text-2xl">{{ form.icon || '📁' }}</span>
                                        <div>
                                            <h3 class="font-bold text-gray-900 dark:text-gray-100">
                                                {{ form.name || 'Название проекта' }}
                                            </h3>
                                            <p class="text-sm font-mono text-gray-500 dark:text-gray-400">
                                                {{ form.key || 'KEY' }}
                                            </p>
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ form.description || 'Описание проекта' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Кнопки -->
                        <div class="flex items-center justify-end space-x-3 pt-4">
                            <Link
                                :href="route('projects.index')"
                                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition"
                            >
                                Отмена
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 transition"
                            >
                                {{ form.processing ? 'Создание...' : 'Создать проект' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
