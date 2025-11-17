<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    tickets: Object,
    filters: Object,
    statuses: Array,
    problems: Array,
    categories: Array,
    userCategory: Number, // Категория текущего пользователя
});

const searchQuery = ref(props.filters?.search || '');
const selectedStatus = ref(props.filters?.status || '');
const selectedProblem = ref(props.filters?.problem || '');
const selectedCategory = ref(props.filters?.category || '');

const getStatusColor = (statusName) => {
    const colors = {
        'Создана': 'bg-gray-500/20 text-gray-400 border-gray-500/50',
        'В работе': 'bg-blue-500/20 text-blue-400 border-blue-500/50',
        'Подтверждена': 'bg-yellow-500/20 text-yellow-400 border-yellow-500/50',
        'Отложена': 'bg-orange-500/20 text-orange-400 border-orange-500/50',
        'Завершена': 'bg-green-500/20 text-green-400 border-green-500/50',
    };
    return colors[statusName] || 'bg-gray-500/20 text-gray-400 border-gray-500/50';
};

const applyFilters = () => {
    const params = {
        search: searchQuery.value,
        status: selectedStatus.value,
        problem: selectedProblem.value,
    };

    // Добавляем фильтр категории только если у пользователя нет своей категории
    if (!props.userCategory && selectedCategory.value) {
        params.category = selectedCategory.value;
    }

    router.get(route('tickets.index'), params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    searchQuery.value = '';
    selectedStatus.value = '';
    selectedProblem.value = '';
    selectedCategory.value = '';
    applyFilters();
};

const selectCategory = (categoryId) => {
    selectedCategory.value = categoryId;
    applyFilters();
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleString('ru-RU', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <AppLayout title="Заявки">
        <Head title="Заявки" />

        <div class="py-8 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <!-- Заголовок и кнопка создания -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-2">Заявки</h1>
                        <p class="text-gray-400">Управление заявками и запросами</p>
                    </div>
                    <Link :href="route('tickets.create')" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-semibold rounded-lg shadow-lg transition-all duration-200 transform hover:scale-105">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Создать заявку
                    </Link>
                </div>

                    <!-- Вкладки категорий (только для суперадминов без назначенной категории) -->
                    <div class="mb-6" v-if="!userCategory">
                        <div class="flex gap-2 overflow-x-auto pb-2">
                            <button
                                @click="selectCategory('')"
                                :class="[
                                    'px-6 py-3 rounded-lg font-medium transition-all whitespace-nowrap',
                                    !selectedCategory
                                        ? 'bg-gradient-to-r from-green-600 to-emerald-600 text-white shadow-lg'
                                        : 'bg-gray-800/50 text-gray-300 hover:bg-gray-700/50'
                                ]"
                            >
                                Все категории
                            </button>
                            <button
                                v-for="category in (categories || [])"
                                :key="category.id"
                                @click="selectCategory(category.id)"
                                :class="[
                                    'px-6 py-3 rounded-lg font-medium transition-all whitespace-nowrap',
                                    selectedCategory == category.id
                                        ? 'bg-gradient-to-r from-green-600 to-emerald-600 text-white shadow-lg'
                                        : 'bg-gray-800/50 text-gray-300 hover:bg-gray-700/50'
                                ]"
                            >
                                {{ category.name }}
                            </button>
                        </div>
                    </div>

                    <!-- Информация о категории для администраторов категории -->
                    <div class="mb-6" v-else>
                        <div class="bg-gradient-to-r from-gray-800/50 to-gray-800/30 backdrop-blur-xl border border-green-500/30 rounded-lg p-4 shadow-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-r from-green-600 to-emerald-600 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-400">Ваша категория</p>
                                    <p class="text-white font-semibold text-lg">{{ categories.find(c => c.id === userCategory)?.name || 'Не указана' }}</p>
                                </div>
                                <div class="text-sm text-gray-400 bg-gray-700/50 px-4 py-2 rounded-lg">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Отображаются только тикеты вашей категории
                                </div>
                            </div>
                        </div>
                    </div>

                <!-- Фильтры -->
                <div class="bg-gray-800/50 backdrop-blur-xl border border-gray-700/50 rounded-2xl p-6 mb-6 shadow-xl">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Поиск -->
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Поиск</label>
                            <div class="relative">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Поиск по заявкам..."
                                    class="w-full bg-gray-700/50 border-gray-600 text-white placeholder-gray-400 rounded-lg pl-10 pr-4 py-2.5 focus:border-green-500 focus:ring-green-500"
                                    @keyup.enter="applyFilters"
                                >
                                <svg class="absolute left-3 top-3 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Статус -->
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Статус</label>
                            <select
                                v-model="selectedStatus"
                                class="w-full bg-gray-700/50 border-gray-600 text-white rounded-lg px-4 py-2.5 focus:border-green-500 focus:ring-green-500"
                                @change="applyFilters"
                            >
                                <option value="">Все</option>
                                <option v-for="status in (statuses || [])" :key="status.id" :value="status.id">
                                    {{ status.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Проблема -->
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Тип проблемы</label>
                            <select
                                v-model="selectedProblem"
                                class="w-full bg-gray-700/50 border-gray-600 text-white rounded-lg px-4 py-2.5 focus:border-green-500 focus:ring-green-500"
                                @change="applyFilters"
                            >
                                <option value="">Все</option>
                                <option v-for="problem in (problems || [])" :key="problem.id" :value="problem.id">
                                    {{ problem.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Кнопки фильтров -->
                    <div class="flex gap-3 mt-4">
                        <button
                            @click="applyFilters"
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors"
                        >
                            Применить
                        </button>
                        <button
                            @click="resetFilters"
                            class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors"
                        >
                            Сбросить
                        </button>
                    </div>
                </div>

                <!-- Таблица заявок -->
                <div class="bg-gray-800/50 backdrop-blur-xl border border-gray-700/50 rounded-2xl shadow-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full" v-if="tickets?.data && tickets.data.length > 0">
                            <thead>
                                <tr class="border-b border-gray-700">
                                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">
                                            Название
                                        </th>
                                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300" v-if="!userCategory">
                                            Категория
                                        </th>
                                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">
                                            Статус
                                        </th>
                                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">
                                            Проблема
                                        </th>
                                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">
                                            Магазин
                                        </th>
                                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">
                                            Автор
                                        </th>
                                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">
                                            Дата создания
                                        </th>
                                        <th class="px-6 py-4 text-right text-sm font-semibold text-gray-300">
                                            Действия
                                        </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700/50">
                                <tr
                                    v-for="ticket in tickets.data"
                                    :key="ticket.id"
                                    class="hover:bg-gray-700/30 transition-colors cursor-pointer"
                                    @click="router.visit(route('tickets.show', ticket.id))"
                                >
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-2 h-2 rounded-full bg-green-500"></div>
                                            <span class="text-white font-medium">{{ ticket.title }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4" v-if="!userCategory">
                                        <span v-if="ticket.ticketCategory" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-500/20 text-blue-400 border border-blue-500/50">
                                            {{ ticket.ticketCategory.name }}
                                        </span>
                                        <span v-else class="text-gray-500 text-sm">Не указана</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            v-if="ticket.status"
                                            :class="['inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border', getStatusColor(ticket.status.name)]"
                                        >
                                            {{ ticket.status.name }}
                                        </span>
                                        <span v-else class="text-gray-500 text-sm">Не указан</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span v-if="ticket.problem" class="text-gray-300 text-sm">
                                            {{ ticket.problem.name }}
                                        </span>
                                        <span v-else class="text-gray-500 text-sm">Не указана</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div v-if="ticket.store" class="flex items-center gap-2 text-gray-400 text-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                            <span>{{ ticket.store.name }}</span>
                                        </div>
                                        <span v-else class="text-gray-500 text-sm">Не указан</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div v-if="ticket.author" class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-r from-green-600 to-emerald-600 flex items-center justify-center text-white text-xs font-semibold">
                                                {{ ticket.author.name.charAt(0) }}
                                            </div>
                                            <span class="text-gray-300 text-sm">{{ ticket.author.name }}</span>
                                        </div>
                                        <span v-else class="text-gray-500 text-sm">Неизвестен</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2 text-gray-400 text-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span>{{ formatDate(ticket.created_at) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4" @click.stop>
                                        <div class="flex items-center justify-end gap-2">
                                            <Link
                                                :href="route('tickets.show', ticket.id)"
                                                class="p-2 text-gray-400 hover:text-green-400 hover:bg-gray-700 rounded-lg transition-colors"
                                                title="Просмотр"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </Link>
                                            <Link
                                                :href="route('tickets.edit', ticket.id)"
                                                class="p-2 text-gray-400 hover:text-blue-400 hover:bg-gray-700 rounded-lg transition-colors"
                                                title="Редактировать"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Пустое состояние -->
                        <div v-else class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-2 text-lg font-medium text-white">Заявок не найдено</h3>
                            <p class="mt-1 text-gray-400">Попробуйте изменить фильтры или создайте новый заявку</p>
                            <div class="mt-6">
                                <Link :href="route('tickets.create')" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Создать первую заявку
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Пагинация -->
                <div v-if="tickets?.data && tickets.data.length > 0 && tickets.links" class="mt-6 flex items-center justify-between">
                    <div class="text-sm text-gray-400">
                        Показано {{ tickets.from || 0 }} - {{ tickets.to || 0 }} из {{ tickets.total || 0 }} заявок
                    </div>
                    <div class="flex gap-2">
                        <Link
                            v-for="link in tickets.links"
                            :key="link.label"
                            :href="link.url"
                            :class="[
                                'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                                link.active
                                    ? 'bg-green-600 text-white'
                                    : link.url
                                        ? 'bg-gray-700 text-gray-300 hover:bg-gray-600'
                                        : 'bg-gray-800 text-gray-600 cursor-not-allowed'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
