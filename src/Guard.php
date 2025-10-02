<?php

namespace Litepie\Roles;

class Guard
{
    /**
     * Return the default guard name defined in auth config.
     */
    public static function getDefaultName($class): string
    {
        $guard = config('auth.defaults.guard');

        return config("auth.guards.{$guard}.provider");
    }
}