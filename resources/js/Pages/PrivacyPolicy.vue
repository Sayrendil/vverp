<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';

defineProps({
    policy: String,
});
</script>

<template>
    <Head title="Политика конфиденциальности" />

    <div class="font-sans text-white antialiased bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 min-h-screen">
        <div class="pt-4">
            <div class="min-h-screen flex flex-col items-center pt-6 sm:pt-0">
                <div class="mb-8">
                    <AuthenticationCardLogo />
                </div>

                <div class="w-full sm:max-w-2xl mt-6 p-6 bg-gray-800/50 backdrop-blur-xl border border-gray-700/50 shadow-2xl overflow-hidden sm:rounded-2xl prose prose-invert prose-green max-w-none" v-html="policy" />
            </div>
        </div>
    </div>
</template>
