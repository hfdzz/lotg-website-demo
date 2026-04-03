<?php

namespace Database\Seeders;

use App\Models\Edition;
use Illuminate\Database\Seeder;

class EditionSeeder extends Seeder
{
    public function run(): void
    {
        Edition::query()->update(['is_active' => false]);

        Edition::updateOrCreate(
            ['name' => '2025/26'],
            [
                'year_start' => 2025,
                'year_end' => 2026,
                'is_active' => true,
            ]
        );
    }
}
