<script setup>
import { nextTick, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import InputError from '@/Components/UI/Forms/InputError.vue';
import InputLabel from '@/Components/UI/Forms/InputLabel.vue';
import PrimaryButton from '@/Components/UI/Buttons/PrimaryButton.vue';
import TextInput from '@/Components/UI/Forms/TextInput.vue';

const recovery = ref(false);

const form = useForm({
    code: '',
    recovery_code: '',
});

const recoveryCodeInput = ref(null);
const codeInput = ref(null);

const toggleRecovery = async () => {
    recovery.value ^= true;

    await nextTick();

    if (recovery.value) {
        recoveryCodeInput.value.focus();
        form.code = '';
    } else {
        codeInput.value.focus();
        form.recovery_code = '';
    }
};

const submit = () => {
    form.post(route('two-factor.login'));
};
</script>

<template>
    <Head title="Двухфакторная аутентификация" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div class="mb-8 text-center">
            <h2 class="text-3xl font-bold text-white mb-2">Двухфакторная аутентификация</h2>
            <p class="text-gray-400">
                <template v-if="! recovery">
                    Пожалуйста, подтвердите доступ к вашему аккаунту, введя код аутентификации из вашего приложения-аутентификатора.
                </template>
                <template v-else>
                    Пожалуйста, подтвердите доступ к вашему аккаунту, введя один из ваших резервных кодов восстановления.
                </template>
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div v-if="! recovery">
                <InputLabel for="code" value="Код" class="text-gray-300" />
                <TextInput
                    id="code"
                    ref="codeInput"
                    v-model="form.code"
                    type="text"
                    inputmode="numeric"
                    class="mt-1 block w-full bg-gray-700/50 border-gray-600 text-white placeholder-gray-400 focus:border-green-500 focus:ring-green-500"
                    autofocus
                    autocomplete="one-time-code"
                    placeholder="123456"
                />
                <InputError class="mt-2" :message="form.errors.code" />
            </div>

            <div v-else>
                <InputLabel for="recovery_code" value="Код восстановления" class="text-gray-300" />
                <TextInput
                    id="recovery_code"
                    ref="recoveryCodeInput"
                    v-model="form.recovery_code"
                    type="text"
                    class="mt-1 block w-full bg-gray-700/50 border-gray-600 text-white placeholder-gray-400 focus:border-green-500 focus:ring-green-500"
                    autocomplete="one-time-code"
                    placeholder="abcd-efgh-ijkl"
                />
                <InputError class="mt-2" :message="form.errors.recovery_code" />
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <button type="button" class="text-sm text-green-400 hover:text-green-300 underline cursor-pointer transition-colors" @click.prevent="toggleRecovery">
                    <template v-if="! recovery">
                        Использовать код восстановления
                    </template>
                    <template v-else>
                        Использовать код аутентификации
                    </template>
                </button>

                <PrimaryButton class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 focus:ring-green-500" :class="{ 'opacity-50': form.processing }" :disabled="form.processing">
                    Войти
                </PrimaryButton>
            </div>
        </form>
    </AuthenticationCard>
</template>
