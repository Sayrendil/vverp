<script setup>
import { watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Modal from '@/Components/UI/Modals/ModalSimple.vue'
import InputLabel from '@/Components/UI/Forms/InputLabel.vue'
import InputError from '@/Components/UI/Forms/InputError.vue'
import PrimaryButton from '@/Components/UI/Buttons/PrimaryButton.vue'
import SecondaryButton from '@/Components/UI/Buttons/SecondaryButton.vue'

const props = defineProps({
    show: Boolean,
    categoryId: Number,
    executor: {
        type: Object,
        default: null,
    },
})

const emit = defineEmits(['close'])

const form = useForm({
    is_active: true,
    priority: 0,
    max_tickets: 10,
})

// Обновляем форму при открытии модалки с данными исполнителя
watch([() => props.show, () => props.executor], ([show, executor]) => {
    if (show && executor) {
        form.is_active = Boolean(executor.is_active)
        form.priority = executor.priority || 0
        form.max_tickets = executor.max_tickets || 10
        form.clearErrors()
    }
})

const submit = () => {
    if (!props.executor) return

    form.put(route('executors.update', [props.executor.id, props.categoryId]), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close')
        },
    })
}
</script>

<template>
    <Modal :show="show" @close="emit('close')" max-width="md">
        <div class="p-4 sm:p-6">
            <h2 class="text-base sm:text-lg font-medium text-gray-900 dark:text-gray-100">
                Редактировать исполнителя
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 truncate">
                {{ executor?.name }}
            </p>

            <form @submit.prevent="submit" class="mt-4 sm:mt-6 space-y-4 sm:space-y-6">
                <!-- Active Status -->
                <div>
                    <label class="flex items-center">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700"
                        />
                        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                            Активен как исполнитель
                        </span>
                    </label>
                    <InputError :message="form.errors.is_active" class="mt-2" />
                </div>

                <!-- Priority -->
                <div>
                    <InputLabel for="priority" value="Приоритет (0-100)" />
                    <input
                        id="priority"
                        v-model.number="form.priority"
                        type="number"
                        min="0"
                        max="100"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Чем выше приоритет, тем чаще будут назначаться заявки
                    </p>
                    <InputError :message="form.errors.priority" class="mt-2" />
                </div>

                <!-- Max Tickets -->
                <div>
                    <InputLabel for="max_tickets" value="Максимум заявок" />
                    <input
                        id="max_tickets"
                        v-model.number="form.max_tickets"
                        type="number"
                        min="1"
                        max="100"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Максимальное количество одновременных активных заявок
                    </p>
                    <InputError :message="form.errors.max_tickets" class="mt-2" />
                </div>

                <!-- Actions -->
                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 sm:gap-3 pt-2">
                    <SecondaryButton type="button" @click="emit('close')" class="w-full sm:w-auto justify-center">
                        Отмена
                    </SecondaryButton>
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" class="w-full sm:w-auto justify-center">
                        Сохранить
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
