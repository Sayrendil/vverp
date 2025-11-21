<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, computed } from 'vue'

const props = defineProps({
    stores: {
        type: Array,
        default: () => [],
    },
    statistics: {
        type: Object,
        required: true,
    },
    problematicHostsCount: {
        type: Number,
        default: 0,
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

// Цвет для статуса магазина
const getStoreStatusColor = (status) => {
    switch (status) {
        case 'healthy':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border-green-200 dark:border-green-800'
        case 'warning':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 border-yellow-200 dark:border-yellow-800'
        case 'critical':
            return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 border-red-200 dark:border-red-800'
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400 border-gray-200 dark:border-gray-800'
    }
}

// Иконка для статуса магазина
const getStoreStatusIcon = (status) => {
    switch (status) {
        case 'healthy':
            return '✅'
        case 'warning':
            return '⚠️'
        case 'critical':
            return '❌'
        default:
            return '⏸️'
    }
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
            router.reload({ only: ['stores', 'statistics', 'problematicHostsCount'] })
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
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">
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

                <!-- Общая статистика (компактная) -->
                <div class="grid gap-4 grid-cols-2 sm:grid-cols-4 mb-6">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                        <div class="text-sm text-blue-600 dark:text-blue-400 mb-1">Всего хостов</div>
                        <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ statistics.total_hosts }}</div>
                        <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">{{ statistics.active_hosts }} активных</div>
                    </div>

                    <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                        <div class="text-sm text-green-600 dark:text-green-400 mb-1">Uptime</div>
                        <div :class="['text-2xl font-bold', getUptimeColor(statistics.uptime_percent)]">{{ statistics.uptime_percent }}%</div>
                        <div class="text-xs text-green-600 dark:text-green-400 mt-1">За {{ days }} дней</div>
                    </div>

                    <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900/20 dark:to-indigo-800/20 rounded-lg p-4 border border-indigo-200 dark:border-indigo-800">
                        <div class="text-sm text-indigo-600 dark:text-indigo-400 mb-1">Проверок</div>
                        <div class="text-2xl font-bold text-indigo-900 dark:text-indigo-100">{{ formatNumber(statistics.total_checks) }}</div>
                        <div class="text-xs text-indigo-600 dark:text-indigo-400 mt-1">Всего</div>
                    </div>

                    <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-800">
                        <div class="text-sm text-yellow-600 dark:text-yellow-400 mb-1">Отклик</div>
                        <div class="text-2xl font-bold text-yellow-900 dark:text-yellow-100">
                            {{ statistics.avg_response_time ? Math.round(statistics.avg_response_time) : 'N/A' }}
                            <span v-if="statistics.avg_response_time" class="text-sm">мс</span>
                        </div>
                        <div class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">Средний</div>
                    </div>
                </div>

                <!-- Предупреждение о проблемных хостах (компактное) -->
                <div v-if="problematicHostsCount > 0" class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3">
                    <div class="flex items-center text-sm">
                        <svg class="h-5 w-5 text-red-600 dark:text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span class="font-medium text-red-800 dark:text-red-200">
                            Внимание: Обнаружено {{ problematicHostsCount }} проблемных хостов (более 50% неудачных проверок)
                        </span>
                    </div>
                </div>

                <!-- Список магазинов -->
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            🏪 Магазины
                        </h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Выберите магазин для просмотра хостов
                        </p>
                    </div>

                    <div v-if="stores.length === 0" class="px-6 py-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Нет магазинов с хостами</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Добавьте хосты в справочнике
                        </p>
                        <div class="mt-6">
                            <Link
                                :href="route('dictionaries.show', 'hosts')"
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
                            >
                                Управление хостами
                            </Link>
                        </div>
                    </div>

                    <div v-else class="grid gap-4 p-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                        <Link
                            v-for="store in stores"
                            :key="store.id"
                            :href="route('monitoring.stores.show', store.id)"
                            :class="['block rounded-lg border-2 p-5 hover:shadow-lg transition-all', getStoreStatusColor(store.status)]"
                        >
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">
                                        {{ store.name }}
                                    </h3>
                                    <div class="flex items-center gap-2 text-sm">
                                        <span class="font-medium">
                                            {{ getStoreStatusIcon(store.status) }}
                                            {{ store.available_hosts }}/{{ store.total_hosts }}
                                        </span>
                                        <span class="text-gray-600 dark:text-gray-400">хостов доступно</span>
                                    </div>
                                </div>
                                <svg class="h-5 w-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>

                            <div class="grid grid-cols-3 gap-2 text-center text-xs">
                                <div class="bg-white dark:bg-gray-800 rounded p-2">
                                    <div class="font-semibold text-green-600 dark:text-green-400">{{ store.available_hosts }}</div>
                                    <div class="text-gray-600 dark:text-gray-400">Доступны</div>
                                </div>
                                <div class="bg-white dark:bg-gray-800 rounded p-2">
                                    <div class="font-semibold text-red-600 dark:text-red-400">{{ store.unavailable_hosts }}</div>
                                    <div class="text-gray-600 dark:text-gray-400">Недоступны</div>
                                </div>
                                <div class="bg-white dark:bg-gray-800 rounded p-2">
                                    <div class="font-semibold text-gray-600 dark:text-gray-400">{{ store.not_checked_hosts }}</div>
                                    <div class="text-gray-600 dark:text-gray-400">Не проверены</div>
                                </div>
                            </div>

                            <div v-if="store.problematic_hosts_count > 0" class="mt-3 flex items-center text-xs text-red-600 dark:text-red-400">
                                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span class="font-medium">{{ store.problematic_hosts_count }} проблемных хостов</span>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Информационный блок -->
                <div class="mt-6 rounded-lg bg-blue-50 dark:bg-blue-900/20 p-4 border border-blue-200 dark:border-blue-800">
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
