<?php

namespace Litepie\Roles\Commands;

use Illuminate\Console\Command;
use Litepie\Roles\Contracts\Role as RoleContract;

class CreateRole extends Command
{
    protected $signature = 'roles:create
        {name : The name of the role}
        {guard? : The name of the guard}';

    protected $description = 'Create a role';

    public function handle()
    {
        $roleClass = app(RoleContract::class);

        $name = $this->argument('name');
        $guardName = $this->argument('guard');

        $role = $roleClass::findOrCreate($name, $guardName);

        $this->info("Role `{$role->name}` created for guard `{$role->guard_name}`.");
    }
}