<?php

if (! function_exists('getModelForGuard')) {
    function getModelForGuard(string $guard): string
    {
        return collect(config('auth.guards'))
            ->map(function ($guard) {
                if (! isset($guard['provider'])) {
                    return null;
                }

                return config("auth.providers.{$guard['provider']}.model");
            })->get($guard, config('auth.providers.users.model'));
    }
}