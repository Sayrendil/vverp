<script setup>
import { ref } from 'vue'
import Modal from '@/Components/UI/Modals/Modal.vue'
import DangerButton from '@/Components/UI/Buttons/DangerButton.vue'
import SecondaryButton from '@/Components/UI/Buttons/SecondaryButton.vue'

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    dictionary: {
        type: Object,
        required: true,
    },
    item: {
        type: Object,
        default: null,
    },
})

const emit = defineEmits(['close', 'confirm'])

const processing = ref(false)

const handleConfirm = () => {
    processing.value = true
    emit('confirm')

    // Сброс после небольшой задержки
    setTimeout(() => {
        processing.value = false
    }, 500)
}

const handleClose = () => {
    processing.value = false
    emit('close')
}
</script>

<template>
    <Modal :show="show" @close="handleClose" max-width="md">
        <div class="p-6">
            <!-- Иконка предупреждения -->
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/20">
                <svg
                    class="h-6 w-6 text-red-600 dark:text-red-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                    />
                </svg>
            </div>

            <!-- Заголовок -->
            <div class="mt-4 text-center">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Удалить {{ dictionary.singular_name.toLowerCase() }}?
                </h3>
                <div v-if="item" class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    <p>
                        Вы действительно хотите удалить
                        <span class="font-semibold">{{ item.name }}</span>?
                    </p>
                    <p class="mt-1 text-red-600 dark:text-red-400">
                        Это действие нельзя будет отменить.
                    </p>
                </div>
            </div>

            <!-- Кнопки -->
            <div class="mt-6 flex items-center justify-center gap-3">
                <SecondaryButton type="button" @click="handleClose">
                    Отмена
                </SecondaryButton>

                <DangerButton type="button" @click="handleConfirm" :disabled="processing">
                    <span v-if="processing">Удаление...</span>
                    <span v-else>Удалить</span>
                </DangerButton>
            </div>
        </div>
    </Modal>
</template>
