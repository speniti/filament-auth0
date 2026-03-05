<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Auth;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Manager;
use Peniti\FilamentAuth0\Contracts\Auth0TokenStore;

/**
 * @mixin Auth0TokenStore
 *
 * @method string|null getAccessToken(int|string|null $identifier = null)
 * @method void save(array<string, mixed> $tokens, int|string|null $identifier = null)
 * @method bool needsRefresh(int|string|null $identifier = null)
 * @method void refresh(int|string|null $identifier = null)
 * @method void forget(int|string|null $identifier = null)
 * @method int|string|null getDefaultIdentifier()
 */
class Auth0TokenManager extends Manager
{
    /** @throws BindingResolutionException */
    public function createCacheDriver(): Stores\CacheTokenStore
    {
        return $this->container->make(Stores\CacheTokenStore::class);
    }

    /** @throws BindingResolutionException */
    public function createDatabaseDriver(): Stores\DatabaseTokenStore
    {
        return $this->container->make(Stores\DatabaseTokenStore::class);
    }

    public function getDefaultDriver(): string
    {
        /** @var string */
        return $this->config->get('filament-auth0.tokens.driver', 'cache');
    }
}
