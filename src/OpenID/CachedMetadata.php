<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\OpenID;

use ArrayAccess;
use BadMethodCallException;
use Closure;

use function data_get;
use function data_has;

use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Peniti\FilamentAuth0\Actions\ValidateMetadata;
use Peniti\FilamentAuth0\Exceptions\EndpointNotFoundException;
use Peniti\FilamentAuth0\Exceptions\IdentityProviderConnectionException;
use Peniti\FilamentAuth0\Exceptions\UnableToRetrieveMetadataException;

use function sprintf;
use function strtotime;

/** @implements ArrayAccess<string, mixed> */
readonly class CachedMetadata implements ArrayAccess
{
    public function __construct(
        #[Config('filament-auth0.domain')] private string $domain,
        #[Config('filament-auth0.metadata.ttl')] private string $ttl,
        #[Config('filament-auth0.http.timeout')] private int $timeout,
        #[Config('filament-auth0.http.retry_times')] private int $retryTimes,
        #[Config('filament-auth0.http.retry_sleep')] private int $retrySleep,
        private ValidateMetadata $verifier
    ) {}

    /**
     * Retrieve a specific endpoint URL from the OpenID Connect discovery metadata.
     *
     * This method provides type-safe access to common Auth0/OAuth2 endpoints
     * defined in the .well-known/openid-configuration document.
     *
     * @param  'authorization'|'token'|'device_authorization'|'userinfo'|'mfa_challenge'|'registration'|'revocation'  $endpoint
     *
     * @throws EndpointNotFoundException|LockTimeoutException
     */
    public function getEndpoint(string $endpoint): string
    {
        $key = "{$endpoint}_endpoint";

        /** @var string $endpoint */
        if (! $endpoint = $this->offsetGet($key)) {
            throw EndpointNotFoundException::create($key);
        }

        return $endpoint;
    }

    /** @throws LockTimeoutException */
    public function offsetExists(mixed $offset): bool
    {
        return data_has($this->metadata(), $offset);
    }

    /** @throws LockTimeoutException */
    public function offsetGet(mixed $offset): mixed
    {
        if (! $key = data_get($this->metadata(), $offset)) {
            $key = $this->fresh($offset);
        }

        return $key;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new BadMethodCallException('OpenID metadata is read-only');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new BadMethodCallException('OpenID metadata is read-only');
    }

    /** @return Closure(): array<string, mixed> */
    private function fetch(): Closure
    {
        return function () {
            $url = sprintf('https://%s/.well-known/openid-configuration', $this->domain);

            try {
                /** @var Response $response */
                $response = Http::timeout($this->timeout)
                    ->retry($this->retryTimes, $this->retrySleep, throw: false)
                    ->get($url);
            } catch (ConnectionException) {
                throw IdentityProviderConnectionException::create();
            }

            if ($response->failed()) {
                throw UnableToRetrieveMetadataException::create($response);
            }

            /** @var array<string, mixed> $metadata */
            $metadata = $response->json();

            return $this->verifier->validate($metadata);
        };
    }

    /** @throws LockTimeoutException */
    private function fresh(mixed $offset): mixed
    {
        Cache::forget($this->key());

        /** @var string $offset */
        return data_get($this->metadata(), $offset);
    }

    private function key(): string
    {
        return "$this->domain:openid_metadata";
    }

    /**
     * @return array<string, mixed>
     *
     * @throws LockTimeoutException
     */
    private function metadata(): array
    {
        $lock = Cache::lock($this->key().':lock', 10);

        /** @var array<string, mixed> */
        return $lock->block(5, function () {
            /** @var array<string, mixed> */
            return Cache::remember(
                key: $this->key(),
                ttl: strtotime($this->ttl, 0) ?: now()->addDay(),
                callback: $this->fetch()
            );
        });
    }
}
