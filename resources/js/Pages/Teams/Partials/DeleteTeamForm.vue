<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionSection from '@/Components/ActionSection.vue';
import ConfirmationModal from '@/Components/UI/Modals/ConfirmationModal.vue';
import DangerButton from '@/Components/UI/Buttons/DangerButton.vue';
import SecondaryButton from '@/Components/UI/Buttons/SecondaryButton.vue';

const props = defineProps({
    team: Object,
});

const confirmingTeamDeletion = ref(false);
const form = useForm({});

const confirmTeamDeletion = () => {
    confirmingTeamDeletion.value = true;
};

const deleteTeam = () => {
    form.delete(route('teams.destroy', props.team), {
        errorBag: 'deleteTeam',
    });
};
</script>

<template>
    <ActionSection>
        <template #title>
            Удалить команду
        </template>

        <template #description>
            Навсегда удалить эту команду.
        </template>

        <template #content>
            <div class="max-w-xl text-sm text-gray-400">
                После удаления команды все её ресурсы и данные будут навсегда удалены. Перед удалением этой команды, пожалуйста, загрузите любые данные или информацию, касающуюся этой команды, которые вы хотите сохранить.
            </div>

            <div class="mt-5">
                <DangerButton @click="confirmTeamDeletion">
                    Удалить команду
                </DangerButton>
            </div>

            <!-- Delete Team Confirmation Modal -->
            <ConfirmationModal :show="confirmingTeamDeletion" @close="confirmingTeamDeletion = false">
                <template #title>
                    Удалить команду
                </template>

                <template #content>
                    Вы уверены, что хотите удалить эту команду? После удаления команды все её ресурсы и данные будут навсегда удалены.
                </template>

                <template #footer>
                    <SecondaryButton @click="confirmingTeamDeletion = false">
                        Отмена
                    </SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteTeam"
                    >
                        Удалить команду
                    </DangerButton>
                </template>
            </ConfirmationModal>
        </template>
    </ActionSection>
</template>
