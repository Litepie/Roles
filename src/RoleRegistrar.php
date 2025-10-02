<?php

namespace Litepie\Roles;

use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Litepie\Roles\Contracts\Role;

class RoleRegistrar
{
    protected Repository $cache;

    protected CacheManager $cacheManager;

    protected string $roleClass;

    /** @var Collection|array|null */
    protected $roles;

    public string $pivotRole;

    /** @var \DateInterval|int */
    public $cacheExpirationTime;

    public string $cacheKey;

    private array $cachedRoles = [];

    /**
     * RoleRegistrar constructor.
     */
    public function __construct(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
        $this->initializeCache();
        
        $this->roleClass = config('roles.models.role');
        $this->cacheKey = config('roles.cache.key');
        $this->cacheExpirationTime = config('roles.cache.expiration_time', \DateInterval::createFromDateString('24 hours'));
        $this->pivotRole = config('roles.column_names.role_pivot_key', 'role_id');
    }

    protected function initializeCache(): self
    {
        $cacheDriver = config('roles.cache.store', 'default');

        if ($cacheDriver === 'default') {
            $cacheDriver = config('cache.default');
        }

        $this->cache = $this->cacheManager->store($cacheDriver);

        return $this;
    }

    public function registerRoles(): bool
    {
        $this->forgetCachedRoles();

        $this->roles = $this->getRoles();

        return true;
    }

    public function forgetCachedRoles(): self
    {
        $this->roles = null;
        $this->cache->forget($this->cacheKey);

        return $this;
    }

    protected function getRoles(): Collection
    {
        return $this->cache->remember($this->cacheKey, $this->cacheExpirationTime, function () {
            return $this->roleClass::all();
        });
    }

    public function getRoleClass(): string
    {
        return $this->roleClass;
    }

    public function setRoleClass($roleClass)
    {
        $this->roleClass = $roleClass;
        config()->set('roles.models.role', $roleClass);
        app()->bind(Role::class, $roleClass);

        return $this;
    }

    public function getCacheRepository(): Repository
    {
        return $this->cache;
    }

    public function getCacheStore(): Store
    {
        return $this->cache->getStore();
    }
}