<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
        <!-- Logo Section -->
        <div class="mb-8">
            <slot name="logo" />
        </div>

        <!-- Card -->
        <div class="w-full sm:max-w-md px-8 py-10 bg-gray-800/50 backdrop-blur-xl shadow-2xl border border-gray-700/50 overflow-hidden sm:rounded-2xl">
            <slot />
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center">
            <p class="text-sm text-gray-400">
                © {{ new Date().getFullYear() }} VVERP. All rights reserved.
            </p>
        </div>
    </div>
</template>
