<?php

namespace Database\Seeders;

use App\Models\TicketCategory;
use Illuminate\Database\Seeder;

class TicketCategorySeeder extends Seeder
{
    public function run()
    {
        TicketCategory::create([
            'name' => 'Магазин',
        ]);
        TicketCategory::create([
            'name' => 'Офис',
        ]);
        TicketCategory::create([
            'name' => 'Склад',
        ]);
        TicketCategory::create([
            'name' => 'АХО',
        ]);
    }
}
