<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import Modal from '@/Components/UI/Modals/Modal.vue'
import InputError from '@/Components/UI/Forms/InputError.vue'
import InputLabel from '@/Components/UI/Forms/InputLabel.vue'
import TextInput from '@/Components/UI/Forms/TextInput.vue'
import PrimaryButton from '@/Components/UI/Buttons/PrimaryButton.vue'
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
    isEditing: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['close', 'submit'])

// Форма
const form = ref({})
const errors = ref({})
const processing = ref(false)

// Computed свойства
const isEdit = computed(() => props.isEditing && !!props.item)
const modalTitle = computed(() =>
    isEdit.value
        ? `Редактировать ${props.dictionary.singular_name.toLowerCase()}`
        : `Создать ${props.dictionary.singular_name.toLowerCase()}`
)

// Инициализация формы
const initializeForm = () => {
    if (!props.dictionary || !props.dictionary.form_fields) {
        return
    }

    const formData = {}

    props.dictionary.form_fields.forEach((field) => {
        if (isEdit.value && props.item) {
            formData[field.key] = props.item[field.key] ?? field.default ?? ''
        } else {
            formData[field.key] = field.default ?? ''
        }
    })

    form.value = formData
    errors.value = {}
}

// Watch для инициализации формы при показе модалки
watch(
    () => props.show,
    async (show) => {
        if (show) {
            // Ждем следующий тик для корректной инициализации
            await nextTick()
            initializeForm()
        }
    },
    { immediate: true }
)

const handleSubmit = () => {
    processing.value = true
    errors.value = {}

    emit('submit', form.value)

    // Сброс после небольшой задержки (время для обработки запроса)
    setTimeout(() => {
        processing.value = false
    }, 500)
}

const handleClose = () => {
    form.value = {}
    errors.value = {}
    processing.value = false
    emit('close')
}

// Определение типа поля
const getFieldComponent = (field) => {
    switch (field.type) {
        case 'textarea':
            return 'textarea'
        case 'select':
            return 'select'
        case 'checkbox':
            return 'checkbox'
        default:
            return 'input'
    }
}
</script>

<template>
    <Modal :show="show" @close="handleClose" max-width="2xl">
        <div class="p-6">
            <!-- Заголовок -->
            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ modalTitle }}
                </h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ dictionary.description }}
                </p>
            </div>

            <!-- Форма -->
            <form @submit.prevent="handleSubmit" class="space-y-4">
                <div
                    v-for="field in dictionary.form_fields"
                    :key="field.key"
                    class="space-y-2"
                >
                    <InputLabel :for="field.key" :value="field.label" :required="field.required" />

                    <!-- Text Input -->
                    <TextInput
                        v-if="field.type === 'text' || field.type === 'email' || field.type === 'number'"
                        :id="field.key"
                        v-model="form[field.key]"
                        :type="field.type"
                        :placeholder="field.placeholder"
                        :required="field.required"
                        class="w-full"
                    />

                    <!-- Textarea -->
                    <textarea
                        v-else-if="field.type === 'textarea'"
                        :id="field.key"
                        v-model="form[field.key]"
                        :placeholder="field.placeholder"
                        :required="field.required"
                        rows="3"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    />

                    <!-- Select -->
                    <select
                        v-else-if="field.type === 'select'"
                        :id="field.key"
                        v-model="form[field.key]"
                        :required="field.required"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 [&>option]:text-gray-900 [&>option]:dark:text-gray-100 [&>option]:dark:bg-gray-700"
                    >
                        <option value="" class="text-gray-500 dark:text-gray-400 dark:bg-gray-700">{{ field.placeholder || 'Выберите...' }}</option>
                        <option
                            v-for="option in field.options"
                            :key="option.value"
                            :value="option.value"
                            class="text-gray-900 dark:text-gray-100 dark:bg-gray-700"
                        >
                            {{ option.label }}
                        </option>
                    </select>

                    <!-- Help text -->
                    <p v-if="field.help" class="text-xs text-gray-500 dark:text-gray-400">
                        {{ field.help }}
                    </p>

                    <InputError v-if="errors[field.key]" :message="errors[field.key]" />
                </div>

                <!-- Кнопки -->
                <div class="mt-6 flex items-center justify-end gap-3">
                    <SecondaryButton type="button" @click="handleClose">
                        Отмена
                    </SecondaryButton>

                    <PrimaryButton type="submit" :disabled="processing">
                        <span v-if="processing">Сохранение...</span>
                        <span v-else>{{ isEdit ? 'Сохранить' : 'Создать' }}</span>
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
