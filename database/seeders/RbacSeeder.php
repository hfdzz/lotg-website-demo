<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach (Permission::definitions() as $definition) {
            Permission::updateOrCreate(
                ['code' => $definition['code']],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                ]
            );
        }

        $superAdminRole = Role::updateOrCreate(
            ['code' => Role::SUPER_ADMIN],
            [
                'name' => 'Super Admin',
                'description' => 'Full administrative access to the LotG system.',
            ]
        );

        $superAdminRole->permissions()->sync(Permission::query()->pluck('id')->all());
    }
}
