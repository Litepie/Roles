<?php

namespace Litepie\Roles\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Litepie\Roles\Exceptions\UnauthorizedException;

class RoleMiddleware
{
    public function handle($request, Closure $next, $role, $guard = null)
    {
        $authGuard = Auth::guard($guard);

        $user = $authGuard->user();

        if (! $user) {
            throw UnauthorizedException::notLoggedIn();
        }

        if (! method_exists($user, 'hasAnyRole')) {
            throw UnauthorizedException::missingTraitHasRoles($user);
        }

        $roles = explode('|', self::parseRolesToString($role));

        if (! $user->hasAnyRole($roles)) {
            throw UnauthorizedException::forRoles($roles);
        }

        return $next($request);
    }

    /**
     * Parse role parameter into string format.
     */
    public static function parseRolesToString($roles): string
    {
        if (is_array($roles)) {
            $roles = implode('|', $roles);
        }

        if ($roles instanceof \BackedEnum) {
            $roles = $roles->value;
        }

        return $roles;
    }
}