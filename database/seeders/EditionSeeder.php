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
            ['name' => '2024/25'],
            [
                'slug' => 'edition_2024_2025_2024_25',
                'year_start' => 2024,
                'year_end' => 2025,
                'is_active' => false,
            ]
        );

        Edition::updateOrCreate(
            ['name' => '2025/26'],
            [
                'slug' => 'edition_2025_2026_2025_26',
                'year_start' => 2025,
                'year_end' => 2026,
                'is_active' => true,
            ]
        );
    }
}
