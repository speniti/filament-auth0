<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Contracts;

use DateTimeInterface;

/**
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
    public function forget(int|string|null $identifier = null): void;

    public function getAccessToken(int|string|null $identifier = null): ?string;

    public function getDefaultIdentifier(): int|string|null;

    public function getRefreshToken(int|string|null $identifier = null): ?string;

    public function needsRefresh(int|string|null $identifier = null): bool;

    public function refresh(int|string|null $identifier = null): void;

    /** @param  TokenExchangeResponse  $tokens */
    public function save(array $tokens, int|string|null $identifier = null): void;
}
