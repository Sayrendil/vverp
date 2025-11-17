<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import PrimaryButton from '@/Components/UI/Buttons/PrimaryButton.vue';

const props = defineProps({
    status: String,
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <Head title="Подтверждение Email" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div class="mb-8 text-center">
            <h2 class="text-3xl font-bold text-white mb-2">Подтвердите Email</h2>
            <p class="text-gray-400">
                Перед продолжением, пожалуйста, подтвердите свой email адрес, перейдя по ссылке, которую мы отправили вам. Если вы не получили письмо, мы с радостью отправим вам другое.
            </p>
        </div>

        <div v-if="verificationLinkSent" class="mb-4 font-medium text-sm text-green-400 bg-green-900/30 border border-green-500/30 rounded-lg p-3">
            Новая ссылка для подтверждения была отправлена на email адрес, указанный в настройках вашего профиля.
        </div>

        <form @submit.prevent="submit">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <PrimaryButton class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 focus:ring-green-500" :class="{ 'opacity-50': form.processing }" :disabled="form.processing">
                    Отправить письмо повторно
                </PrimaryButton>

                <div class="flex gap-4">
                    <Link
                        :href="route('profile.show')"
                        class="text-sm text-green-400 hover:text-green-300 underline transition-colors"
                    >
                        Редактировать профиль
                    </Link>

                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="text-sm text-green-400 hover:text-green-300 underline transition-colors"
                    >
                        Выйти
                    </Link>
                </div>
            </div>
        </form>
    </AuthenticationCard>
</template>
