<script setup>
import { Link } from '@inertiajs/vue3';

defineEmits(['toggle-sidebar']);
</script>

<template>
    <header class="lg:hidden bg-gray-800 border-b border-gray-700 sticky top-0 z-30 shadow-lg">
        <div class="flex items-center justify-between h-16 px-4">
            <!-- Menu Button -->
            <button
                @click="$emit('toggle-sidebar')"
                class="p-2 -ml-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors"
                aria-label="Открыть меню"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <!-- Logo and Title -->
            <Link :href="route('dashboard')" class="flex items-center space-x-2">
                <img src="/assets/logo.svg" alt="VVERP" class="h-8 w-8" />
                <span class="text-white font-bold text-lg">VVERP</span>
            </Link>

            <!-- User Avatar -->
            <Link :href="route('profile.show')" class="p-1 -mr-2">
                <div v-if="$page.props.jetstream.managesProfilePhotos" class="w-8 h-8 rounded-full overflow-hidden ring-2 ring-green-600">
                    <img class="w-full h-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                </div>
                <div v-else class="w-8 h-8 rounded-full bg-green-600 flex items-center justify-center ring-2 ring-green-500">
                    <span class="text-white font-medium text-sm">{{ $page.props.auth.user.name.charAt(0) }}</span>
                </div>
            </Link>
        </div>
    </header>
</template>
