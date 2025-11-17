<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { computed } from 'vue'

const props = defineProps({
    dictionaries: {
        type: Array,
        required: true,
    },
})

// Группировка справочников по типам (можно расширить)
const mainDictionaries = computed(() => props.dictionaries)
</script>

<template>
    <AppLayout title="Справочники">
        <Head title="Управление справочниками" />

        <div class="py-6 sm:py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Заголовок -->
                <div class="mb-4 sm:mb-6">
                    <h2 class="text-xl sm:text-2xl font-semibold text-gray-800 dark:text-gray-100">
                        📚 Управление справочниками
                    </h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Настройка и управление базовыми справочниками системы
                    </p>
                </div>

                <!-- Сетка карточек справочников -->
                <div class="grid gap-4 sm:gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="dictionary in mainDictionaries"
                        :key="dictionary.key"
                        :href="route('dictionaries.show', dictionary.key)"
                        class="group block overflow-hidden rounded-lg bg-white p-4 sm:p-6 shadow transition hover:shadow-lg dark:bg-gray-800"
                    >
                        <!-- Иконка и название -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 sm:gap-3">
                                    <span class="text-3xl sm:text-4xl flex-shrink-0">{{ dictionary.icon }}</span>
                                    <div class="min-w-0 flex-1">
                                        <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-100 truncate">
                                            {{ dictionary.name }}
                                        </h3>
                                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                            Записей: {{ dictionary.count }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Стрелка -->
                            <svg
                                class="h-5 w-5 flex-shrink-0 text-gray-400 transition group-hover:translate-x-1 group-hover:text-blue-500 dark:text-gray-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5l7 7-7 7"
                                />
                            </svg>
                        </div>

                        <!-- Описание -->
                        <p class="mt-3 sm:mt-4 text-xs sm:text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                            {{ dictionary.description }}
                        </p>

                        <!-- Footer -->
                        <div class="mt-3 sm:mt-4 flex items-center justify-between border-t border-gray-200 pt-3 sm:pt-4 dark:border-gray-700">
                            <span class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                {{ dictionary.singular_name }}
                            </span>
                            <span class="text-xs sm:text-sm font-medium text-blue-600 dark:text-blue-400 whitespace-nowrap ml-2">
                                Управлять →
                            </span>
                        </div>
                    </Link>
                </div>

                <!-- Информационный блок -->
                <div class="mt-8 rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg
                                class="h-5 w-5 text-blue-400"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                Справочная информация
                            </h3>
                            <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                                <p>
                                    Справочники используются для классификации и систематизации данных в системе.
                                    Изменение данных в справочниках влияет на всю систему.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
