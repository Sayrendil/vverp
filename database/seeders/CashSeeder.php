<?php

namespace Database\Seeders;

use App\Models\Cash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CashSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cashes = [
            ['name' => 'Касса 1'],
            ['name' => 'Касса 2'],
        ];

        foreach ($cashes as $cash) {
            Cash::create($cash);
        }
    }
}
