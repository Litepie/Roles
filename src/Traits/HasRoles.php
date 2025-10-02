<?php

namespace Litepie\Roles\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Litepie\Roles\Contracts\Role;
use Litepie\Roles\Events\RoleAttached;
use Litepie\Roles\Events\RoleDetached;
use Litepie\Roles\RoleRegistrar;

trait HasRoles
{
    private ?string $roleClass = null;

    public static function bootHasRoles()
    {
        static::deleting(function ($model) {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            $model->roles()->detach();
        });
    }

    public function getRoleClass(): string
    {
        if (! $this->roleClass) {
            $this->roleClass = app(RoleRegistrar::class)->getRoleClass();
        }

        return $this->roleClass;
    }

    /**
     * A model may have multiple roles.
     */
    public function roles(): BelongsToMany
    {
        return $this->morphToMany(
            config('roles.models.role'),
            'model',
            config('roles.table_names.model_has_roles'),
            config('roles.column_names.model_morph_key'),
            app(RoleRegistrar::class)->pivotRole
        );
    }

    /**
     * Scope the model query to certain roles only.
     *
     * @param  string|int|array|Role|Collection|\BackedEnum  $roles
     * @param  string  $guard
     * @param  bool  $without
     */
    public function scopeRole(Builder $query, $roles, $guard = null, $without = false): Builder
    {
        if ($roles instanceof Collection) {
            $roles = $roles->all();
        }

        $roles = array_map(function ($role) use ($guard) {
            if ($role instanceof Role) {
                return $role;
            }

            if ($role instanceof \BackedEnum) {
                $role = $role->value;
            }

            $method = is_int($role) ? 'findById' : 'findByName';

            return $this->getRoleClass()::{$method}($role, $guard ?: $this->getDefaultGuardName());
        }, Arr::wrap($roles));

        $key = (new ($this->getRoleClass())())->getKeyName();
        $rolesTable = config('roles.table_names.roles');
        $modelHasRolesTable = config('roles.table_names.model_has_roles');

        return $query->whereHas('roles', function (Builder $subQuery) use ($roles, $key, $without) {
            $roleKeys = array_map(fn ($role) => $role->getKey(), $roles);
            
            if ($without) {
                $subQuery->whereNotIn($key, $roleKeys);
            } else {
                $subQuery->whereIn($key, $roleKeys);
            }
        });
    }

    /**
     * Scope the model query to exclude certain roles.
     *
     * @param  string|int|array|Role|Collection|\BackedEnum  $roles
     */
    public function scopeWithoutRole(Builder $query, $roles, $guard = null): Builder
    {
        return $this->scopeRole($query, $roles, $guard, true);
    }

    protected function getDefaultGuardName(): string
    {
        return config('auth.defaults.guard');
    }

    protected function getGuardNames(): Collection
    {
        return $this->roles->pluck('guard_name')->unique();
    }

    protected function collectRoles($roles)
    {
        if ($roles instanceof Role) {
            $roles = [$roles];
        }

        if ($roles instanceof \BackedEnum) {
            $roles = [$roles];
        }

        $roles = Arr::flatten($roles);

        return array_reduce($roles, function ($array, $role) {
            if (empty($role)) {
                return $array;
            }

            $role = $this->getStoredRole($role);

            if (! in_array($role->getKey(), $array)) {
                $array[] = $role->getKey();
            }

            return $array;
        }, []);
    }

    /**
     * Assign the given role to the model.
     *
     * @param  string|int|array|Role|Collection|\BackedEnum  ...$roles
     * @return $this
     */
    public function assignRole(...$roles)
    {
        $roles = $this->collectRoles($roles);

        $model = $this->getModel();

        if ($model->exists) {
            $this->roles()->sync($roles, false);
        } else {
            $class = \get_class($model);

            $class::saved(
                function ($object) use ($roles, $model) {
                    if ($model->getKey() != $object->getKey()) {
                        return;
                    }
                    $model->roles()->sync($roles, false);
                }
            );
        }

        $model->load('roles');

        $roleModels = $this->getRoleClass()::whereIn('id', $roles)->get();

        event(new RoleAttached($this, $roleModels));

        return $this;
    }

    /**
     * Revoke the given role from the model.
     *
     * @param  string|int|array|Role|Collection|\BackedEnum  ...$roles
     */
    public function removeRole(...$roles): self
    {
        $roles = $this->collectRoles($roles);

        $this->roles()->detach($roles);

        $this->load('roles');

        $roleModels = $this->getRoleClass()::whereIn('id', $roles)->get();

        event(new RoleDetached($this, $roleModels));

        return $this;
    }

    /**
     * Remove all current roles and set the given ones.
     *
     * @param  string|int|array|Role|Collection|\BackedEnum  ...$roles
     */
    public function syncRoles(...$roles): self
    {
        $this->roles()->detach();

        return $this->assignRole($roles);
    }

