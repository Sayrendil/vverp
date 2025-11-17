<script setup>
import { computed } from 'vue'

const props = defineProps({
    executor: {
        type: Object,
        required: true,
    },
})

const emit = defineEmits(['toggle', 'edit', 'remove'])

const loadPercentage = computed(() => {
    return Math.round((props.executor.active_tickets_count / props.executor.max_tickets) * 100)
})

const loadColor = computed(() => {
    if (loadPercentage.value >= 80) return 'bg-red-500'
    if (loadPercentage.value >= 60) return 'bg-yellow-500'
    return 'bg-green-500'
})

const statusIcon = computed(() => {
    if (!props.executor.is_active) return '⚫'
    if (loadPercentage.value >= 80) return '🔴'
    if (loadPercentage.value >= 60) return '🟡'
    return '🟢'
})

const statusText = computed(() => {
    if (!props.executor.is_active) return 'Неактивен'
    if (loadPercentage.value >= 80) return 'Перегружен'
    if (loadPercentage.value >= 60) return 'Занят'
    return 'Доступен'
})
</script>

<template>
    <div class="p-4 sm:p-6 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
        <!-- Mobile Layout -->
        <div class="sm:hidden space-y-3">
            <!-- Header: Avatar + Name + Status -->
            <div class="flex items-start gap-3">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                    <span class="text-lg font-semibold text-blue-600 dark:text-blue-400">
                        {{ executor.name.charAt(0) }}
                    </span>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="font-medium text-gray-900 dark:text-gray-100 truncate">
                        {{ executor.name }}
                    </h4>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-xs text-gray-500">{{ statusIcon }}</span>
                        <span :class="[
                            'text-xs',
                            executor.is_active ? 'text-green-600' : 'text-gray-400'
                        ]">
                            {{ statusText }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Email & Role -->
            <div class="text-sm text-gray-500 dark:text-gray-400 space-y-1">
                <div class="truncate">{{ executor.email }}</div>
                <div>{{ executor.role_label }}</div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 gap-2">
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-2">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Активных</div>
                    <div class="text-lg font-bold text-gray-900 dark:text-gray-100">
                        {{ executor.active_tickets_count }}
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-2">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Максимум</div>
                    <div class="text-lg font-bold text-gray-500 dark:text-gray-400">
                        {{ executor.max_tickets }}
                    </div>
                </div>
            </div>

            <!-- Load Bar -->
            <div>
                <div class="mb-1 flex items-center justify-between text-xs">
                    <span class="text-gray-600 dark:text-gray-400">Нагрузка</span>
                    <span class="font-medium">{{ loadPercentage }}%</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                    <div :class="loadColor" :style="{ width: `${loadPercentage}%` }" class="h-full transition-all" />
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2 pt-2">
                <button
                    @click="emit('toggle')"
                    :class="[
                        'flex-1 rounded-md px-3 py-2 text-xs font-medium transition',
                        executor.is_active
                            ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                            : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                    ]"
                >
                    {{ executor.is_active ? '✓ Активен' : '✗ Неактивен' }}
                </button>
                <button
                    @click="emit('edit')"
                    class="rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </button>
                <button
                    @click="emit('remove')"
                    class="rounded-md p-2 text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Desktop Layout -->
        <div class="hidden sm:flex items-center justify-between">
            <!-- Info -->
            <div class="flex flex-1 items-center gap-4">
                <!-- Avatar -->
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                    <span class="text-lg font-semibold text-blue-600 dark:text-blue-400">
                        {{ executor.name.charAt(0) }}
                    </span>
                </div>

                <!-- Details -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <h4 class="font-medium text-gray-900 dark:text-gray-100">
                            {{ executor.name }}
                        </h4>
                        <span class="text-xs text-gray-500">{{ statusIcon }}</span>
                        <span :class="[
                            'text-xs',
                            executor.is_active ? 'text-green-600' : 'text-gray-400'
                        ]">
                            {{ statusText }}
                        </span>
                    </div>
                    <div class="mt-1 flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                        <span class="truncate">{{ executor.email }}</span>
                        <span>•</span>
                        <span>{{ executor.role_label }}</span>
                        <span v-if="executor.priority > 0">•</span>
                        <span v-if="executor.priority > 0">Приоритет: {{ executor.priority }}</span>
                    </div>
                </div>

                <!-- Stats -->
                <div class="flex items-center gap-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ executor.active_tickets_count }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            активных
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-500 dark:text-gray-400">
                            {{ executor.max_tickets }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            максимум
                        </div>
                    </div>
                    <div class="w-24">
                        <div class="mb-1 flex items-center justify-between text-xs">
                            <span class="text-gray-600 dark:text-gray-400">Нагрузка</span>
                            <span class="font-medium">{{ loadPercentage }}%</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div :class="loadColor" :style="{ width: `${loadPercentage}%` }" class="h-full transition-all" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="ml-4 flex items-center gap-2">
                <!-- Toggle Active -->
                <button
                    @click="emit('toggle')"
                    :class="[
                        'rounded-md px-3 py-2 text-sm font-medium transition',
                        executor.is_active
                            ? 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300'
                    ]"
                    :title="executor.is_active ? 'Деактивировать' : 'Активировать'"
                >
                    {{ executor.is_active ? '✓ Активен' : '✗ Неактивен' }}
                </button>

                <!-- Edit -->
                <button
                    @click="emit('edit')"
                    class="rounded-md p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                    title="Редактировать"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </button>

                <!-- Remove -->
                <button
                    @click="emit('remove')"
                    class="rounded-md p-2 text-gray-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400"
                    title="Удалить"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>
