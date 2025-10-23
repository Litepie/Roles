<?php

namespace Litepie\Roles\Database\Seeders;

use Illuminate\Database\Seeder;
use Litepie\Roles\Models\Role;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create essential user roles
        $roles = [
            [
                'name' => 'user',
                'guard_name' => 'web',
                'description' => 'Regular user with basic access',
            ],
            [
                'name' => 'superadmin',
                'guard_name' => 'web',
                'description' => 'Super Administrator with full system access',
            ],
            [
                'name' => 'client',
                'guard_name' => 'web',
                'description' => 'Client with restricted access',
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name'], 'guard_name' => $role['guard_name']],
                $role
            );
        }

        if (method_exists($this, 'command') && $this->command) {
            $this->command->info('Essential user roles created successfully.');
            $this->command->info('Created roles: ' . implode(', ', array_column($roles, 'name')));
        }
    }
}