    /**
     * Determine if the model has (one of) the given role(s).
     *
     * @param  string|int|array|Role|Collection|\BackedEnum  $roles
     */
    public function hasRole($roles, ?string $guard = null): bool
    {
        $this->loadMissing('roles');

        if (is_string($roles) && strpos($roles, '|') !== false) {
            $roles = $this->convertPipeToArray($roles);
        }

        if ($roles instanceof \BackedEnum) {
            $roles = $roles->value;

            return $this->roles
                ->when($guard, fn ($q) => $q->where('guard_name', $guard))
                ->pluck('name')
                ->contains(function ($name) use ($roles) {
                    /** @var string|\BackedEnum $name */
                    if ($name instanceof \BackedEnum) {
                        return $name->value == $roles;
                    }

                    return $name == $roles;
                });
        }

        if (is_int($roles)) {
            $key = (new ($this->getRoleClass())())->getKeyName();

            return $guard
                ? $this->roles->where('guard_name', $guard)->contains($key, $roles)
                : $this->roles->contains($key, $roles);
        }

        if (is_string($roles)) {
            return $guard
                ? $this->roles->where('guard_name', $guard)->contains('name', $roles)
                : $this->roles->contains('name', $roles);
        }

        if ($roles instanceof Role) {
            return $this->roles->contains($roles->getKeyName(), $roles->getKey());
        }

        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($this->hasRole($role, $guard)) {
                    return true;
                }
            }

            return false;
        }

        if ($roles instanceof Collection) {
            return $roles->intersect($guard ? $this->roles->where('guard_name', $guard) : $this->roles)->isNotEmpty();
        }

        throw new \TypeError('Unsupported type for $roles parameter to hasRole().');
    }

    /**
     * Determine if the model has any of the given role(s).
     *
     * Alias to hasRole() but without Guard controls
     *
     * @param  string|int|array|Role|Collection|\BackedEnum  $roles
     */
    public function hasAnyRole(...$roles): bool
    {
        return $this->hasRole($roles);
    }

    /**
     * Determine if the model has all of the given role(s).
     *
     * @param  string|array|Role|Collection|\BackedEnum  $roles
     */
    public function hasAllRoles($roles, ?string $guard = null): bool
    {
        $this->loadMissing('roles');

        if ($roles instanceof \BackedEnum) {
            $roles = $roles->value;
        }

        if (is_string($roles) && strpos($roles, '|') !== false) {
            $roles = $this->convertPipeToArray($roles);
        }

        if (is_string($roles)) {
            return $this->hasRole($roles, $guard);
        }

        if ($roles instanceof Role) {
            return $this->roles->contains($roles->getKeyName(), $roles->getKey());
        }

        $roles = collect()->make($roles)->map(function ($role) {
            if ($role instanceof \BackedEnum) {
                return $role->value;
            }

            return $role instanceof Role ? $role->name : $role;
        });

        $roleNames = $guard
            ? $this->roles->where('guard_name', $guard)->pluck('name')
            : $this->getRoleNames();

        $roleNames = $roleNames->transform(function ($roleName) {
            if ($roleName instanceof \BackedEnum) {
                return $roleName->value;
            }

            return $roleName;
        });

        return $roles->intersect($roleNames) == $roles;
    }

    /**
     * Determine if the model has exactly all of the given role(s).
     *
     * @param  string|array|Role|Collection|\BackedEnum  $roles
     */
    public function hasExactRoles($roles, ?string $guard = null): bool
    {
        $this->loadMissing('roles');

        if (is_string($roles) && strpos($roles, '|') !== false) {
            $roles = $this->convertPipeToArray($roles);
        }

        if (is_string($roles)) {
            $roles = [$roles];
        }

        if ($roles instanceof Role) {
            $roles = [$roles->name];
        }

        $roles = collect()->make($roles)->map(fn ($role) => $role instanceof Role ? $role->name : $role
        );

        return $this->roles->count() == $roles->count() && $this->hasAllRoles($roles, $guard);
    }

    public function getRoleNames(): Collection
    {
        $this->loadMissing('roles');

        return $this->roles->pluck('name');
    }

    protected function getStoredRole($role): Role
    {
        if ($role instanceof \BackedEnum) {
            $role = $role->value;
        }

        if (is_int($role)) {
            return $this->getRoleClass()::findById($role, $this->getDefaultGuardName());
        }

        if (is_string($role)) {
            return $this->getRoleClass()::findByName($role, $this->getDefaultGuardName());
        }

        return $role;
    }

    protected function convertPipeToArray(string $pipeString)
    {
        $pipeString = trim($pipeString);

        if (strlen($pipeString) <= 2) {
            return [str_replace('|', '', $pipeString)];
        }

        $quoteCharacter = substr($pipeString, 0, 1);
        $endCharacter = substr($quoteCharacter, -1, 1);

        if ($quoteCharacter !== $endCharacter) {
            return explode('|', $pipeString);
        }

        if (! in_array($quoteCharacter, ["'", '"'])) {
            return explode('|', $pipeString);
        }

        return explode('|', trim($pipeString, $quoteCharacter));
    }
}