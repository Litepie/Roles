<?php

namespace Litepie\Roles\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UnauthorizedException extends HttpException
{
    private array $requiredRoles = [];

    public static function forRoles(array $roles): static
    {
        $message = 'User does not have any of the necessary access rights.';

        if (config('roles.display_role_in_exception')) {
            $message = 'User does not have the right roles.';

            if (count($roles) === 1) {
                $message = "User does not have the right role. Necessary role: {$roles[0]}.";
            } elseif (count($roles) > 1) {
                $message = 'User does not have any of the necessary roles. Necessary roles are: ' . implode(', ', $roles) . '.';
            }
        }

        $exception = new static(Response::HTTP_FORBIDDEN, $message, null, []);
        $exception->requiredRoles = $roles;

        return $exception;
    }

    public static function notLoggedIn(): static
    {
        return new static(Response::HTTP_UNAUTHORIZED, 'User is not logged in.', null, []);
    }

    public static function missingTraitHasRoles($user): static
    {
        return new static(Response::HTTP_INTERNAL_SERVER_ERROR, 'User model ' . get_class($user) . ' does not have the HasRoles trait.', null, []);
    }

    public function getRequiredRoles(): array
    {
        return $this->requiredRoles;
    }
}