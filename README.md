# Laravel Roles Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/litepie/roles.svg?style=flat-square)](https://packagist.org/packages/litepie/roles)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/litepie/roles/run-tests?label=tests)](https://github.com/litepie/roles/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/litepie/roles/Check%20&%20fix%20styling?label=code%20style)](https://github.com/litepie/roles/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/litepie/roles.svg?style=flat-square)](https://packagist.org/packages/litepie/roles)

A Laravel package for role management system, focusing on role-based access control. This package is extracted and inspired by the role functionality from Spatie's Laravel Permission package, providing a lightweight and focused solution for role management.

## Features

- Create and manage roles
- Assign roles to users
- Check user roles with various methods
- Role-based middleware
- Artisan commands for role management
- Blade directives for role checks
- Event system for role operations
- Support for multiple guards
- Enum support for roles
- Caching for performance

## Installation

You can install the package via composer:

```bash
composer require litepie/roles
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="roles-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="roles-config"
```

This is the contents of the published config file:

```php
<?php

return [
    'models' => [
        'role' => Litepie\Roles\Models\Role::class,
    ],

    'table_names' => [
        'roles' => 'roles',
        'model_has_roles' => 'model_has_roles',
    ],

    'column_names' => [
        'role_pivot_key' => null,
        'model_morph_key' => 'model_id',
    ],

    'cache' => [
        'expiration_time' => \DateInterval::createFromDateString('24 hours'),
        'key' => 'litepie.roles.cache',
        'store' => 'default',
    ],
];
```

## Usage

Add the `HasRoles` trait to your User model:

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Litepie\Roles\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;

    // ...
}
```

### Creating Roles

```php
use Litepie\Roles\Models\Role;

$role = Role::create(['name' => 'admin']);
$role = Role::create(['name' => 'editor']);
```

### Assigning Roles

```php
$user->assignRole('admin');
$user->assignRole(['editor', 'moderator']);
$user->assignRole(Role::find(1));
```

### Checking Roles

```php
$user->hasRole('admin');
$user->hasAnyRole(['admin', 'editor']);
$user->hasAllRoles(['admin', 'editor']);
$user->hasExactRoles(['admin', 'editor']);
```

### Removing Roles

```php
$user->removeRole('admin');
$user->syncRoles(['editor', 'moderator']);
```

### Middleware

Register the middleware in your `app/Http/Kernel.php`:

```php
protected $routeMiddleware = [
    // ...
    'role' => \Litepie\Roles\Middleware\RoleMiddleware::class,
];
```

Use it in routes:

```php
Route::group(['middleware' => ['role:admin']], function () {
    // Routes that require admin role
});
```

### Blade Directives

```blade
@role('admin')
    I am an admin!
@endrole

@hasrole('admin')
    I have admin role!
@endhasrole

@hasanyrole(['admin', 'editor'])
    I have at least one of these roles!
@endhasanyrole
```

### Artisan Commands

```bash
# Create a role
php artisan roles:create admin

# List all roles
php artisan roles:list

# Show role details
php artisan roles:show admin
```

### Using Enums

```php
enum UserRole: string
{
    case ADMIN = 'admin';
    case EDITOR = 'editor';
    case USER = 'user';
}

// Create role with enum
$role = Role::create(['name' => UserRole::ADMIN->value]);

// Check role with enum
$user->hasRole(UserRole::ADMIN);
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Litepie Team](https://github.com/litepie)
- [All Contributors](../../contributors)
- Inspired by [Spatie Laravel Permission](https://github.com/spatie/laravel-permission)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.