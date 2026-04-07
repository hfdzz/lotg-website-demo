<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SuperAdminSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $name = (string) env('SUPER_ADMIN_NAME', '');
        $email = (string) env('SUPER_ADMIN_EMAIL', '');
        $password = (string) env('SUPER_ADMIN_PASSWORD', '');

        if ($name === '' || $email === '' || $password === '') {
            $this->command?->warn('Skipping SuperAdminSeeder. Set SUPER_ADMIN_NAME, SUPER_ADMIN_EMAIL, and SUPER_ADMIN_PASSWORD to create the super admin user.');

            return;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('SUPER_ADMIN_EMAIL must be a valid email address.');
        }

        if (Str::length($password) < 8) {
            throw new InvalidArgumentException('SUPER_ADMIN_PASSWORD must be at least 8 characters.');
        }

        if (Str::length($password) < 12) {
            $this->command?->warn('SUPER_ADMIN_PASSWORD is less than 12 characters. Consider using a longer password for better security.');
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'email_verified_at' => now(),
            ]
        );

        $this->command?->info('Super admin user seeded.');
    }
}
