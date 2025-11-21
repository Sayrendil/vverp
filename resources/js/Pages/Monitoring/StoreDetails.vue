<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, computed } from 'vue'

const props = defineProps({
    storeStatistics: {
        type: Object,
        required: true,
    },
    days: {
        type: Number,
        default: 7,
    },
})

const isCheckingStore = ref(false)

// Цвет для uptime
const getUptimeColor = (percent) => {
    if (percent >= 99) return 'text-green-600 dark:text-green-400'
    if (percent >= 95) return 'text-yellow-600 dark:text-yellow-400'
    return 'text-red-600 dark:text-red-400'
}

// Цвет для статуса хоста
const getHostStatusColor = (status) => {
    switch (status) {
        case 'available':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
        case 'unavailable':
            return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
    }
}

// Текст статуса
const getHostStatusText = (status) => {
    switch (status) {
        case 'available':
            return '✅ Доступен'
        case 'unavailable':
            return '❌ Недоступен'
        default:
            return '⏸️ Не проверялся'
    }
}

// Проверить все хосты магазина
const checkStoreHosts = async () => {
    if (isCheckingStore.value) return

    isCheckingStore.value = true

    try {
        const response = await axios.post(route('monitoring.check-store', props.storeStatistics.store.id))

        if (response.data.success) {
            alert(`✅ ${response.data.message}`)
        }
    } catch (error) {
        console.error('Error checking store hosts:', error)
        alert('❌ Ошибка при запуске проверки')
    } finally {
        isCheckingStore.value = false

        // Обновить страницу через 3 секунды
        setTimeout(() => {
            router.reload({ only: ['storeStatistics'] })
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
    if (!date) return 'Никогда'
    return new Date(date).toLocaleString('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    })
}

// Группировка хостов: проблемные, активные, неактивные
const problematicHosts = computed(() => (props.storeStatistics.hosts || []).filter(h => h.is_problematic && h.is_active))
const healthyHosts = computed(() => (props.storeStatistics.hosts || []).filter(h => !h.is_problematic && h.is_active))
const inactiveHosts = computed(() => (props.storeStatistics.hosts || []).filter(h => !h.is_active))
</script>

<template>
    <AppLayout title="Мониторинг магазина">
        <Head :title="`Мониторинг: ${storeStatistics.store.name}`" />

        <div class="py-6 sm:py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Breadcrumb -->
                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-4">
                    <Link :href="route('monitoring.index')" class="hover:text-gray-900 dark:hover:text-gray-200">
                        Мониторинг
                    </Link>
                    <span>/</span>
                    <span class="text-gray-900 dark:text-gray-100">{{ storeStatistics.store.name }}</span>
                </div>

                <!-- Заголовок -->
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">
                            🏪 {{ storeStatistics.store.name }}
                        </h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Хосты магазина • Статистика за {{ days }} дней
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button
                            @click="checkStoreHosts"
                            :disabled="isCheckingStore"
                            class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring focus:ring-green-300 disabled:opacity-50 transition"
                        >
                            <svg v-if="isCheckingStore" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ isCheckingStore ? 'Проверка...' : 'Проверить все' }}
                        </button>
                    </div>
                </div>

                <!-- Статистика магазина -->
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
                                            {{ storeStatistics.total_hosts }}
                                        </div>
                                        <div class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                                            ({{ storeStatistics.active_hosts }} активных)
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
                                        <div :class="['text-2xl font-semibold', getUptimeColor(storeStatistics.uptime_percent)]">
                                            {{ storeStatistics.uptime_percent }}%
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
                                            {{ formatNumber(storeStatistics.total_checks) }}
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
                                            {{ storeStatistics.avg_response_time ? Math.round(storeStatistics.avg_response_time) : 'N/A' }}
                                        </div>
                                        <div v-if="storeStatistics.avg_response_time" class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                                            мс
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Проблемные хосты (компактный вид) -->
                <div v-if="problematicHosts.length > 0" class="mb-6">
                    <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">
                        <div class="flex items-center mb-3">
                            <svg class="h-5 w-5 text-red-600 dark:text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <h3 class="text-sm font-semibold text-red-800 dark:text-red-200">
                                Проблемные хосты ({{ problematicHosts.length }})
                            </h3>
                        </div>
                        <div class="grid gap-2 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                            <Link
                                v-for="host in problematicHosts"
                                :key="host.id"
                                :href="route('monitoring.hosts.show', host.id)"
                                class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded border border-red-200 dark:border-red-800 hover:shadow-md transition"
                            >
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-sm text-gray-900 dark:text-gray-100 truncate">
                                        {{ host.name }}
                                    </div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">
                                        {{ host.ip_address }} • Uptime: {{ host.uptime_percent }}%
                                    </div>
                                </div>
                                <svg class="h-4 w-4 text-gray-400 ml-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Список хостов -->
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            🖥️ Хосты магазина
                        </h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Название
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        IP адрес
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Статус
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Uptime
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Отклик
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Последняя проверка
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Действия
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="host in healthyHosts" :key="host.id" class="hover:bg-gray-50 dark:hover:bg-gray-750">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ host.name }}
                                        </div>
                                        <div v-if="host.description" class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">
                                            {{ host.description }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                        {{ host.ip_address }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="['px-2 inline-flex text-xs leading-5 font-semibold rounded-full', getHostStatusColor(host.last_status)]">
                                            {{ getHostStatusText(host.last_status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div :class="['text-sm font-semibold', getUptimeColor(host.uptime_percent)]">
                                            {{ host.uptime_percent }}%
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                        {{ host.last_response_time ? host.last_response_time + ' мс' : 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                        {{ formatDate(host.last_check) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link
                                            :href="route('monitoring.hosts.show', host.id)"
                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                        >
                                            Детали →
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Неактивные хосты (свернуто) -->
                        <div v-if="inactiveHosts.length > 0" class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-6 py-3">
                            <details class="cursor-pointer">
                                <summary class="text-sm text-gray-600 dark:text-gray-400 font-medium">
                                    Неактивные хосты ({{ inactiveHosts.length }})
                                </summary>
                                <div class="mt-2 space-y-1">
                                    <div v-for="host in inactiveHosts" :key="host.id" class="text-sm text-gray-500 dark:text-gray-500 flex items-center justify-between py-1">
                                        <span>{{ host.name }} - {{ host.ip_address }}</span>
                                        <Link
                                            :href="route('monitoring.hosts.show', host.id)"
                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 text-xs"
                                        >
                                            Детали →
                                        </Link>
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
