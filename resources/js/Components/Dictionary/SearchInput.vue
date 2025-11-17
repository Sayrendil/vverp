<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Поиск...',
    },
})

const emit = defineEmits(['update:modelValue', 'search'])

const localValue = ref(props.modelValue)

watch(
    () => props.modelValue,
    (newValue) => {
        localValue.value = newValue
    }
)

const handleInput = (event) => {
    localValue.value = event.target.value
    emit('update:modelValue', event.target.value)
}

const handleSearch = () => {
    emit('search')
}

const handleClear = () => {
    localValue.value = ''
    emit('update:modelValue', '')
    emit('search')
}
</script>

<template>
    <div class="relative flex-1">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <svg
                class="h-5 w-5 text-gray-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                />
            </svg>
        </div>
        <input
            type="text"
            :value="localValue"
            :placeholder="placeholder"
            @input="handleInput"
            @keydown.enter="handleSearch"
            class="block w-full rounded-md border-gray-300 py-2 pl-10 pr-10 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-400"
        />
        <div v-if="localValue" class="absolute inset-y-0 right-0 flex items-center pr-3">
            <button
                type="button"
                @click="handleClear"
                class="rounded p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                title="Очистить"
            >
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clip-rule="evenodd"
                    />
                </svg>
            </button>
        </div>
    </div>
</template>
