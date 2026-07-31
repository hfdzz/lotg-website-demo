<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $email = trim((string) config('lotg.seeders.admin_user.email', 'admin@example.com'));

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => (string) config('lotg.seeders.admin_user.name', 'LotG Admin'),
                'password' => (string) config('lotg.seeders.admin_user.password', 'password'),
            ]
        );
    }
}
