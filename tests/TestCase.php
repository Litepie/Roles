<?php

namespace Litepie\Roles\Tests;

use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\TestCase as Orchestra;
use Litepie\Roles\RolesServiceProvider;
use Litepie\Roles\Models\Role;
use Litepie\Roles\Tests\TestModels\User;

abstract class TestCase extends Orchestra
{
    protected $testUser;
    protected $testRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
        
        $this->testUser = User::create(['email' => 'test@example.com']);
        $this->testRole = Role::create(['name' => 'testRole']);
    }

    protected function getPackageProviders($app)
    {
        return [
            RolesServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('auth.providers.users.model', User::class);
    }

    protected function setUpDatabase()
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        
        include_once __DIR__.'/../database/migrations/create_roles_tables.php.stub';
        
        (new \CreateRolesTables())->up();
    }
}