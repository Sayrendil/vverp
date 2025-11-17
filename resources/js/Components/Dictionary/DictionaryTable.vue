<script setup>
import { computed } from 'vue'

const props = defineProps({
    columns: {
        type: Array,
        required: true,
    },
    items: {
        type: Array,
        required: true,
    },
    sortBy: {
        type: String,
        default: null,
    },
    sortDirection: {
        type: String,
        default: 'asc',
    },
})

const emit = defineEmits(['sort', 'edit', 'delete'])

const formatValue = (item, column) => {
    const value = item[column.key]

    if (value === null || value === undefined) {
        return '—'
    }

    if (column.type === 'datetime' && value) {
        return new Date(value).toLocaleString('ru-RU', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
        })
    }

    return value
}

const handleSort = (column) => {
    if (column.sortable) {
        emit('sort', column.key)
    }
}

const getSortIcon = (column) => {
    if (!column.sortable) return null
    if (props.sortBy !== column.key) return '↕️'
    return props.sortDirection === 'asc' ? '↑' : '↓'
}
</script>

<template>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th
                        v-for="column in columns"
                        :key="column.key"
                        :class="[
                            'px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400',
                            column.sortable && 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800',
                        ]"
                        :style="column.width ? { width: column.width } : {}"
                        @click="handleSort(column)"
                    >
                        <div class="flex items-center gap-1">
                            {{ column.label }}
                            <span v-if="column.sortable" class="text-xs">
                                {{ getSortIcon(column) }}
                            </span>
                        </div>
                    </th>
                    <th
                        class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
                        style="width: 150px"
                    >
                        Действия
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                <tr
                    v-for="item in items"
                    :key="item.id"
                    class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                >
                    <td
                        v-for="column in columns"
                        :key="column.key"
                        class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-100"
                    >
                        {{ formatValue(item, column) }}
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                        <div class="flex items-center justify-end gap-2">
                            <!-- Редактировать -->
                            <button
                                type="button"
                                @click="emit('edit', item)"
                                class="rounded p-1 text-blue-600 transition hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20"
                                title="Редактировать"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                    />
                                </svg>
                            </button>

                            <!-- Удалить -->
                            <button
                                type="button"
                                @click="emit('delete', item)"
                                class="rounded p-1 text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                                title="Удалить"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    />
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>

                <!-- Нет данных -->
                <tr v-if="items.length === 0">
                    <td
                        :colspan="columns.length + 1"
                        class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400"
                    >
                        <div class="flex flex-col items-center gap-2">
                            <svg
                                class="h-12 w-12 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                                />
                            </svg>
                            <p class="font-medium">Нет записей</p>
                            <p class="text-xs">Добавьте первую запись в справочник</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
