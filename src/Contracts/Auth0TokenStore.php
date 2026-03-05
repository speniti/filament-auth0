<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Contracts;

use DateTimeInterface;

/**
 * Contract for Auth0 token storage implementations.
 *
 * Similar to Laravel's CacheStore contract, this defines the interface
 * for storing and retrieving Auth0 tokens using different drivers.
 *
 * @phpstan-type TokenExchangeResponse array{
 *   access_token: string,
 *   expires_in: int,
 *   id_token: string,
 *   refresh_token?: string,
 *   scope: string,
 *   token_type: string
 * }
 * @phpstan-type StoredTokenExchangeResponse array{
 *  access_token: string,
 *  expires_in: int,
 *  refresh_token?: string,
 *  needs_refresh_at: DateTimeInterface
 * }
 */
interface Auth0TokenStore
{
    /** Remove the stored token for the current or given identifier. */
    public function forget(int|string|null $identifier = null): void;

    /** Get the access token for the current or given identifier. */
    public function getAccessToken(int|string|null $identifier = null): ?string;

    /** Get the current user identifier. */
    public function getDefaultIdentifier(): int|string|null;

    /** Get the refresh token for the current or given identifier. */
    public function getRefreshToken(int|string|null $identifier = null): ?string;

    /** Check if the stored token needs refresh. */
    public function needsRefresh(int|string|null $identifier = null): bool;

    /** Refresh the stored token. */
    public function refresh(int|string|null $identifier = null): void;

    /**
     * Store the token response for the current or given identifier.
     *
     * @param  TokenExchangeResponse  $tokens
     */
    public function save(array $tokens, int|string|null $identifier = null): void;
}
