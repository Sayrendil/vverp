<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, computed } from 'vue'

const props = defineProps({
    statistics: {
        type: Object,
        required: true,
    },
    days: {
        type: Number,
        default: 7,
    },
})

const isChecking = ref(false)

// Цвет для uptime
const getUptimeColor = (percent) => {
    if (percent >= 99) return 'text-green-600 dark:text-green-400'
    if (percent >= 95) return 'text-yellow-600 dark:text-yellow-400'
    return 'text-red-600 dark:text-red-400'
}

// Проверить хост
const checkHost = async () => {
    if (isChecking.value) return

    isChecking.value = true

    try {
        const response = await axios.post(route('monitoring.check-host', props.statistics.host.id))

        if (response.data.success) {
            alert(`✅ ${response.data.message}`)
        }
    } catch (error) {
        console.error('Error checking host:', error)
        alert('❌ Ошибка при запуске проверки')
    } finally {
        isChecking.value = false

        // Обновить страницу через 3 секунды
        setTimeout(() => {
            router.reload({ only: ['statistics'] })
        }, 3000)
    }
}

// Форматирование числа
const formatNumber = (num) => {
    if (num === null || num === undefined) return 'N/A'
    return num.toLocaleString('ru-RU')
}

// Форматирование даты
const formatDate = (date) => {
    return new Date(date).toLocaleString('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}
</script>

<template>
    <AppLayout title="Детали хоста">
        <Head :title="`Мониторинг: ${statistics.host.name}`" />

        <div class="py-6 sm:py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Breadcrumb -->
                <div class="mb-4">
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-2">
                        <Link :href="route('monitoring.index')" class="hover:text-gray-900 dark:hover:text-gray-200">
                            Мониторинг
                        </Link>
                        <span>/</span>
                        <Link :href="route('monitoring.stores.show', statistics.host.store.id)" class="hover:text-gray-900 dark:hover:text-gray-200">
                            {{ statistics.host.store.name }}
                        </Link>
                        <span>/</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ statistics.host.name }}</span>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div class="flex-1">
                            <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">
                                🖥️ {{ statistics.host.name }}
                            </h2>
                            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-600 dark:text-gray-400">
                                <span class="flex items-center">
                                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                    </svg>
                                    {{ statistics.host.ip_address }}
                                </span>
                                <span v-if="statistics.host.description">• {{ statistics.host.description }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button
                                @click="checkHost"
                                :disabled="isChecking"
                                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring focus:ring-green-300 disabled:opacity-50 transition"
                            >
                                <svg v-if="isChecking" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ isChecking ? 'Проверка...' : 'Проверить сейчас' }}
                            </button>
                            <Link
                                :href="route('dictionaries.show', 'hosts')"
                                class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition"
                            >
                                Редактировать
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Статистика - карточки -->
                <div class="grid gap-4 grid-cols-2 lg:grid-cols-4 mb-6">
                    <!-- Uptime -->
                    <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                        <div class="text-sm text-green-600 dark:text-green-400 mb-1">Uptime ({{ days }} дней)</div>
                        <div :class="['text-2xl font-bold', getUptimeColor(statistics.uptime_percent)]">
                            {{ statistics.uptime_percent }}%
                        </div>
                    </div>

                    <!-- Проверок -->
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                        <div class="text-sm text-blue-600 dark:text-blue-400 mb-1">Проверок</div>
                        <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                            {{ formatNumber(statistics.total_checks) }}
                        </div>
                        <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                            ✅ {{ statistics.available_checks }} / ❌ {{ statistics.unavailable_checks }}
                        </div>
                    </div>

                    <!-- Среднее время отклика -->
                    <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-800">
                        <div class="text-sm text-yellow-600 dark:text-yellow-400 mb-1">Средний отклик</div>
                        <div class="text-2xl font-bold text-yellow-900 dark:text-yellow-100">
                            {{ statistics.avg_response_time ? Math.round(statistics.avg_response_time) : 'N/A' }}
                            <span v-if="statistics.avg_response_time" class="text-sm">мс</span>
                        </div>
                        <div v-if="statistics.min_response_time && statistics.max_response_time" class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">
                            {{ statistics.min_response_time }}мс - {{ statistics.max_response_time }}мс
                        </div>
                    </div>

                    <!-- Потери пакетов -->
                    <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 rounded-lg p-4 border border-red-200 dark:border-red-800">
                        <div class="text-sm text-red-600 dark:text-red-400 mb-1">Потери пакетов</div>
                        <div class="text-2xl font-bold text-red-900 dark:text-red-100">
                            {{ statistics.avg_packet_loss }}%
                        </div>
                    </div>
                </div>

                <!-- История проверок (компактная) -->
                <div v-if="statistics.recent_logs && statistics.recent_logs.length > 0" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            📊 История проверок
                        </h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Последние {{ Math.min(statistics.recent_logs.length, 20) }} проверок
                        </p>
                    </div>

                    <!-- Компактный список вместо таблицы -->
                    <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-96 overflow-y-auto">
                        <div
                            v-for="log in statistics.recent_logs.slice(0, 20)"
                            :key="log.id"
                            class="px-6 py-3 hover:bg-gray-50 dark:hover:bg-gray-750 flex items-center justify-between"
                        >
                            <div class="flex items-center gap-4 flex-1">
                                <span v-if="log.is_available" class="flex-shrink-0 w-20 px-2 py-1 inline-flex items-center justify-center text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                    ✅ OK
                                </span>
                                <span v-else class="flex-shrink-0 w-20 px-2 py-1 inline-flex items-center justify-center text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                    ❌ FAIL
                                </span>

                                <div class="text-sm text-gray-900 dark:text-gray-100 min-w-[120px]">
                                    {{ formatDate(log.checked_at) }}
                                </div>

                                <div class="flex gap-4 text-sm text-gray-600 dark:text-gray-400">
                                    <span v-if="log.response_time" class="flex items-center gap-1">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        {{ log.response_time }}мс
                                    </span>
                                    <span v-if="log.packet_loss > 0" class="flex items-center gap-1 text-red-600 dark:text-red-400">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        Потери: {{ log.packet_loss }}%
                                    </span>
                                </div>
                            </div>

                            <div v-if="log.error_message" class="text-xs text-gray-500 dark:text-gray-400 max-w-xs truncate ml-4">
                                {{ log.error_message }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Настройки хоста (компактные) -->
                <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-blue-400 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">
                                Настройки мониторинга
                            </h3>
                            <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm text-blue-700 dark:text-blue-300">
                                <span>Интервал: <strong>{{ statistics.host.check_interval }} мин</strong></span>
                                <span>•</span>
                                <span>Таймаут: <strong>{{ statistics.host.timeout }} сек</strong></span>
                                <span>•</span>
                                <span>{{ statistics.host.is_active ? '✅ Активен' : '❌ Неактивен' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
