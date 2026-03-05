<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Peniti\FilamentAuth0\Exceptions\IdentityProviderConnectionException;
use Peniti\FilamentAuth0\OpenID\CachedMetadata;
use Tests\Feature\Support\Mocks\Auth0\DiscoveryEndpoint;

describe(CachedMetadata::class, function () {
    beforeEach(function () {
        Cache::flush();

        DiscoveryEndpoint::fake([
            'issuer' => 'https://tenant-id.auth0.com/',
            'authorization_endpoint' => 'https://tenant-id.auth0.com/authorize',
            'token_endpoint' => 'https://tenant-id.auth0.com/token',
            'jwks_uri' => 'https://tenant-id.auth0.com/.well-known/jwks.json',
        ]);
    });

    it('retrieves metadata from discovery endpoint', function () {
        $metadata = app(CachedMetadata::class);

        expect($metadata['authorization_endpoint'])->toBe('https://tenant-id.auth0.com/authorize')
            ->and($metadata['token_endpoint'])->toBe('https://tenant-id.auth0.com/token')
            ->and($metadata['jwks_uri'])->toBe('https://tenant-id.auth0.com/.well-known/jwks.json');
    });

    it('caches metadata to avoid repeated requests', function () {
        $metadata = app(CachedMetadata::class);

        Http::assertNothingSent();

        $metadata['authorization_endpoint'];
        DiscoveryEndpoint::assertCalled();

        $metadata['token_endpoint'];
        Http::assertSentCount(1);
    });

    it('checks if offset exists', function () {
        $metadata = app(CachedMetadata::class);

        expect(isset($metadata['authorization_endpoint']))->toBeTrue()
            ->and(isset($metadata['non_existent_key']))->toBeFalse();
    });

    it('throws exception when trying to set a value', function () {
        app(CachedMetadata::class)['authorization_endpoint'] = 'https://example.com';
    })->throws(BadMethodCallException::class, 'OpenID metadata is read-only');

    it('throws exception when trying to unset a value', function () {
        unset(app(CachedMetadata::class)['authorization_endpoint']);
    })->throws(BadMethodCallException::class, 'OpenID metadata is read-only');

    it('throws exception when connection to discovery endpoint fails', function () {
        DiscoveryEndpoint::connectionRefused();
        app(CachedMetadata::class)['authorization_endpoint'];
    })->throws(IdentityProviderConnectionException::class);

    it('returns null for non-existent keys', function () {
        expect(app(CachedMetadata::class)['non_existent_key'])->toBeNull();
    });
});
