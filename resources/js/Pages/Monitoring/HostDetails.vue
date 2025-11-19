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
                <!-- Заголовок -->
                <div class="mb-4 sm:mb-6">
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-2">
                        <Link :href="route('monitoring.index')" class="hover:text-gray-900 dark:hover:text-gray-200">
                            Мониторинг
                        </Link>
                        <span>/</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ statistics.host.name }}</span>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h2 class="text-xl sm:text-2xl font-semibold text-gray-800 dark:text-gray-100">
                                🖥️ {{ statistics.host.name }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ statistics.host.store.name }} • {{ statistics.host.ip_address }}
                            </p>
                            <p v-if="statistics.host.description" class="mt-1 text-sm text-gray-500 dark:text-gray-500">
                                {{ statistics.host.description }}
                            </p>
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
                <div class="grid gap-4 sm:gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 mb-8">
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
                                        Uptime ({{ days }} дней)
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

                    <!-- Проверок -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-md bg-blue-50 dark:bg-blue-900/20 p-3">
                                    <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                        Всего проверок
                                    </dt>
                                    <dd class="flex items-baseline">
                                        <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                            {{ formatNumber(statistics.total_checks) }}
                                        </div>
                                    </dd>
                                    <dd class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                        ✅ {{ statistics.available_checks }} / ❌ {{ statistics.unavailable_checks }}
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
                                    <dd v-if="statistics.min_response_time && statistics.max_response_time" class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                        {{ statistics.min_response_time }}мс - {{ statistics.max_response_time }}мс
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <!-- Потери пакетов -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-md bg-red-50 dark:bg-red-900/20 p-3">
                                    <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                        Потери пакетов
                                    </dt>
                                    <dd class="flex items-baseline">
                                        <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                            {{ statistics.avg_packet_loss }}%
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- История проверок -->
                <div v-if="statistics.recent_logs && statistics.recent_logs.length > 0" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            📊 История проверок
                        </h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Последние {{ Math.min(statistics.recent_logs.length, 20) }} проверок
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Время проверки
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Статус
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Время отклика
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Потери
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Ошибка
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="log in statistics.recent_logs.slice(0, 20)" :key="log.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ formatDate(log.checked_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span v-if="log.is_available" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                            ✅ Доступен
                                        </span>
                                        <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                            ❌ Недоступен
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ log.response_time ? log.response_time + ' мс' : 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ log.packet_loss }}%
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                        {{ log.error_message || '-' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Настройки хоста -->
                <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 shadow-sm">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                Настройки мониторинга
                            </h3>
                            <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                                <ul class="list-disc list-inside space-y-1">
                                    <li>Интервал проверки: <strong>{{ statistics.host.check_interval }} минут</strong></li>
                                    <li>Таймаут: <strong>{{ statistics.host.timeout }} секунд</strong></li>
                                    <li>Статус: <strong>{{ statistics.host.is_active ? '✅ Активен' : '❌ Неактивен' }}</strong></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
