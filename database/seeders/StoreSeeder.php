<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = [
            ['name' => 'Магазин 1'],
            ['name' => 'Магазин 2'],
            ['name' => 'Магазин 3'],
            ['name' => 'Магазин 5'],
            ['name' => 'Магазин 6'],
        ];

        foreach ($stores as $store) {
            Store::create($store);
        }
    }
}
