<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Actions;

use Illuminate\Container\Attributes\Config;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Peniti\FilamentAuth0\Contracts\Auth0TokenStore;
use Peniti\FilamentAuth0\Contracts\RefreshesAccessToken;
use Peniti\FilamentAuth0\Exceptions\IdentityProviderConnectionException;
use Peniti\FilamentAuth0\Exceptions\RefreshAccessTokenFailedException;
use Peniti\FilamentAuth0\OpenID\CachedMetadata;

/** @phpstan-import-type TokenExchangeResponse from Auth0TokenStore */
readonly class RefreshAccessToken implements RefreshesAccessToken
{
    public function __construct(
        private CachedMetadata $metadata,
        #[Config('filament-auth0.client_id')] private string $clientId,
        #[Config('filament-auth0.client_secret')] private string $clientSecret,
        #[Config('filament-auth0.http.timeout')] private int $timeout,
        #[Config('filament-auth0.http.retry_times')] private int $retryTimes,
        #[Config('filament-auth0.http.retry_sleep')] private int $retrySleep,
    ) {}

    /**
     * @return TokenExchangeResponse
     *
     * @throws RefreshAccessTokenFailedException
     * @throws IdentityProviderConnectionException
     */
    public function refresh(?string $refreshToken): array
    {
        try {
            $endpoint = $this->metadata->getEndpoint('token');

            /** @var Response $response */
            $response = Http::timeout($this->timeout)
                ->retry($this->retryTimes, $this->retrySleep, throw: false)
                ->asForm()
                ->post($endpoint, [
                    'grant_type' => 'refresh_token',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $refreshToken,
                ]);
        } catch (ConnectionException) {
            throw IdentityProviderConnectionException::create();
        }

        if ($response->failed()) {
            if ($response->clientError()) {
                throw RefreshAccessTokenFailedException::create($response);
            }

            throw IdentityProviderConnectionException::create($response);
        }

        /** @var TokenExchangeResponse */
        return $response->json();
    }
}
