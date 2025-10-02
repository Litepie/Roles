<?php

namespace Litepie\Roles\Commands;

use Illuminate\Console\Command;
use Litepie\Roles\Contracts\Role as RoleContract;

class ShowRoles extends Command
{
    protected $signature = 'roles:show {role? : Show specific role details}';

    protected $description = 'Show roles and their details';

    public function handle()
    {
        $roleClass = app(RoleContract::class);
        $roleName = $this->argument('role');

        if ($roleName) {
            $role = $roleClass::findByName($roleName);
            $this->showRoleDetails($role);
        } else {
            $this->listAllRoles();
        }
    }

    protected function showRoleDetails($role)
    {
        $this->info("Role Details:");
        $this->table(
            ['Property', 'Value'],
            [
                ['ID', $role->id],
                ['Name', $role->name],
                ['Guard', $role->guard_name],
                ['Created', $role->created_at],
                ['Updated', $role->updated_at],
            ]
        );

        $users = $role->users;
        if ($users->count() > 0) {
            $this->info("\nUsers with this role ({$users->count()}):");
            $this->table(
                ['ID', 'Name/Email'],
                $users->map(fn($user) => [$user->id, $user->name ?? $user->email])
            );
        } else {
            $this->info("\nNo users have this role.");
        }
    }

    protected function listAllRoles()
    {
        $roleClass = app(RoleContract::class);
        $roles = $roleClass::all();

        if ($roles->isEmpty()) {
            $this->info('No roles found.');
            return;
        }

        $this->table(
            ['ID', 'Name', 'Guard', 'Users Count', 'Created'],
            $roles->map(function ($role) {
                return [
                    $role->id,
                    $role->name,
                    $role->guard_name,
                    $role->users->count(),
                    $role->created_at->format('Y-m-d H:i:s'),
                ];
            })
        );
    }
}