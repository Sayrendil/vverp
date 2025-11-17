<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <Link :href="'/'">
        <img
            src="/assets/logo.svg"
            alt="VVERP Logo"
            class="h-20 w-auto transition-transform duration-300 hover:scale-105"
        />
    </Link>
</template>
