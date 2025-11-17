<script setup>
import { Link, router } from '@inertiajs/vue3';
import { Transition } from 'vue';
import NavigationLinks from './NavigationLinks.vue';

defineProps({
    show: Boolean,
});

const emit = defineEmits(['close', 'logout']);

const handleLinkClick = () => {
    emit('close');
};

const handleLogout = () => {
    router.post(route('logout'));
    emit('close');
};
</script>

<template>
    <!-- Overlay with fade transition -->
    <Transition
        enter-active-class="transition-opacity duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-show="show" class="fixed inset-0 z-40 lg:hidden">
            <!-- Background overlay -->
            <div
                class="fixed inset-0 bg-gray-900/80 backdrop-blur-sm"
                @click="$emit('close')"
                aria-hidden="true"
            ></div>

            <!-- Sidebar with slide transition -->
            <Transition
                enter-active-class="transition-transform duration-300 ease-out"
                enter-from-class="-translate-x-full"
                enter-to-class="translate-x-0"
                leave-active-class="transition-transform duration-200 ease-in"
                leave-from-class="translate-x-0"
                leave-to-class="-translate-x-full"
            >
                <aside
                    v-show="show"
                    class="fixed inset-y-0 left-0 w-64 max-w-[80vw] bg-gray-800 border-r border-gray-700 z-50 overflow-y-auto"
                >
                    <!-- Header -->
                    <div class="flex items-center justify-between h-16 px-4 border-b border-gray-700 sticky top-0 bg-gray-800 z-10">
                        <Link :href="route('dashboard')" class="flex items-center space-x-3" @click="handleLinkClick">
                            <img src="/assets/logo.svg" alt="VVERP" class="h-10 w-10" />
                            <span class="text-white font-bold text-xl">VVERP</span>
                        </Link>
                        <button
                            @click="$emit('close')"
                            class="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors"
                            aria-label="Закрыть меню"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Navigation - use shared NavigationLinks component -->
                    <div @click="handleLinkClick">
                        <NavigationLinks :sidebar-open="true" />
                    </div>

                    <!-- User info and logout -->
                    <div class="border-t border-gray-700 p-4 sticky bottom-0 bg-gray-800">
                        <div class="flex items-center mb-3 px-3">
                            <div v-if="$page.props.jetstream.managesProfilePhotos" class="w-10 h-10 rounded-full overflow-hidden">
                                <img class="w-full h-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                            </div>
                            <div v-else class="w-10 h-10 rounded-full bg-green-600 flex items-center justify-center">
                                <span class="text-white font-medium">{{ $page.props.auth.user.name.charAt(0) }}</span>
                            </div>
                            <div class="ml-3 flex-1 min-w-0">
                                <p class="text-sm font-medium text-white truncate">{{ $page.props.auth.user.name }}</p>
                                <p class="text-xs text-gray-400 truncate">{{ $page.props.auth.user.email }}</p>
                            </div>
                        </div>
                        <button
                            @click="handleLogout"
                            class="flex items-center justify-center w-full px-3 py-2 rounded-lg text-gray-300 hover:text-white hover:bg-red-600 transition-colors"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Выйти
                        </button>
                    </div>
                </aside>
            </Transition>
        </div>
    </Transition>
</template>
