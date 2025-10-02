<?php

namespace Litepie\Roles\Tests\TestModels;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Litepie\Roles\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;

    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'users';
}