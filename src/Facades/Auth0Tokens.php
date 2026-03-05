<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Facades;

use Illuminate\Support\Facades\Facade;
use Peniti\FilamentAuth0\Auth\Auth0TokenManager;
use Peniti\FilamentAuth0\Contracts\Auth0TokenStore;

/**
 * @see Auth0TokenManager
 *
 * @method static Auth0TokenStore store(string|null $name = null)
 * @method static string getDefaultDriver()
 * @method static void setDefaultDriver(string $name)
 * @method static self extend(string $driver, \Closure $callback)
 * @method static void purge(string|null $name = null)
 * @method static string|null getAccessToken(int|string|null $identifier = null)
 * @method static void save(array<string, mixed> $tokens, int|string|null $identifier = null)
 * @method static bool needsRefresh(int|string|null $identifier = null)
 * @method static void refresh(int|string|null $identifier = null)
 * @method static void forget(int|string|null $identifier = null)
 * @method static int|string|null getDefaultIdentifier()
 */
class Auth0Tokens extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Auth0TokenManager::class;
    }
}
