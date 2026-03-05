<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Auth\Stores;

use function event;

use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

use function now;

use Peniti\FilamentAuth0\Actions\RefreshAccessToken;
use Peniti\FilamentAuth0\Contracts\Auth0TokenStore;
use Peniti\FilamentAuth0\Events\Auth0TokenRefreshed;

/**
 * @phpstan-import-type TokenExchangeResponse from Auth0TokenStore
 * @phpstan-import-type StoredTokenExchangeResponse from Auth0TokenStore
 */
abstract readonly class TokenStore implements Auth0TokenStore
{
    public function __construct(
        protected RefreshAccessToken $refresher,
        #[Config('filament-auth0.tokens.refresh_buffer')] protected string $refreshBuffer,
    ) {}

    /**
     * @template K of key-of<StoredTokenExchangeResponse>
     *
     * @param  K  $key
     * @return StoredTokenExchangeResponse[K]|null
     */
    abstract protected function getTokenData(int|string|null $identifier, string $key);

    /** @param  array<string, mixed>  $data */
    abstract protected function setTokenData(int|string|null $identifier, array $data): void;

    public function getAccessToken(int|string|null $identifier = null): ?string
    {
        return $this->getTokenData($identifier, 'access_token');
    }

    public function getDefaultIdentifier(): int|string
    {
        return Auth::id() ?? 'guest';
    }

    public function getRefreshToken(int|string|null $identifier = null): ?string
    {
        if (! $token = $this->getTokenData($identifier, 'refresh_token')) {
            return null;
        }

        return Crypt::decryptString($token);
    }

    public function needsRefresh(int|string|null $identifier = null): bool
    {
        $refreshAt = $this->getTokenData($identifier, 'needs_refresh_at');

        return now()->isAfter($refreshAt ?? now()->subMinute());
    }

    public function refresh(int|string|null $identifier = null): void
    {
        $tokens = $this->refresher->refresh($this->getRefreshToken($identifier));

        $this->save($tokens, $identifier);

        event(new Auth0TokenRefreshed($identifier, $tokens));
    }

    /** @param  TokenExchangeResponse  $tokens  */
    public function save(array $tokens, int|string|null $identifier = null): void
    {
        $expiresIn = (int) $tokens['expires_in'];
        $buffer = strtotime($this->refreshBuffer, 0);

        if (isset($tokens['refresh_token'])) {
            $tokens['refresh_token'] = Crypt::encryptString($tokens['refresh_token']);
        }

        /** @var array<string, mixed> $data */
        $data = array_filter([
            ...Arr::only($tokens, ['access_token', 'refresh_token']),
            'needs_refresh_at' => now()->addSeconds($expiresIn - $buffer),
            'expires_in' => $expiresIn,
        ]);

        $this->setTokenData($identifier, $data);
    }
}
