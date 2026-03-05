<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

use Peniti\FilamentAuth0\Actions\RefreshAccessToken;
use Peniti\FilamentAuth0\Exceptions\EndpointNotFoundException;
use Peniti\FilamentAuth0\Exceptions\IdentityProviderConnectionException;
use Peniti\FilamentAuth0\Exceptions\RefreshAccessTokenFailedException;
use Tests\Feature\Support\Mocks\Auth0\DiscoveryEndpoint;
use Tests\Feature\Support\Mocks\Auth0\TokenEndpoint;

describe(RefreshAccessToken::class, function () {
    it('successfully refreshes an access token and returns token response', function () {
        DiscoveryEndpoint::fake();
        TokenEndpoint::fake();

        $response = app(RefreshAccessToken::class)->refresh('valid-refresh-token');

        expect($response)->toBeArray()
            ->and($response['access_token'])->toBe('test-access-token')
            ->and($response['id_token'])->toBe('test-id-token')
            ->and($response['token_type'])->toBe('Bearer')
            ->and($response['scope'])->toBe('openid profile email')
            ->and($response['expires_in'])->toBe((string) strtotime('+1 hour', 0));
    });

    it('throws RefreshAccessTokenFailedException on client error response', function () {
        DiscoveryEndpoint::fake();
        TokenEndpoint::fail([
            'error' => 'invalid_grant',
            'error_description' => 'Refresh token expired',
        ], status: 400);

        app(RefreshAccessToken::class)->refresh('expired-refresh-token');
    })->throws(RefreshAccessTokenFailedException::class, 'Refresh token rejected');

    it('throws IdentityProviderConnectionException on server error response', function () {
        DiscoveryEndpoint::fake();
        TokenEndpoint::fail([
            'error' => 'temporarily_unavailable',
            'error_description' => 'Service unavailable',
        ], status: 503);

        app(RefreshAccessToken::class)->refresh('valid-refresh-token');
    })->throws(IdentityProviderConnectionException::class);

    it('throws IdentityProviderConnectionException when token endpoint connection fails', function () {
        DiscoveryEndpoint::fake();
        TokenEndpoint::connectionRefused();

        app(RefreshAccessToken::class)->refresh('valid-refresh-token');
    })->throws(IdentityProviderConnectionException::class);

    it('throws EndpointNotFoundException when token_endpoint is missing from metadata', function () {
        DiscoveryEndpoint::fake(['token_endpoint' => null]);

        app(RefreshAccessToken::class)->refresh('valid-refresh-token');
    })->throws(EndpointNotFoundException::class, "Required endpoint 'token_endpoint' not found in OpenID configuration metadata.");
});
