<?php

declare(strict_types=1);

namespace Litepie\Roles\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Litepie\Roles\Contracts\Role;

class RoleAttached
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param  array|int[]|string[]|Role|Role[]|Collection  $rolesOrIds
     */
    public function __construct(public Model $model, public mixed $rolesOrIds) {}
}