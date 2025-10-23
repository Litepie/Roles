<?php

namespace Litepie\Roles\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Litepie\Roles\Models\Role;

class RoleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->unique()->word,
            'guard_name' => 'web',
        ];
    }

    /**
     * Create a superadmin role.
     */
    public function superadmin()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'superadmin',
                'description' => 'Super Administrator with full system access',
            ];
        });
    }

    /**
     * Create a user role.
     */
    public function user()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'user',
                'description' => 'Regular user with basic access',
            ];
        });
    }

    /**
     * Create a client role.
     */
    public function client()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'client',
                'description' => 'Client with restricted access',
            ];
        });
    }

    /**
     * Create a role with a specific guard.
     */
    public function withGuard(string $guard)
    {
        return $this->state(function (array $attributes) use ($guard) {
            return [
                'guard_name' => $guard,
            ];
        });
    }
}