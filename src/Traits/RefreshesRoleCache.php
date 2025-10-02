<?php

namespace Litepie\Roles\Traits;

trait RefreshesRoleCache
{
    public static function bootRefreshesRoleCache()
    {
        static::saved(function () {
            app(\Litepie\Roles\RoleRegistrar::class)->forgetCachedRoles();
        });

        static::deleted(function () {
            app(\Litepie\Roles\RoleRegistrar::class)->forgetCachedRoles();
        });
    }
}