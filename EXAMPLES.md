# Examples

Here are some practical examples of how to use the Laravel Roles package.

## Basic Setup

### 1. Add the trait to your User model

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Litepie\Roles\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;

    // ... your existing code
}
```

### 2. Create roles using Artisan commands

```bash
php artisan roles:create admin
php artisan roles:create editor  
php artisan roles:create user
```

### 3. Or create roles programmatically

```php
use Litepie\Roles\Models\Role;

Role::create(['name' => 'admin']);
Role::create(['name' => 'editor']);
Role::create(['name' => 'user']);
```

## Role Assignment

### Assign single role
```php
$user = User::find(1);
$user->assignRole('admin');
```

### Assign multiple roles
```php
$user->assignRole(['admin', 'editor']);
// or
$user->assignRole('admin', 'editor');
```

### Assign role using Role model
```php
$adminRole = Role::findByName('admin');
$user->assignRole($adminRole);
```

## Role Checking

### Check if user has a specific role
```php
if ($user->hasRole('admin')) {
    // User is an admin
}
```

### Check if user has any of the given roles
```php
if ($user->hasAnyRole(['admin', 'editor'])) {
    // User is either admin or editor
}
```

### Check if user has all given roles
```php
if ($user->hasAllRoles(['admin', 'editor'])) {
    // User has both admin and editor roles
}
```

### Check for exact roles (user has exactly these roles and no others)
```php
if ($user->hasExactRoles(['admin', 'editor'])) {
    // User has exactly admin and editor roles
}
```

## Role Management

### Remove roles
```php
$user->removeRole('editor');
$user->removeRole(['admin', 'editor']);
```

### Sync roles (remove all current roles and assign new ones)
```php
$user->syncRoles(['user']); // User will only have 'user' role
```

### Get user's roles
```php
$roleNames = $user->getRoleNames(); // Collection of role names
$roles = $user->roles; // Collection of Role models
```

## Middleware Usage

### Register the middleware
In `app/Http/Kernel.php`:

```php
protected $routeMiddleware = [
    // ...
    'role' => \Litepie\Roles\Middleware\RoleMiddleware::class,
];
```

### Use in routes
```php
Route::group(['middleware' => ['role:admin']], function () {
    Route::get('/admin/dashboard', 'AdminController@dashboard');
});

// Multiple roles (user needs ANY of these roles)
Route::group(['middleware' => ['role:admin|editor']], function () {
    Route::get('/admin/posts', 'PostController@index');
});
```

### Use with route macros
```php
Route::get('/admin/users', 'UserController@index')->role('admin');
Route::get('/admin/posts', 'PostController@index')->role(['admin', 'editor']);
```

## Blade Directives

### Check for specific role
```blade
@role('admin')
    <p>You are an administrator!</p>
@endrole

@hasrole('admin')
    <p>You have admin privileges!</p>
@endhasrole
```

### Check for any role
```blade
@hasanyrole(['admin', 'editor'])
    <p>You can manage content!</p>
@endhasanyrole
```

### Check for all roles
```blade
@hasallroles(['admin', 'editor'])
    <p>You have both admin and editor roles!</p>
@endhasallroles
```

## Using with Enums (PHP 8.1+)

### Define your enum
```php
enum UserRole: string
{
    case ADMIN = 'admin';
    case EDITOR = 'editor'; 
    case USER = 'user';
}
```

### Create roles with enum
```php
$role = Role::create(['name' => UserRole::ADMIN->value]);
```

### Check roles with enum
```php
if ($user->hasRole(UserRole::ADMIN)) {
    // User is an admin
}

$user->assignRole(UserRole::EDITOR);
```

## Query Scopes

### Get users with specific role
```php
$admins = User::role('admin')->get();
$editors = User::role(['editor', 'admin'])->get();
```

### Get users without specific role
```php
$nonAdmins = User::withoutRole('admin')->get();
```

### Complex queries
```php
// Get all admin users who are active
$activeAdmins = User::role('admin')
    ->where('status', 'active')
    ->get();

// Get users with editor role in a specific guard
$apiEditors = User::role('editor', 'api')->get();
```

## Events

### Listen for role events
```php
use Litepie\Roles\Events\RoleAttached;
use Litepie\Roles\Events\RoleDetached;

// In EventServiceProvider
protected $listen = [
    RoleAttached::class => [
        SendWelcomeEmail::class,
    ],
    RoleDetached::class => [
        LogRoleChange::class,
    ],
];
```

### Create event listener
```php
class SendWelcomeEmail
{
    public function handle(RoleAttached $event)
    {
        $user = $event->model;
        $roles = $event->rolesOrIds;
        
        // Send email logic
    }
}
```

## Guards

### Use with different guards
```php
// Create role for specific guard
$apiAdminRole = Role::create([
    'name' => 'admin',
    'guard_name' => 'api'
]);

// Assign role with guard
$user->assignRole('admin'); // Uses default guard
$user->hasRole('admin', 'api'); // Check role in specific guard
```

## Advanced Usage

### Custom Role Model
```php
// Create your own Role model
class CustomRole extends \Litepie\Roles\Models\Role
{
    // Add custom methods or properties
    public function getDisplayNameAttribute()
    {
        return ucfirst($this->name);
    }
}

// Update config
// config/roles.php
'models' => [
    'role' => App\Models\CustomRole::class,
],
```

### Caching
The package automatically caches roles for performance. You can configure caching in the config file:

```php
'cache' => [
    'expiration_time' => \DateInterval::createFromDateString('24 hours'),
    'key' => 'litepie.roles.cache',
    'store' => 'redis', // or any cache store
],
```