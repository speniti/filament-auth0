<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Auth\Stores;

use function data_get;

use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use function now;

use Peniti\FilamentAuth0\Actions\RefreshAccessToken;
use Peniti\FilamentAuth0\Contracts\Auth0TokenStore;
use stdClass;

/**
 * @phpstan-import-type TokenExchangeResponse from Auth0TokenStore
 * @phpstan-import-type StoredTokenExchangeResponse from Auth0TokenStore
 */
readonly class DatabaseTokenStore extends TokenStore
{
    public function __construct(
        RefreshAccessToken $refresher,
        #[Config('filament-auth0.tokens.refresh_buffer')] string $refreshBuffer,
        #[Config('filament-auth0.tokens.stores.database.connection')] protected ?string $connection,
        #[Config('filament-auth0.tokens.stores.database.table')] protected string $table,
        #[Config('filament-auth0.tokens.stores.database.user_id_column')] protected string $userIdColumn,
        #[Config('filament-auth0.tokens.stores.database.cache_ttl')] protected string $cacheTtl,
    ) {
        parent::__construct($refresher, $refreshBuffer);
    }

    public function forget(int|string|null $identifier = null): void
    {
        $identifier = $identifier ?? $this->getDefaultIdentifier();

        DB::connection($this->connection)
            ->table($this->table)
            ->where($this->userIdColumn, $identifier)
            ->delete();

        Cache::forget($this->getCacheKey($identifier));
    }

    /**
     * @template K of key-of<StoredTokenExchangeResponse>
     *
     * @param  K  $key
     * @return StoredTokenExchangeResponse[K]|null
     */
    protected function getTokenData(int|string|null $identifier, string $key)
    {
        if (! $record = $this->getRecord($identifier)) {
            return null;
        }

        /** @var StoredTokenExchangeResponse[K]|null */
        return data_get($record, $key);
    }

    /** @param  StoredTokenExchangeResponse  $data */
    protected function setTokenData(int|string|null $identifier, array $data): void
    {
        $identifier = $identifier ?? $this->getDefaultIdentifier();

        DB::connection($this->connection)
            ->table($this->table)
            ->updateOrInsert(
                [$this->userIdColumn => $identifier],
                [
                    'access_token' => $data['access_token'],
                    'needs_refresh_at' => $data['needs_refresh_at'],
                    'expires_at' => now()->addSeconds($data['expires_in']),
                    'updated_at' => now(),
                ]
            );

        // Clear cache after saving
        Cache::forget($this->getCacheKey($identifier));
    }

    private function getCacheKey(int|string $identifier): string
    {
        return "auth0_tokens:$this->table:$identifier";
    }

    private function getRecord(int|string|null $identifier = null): ?stdClass
    {
        $identifier = $identifier ?? $this->getDefaultIdentifier();

        /** @var stdClass|null */
        return Cache::remember(
            $this->getCacheKey($identifier),
            strtotime($this->cacheTtl, 0) ?: 300,
            fn () => DB::connection($this->connection)
                ->table($this->table)
                ->select(['access_token', 'refresh_token', 'needs_refresh_at'])
                ->where($this->userIdColumn, $identifier)
                ->where('expires_at', '>', now())
                ->first()
        );
    }
}
