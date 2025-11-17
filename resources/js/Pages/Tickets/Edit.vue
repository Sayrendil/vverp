<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/UI/Forms/InputError.vue';
import InputLabel from '@/Components/UI/Forms/InputLabel.vue';
import PrimaryButton from '@/Components/UI/Buttons/PrimaryButton.vue';
import SecondaryButton from '@/Components/UI/Buttons/SecondaryButton.vue';
import TextInput from '@/Components/UI/Forms/TextInput.vue';

const props = defineProps({
    ticket: Object,
    statuses: Array,
    problems: Array,
    stores: Array,
    cashes: Array,
    categories: Array,
});

const form = useForm({
    _method: 'PUT',
    title: props.ticket.title,
    description: props.ticket.description || '',
    status_id: props.ticket.status_id,
    problem_id: props.ticket.problem_id,
    store_id: props.ticket.store_id || '',
    cash_id: props.ticket.cash_id || '',
    executor_id: props.ticket.executor_id || '',
    ticket_category_id: props.ticket.ticket_category_id || '',
});

const updateTicket = () => {
    form.put(route('tickets.update', props.ticket.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout title="Редактировать тикет">
        <template #header>
            <h2 class="font-semibold text-xl text-white leading-tight">
                Редактировать тикет: {{ ticket.title }}
            </h2>
        </template>

        <div class="py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <FormSection @submitted="updateTicket">
                    <template #title>
                        Информация о тикете
                    </template>

                    <template #description>
                        Обновите информацию о тикете. Поля отмеченные звездочкой (*) обязательны для заполнения.
                    </template>

                    <template #form>
                        <!-- Название -->
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel for="title" value="Название *" />
                            <TextInput
                                id="title"
                                v-model="form.title"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.title" class="mt-2" />
                        </div>

                        <!-- Описание -->
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel for="description" value="Описание" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                class="mt-1 block w-full bg-gray-700/50 border-gray-600 text-white placeholder-gray-400 focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm"
                                rows="5"
                            ></textarea>
                            <InputError :message="form.errors.description" class="mt-2" />
                        </div>

                        <!-- Статус -->
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel for="status_id" value="Статус *" />
                            <select
                                id="status_id"
                                v-model="form.status_id"
                                class="mt-1 block w-full bg-gray-700/50 border-gray-600 text-white focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm"
                                required
                            >
                                <option value="">Выберите статус</option>
                                <option v-for="status in statuses" :key="status.id" :value="status.id">
                                    {{ status.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.status_id" class="mt-2" />
                        </div>

                        <!-- Категория -->
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel for="ticket_category_id" value="Категория *" />
                            <select
                                id="ticket_category_id"
                                v-model="form.ticket_category_id"
                                class="mt-1 block w-full bg-gray-700/50 border-gray-600 text-white focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm"
                                required
                            >
                                <option value="">Выберите категорию</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.ticket_category_id" class="mt-2" />
                        </div>

                        <!-- Тип проблемы -->
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel for="problem_id" value="Тип проблемы *" />
                            <select
                                id="problem_id"
                                v-model="form.problem_id"
                                class="mt-1 block w-full bg-gray-700/50 border-gray-600 text-white focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm"
                                required
                            >
                                <option value="">Выберите тип проблемы</option>
                                <option v-for="problem in problems" :key="problem.id" :value="problem.id">
                                    {{ problem.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.problem_id" class="mt-2" />
                        </div>

                        <!-- Магазин -->
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel for="store_id" value="Магазин" />
                            <select
                                id="store_id"
                                v-model="form.store_id"
                                class="mt-1 block w-full bg-gray-700/50 border-gray-600 text-white focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm"
                            >
                                <option value="">Не выбрано</option>
                                <option v-for="store in stores" :key="store.id" :value="store.id">
                                    {{ store.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.store_id" class="mt-2" />
                        </div>

                        <!-- Касса -->
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel for="cash_id" value="Касса" />
                            <select
                                id="cash_id"
                                v-model="form.cash_id"
                                class="mt-1 block w-full bg-gray-700/50 border-gray-600 text-white focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm"
                            >
                                <option value="">Не выбрано</option>
                                <option v-for="cash in cashes" :key="cash.id" :value="cash.id">
                                    {{ cash.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.cash_id" class="mt-2" />
                        </div>
                    </template>

                    <template #actions>
                        <SecondaryButton
                            type="button"
                            @click="$inertia.visit(route('tickets.show', ticket.id))"
                            class="mr-3"
                        >
                            Отмена
                        </SecondaryButton>

                        <PrimaryButton
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            Сохранить изменения
                        </PrimaryButton>
                    </template>
                </FormSection>
            </div>
        </div>
    </AppLayout>
</template>
