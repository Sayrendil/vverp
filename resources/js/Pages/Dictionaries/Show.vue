<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, computed } from 'vue'
import DictionaryTable from '@/Components/Dictionary/DictionaryTable.vue'
import CreateEditModal from '@/Components/Dictionary/CreateEditModal.vue'
import DeleteConfirmModal from '@/Components/Dictionary/DeleteConfirmModal.vue'
import SearchInput from '@/Components/Dictionary/SearchInput.vue'

const props = defineProps({
    dictionary: {
        type: Object,
        required: true,
    },
    items: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
})

// Модальные окна
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const editingItem = ref(null)
const deletingItem = ref(null)

// Поиск и фильтрация
const searchQuery = ref(props.filters.search || '')

// Открыть модалку создания
const openCreateModal = () => {
    showCreateModal.value = true
}

// Открыть модалку редактирования
const openEditModal = (item) => {
    editingItem.value = item
    showEditModal.value = true
}

// Открыть модалку удаления
const openDeleteModal = (item) => {
    deletingItem.value = item
    showDeleteModal.value = true
}

// Закрыть модалки
const closeModals = () => {
    showCreateModal.value = false
    showEditModal.value = false
    showDeleteModal.value = false
    editingItem.value = null
    deletingItem.value = null
}

// Поиск
const performSearch = () => {
    router.get(
        route('dictionaries.show', props.dictionary.key),
        { search: searchQuery.value },
        {
            preserveState: true,
            preserveScroll: true,
        }
    )
}

// Сортировка
const handleSort = (field) => {
    const currentSortBy = props.filters.sort_by
    const currentDirection = props.filters.sort_direction || 'asc'

    let newDirection = 'asc'
    if (currentSortBy === field) {
        newDirection = currentDirection === 'asc' ? 'desc' : 'asc'
    }

    router.get(
        route('dictionaries.show', props.dictionary.key),
        {
            ...props.filters,
            sort_by: field,
            sort_direction: newDirection,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    )
}

// Создание
const handleCreate = (data) => {
    router.post(
        route('dictionaries.store', props.dictionary.key),
        data,
        {
            preserveScroll: true,
            onSuccess: () => closeModals(),
        }
    )
}

// Обновление
const handleUpdate = (id, data) => {
    router.put(
        route('dictionaries.update', [props.dictionary.key, id]),
        data,
        {
            preserveScroll: true,
            onSuccess: () => closeModals(),
        }
    )
}

// Удаление
const handleDelete = (id) => {
    router.delete(
        route('dictionaries.destroy', [props.dictionary.key, id]),
        {
            preserveScroll: true,
            onSuccess: () => closeModals(),
        }
    )
}

// Универсальный обработчик для submit
const handleSubmit = (data) => {
    if (showEditModal.value && editingItem.value) {
        handleUpdate(editingItem.value.id, data)
    } else {
        handleCreate(data)
    }
}

// Универсальный обработчик для confirm delete
const handleConfirmDelete = () => {
    if (deletingItem.value && deletingItem.value.id) {
        handleDelete(deletingItem.value.id)
    }
}
</script>

<template>
    <AppLayout :title="dictionary.name">
        <Head :title="dictionary.name" />

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Заголовок и хлебные крошки -->
                <div class="mb-6">
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <Link
                            :href="route('dictionaries.index')"
                            class="hover:text-gray-900 dark:hover:text-gray-200"
                        >
                            Справочники
                        </Link>
                        <span>/</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ dictionary.name }}</span>
                    </div>

                    <div class="mt-2 flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">
                                {{ dictionary.icon }} {{ dictionary.name }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ dictionary.description }}
                            </p>
                        </div>

                        <!-- Кнопка создания -->
                        <button
                            type="button"
                            @click="openCreateModal"
                            class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:bg-blue-500 dark:hover:bg-blue-400"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                            Добавить
                        </button>
                    </div>
                </div>

                <!-- Карточка с таблицей -->
                <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                    <!-- Поиск и фильтры -->
                    <div class="border-b border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/50">
                        <div class="flex items-center justify-between gap-4">
                            <SearchInput
                                v-model="searchQuery"
                                :placeholder="`Поиск в справочнике...`"
                                @search="performSearch"
                            />

                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                Всего записей: <span class="font-semibold">{{ items.total }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Таблица -->
                    <DictionaryTable
                        :columns="dictionary.table_columns"
                        :items="items.data"
                        :sort-by="filters.sort_by"
                        :sort-direction="filters.sort_direction"
                        @sort="handleSort"
                        @edit="openEditModal"
                        @delete="openDeleteModal"
                    />

                    <!-- Пагинация -->
                    <div v-if="items.links.length > 3" class="border-t border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/50">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700 dark:text-gray-300">
                                Показано с <span class="font-medium">{{ items.from }}</span> по
                                <span class="font-medium">{{ items.to }}</span> из
                                <span class="font-medium">{{ items.total }}</span> записей
                            </div>

                            <nav class="flex gap-1">
                                <Link
                                    v-for="(link, index) in items.links"
                                    :key="index"
                                    :href="link.url"
                                    :class="[
                                        'rounded px-3 py-1 text-sm transition',
                                        link.active
                                            ? 'bg-blue-600 text-white'
                                            : 'bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700',
                                        !link.url && 'cursor-not-allowed opacity-50',
                                    ]"
                                    :disabled="!link.url"
                                    preserve-scroll
                                    v-html="link.label"
                                />
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модальные окна -->
        <CreateEditModal
            :dictionary="dictionary"
            :item="editingItem"
            :show="showCreateModal || showEditModal"
            :is-editing="showEditModal"
            @close="closeModals"
            @submit="handleSubmit"
        />

        <DeleteConfirmModal
            :dictionary="dictionary"
            :item="deletingItem"
            :show="showDeleteModal"
            @close="closeModals"
            @confirm="handleConfirmDelete"
        />
    </AppLayout>
</template>
