<?php

namespace Litepie\Roles\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int|string $id
 * @property string $name
 * @property string|null $guard_name
 *
 * @mixin \Litepie\Roles\Models\Role
 *
 * @phpstan-require-extends \Litepie\Roles\Models\Role
 */
interface Role
{
    /**
     * A role belongs to some users of the model associated with its guard.
     */
    public function users(): BelongsToMany;

    /**
     * Find a role by its name and guard name.
     *
     *
     * @throws \Litepie\Roles\Exceptions\RoleDoesNotExist
     */
    public static function findByName(string $name, ?string $guardName): self;

    /**
     * Find a role by its id and guard name.
     *
     *
     * @throws \Litepie\Roles\Exceptions\RoleDoesNotExist
     */
    public static function findById(int|string $id, ?string $guardName): self;

    /**
     * Find or create a role by its name and guard name.
     */
    public static function findOrCreate(string $name, ?string $guardName): self;
}