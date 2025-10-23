<?php

namespace Litepie\Roles;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\Compilers\BladeCompiler;
use Litepie\Roles\Contracts\Role;
use Litepie\Roles\Models\Role as RoleModel;
use Litepie\Roles\RoleRegistrar;

class RolesServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/roles.php', 'roles');

        $this->registerModelBindings();
        $this->registerBladeExtensions();
    }

    public function boot()
    {
        $this->registerPublishing();
        $this->registerMacroHelpers();

        $this->app->singleton(RoleRegistrar::class, function ($app) {
            return new RoleRegistrar($app->make('cache'));
        });
    }

    protected function registerModelBindings()
    {
        $config = $this->app->config['roles.models'];

        if (! $config) {
            return;
        }

        $this->app->bind(Role::class, $config['role']);
    }

    protected function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/roles.php' => config_path('roles.php'),
            ], 'roles-config');

            $this->publishes([
                __DIR__.'/../database/migrations/create_roles_tables.php.stub' => $this->getMigrationFileName('create_roles_tables.php'),
            ], 'roles-migrations');

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'roles-migrations');

            $this->publishes([
                __DIR__.'/../database/seeders/' => database_path('seeders'),
            ], 'roles-seeders');

            $this->publishes([
                __DIR__.'/../database/factories/' => database_path('factories'),
            ], 'roles-factories');
        }
    }



    protected function registerBladeExtensions()
    {
        $this->app->afterResolving('blade.compiler', function (BladeCompiler $bladeCompiler) {
            $bladeMethodWrapper = function ($method, ...$args) {
                return "<?php if(auth()->check() && auth()->user()->{$method}('" . implode("', '", $args) . "')): ?>";
            };

            // Role checks
            $bladeCompiler->if('role', fn () => $bladeMethodWrapper('hasRole', ...func_get_args()));
            $bladeCompiler->if('hasrole', fn () => $bladeMethodWrapper('hasRole', ...func_get_args()));
            $bladeCompiler->if('hasanyrole', fn () => $bladeMethodWrapper('hasAnyRole', ...func_get_args()));
            $bladeCompiler->if('hasallroles', fn () => $bladeMethodWrapper('hasAllRoles', ...func_get_args()));
            $bladeCompiler->if('hasexactroles', fn () => $bladeMethodWrapper('hasExactRoles', ...func_get_args()));
            $bladeCompiler->directive('endunlessrole', fn () => '<?php endif; ?>');
        });
    }

    protected function registerMacroHelpers()
    {
        if (! method_exists(\Illuminate\Routing\Route::class, 'macro')) {
            return;
        }

        \Illuminate\Routing\Route::macro('role', function ($roles = []) {
            if (is_string($roles)) {
                $roles = explode('|', $roles);
            }

            $this->middleware('role:'.implode('|', $roles));

            return $this;
        });
    }

    /**
     * Returns existing migration file if found, else uses the current timestamp.
     */
    protected function getMigrationFileName($migrationFileName): string
    {
        $timestamp = date('Y_m_d_His');

        $filesystem = $this->app->make('files');

        return $this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.$timestamp.'_'.$migrationFileName;
    }
}