<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import Banner from '@/Components/UI/Feedback/Banner.vue';
import Sidebar from '@/Components/Layout/Sidebar.vue';
import MobileSidebar from '@/Components/Layout/MobileSidebar.vue';
import MobileHeader from '@/Components/Layout/MobileHeader.vue';

defineProps({
    title: String,
});

const sidebarOpen = ref(true);
const showMobileSidebar = ref(false);

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div>
        <Head :title="title" />

        <Banner />

        <div class="min-h-screen bg-gray-900">
            <!-- Desktop Sidebar -->
            <Sidebar :sidebar-open="sidebarOpen" @toggle="toggleSidebar" />

            <!-- Mobile Sidebar -->
            <MobileSidebar
                :show="showMobileSidebar"
                @close="showMobileSidebar = false"
                @logout="logout"
            />

            <!-- Main Content -->
            <div :class="[
                'transition-all duration-300 ease-in-out',
                sidebarOpen ? 'lg:ml-64' : 'lg:ml-20'
            ]">
                <!-- Mobile Header -->
                <MobileHeader @toggle-sidebar="showMobileSidebar = true" />

                <!-- Page Content -->
                <main class="min-h-screen">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
