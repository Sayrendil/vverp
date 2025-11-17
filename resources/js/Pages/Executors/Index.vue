<script setup>
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, computed } from 'vue'
import ExecutorCard from '@/Components/Executors/ExecutorCard.vue'
import AddExecutorModal from '@/Components/Executors/AddExecutorModal.vue'
import EditExecutorModal from '@/Components/Executors/EditExecutorModal.vue'

const props = defineProps({
    categories: {
        type: Array,
        required: true,
    },
})

// State
const selectedCategory = ref(props.categories[0]?.id || null)
const showAddModal = ref(false)
const showEditModal = ref(false)
const editingExecutor = ref(null)

// Computed
const currentCategory = computed(() => {
    return props.categories.find(c => c.id === selectedCategory.value)
})

// Methods
const openAddModal = () => {
    showAddModal.value = true
}

const closeAddModal = () => {
    showAddModal.value = false
}

const openEditModal = (executor) => {
    editingExecutor.value = executor
    showEditModal.value = true
}

const closeEditModal = () => {
    showEditModal.value = false
    editingExecutor.value = null
}

const toggleExecutor = (userId) => {
    router.post(route('executors.toggle', [userId, selectedCategory.value]), {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Success notification
        },
    })
}

const removeExecutor = (userId) => {
    if (!confirm('Вы уверены, что хотите удалить исполнителя из этой категории?')) {
        return
    }

    router.delete(route('executors.destroy', [userId, selectedCategory.value]), {
        preserveScroll: true,
    })
}

const getStatusColor = (activeTickets, maxTickets) => {
    const percentage = (activeTickets / maxTickets) * 100
    if (percentage >= 80) return 'text-red-600'
    if (percentage >= 60) return 'text-yellow-600'
    return 'text-green-600'
}

const getStatusIcon = (activeTickets, maxTickets) => {
    const percentage = (activeTickets / maxTickets) * 100
    if (percentage >= 80) return '🔴'
    if (percentage >= 60) return '🟡'
    return '🟢'
}
</script>

<template>
    <Head title="Исполнители" />

    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                👥 Управление исполнителями
            </h2>
        </template>

        <div class="py-6 sm:py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Tabs Categories -->
                <div class="mb-6 border-b border-gray-200 dark:border-gray-700 overflow-x-auto">
                    <nav class="-mb-px flex space-x-4 sm:space-x-8 min-w-max sm:min-w-0">
                        <button
                            v-for="category in categories"
                            :key="category.id"
                            @click="selectedCategory = category.id"
                            :class="[
                                'whitespace-nowrap border-b-2 px-1 py-3 sm:py-4 text-xs sm:text-sm font-medium transition-colors',
                                selectedCategory === category.id
                                    ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                            ]"
                        >
                            {{ category.name }}
                            <span class="ml-2 rounded-full bg-gray-100 px-2 sm:px-2.5 py-0.5 text-xs dark:bg-gray-800">
                                {{ category.executors.length }}
                            </span>
                        </button>
                    </nav>
                </div>

                <!-- Statistics -->
                <div v-if="currentCategory" class="mb-6 grid gap-3 sm:gap-4 grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-lg bg-white p-3 sm:p-4 shadow dark:bg-gray-800">
                        <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Всего</div>
                        <div class="mt-1 text-xl sm:text-2xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ currentCategory.statistics.total_executors }}
                        </div>
                    </div>
                    <div class="rounded-lg bg-white p-3 sm:p-4 shadow dark:bg-gray-800">
                        <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Активных</div>
                        <div class="mt-1 text-xl sm:text-2xl font-semibold text-green-600">
                            {{ currentCategory.statistics.active_executors }}
                        </div>
                    </div>
                    <div class="rounded-lg bg-white p-3 sm:p-4 shadow dark:bg-gray-800">
                        <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Заявок</div>
                        <div class="mt-1 text-xl sm:text-2xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ currentCategory.statistics.total_active_tickets }}
                        </div>
                    </div>
                    <div class="rounded-lg bg-white p-3 sm:p-4 shadow dark:bg-gray-800">
                        <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Среднее</div>
                        <div class="mt-1 text-xl sm:text-2xl font-semibold text-blue-600">
                            {{ currentCategory.statistics.avg_tickets_per_executor }}
                        </div>
                    </div>
                </div>

                <!-- Executors List -->
                <div v-if="currentCategory" class="rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4 border-b border-gray-200 p-4 sm:p-6 dark:border-gray-700">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-gray-100">
                            Исполнители: {{ currentCategory.name }}
                        </h3>
                        <button
                            @click="openAddModal"
                            class="inline-flex items-center justify-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 whitespace-nowrap"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="hidden sm:inline">Добавить исполнителя</span>
                            <span class="sm:hidden">Добавить</span>
                        </button>
                    </div>

                    <div v-if="currentCategory.executors.length === 0" class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            В этой категории пока нет исполнителей
                        </p>
                        <button
                            @click="openAddModal"
                            class="mt-4 text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400"
                        >
                            Добавить первого исполнителя
                        </button>
                    </div>

                    <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                        <ExecutorCard
                            v-for="executor in currentCategory.executors"
                            :key="executor.id"
                            :executor="executor"
                            @toggle="toggleExecutor(executor.id)"
                            @edit="openEditModal(executor)"
                            @remove="removeExecutor(executor.id)"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <AddExecutorModal
            :show="showAddModal"
            :category-id="selectedCategory"
            @close="closeAddModal"
        />

        <EditExecutorModal
            :show="showEditModal"
            :category-id="selectedCategory"
            :executor="editingExecutor"
            @close="closeEditModal"
        />
    </AppLayout>
</template>
