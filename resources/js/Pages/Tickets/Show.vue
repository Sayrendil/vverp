<script setup>
import { useForm, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DangerButton from '@/Components/UI/Buttons/DangerButton.vue';
import SecondaryButton from '@/Components/UI/Buttons/SecondaryButton.vue';
import PrimaryButton from '@/Components/UI/Buttons/PrimaryButton.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    ticket: Object,
    isExecutorOfCategory: Boolean,
    availableExecutors: Array,
    allStatuses: Array,
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const deleteForm = useForm({});
const showDeleteConfirm = ref(false);

// Формы для админских действий
const assignExecutorForm = useForm({
    executor_id: null,
});

const changeStatusForm = useForm({
    status_id: props.ticket.status?.id || null,
});

const showAssignExecutorModal = ref(false);
const showChangeStatusModal = ref(false);

// Проверки прав доступа
const isExecutor = computed(() => {
    return props.ticket.executor?.id === user.value.id;
});

const isAuthor = computed(() => {
    return props.ticket.author?.id === user.value.id;
});

const isAdmin = computed(() => {
    return user.value.role === 'admin'
        && user.value.ticket_category_id === props.ticket.ticket_category_id;
});

const canTakeToWork = computed(() => {
    // Может взять в работу, если:
    // 1. Заявка "Создана" И нет исполнителя И пользователь исполнитель категории
    // 2. Заявка "Отложена" И пользователь исполнитель (может возобновить свою работу)
    // 3. Заявка "Отложена" И нет исполнителя И пользователь исполнитель категории
    const statusCreated = props.ticket.status?.name === 'Создана'
        && !props.ticket.executor
        && props.isExecutorOfCategory;

    const statusPostponed = props.ticket.status?.name === 'Отложена'
        && (isExecutor.value || (!props.ticket.executor && props.isExecutorOfCategory));

    return statusCreated || statusPostponed;
});

const canPostpone = computed(() => {
    // Исполнитель может отложить заявку:
    // 1. Если заявка "Создана" И он является исполнителем категории (может отложить без взятия в работу)
    // 2. Если заявка "В работе" И он исполнитель этой заявки
    const statusCreated = props.ticket.status?.name === 'Создана' && props.isExecutorOfCategory;
    const statusInProgress = isExecutor.value && props.ticket.status?.name === 'В работе';

    return statusCreated || statusInProgress;
});

const canSendForConfirmation = computed(() => {
    // Исполнитель может отправить на подтверждение, если заявка в работе
    return isExecutor.value && props.ticket.status?.name === 'В работе';
});

const canConfirmCompletion = computed(() => {
    // Автор может подтвердить, если заявка на подтверждении
    return isAuthor.value && props.ticket.status?.name === 'Подтверждена';
});

const canReturnToWork = computed(() => {
    // Автор может вернуть в работу, если заявка на подтверждении
    return isAuthor.value && props.ticket.status?.name === 'Подтверждена';
});

const isCompleted = computed(() => {
    // Проверка, завершена ли заявка
    return props.ticket.status?.name === 'Завершена';
});

const canEdit = computed(() => {
    // Нельзя редактировать завершенные заявки
    return !isCompleted.value;
});

const canDelete = computed(() => {
    // Нельзя удалять завершенные заявки
    return !isCompleted.value;
});

// Обработчики действий
const takeToWork = () => {
    router.post(route('tickets.take-to-work', props.ticket.id));
};

const postpone = () => {
    router.post(route('tickets.postpone', props.ticket.id));
};

const sendForConfirmation = () => {
    router.post(route('tickets.send-for-confirmation', props.ticket.id));
};

const confirmCompletion = () => {
    router.post(route('tickets.confirm-completion', props.ticket.id));
};

const returnToWork = () => {
    router.post(route('tickets.return-to-work', props.ticket.id));
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

const deleteTicket = () => {
    deleteForm.delete(route('tickets.destroy', props.ticket.id));
};

const handleImageError = (event) => {
    event.target.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iIzI4MjgyOCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM2NjY2NjYiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5ObyBpbWFnZTwvdGV4dD48L3N2Zz4=';
};

// Админские действия
const assignExecutor = () => {
    if (!assignExecutorForm.executor_id) {
        return;
    }

    assignExecutorForm.post(route('tickets.assign-executor', props.ticket.id), {
        preserveScroll: true,
        onSuccess: () => {
            showAssignExecutorModal.value = false;
            assignExecutorForm.reset();
        },
    });
};

const unassignExecutor = () => {
    if (confirm('Вы уверены, что хотите снять исполнителя с заявки?')) {
        router.post(route('tickets.unassign-executor', props.ticket.id));
    }
};

const changeStatus = () => {
    if (!changeStatusForm.status_id) {
        return;
    }

    changeStatusForm.post(route('tickets.admin-change-status', props.ticket.id), {
        preserveScroll: true,
        onSuccess: () => {
            showChangeStatusModal.value = false;
        },
    });
};
</script>

<template>
    <AppLayout title="Просмотр заявки">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-white leading-tight">
                    Заявка #{{ ticket.id }}
                </h2>
                <div class="flex gap-2">
                    <SecondaryButton
                        v-if="canEdit"
                        @click="router.visit(route('tickets.edit', ticket.id))"
                    >
                        Редактировать
                    </SecondaryButton>
                    <DangerButton
                        v-if="canDelete"
                        @click="showDeleteConfirm = true"
                    >
                        Удалить
                    </DangerButton>
                    <span
                        v-if="isCompleted"
                        class="inline-flex items-center px-4 py-2 bg-green-500/20 text-green-400 border border-green-500/50 rounded-lg text-sm font-medium"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Заявка завершена
                    </span>
                </div>
            </div>
        </template>

        <div class="py-10 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <!-- Навигация назад -->
                <Link :href="route('tickets.index')" class="inline-flex items-center text-gray-400 hover:text-green-400 mb-6 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Назад к списку
                </Link>

                <!-- Основная информация -->
                <div class="bg-gray-800/50 backdrop-blur-xl border border-gray-700/50 rounded-2xl p-8 mb-6 shadow-xl">
                    <!-- Заголовок и статус -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                        <h1 class="text-3xl font-bold text-white">{{ ticket.title }}</h1>
                        <span
                            v-if="ticket.status"
                            :class="['inline-flex items-center px-4 py-2 rounded-full text-sm font-medium border', getStatusColor(ticket.status.name)]"
                        >
                            {{ ticket.status.name }}
                        </span>
                    </div>

                    <!-- Описание -->
                    <div v-if="ticket.description" class="mb-6">
                        <h3 class="text-lg font-semibold text-white mb-2">Описание</h3>
                        <p class="text-gray-300 leading-relaxed">{{ ticket.description }}</p>
                    </div>

                    <!-- Детали заявки -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Категория -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-400 mb-2">Категория</h4>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                                </svg>
                                <span class="text-white font-medium">{{ ticket.ticketCategory?.name || 'Не указана' }}</span>
                            </div>
                        </div>

                        <!-- Тип проблемы -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-400 mb-2">Тип проблемы</h4>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                <span class="text-white font-medium">{{ ticket.problem?.name || 'Не указан' }}</span>
                            </div>
                        </div>

                        <!-- Магазин -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-400 mb-2">Магазин</h4>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <span class="text-white font-medium">{{ ticket.store?.name || 'Не указан' }}</span>
                            </div>
                        </div>

                        <!-- Касса -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-400 mb-2">Касса</h4>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                                <span class="text-white font-medium">{{ ticket.cash?.name || 'Не указана' }}</span>
                            </div>
                        </div>

                        <!-- Автор -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-400 mb-2">Автор</h4>
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-r from-green-600 to-emerald-600 flex items-center justify-center text-white text-sm font-semibold">
                                    {{ ticket.author?.name.charAt(0) || '?' }}
                                </div>
                                <span class="text-white font-medium">{{ ticket.author?.name || 'Неизвестен' }}</span>
                            </div>
                        </div>

                        <!-- Исполнитель -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-400 mb-2">Исполнитель</h4>
                            <div v-if="ticket.executor" class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-600 to-cyan-600 flex items-center justify-center text-white text-sm font-semibold">
                                    {{ ticket.executor.name.charAt(0) }}
                                </div>
                                <span class="text-white font-medium">{{ ticket.executor.name }}</span>
                            </div>
                            <span v-else class="text-gray-500">Не назначен</span>
                        </div>

                        <!-- Дата создания -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-400 mb-2">Дата создания</h4>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="text-white font-medium">{{ formatDate(ticket.created_at) }}</span>
                            </div>
                        </div>

                        <!-- Дата обновления -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-400 mb-2">Последнее обновление</h4>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-white font-medium">{{ formatDate(ticket.updated_at) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Админские кнопки управления -->
                <div v-if="isAdmin" class="bg-gradient-to-r from-purple-900/20 to-purple-800/20 backdrop-blur-xl border border-purple-500/50 rounded-2xl p-6 mb-6 shadow-xl">
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Управление администратора
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Назначить/Сменить исполнителя -->
                        <div class="bg-gray-900/50 rounded-lg p-4 border border-gray-700/50">
                            <h4 class="text-sm font-semibold text-gray-300 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Исполнитель
                            </h4>
                            <div class="space-y-2">
                                <button
                                    @click="showAssignExecutorModal = true"
                                    class="w-full px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm rounded-lg transition-colors flex items-center justify-center gap-2"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    {{ ticket.executor ? 'Сменить' : 'Назначить' }}
                                </button>
                                <button
                                    v-if="ticket.executor"
                                    @click="unassignExecutor"
                                    class="w-full px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 text-sm rounded-lg transition-colors flex items-center justify-center gap-2"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Снять
                                </button>
                            </div>
                        </div>

                        <!-- Изменить статус -->
                        <div class="bg-gray-900/50 rounded-lg p-4 border border-gray-700/50">
                            <h4 class="text-sm font-semibold text-gray-300 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Статус
                            </h4>
                            <button
                                @click="showChangeStatusModal = true"
                                class="w-full px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm rounded-lg transition-colors flex items-center justify-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Изменить статус
                            </button>
                        </div>

                        <!-- Информация -->
                        <div class="bg-gray-900/50 rounded-lg p-4 border border-gray-700/50">
                            <h4 class="text-sm font-semibold text-gray-300 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Информация
                            </h4>
                            <p class="text-xs text-gray-400 leading-relaxed">
                                У вас есть полные права на управление этой заявкой как у администратора категории "{{ ticket.ticketCategory?.name }}"
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Кнопки действий -->
                <div v-if="canTakeToWork || canPostpone || canSendForConfirmation || canConfirmCompletion || canReturnToWork" class="bg-gray-800/50 backdrop-blur-xl border border-gray-700/50 rounded-2xl p-6 mb-6 shadow-xl">
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Действия с заявкой
                    </h3>

                    <div class="flex flex-wrap gap-3">
                        <!-- Взять в работу -->
                        <PrimaryButton v-if="canTakeToWork" @click="takeToWork" type="button">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Взять в работу
                        </PrimaryButton>

                        <!-- Отложить -->
                        <SecondaryButton v-if="canPostpone" @click="postpone" type="button">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Отложить
                        </SecondaryButton>

                        <!-- Отправить на подтверждение -->
                        <PrimaryButton v-if="canSendForConfirmation" @click="sendForConfirmation" type="button">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Отправить на подтверждение
                        </PrimaryButton>

                        <!-- Подтвердить выполнение -->
                        <PrimaryButton v-if="canConfirmCompletion" @click="confirmCompletion" type="button">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Подтвердить выполнение
                        </PrimaryButton>

                        <!-- Вернуть в работу -->
                        <DangerButton v-if="canReturnToWork" @click="returnToWork" type="button">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Вернуть в работу
                        </DangerButton>
                    </div>

                    <!-- Подсказки по статусам -->
                    <div class="mt-4 p-4 bg-gray-900/50 rounded-lg border border-gray-700/30">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="text-sm text-gray-300">
                                <p v-if="canTakeToWork" class="mb-1">
                                    <span class="font-semibold text-white">Доступные действия:</span> Вы можете взять эту заявку в работу.
                                </p>
                                <p v-if="isExecutor && ticket.status?.name === 'В работе'" class="mb-1">
                                    <span class="font-semibold text-white">Вы исполнитель:</span> Можете отложить заявку или отправить на подтверждение автору.
                                </p>
                                <p v-if="isAuthor && ticket.status?.name === 'Подтверждена'">
                                    <span class="font-semibold text-white">Вы автор:</span> Можете подтвердить выполнение или вернуть заявку в работу.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Вложения -->
                <div v-if="ticket.attachments && ticket.attachments.length > 0" class="bg-gray-800/50 backdrop-blur-xl border border-gray-700/50 rounded-2xl p-6 shadow-xl">
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                        </svg>
                        Вложения
                        <span class="text-sm text-gray-400">({{ ticket.attachments.length }})</span>
                    </h3>

                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <div v-for="attachment in ticket.attachments" :key="attachment.id" class="group relative">
                            <!-- Фото -->
                            <a
                                v-if="attachment.file_type === 'photo'"
                                :href="`/storage/${attachment.file_path}`"
                                target="_blank"
                                class="aspect-square rounded-lg overflow-hidden bg-gray-900 border border-gray-700 hover:border-green-500 transition-colors block"
                            >
                                <img
                                    :src="`/storage/${attachment.file_path}`"
                                    alt="Фото"
                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                                    @error="handleImageError"
                                />
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </div>
                            </a>

                            <!-- Видео -->
                            <a
                                v-else-if="attachment.file_type === 'video'"
                                :href="`/storage/${attachment.file_path}`"
                                target="_blank"
                                class="aspect-square rounded-lg overflow-hidden bg-gray-900 border border-gray-700 hover:border-green-500 transition-colors flex items-center justify-center relative"
                            >
                                <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="absolute bottom-2 left-2 text-xs text-white bg-black/70 px-2 py-1 rounded">Видео</span>
                            </a>

                            <!-- Документ -->
                            <a
                                v-else
                                :href="`/storage/${attachment.file_path}`"
                                target="_blank"
                                :download="attachment.file_name"
                                class="aspect-square rounded-lg overflow-hidden bg-gray-900 border border-gray-700 hover:border-green-500 transition-colors flex flex-col items-center justify-center p-4"
                            >
                                <svg class="w-12 h-12 text-green-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                <span class="text-xs text-gray-400 text-center break-all">{{ attachment.file_name }}</span>
                            </a>
                        </div>
                    </div>

                    <p class="mt-4 text-sm text-gray-400 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Нажмите на файл для просмотра в полном размере
                    </p>
                </div>

                <!-- Модальное окно подтверждения удаления -->
                <div v-if="showDeleteConfirm" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showDeleteConfirm = false">
                    <div class="flex items-center justify-center min-h-screen px-4">
                        <div class="fixed inset-0 bg-black/70 backdrop-blur-sm transition-opacity"></div>
                        <div class="relative bg-gray-800 rounded-2xl p-8 max-w-md w-full shadow-2xl border border-gray-700">
                            <h3 class="text-2xl font-bold text-white mb-4">Подтверждение удаления</h3>
                            <p class="text-gray-300 mb-6">Вы действительно хотите удалить эту заявку? Это действие нельзя отменить.</p>
                            <div class="flex gap-3 justify-end">
                                <SecondaryButton @click="showDeleteConfirm = false">
                                    Отмена
                                </SecondaryButton>
                                <DangerButton
                                    @click="deleteTicket"
                                    :class="{ 'opacity-25': deleteForm.processing }"
                                    :disabled="deleteForm.processing"
                                >
                                    Удалить
                                </DangerButton>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Модальное окно назначения исполнителя -->
                <div v-if="showAssignExecutorModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showAssignExecutorModal = false">
                    <div class="flex items-center justify-center min-h-screen px-4">
                        <div class="fixed inset-0 bg-black/70 backdrop-blur-sm transition-opacity"></div>
                        <div class="relative bg-gray-800 rounded-2xl p-8 max-w-md w-full shadow-2xl border border-purple-700">
                            <h3 class="text-2xl font-bold text-white mb-4 flex items-center gap-2">
                                <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Назначить исполнителя
                            </h3>
                            <p class="text-gray-300 mb-4">Выберите исполнителя из списка активных исполнителей вашей категории:</p>

                            <div v-if="availableExecutors && availableExecutors.length > 0" class="mb-6">
                                <label class="block text-sm font-medium text-gray-300 mb-2">Исполнитель</label>
                                <select
                                    v-model="assignExecutorForm.executor_id"
                                    class="w-full bg-gray-700/50 border-gray-600 text-white rounded-lg px-4 py-2.5 focus:border-purple-500 focus:ring-purple-500"
                                >
                                    <option :value="null">Выберите исполнителя</option>
                                    <option v-for="executor in availableExecutors" :key="executor.id" :value="executor.id">
                                        {{ executor.name }} ({{ executor.email }})
                                    </option>
                                </select>
                            </div>
                            <div v-else class="mb-6 p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-lg">
                                <p class="text-sm text-yellow-400">Нет доступных исполнителей в вашей категории</p>
                            </div>

                            <div class="flex gap-3 justify-end">
                                <SecondaryButton @click="showAssignExecutorModal = false">
                                    Отмена
                                </SecondaryButton>
                                <PrimaryButton
                                    @click="assignExecutor"
                                    :class="{ 'opacity-25': assignExecutorForm.processing || !assignExecutorForm.executor_id }"
                                    :disabled="assignExecutorForm.processing || !assignExecutorForm.executor_id"
                                    class="!bg-purple-600 hover:!bg-purple-700"
                                >
                                    Назначить
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Модальное окно изменения статуса -->
                <div v-if="showChangeStatusModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showChangeStatusModal = false">
                    <div class="flex items-center justify-center min-h-screen px-4">
                        <div class="fixed inset-0 bg-black/70 backdrop-blur-sm transition-opacity"></div>
                        <div class="relative bg-gray-800 rounded-2xl p-8 max-w-md w-full shadow-2xl border border-purple-700">
                            <h3 class="text-2xl font-bold text-white mb-4 flex items-center gap-2">
                                <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Изменить статус
                            </h3>
                            <p class="text-gray-300 mb-4">Выберите новый статус для заявки:</p>

                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-300 mb-2">Статус</label>
                                <select
                                    v-model="changeStatusForm.status_id"
                                    class="w-full bg-gray-700/50 border-gray-600 text-white rounded-lg px-4 py-2.5 focus:border-purple-500 focus:ring-purple-500"
                                >
                                    <option v-for="status in allStatuses" :key="status.id" :value="status.id">
                                        {{ status.name }}
                                    </option>
                                </select>
                                <p class="mt-2 text-xs text-gray-400">
                                    Текущий статус: <span class="font-semibold text-white">{{ ticket.status?.name }}</span>
                                </p>
                            </div>

                            <div class="flex gap-3 justify-end">
                                <SecondaryButton @click="showChangeStatusModal = false">
                                    Отмена
                                </SecondaryButton>
                                <PrimaryButton
                                    @click="changeStatus"
                                    :class="{ 'opacity-25': changeStatusForm.processing }"
                                    :disabled="changeStatusForm.processing"
                                    class="!bg-purple-600 hover:!bg-purple-700"
                                >
                                    Изменить
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
