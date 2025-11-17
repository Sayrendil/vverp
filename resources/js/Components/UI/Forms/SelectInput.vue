<script setup>
import { computed } from 'vue';

defineProps({
    modelValue: {
        type: [String, Number, Boolean, null],
        default: null,
    },
});

defineEmits(['update:modelValue']);

const classes = computed(() => {
    return 'border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 ' +
           'focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 ' +
           'rounded-md shadow-sm ' +
           '[&>option]:text-gray-900 [&>option]:dark:text-gray-100 ' +
           '[&>option]:bg-white [&>option]:dark:bg-gray-800 ' +
           '[&>option:checked]:bg-indigo-100 [&>option:checked]:dark:bg-indigo-900';
});
</script>

<template>
    <select
        :value="modelValue"
        :class="classes"
        @input="$emit('update:modelValue', $event.target.value)"
    >
        <slot />
    </select>
</template>
