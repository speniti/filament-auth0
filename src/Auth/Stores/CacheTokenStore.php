<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Auth\Stores;

use function data_get;

use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Facades\Cache;
use Peniti\FilamentAuth0\Actions\RefreshAccessToken;
use Peniti\FilamentAuth0\Contracts\Auth0TokenStore;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @phpstan-import-type TokenExchangeResponse from Auth0TokenStore
 * @phpstan-import-type StoredTokenExchangeResponse from Auth0TokenStore
 */
readonly class CacheTokenStore extends TokenStore
{
    public function __construct(
        RefreshAccessToken $refresher,
        #[Config('filament-auth0.tokens.refresh_buffer')] string $refreshBuffer,
        #[Config('filament-auth0.tokens.prefix')] private string $prefix,
        #[Config('filament-auth0.tokens.stores.cache.store')] private ?string $store,
    ) {
        parent::__construct($refresher, $refreshBuffer);
    }

    public function forget(int|string|null $identifier = null): void
    {
        Cache::store($this->store)->forget($this->getCacheKey($identifier));
    }

    /** Get the cache key for the given identifier. */
    protected function getCacheKey(int|string|null $identifier = null): string
    {
        return $this->prefix.($identifier ?? $this->getDefaultIdentifier());
    }

    /**
     * @template K of key-of<StoredTokenExchangeResponse>
     *
     * @param  K  $key
     * @return StoredTokenExchangeResponse[K]|null
     *
     * @throws InvalidArgumentException
     */
    protected function getTokenData(int|string|null $identifier, string $key)
    {
        /** @var StoredTokenExchangeResponse[K]|null */
        return data_get($this->getTokens($identifier), $key);
    }

    /**
     * @return StoredTokenExchangeResponse|array<empty>
     *
     * @throws InvalidArgumentException
     */
    protected function getTokens(int|string|null $identifier = null): array
    {
        /** @var StoredTokenExchangeResponse|array<empty> */
        return Cache::store($this->store)->get($this->getCacheKey($identifier), []);
    }

    /** @param  StoredTokenExchangeResponse  $data */
    protected function setTokenData(int|string|null $identifier, array $data): void
    {
        Cache::store($this->store)->put(
            key: $this->getCacheKey($identifier),
            value: $data,
            ttl: $data['expires_in'],
        );
    }
}
