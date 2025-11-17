<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\UserRole;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Заполняем справочники
        $this->call([
            ProblemSeeder::class,
            CashSeeder::class,
            StatusSeeder::class,
            StoreSeeder::class,
            TicketCategorySeeder::class,
        ]);
    }
}
