<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
</script>

<template>
    <AppLayout title="Dashboard">
        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <!-- Welcome Banner -->
            <div class="bg-gradient-to-r from-green-600 to-emerald-600 rounded-2xl shadow-xl p-8 mb-6">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-2">
                            Добро пожаловать, {{ $page.props.auth.user.name }}! 👋
                        </h1>
                        <p class="text-green-100 text-lg">
                            Вот что происходит с вашими проектами сегодня
                        </p>
                    </div>
                    <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4">
                        <p class="text-white/80 text-sm font-medium">Текущая дата</p>
                        <p class="text-white text-xl font-bold">{{ new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }}</p>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <!-- Stat Card 1 -->
                <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700 hover:border-green-500 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm font-medium">Всего проектов</p>
                            <p class="text-white text-3xl font-bold mt-2">24</p>
                            <div class="flex items-center mt-2">
                                <span class="text-green-400 text-sm font-medium">+12%</span>
                                <span class="text-gray-500 text-sm ml-2">с прошлого месяца</span>
                            </div>
                        </div>
                        <div class="bg-green-600/20 p-3 rounded-lg">
                            <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Stat Card 2 -->
                <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700 hover:border-blue-500 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm font-medium">Активные задачи</p>
                            <p class="text-white text-3xl font-bold mt-2">142</p>
                            <div class="flex items-center mt-2">
                                <span class="text-blue-400 text-sm font-medium">+8%</span>
                                <span class="text-gray-500 text-sm ml-2">за последнюю неделю</span>
                            </div>
                        </div>
                        <div class="bg-blue-600/20 p-3 rounded-lg">
                            <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Stat Card 3 -->
                <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700 hover:border-purple-500 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm font-medium">Участники команды</p>
                            <p class="text-white text-3xl font-bold mt-2">18</p>
                            <div class="flex items-center mt-2">
                                <span class="text-purple-400 text-sm font-medium">+2</span>
                                <span class="text-gray-500 text-sm ml-2">новых в этом месяце</span>
                            </div>
                        </div>
                        <div class="bg-purple-600/20 p-3 rounded-lg">
                            <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Stat Card 4 -->
                <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700 hover:border-yellow-500 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm font-medium">Завершено</p>
                            <p class="text-white text-3xl font-bold mt-2">89%</p>
                            <div class="flex items-center mt-2">
                                <span class="text-yellow-400 text-sm font-medium">+5%</span>
                                <span class="text-gray-500 text-sm ml-2">уровень завершения</span>
                            </div>
                        </div>
                        <div class="bg-yellow-600/20 p-3 rounded-lg">
                            <svg class="w-8 h-8 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Recent Activity -->
                <div class="lg:col-span-2 bg-gray-800 rounded-xl shadow-lg border border-gray-700">
                    <div class="p-6 border-b border-gray-700">
                        <h2 class="text-xl font-bold text-white">Последняя активность</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <!-- Activity Item -->
                            <div class="flex items-start space-x-4 p-4 bg-gray-700/50 rounded-lg hover:bg-gray-700 transition-colors">
                                <div class="bg-green-600 rounded-full p-2 flex-shrink-0">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-white font-medium">Задача выполнена</p>
                                    <p class="text-gray-400 text-sm mt-1">Вы завершили "Дизайн новой панели"</p>
                                    <p class="text-gray-500 text-xs mt-2">2 часа назад</p>
                                </div>
                            </div>

                            <div class="flex items-start space-x-4 p-4 bg-gray-700/50 rounded-lg hover:bg-gray-700 transition-colors">
                                <div class="bg-blue-600 rounded-full p-2 flex-shrink-0">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-white font-medium">Создан новый проект</p>
                                    <p class="text-gray-400 text-sm mt-1">Начат "Редизайн мобильного приложения"</p>
                                    <p class="text-gray-500 text-xs mt-2">5 часов назад</p>
                                </div>
                            </div>

                            <div class="flex items-start space-x-4 p-4 bg-gray-700/50 rounded-lg hover:bg-gray-700 transition-colors">
                                <div class="bg-purple-600 rounded-full p-2 flex-shrink-0">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-white font-medium">Добавлен участник команды</p>
                                    <p class="text-gray-400 text-sm mt-1">Иван Иванов присоединился к команде</p>
                                    <p class="text-gray-500 text-xs mt-2">1 день назад</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-gray-800 rounded-xl shadow-lg border border-gray-700">
                    <div class="p-6 border-b border-gray-700">
                        <h2 class="text-xl font-bold text-white">Быстрые действия</h2>
                    </div>
                    <div class="p-6 space-y-3">
                        <button class="w-full flex items-center justify-between px-4 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-lg transition-all transform hover:scale-105">
                            <span class="font-medium">Создать новый проект</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </button>

                        <button class="w-full flex items-center justify-between px-4 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors">
                            <span class="font-medium">Добавить задачу</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </button>

                        <button class="w-full flex items-center justify-between px-4 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors">
                            <span class="font-medium">Пригласить участника</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                        </button>

                        <button class="w-full flex items-center justify-between px-4 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors">
                            <span class="font-medium">Просмотреть отчеты</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
