<script setup>
import { router } from '@inertiajs/vue3';
import Dropdown from '@/Components/UI/Navigation/Dropdown.vue';
import DropdownLink from '@/Components/UI/Navigation/DropdownLink.vue';

defineProps({
    sidebarOpen: Boolean,
});

// const switchToTeam = (team) => {
//     router.put(route('current-team.update'), {
//         team_id: team.id,
//     }, {
//         preserveState: false,
//     });
// };

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="border-t border-gray-700 p-4">
        <Dropdown align="right" :width="sidebarOpen ? '64' : '48'">
            <template #trigger>
                <button class="flex items-center w-full px-3 py-2 text-left text-gray-300 hover:bg-gray-700 rounded-lg transition-colors">
                    <img v-if="$page.props.jetstream.managesProfilePhotos" class="w-8 h-8 rounded-full object-cover flex-shrink-0" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                    <div v-else class="w-8 h-8 rounded-full bg-green-600 flex items-center justify-center flex-shrink-0">
                        <span class="text-white font-medium text-sm">{{ $page.props.auth.user.name.charAt(0) }}</span>
                    </div>
                    <div v-show="sidebarOpen" class="ml-3 flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ $page.props.auth.user.name }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ $page.props.auth.user.email }}</p>
                    </div>
                    <svg v-show="sidebarOpen" class="w-5 h-5 flex-shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </template>

            <template #content>
                <!-- Account Management -->
                <div class="block px-4 py-2 text-xs text-gray-400">
                    Управление аккаунтом
                </div>

                <DropdownLink :href="route('profile.show')">
                    Профиль
                </DropdownLink>

                <DropdownLink v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')">
                    API Токены
                </DropdownLink>

                <!-- Team Management (закомментировано - не используется) -->
<!--                <template v-if="$page.props.jetstream.hasTeamFeatures">-->
<!--                    <div class="border-t border-gray-700" />-->

<!--                    <div class="block px-4 py-2 text-xs text-gray-400">-->
<!--                        Управление командой-->
<!--                    </div>-->

<!--                    <DropdownLink :href="route('teams.show', $page.props.auth.user.current_team)">-->
<!--                        Настройки команды-->
<!--                    </DropdownLink>-->

<!--                    <DropdownLink v-if="$page.props.jetstream.canCreateTeams" :href="route('teams.create')">-->
<!--                        Создать новую команду-->
<!--                    </DropdownLink>-->

<!--                    &lt;!&ndash; Team Switcher &ndash;&gt;-->
<!--                    <template v-if="$page.props.auth.user.all_teams.length > 1">-->
<!--                        <div class="border-t border-gray-700" />-->

<!--                        <div class="block px-4 py-2 text-xs text-gray-400">-->
<!--                            Переключить команду-->
<!--                        </div>-->

<!--                        <template v-for="team in $page.props.auth.user.all_teams" :key="team.id">-->
<!--                            <form @submit.prevent="switchToTeam(team)">-->
<!--                                <DropdownLink as="button">-->
<!--                                    <div class="flex items-center">-->
<!--                                        <svg v-if="team.id == $page.props.auth.user.current_team_id" class="me-2 size-5 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">-->
<!--                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />-->
<!--                                        </svg>-->

<!--                                        <div>{{ team.name }}</div>-->
<!--                                    </div>-->
<!--                                </DropdownLink>-->
<!--                            </form>-->
<!--                        </template>-->
<!--                    </template>-->
<!--                </template>-->

                <div class="border-t border-gray-700" />

                <!-- Authentication -->
                <form @submit.prevent="logout">
                    <DropdownLink as="button">
                        Выйти
                    </DropdownLink>
                </form>
            </template>
        </Dropdown>
    </div>
</template>
