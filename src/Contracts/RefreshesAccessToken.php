<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Contracts;

/** @phpstan-import-type TokenExchangeResponse from Auth0TokenStore */
interface RefreshesAccessToken
{
    /** @return TokenExchangeResponse */
    public function refresh(?string $refreshToken): array;
}
