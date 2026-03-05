<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Jwks;

use ArrayAccess;
use BadMethodCallException;
use Closure;

use function data_get;
use function data_has;

use Firebase\JWT\JWK;
use Firebase\JWT\Key;
use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

use function parse_url;

use Peniti\FilamentAuth0\Exceptions\IdentityProviderConnectionException;
use Peniti\FilamentAuth0\Exceptions\UnableToRetrieveKeysException;

use const PHP_URL_HOST;

use function strtotime;

/** @implements ArrayAccess<string, Key> */
readonly class CachedKeySet implements ArrayAccess
{
    public function __construct(
        private string $jwksUri,
        private string $ttl,
        #[Config('filament-auth0.http.timeout')] private int $timeout,
        #[Config('filament-auth0.http.retry_times')] private int $retryTimes,
        #[Config('filament-auth0.http.retry_sleep')] private int $retrySleep,
    ) {}

    /** @throws LockTimeoutException */
    public function offsetExists(mixed $offset): bool
    {
        if (! $exists = data_has($this->keys(), $offset)) {
            $exists = ! is_null($this->fresh($offset));
        }

        return $exists;
    }

    /** @throws LockTimeoutException */
    public function offsetGet(mixed $offset): ?Key
    {
        if (! $key = data_get($this->keys(), $offset)) {
            $key = $this->fresh($offset);
        }

        /** @var ?Key */
        return $key;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new BadMethodCallException('Cannot modify JWKS keys');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new BadMethodCallException('Cannot modify JWKS keys');
    }

    /** @return Closure(): array<string, Key> */
    private function fetch(): Closure
    {
        return function () {
            try {
                /** @var Response $response */
                $response = Http::timeout($this->timeout)
                    ->retry($this->retryTimes, $this->retrySleep, throw: false)
                    ->get($this->jwksUri);
            } catch (ConnectionException) {
                throw IdentityProviderConnectionException::create();
            }

            if ($response->failed()) {
                throw UnableToRetrieveKeysException::create($response);
            }

            /** @var array<int|string, mixed> $jwks */
            $jwks = $response->json();

            return JWK::parseKeySet($jwks);
        };
    }

    /** @throws LockTimeoutException */
    private function fresh(mixed $offset): ?Key
    {
        Cache::forget($this->key());

        /** @var int|string $offset */
        /** @var ?Key */
        return data_get($this->keys(), $offset);
    }

    private function key(): string
    {
        $domain = parse_url($this->jwksUri, PHP_URL_HOST);

        return "$domain:jwks";
    }

    /**
     * @return array<string, Key>
     *
     * @throws LockTimeoutException
     */
    private function keys(): array
    {
        /** @var array<string, Key> */
        return Cache::lock(sprintf('%s:lock', $this->key()), seconds: 10)
            ->block(seconds: 5, callback: function () {
                return Cache::remember(
                    key: $this->key(),
                    ttl: strtotime($this->ttl, 0) ?: now()->addDay(),
                    callback: $this->fetch(),
                );
            });
    }
}
