<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import InputError from '@/Components/UI/Forms/InputError.vue';
import InputLabel from '@/Components/UI/Forms/InputLabel.vue';
import PrimaryButton from '@/Components/UI/Buttons/PrimaryButton.vue';
import TextInput from '@/Components/UI/Forms/TextInput.vue';

const form = useForm({
    password: '',
});

const passwordInput = ref(null);

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset();
            passwordInput.value.focus();
        },
    });
};
</script>

<template>
    <Head title="Подтверждение пароля" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div class="mb-8 text-center">
            <h2 class="text-3xl font-bold text-white mb-2">Защищенная зона</h2>
            <p class="text-gray-400">
                Это защищенная область приложения. Пожалуйста, подтвердите свой пароль перед продолжением.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div>
                <InputLabel for="password" value="Пароль" class="text-gray-300" />
                <TextInput
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full bg-gray-700/50 border-gray-600 text-white placeholder-gray-400 focus:border-green-500 focus:ring-green-500"
                    required
                    autocomplete="current-password"
                    autofocus
                    placeholder="••••••••"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <PrimaryButton class="w-full justify-center py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 focus:ring-green-500" :class="{ 'opacity-50': form.processing }" :disabled="form.processing">
                Подтвердить
            </PrimaryButton>
        </form>
    </AuthenticationCard>
</template>
