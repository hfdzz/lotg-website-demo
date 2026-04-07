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
            ['name' => '2024/25 [SEEDER]'],
            [
                'code' => '2024-25',
                'year_start' => 2024,
                'year_end' => 2025,
                'status' => 'published',
                'is_active' => false,
            ]
        );

        Edition::updateOrCreate(
            ['name' => '2025/26 [SEEDER]'],
            [
                'code' => '2025-26',
                'year_start' => 2025,
                'year_end' => 2026,
                'status' => 'published',
                'is_active' => true,
            ]
        );
    }
}
