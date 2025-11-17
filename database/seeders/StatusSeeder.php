<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['name' => 'Создана'],
            ['name' => 'В работе'],
            ['name' => 'Подтверждена'],
            ['name' => 'Отложена'],
            ['name' => 'Завершена'],
        ];

        foreach ($statuses as $status) {
            Status::create($status);
        }
    }
}
