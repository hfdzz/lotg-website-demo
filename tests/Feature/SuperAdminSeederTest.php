<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_seeder_reads_values_from_config(): void
    {
        config()->set('lotg.seeders.super_admin.name', 'Production Super Admin');
        config()->set('lotg.seeders.super_admin.email', 'superadmin@example.com');
        config()->set('lotg.seeders.super_admin.password', 'strong-password-123');

        $this->seed(RbacSeeder::class);
        $this->seed(SuperAdminSeeder::class);

        $user = User::query()->where('email', 'superadmin@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Production Super Admin', $user->name);
        $this->assertTrue(Hash::check('strong-password-123', $user->password));
        $this->assertTrue($user->roles->contains(fn (Role $role) => $role->code === Role::SUPER_ADMIN));
    }
}
