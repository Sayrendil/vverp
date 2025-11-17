<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionSection from '@/Components/ActionSection.vue';
import DangerButton from '@/Components/UI/Buttons/DangerButton.vue';
import DialogModal from '@/Components/UI/Modals/DialogModal.vue';
import InputError from '@/Components/UI/Forms/InputError.vue';
import SecondaryButton from '@/Components/UI/Buttons/SecondaryButton.vue';
import TextInput from '@/Components/UI/Forms/TextInput.vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    setTimeout(() => passwordInput.value.focus(), 250);
};

const deleteUser = () => {
    form.delete(route('current-user.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.reset();
};
</script>

<template>
    <ActionSection>
        <template #title>
            Удалить аккаунт
        </template>

        <template #description>
            Навсегда удалить ваш аккаунт.
        </template>

        <template #content>
            <div class="max-w-xl text-sm text-gray-400">
                После удаления вашего аккаунта все его ресурсы и данные будут навсегда удалены. Перед удалением аккаунта, пожалуйста, загрузите любые данные или информацию, которую вы хотите сохранить.
            </div>

            <div class="mt-5">
                <DangerButton @click="confirmUserDeletion">
                    Удалить аккаунт
                </DangerButton>
            </div>

            <!-- Delete Account Confirmation Modal -->
            <DialogModal :show="confirmingUserDeletion" @close="closeModal">
                <template #title>
                    Удалить аккаунт
                </template>

                <template #content>
                    Вы уверены, что хотите удалить свой аккаунт? После удаления аккаунта все его ресурсы и данные будут навсегда удалены. Пожалуйста, введите ваш пароль, чтобы подтвердить, что вы хотите навсегда удалить свой аккаунт.

                    <div class="mt-4">
                        <TextInput
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            class="mt-1 block w-3/4"
                            placeholder="Пароль"
                            autocomplete="current-password"
                            @keyup.enter="deleteUser"
                        />

                        <InputError :message="form.errors.password" class="mt-2" />
                    </div>
                </template>

                <template #footer>
                    <SecondaryButton @click="closeModal">
                        Отмена
                    </SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        Удалить аккаунт
                    </DangerButton>
                </template>
            </DialogModal>
        </template>
    </ActionSection>
</template>
