<script setup>
import { Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

defineProps({
    sidebarOpen: Boolean,
});

// Состояние раскрытия справочников
const dictionariesOpen = ref(false);

// Проверяем, находимся ли мы на странице справочников
const isDictionariesActive = computed(() => {
    return route().current('dictionaries.*') || route().current('executors.*');
});

// Автоматически открываем справочники, если мы на одной из их страниц
if (isDictionariesActive.value) {
    dictionariesOpen.value = true;
}
</script>

<template>
    <nav class="flex-1 px-3 py-4 space-y-1">
        <!-- Dashboard -->
        <Link :href="route('dashboard')" :class="[
            'flex items-center rounded-lg transition-colors group',
            sidebarOpen ? 'px-3 py-3' : 'px-3 py-3 justify-center',
            route().current('dashboard')
                ? 'bg-green-600 text-white'
                : 'text-gray-300 hover:bg-gray-700 hover:text-white'
        ]">
            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span v-show="sidebarOpen" class="ml-3 font-medium">Панель управления</span>
        </Link>

        <!-- Tickets -->
        <Link :href="route('tickets.index')" :class="[
            'flex items-center rounded-lg transition-colors group',
            sidebarOpen ? 'px-3 py-3' : 'px-3 py-3 justify-center',
            route().current('tickets.*')
                ? 'bg-green-600 text-white'
                : 'text-gray-300 hover:bg-gray-700 hover:text-white'
        ]">
            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span v-show="sidebarOpen" class="ml-3 font-medium">Заявки</span>
        </Link>

        <!-- Tasks / Projects -->
        <Link :href="route('projects.index')" :class="[
            'flex items-center rounded-lg transition-colors group',
            sidebarOpen ? 'px-3 py-3' : 'px-3 py-3 justify-center',
            route().current('projects.*') || route().current('tasks.*')
                ? 'bg-green-600 text-white'
                : 'text-gray-300 hover:bg-gray-700 hover:text-white'
        ]">
            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
            <span v-show="sidebarOpen" class="ml-3 font-medium">Задачи</span>
        </Link>

        <!-- Dictionaries (Admin only) - Collapsible -->
        <div v-if="$page.props.auth.user.is_admin" class="space-y-1">
            <!-- Dictionaries Toggle -->
            <button
                @click="dictionariesOpen = !dictionariesOpen"
                :class="[
                    'w-full flex items-center justify-between rounded-lg transition-colors group',
                    sidebarOpen ? 'px-3 py-3' : 'px-3 py-3 justify-center',
                    isDictionariesActive
                        ? 'bg-green-600 text-white'
                        : 'text-gray-300 hover:bg-gray-700 hover:text-white'
                ]"
            >
                <div class="flex items-center">
                    <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span v-show="sidebarOpen" class="ml-3 font-medium">Справочники</span>
                </div>
                <svg
                    v-show="sidebarOpen"
                    :class="['w-5 h-5 transition-transform', dictionariesOpen ? 'rotate-180' : '']"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <!-- Submenu Items -->
            <transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div v-show="dictionariesOpen && sidebarOpen" class="space-y-1">
                    <!-- Dictionaries Main -->
                    <Link :href="route('dictionaries.index')" :class="[
                        'flex items-center rounded-lg transition-colors group',
                        'px-3 py-2 ml-4',
                        route().current('dictionaries.index')
                            ? 'bg-green-600 text-white'
                            : 'text-gray-300 hover:bg-gray-700 hover:text-white'
                    ]">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                        <span class="ml-3 text-sm font-medium">Все справочники</span>
                    </Link>

                    <!-- Executors -->
                    <Link :href="route('executors.index')" :class="[
                        'flex items-center rounded-lg transition-colors group',
                        'px-3 py-2 ml-4',
                        route().current('executors.*')
                            ? 'bg-green-600 text-white'
                            : 'text-gray-300 hover:bg-gray-700 hover:text-white'
                    ]">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span class="ml-3 text-sm font-medium">Исполнители</span>
                    </Link>
                </div>
            </transition>
        </div>

        <!-- Teams (if enabled) -->
<!--        <template v-if="$page.props.jetstream.hasTeamFeatures">-->
<!--            <Link :href="route('teams.show', $page.props.auth.user.current_team)" :class="[-->
<!--                'flex items-center rounded-lg transition-colors group',-->
<!--                sidebarOpen ? 'px-3 py-3' : 'px-3 py-3 justify-center',-->
<!--                route().current('teams.show')-->
<!--                    ? 'bg-green-600 text-white'-->
<!--                    : 'text-gray-300 hover:bg-gray-700 hover:text-white'-->
<!--            ]">-->
<!--                <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">-->
<!--                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />-->
<!--                </svg>-->
<!--                <span v-show="sidebarOpen" class="ml-3 font-medium">Настройки команды</span>-->
<!--            </Link>-->
<!--        </template>-->

        <!-- Profile -->
        <Link :href="route('profile.show')" :class="[
            'flex items-center rounded-lg transition-colors group',
            sidebarOpen ? 'px-3 py-3' : 'px-3 py-3 justify-center',
            route().current('profile.show')
                ? 'bg-green-600 text-white'
                : 'text-gray-300 hover:bg-gray-700 hover:text-white'
        ]">
            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span v-show="sidebarOpen" class="ml-3 font-medium">Профиль</span>
        </Link>

        <!-- API Tokens -->
        <Link v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')" :class="[
            'flex items-center rounded-lg transition-colors group',
            sidebarOpen ? 'px-3 py-3' : 'px-3 py-3 justify-center',
            route().current('api-tokens.index')
                ? 'bg-green-600 text-white'
                : 'text-gray-300 hover:bg-gray-700 hover:text-white'
        ]">
            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
            </svg>
            <span v-show="sidebarOpen" class="ml-3 font-medium">API Токены</span>
        </Link>
    </nav>
</template>
