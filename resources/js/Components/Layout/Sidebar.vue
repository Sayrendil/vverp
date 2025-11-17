<script setup>
import { Link } from '@inertiajs/vue3';
import NavigationLinks from './NavigationLinks.vue';
import UserDropdown from './UserDropdown.vue';

defineProps({
    sidebarOpen: Boolean,
});

defineEmits(['toggle']);
</script>

<template>
    <aside :class="[
        'fixed inset-y-0 left-0 z-50 bg-gray-800 border-r border-gray-700 transition-all duration-300 ease-in-out',
        sidebarOpen ? 'w-64' : 'w-20'
    ]" class="hidden lg:block">
        <!-- Logo -->
        <div :class="[
            'flex items-center h-16 px-4 border-b border-gray-700',
            sidebarOpen ? 'justify-between' : 'justify-center'
        ]">
            <Link :href="route('dashboard')" :class="[
                'flex items-center',
                sidebarOpen ? 'space-x-3' : ''
            ]">
                <img src="/assets/logo.svg" alt="VVERP" class="h-10 w-10" />
                <span v-show="sidebarOpen" class="text-white font-bold text-xl transition-opacity duration-300">VVERP</span>
            </Link>
        </div>

        <!-- Toggle Button -->
        <div class="px-3 py-3 border-b border-gray-700">
            <button
                @click="$emit('toggle')"
                class="w-full flex items-center justify-center p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors"
                :title="sidebarOpen ? 'Свернуть' : 'Развернуть'"
            >
                <svg v-if="sidebarOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                </svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        <!-- Navigation Links -->
        <NavigationLinks :sidebar-open="sidebarOpen" />

        <!-- User Profile & Logout -->
        <UserDropdown :sidebar-open="sidebarOpen" />
    </aside>
</template>
