<?php

namespace Database\Seeders;

use App\Models\Problem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProblemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $problems = [
            ['name' => '1C'],
            ['name' => 'ТСД'],
            ['name' => 'Этикетки'],
            ['name' => 'Оборудование'],
            ['name' => 'Сеть'],
            ['name' => 'Слаботочка'],
            ['name' => 'Другое'],
            ['name' => 'Доработки'],
        ];

        foreach ($problems as $problem) {
            Problem::create($problem);
        }
    }
}
