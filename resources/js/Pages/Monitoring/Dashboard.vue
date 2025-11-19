<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, computed } from 'vue'

const props = defineProps({
    statistics: {
        type: Object,
        required: true,
    },
    problematicHosts: {
        type: Array,
        required: true,
    },
    days: {
        type: Number,
        default: 7,
    },
})

const isCheckingAll = ref(false)

// Цвет для uptime
const getUptimeColor = (percent) => {
    if (percent >= 99) return 'text-green-600 dark:text-green-400'
    if (percent >= 95) return 'text-yellow-600 dark:text-yellow-400'
    return 'text-red-600 dark:text-red-400'
}

// Проверить все хосты
const checkAllHosts = async () => {
    if (isCheckingAll.value) return

    isCheckingAll.value = true

    try {
        const response = await axios.post(route('monitoring.check-all'))

        if (response.data.success) {
            alert(`✅ ${response.data.message}`)
        }
    } catch (error) {
        console.error('Error checking hosts:', error)
        alert('❌ Ошибка при запуске проверки')
    } finally {
        isCheckingAll.value = false

        // Обновить страницу через 3 секунды
        setTimeout(() => {
            router.reload({ only: ['statistics', 'problematicHosts'] })
        }, 3000)
    }
}

// Форматирование числа
const formatNumber = (num) => {
    if (num === null || num === undefined) return 'N/A'
    return num.toLocaleString('ru-RU')
}
</script>

<template>
    <AppLayout title="Мониторинг">
        <Head title="Мониторинг хостов" />

        <div class="py-6 sm:py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Заголовок -->
                <div class="mb-4 sm:mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-semibold text-gray-800 dark:text-gray-100">
                            🖥️ Мониторинг доступности хостов
                        </h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Статистика за последние {{ days }} дней
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button
                            @click="checkAllHosts"
                            :disabled="isCheckingAll"
                            class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring focus:ring-green-300 disabled:opacity-50 transition"
                        >
                            <svg v-if="isCheckingAll" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ isCheckingAll ? 'Проверка...' : 'Проверить все' }}
                        </button>
                        <Link
                            :href="route('dictionaries.show', 'hosts')"
                            class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition"
                        >
                            Управление хостами
                        </Link>
                    </div>
                </div>

                <!-- Статистика - карточки -->
                <div class="grid gap-4 sm:gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                    <!-- Всего хостов -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-md bg-blue-50 dark:bg-blue-900/20 p-3">
                                    <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                        Всего хостов
                                    </dt>
                                    <dd class="flex items-baseline">
                                        <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                            {{ statistics.total_hosts }}
                                        </div>
                                        <div class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                                            ({{ statistics.active_hosts }} активных)
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <!-- Uptime -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-md bg-green-50 dark:bg-green-900/20 p-3">
                                    <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                        Uptime
                                    </dt>
                                    <dd class="flex items-baseline">
                                        <div :class="['text-2xl font-semibold', getUptimeColor(statistics.uptime_percent)]">
                                            {{ statistics.uptime_percent }}%
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <!-- Проверок выполнено -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-md bg-indigo-50 dark:bg-indigo-900/20 p-3">
                                    <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                        Проверок
                                    </dt>
                                    <dd class="flex items-baseline">
                                        <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                            {{ formatNumber(statistics.total_checks) }}
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <!-- Среднее время отклика -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-md bg-yellow-50 dark:bg-yellow-900/20 p-3">
                                    <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                        Средний отклик
                                    </dt>
                                    <dd class="flex items-baseline">
                                        <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                            {{ statistics.avg_response_time ? Math.round(statistics.avg_response_time) : 'N/A' }}
                                        </div>
                                        <div v-if="statistics.avg_response_time" class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                                            мс
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Проблемные хосты -->
                <div v-if="problematicHosts.length > 0" class="mb-8">
                    <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 sm:p-6 shadow-sm">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="ml-3 flex-1">
                                <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                                    ⚠️ Обнаружены проблемные хосты: {{ problematicHosts.length }}
                                </h3>
                                <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                                    <p class="mb-3">Хосты с частыми проблемами доступности (более 50% неудачных проверок):</p>

                                    <div class="space-y-2">
                                        <Link
                                            v-for="host in problematicHosts"
                                            :key="host.id"
                                            :href="route('monitoring.hosts.show', host.id)"
                                            class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-md hover:bg-red-100 dark:hover:bg-gray-700 transition"
                                        >
                                            <div class="flex-1">
                                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                                    {{ host.store.name }} - {{ host.name }}
                                                </div>
                                                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                                    IP: {{ host.ip_address }}
                                                </div>
                                            </div>
                                            <div class="ml-4 text-red-600 dark:text-red-400">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </div>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Информационный блок -->
                <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 p-4 shadow-sm">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                О системе мониторинга
                            </h3>
                            <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                                <ul class="list-disc list-inside space-y-1">
                                    <li>Автоматическая проверка каждые 5 минут</li>
                                    <li>Проверка выполняется через очередь (queue)</li>
                                    <li>История хранится 30 дней</li>
                                    <li>Настраивается индивидуальный интервал для каждого хоста</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
