<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;

class UpdateExistingUsersRolesSeeder extends Seeder
{
    /**
     * Обновить роли существующих пользователей
     */
    public function run(): void
    {
        // Устанавливаем роль "employee" всем пользователям, у которых еще нет роли
        User::whereNull('role')->update([
            'role' => UserRole::EMPLOYEE->value
        ]);

        $this->command->info('Роли существующих пользователей обновлены на "employee"');

        // Показываем статистику
        $adminCount = User::where('role', UserRole::ADMIN->value)->count();
        $employeeCount = User::where('role', UserRole::EMPLOYEE->value)->count();
        $ahoCount = User::where('role', UserRole::AHO_SPECIALIST->value)->count();

        $this->command->info("Администраторов: {$adminCount}");
        $this->command->info("Сотрудников: {$employeeCount}");
        $this->command->info("АХО специалистов: {$ahoCount}");
    }
}
