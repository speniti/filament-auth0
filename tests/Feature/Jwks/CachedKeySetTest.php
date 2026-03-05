<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Peniti\FilamentAuth0\Exceptions\IdentityProviderConnectionException;
use Peniti\FilamentAuth0\Exceptions\UnableToRetrieveKeysException;
use Peniti\FilamentAuth0\Jwks\CachedKeySet;
use Tests\Feature\Support\Mocks\Auth0\DiscoveryEndpoint;
use Tests\Feature\Support\Mocks\Auth0\JwksEndpoint;
use Tests\Feature\Support\TestKeys;

describe(CachedKeySet::class, function () {
    beforeEach(function () {
        Cache::flush();
        DiscoveryEndpoint::fake();
    });

    it('retrieves keys from JWKS endpoint', function () {
        JwksEndpoint::fake();
        $keySet = app(CachedKeySet::class);

        /** @noinspection PhpUnhandledExceptionInspection */
        $keyId = TestKeys::keyId();

        expect($keySet[$keyId])->toBeInstanceOf(Key::class)
            ->and($keySet[$keyId]->getAlgorithm())->toBe('RS256');
    });

    it('caches keys to avoid repeated requests', function () {
        JwksEndpoint::fake();
        $keySet = app(CachedKeySet::class);

        /** @noinspection PhpUnhandledExceptionInspection */
        $keyId = TestKeys::keyId();

        JwksEndpoint::assertNotCalled();
        DiscoveryEndpoint::assertCalled();

        Http::assertSentCount(1);

        $keySet[$keyId];
        JwksEndpoint::assertCalled();

        $keySet[$keyId];
        Http::assertSentCount(2);
    });

    it('checks if offset exists and tries to fetch data again', function () {
        JwksEndpoint::fake();
        $keySet = app(CachedKeySet::class);

        /** @noinspection PhpUnhandledExceptionInspection */
        $keyId = TestKeys::keyId();
        DiscoveryEndpoint::assertCalled();

        expect(isset($keySet[$keyId]))->toBeTrue()
            ->and($keySet[$keyId])->toBeInstanceOf(Key::class);
        JwksEndpoint::assertCalled();

        expect(isset($keySet['non_existent_key']))->toBeFalse();
        JwksEndpoint::assertCalled();

        expect($keySet['non_existent_key'])->toBeNull();
        JwksEndpoint::assertCalled();

        Http::assertSentCount(4);
    });

    it('throws exception when trying to set a value', function () {
        JwksEndpoint::fake();

        /** @noinspection PhpUnhandledExceptionInspection */
        $keyId = TestKeys::keyId();
        app(CachedKeySet::class)[$keyId] = 'new_value';
    })->throws(BadMethodCallException::class, 'Cannot modify JWKS keys');

    it('throws exception when trying to unset a value', function () {
        JwksEndpoint::fake();

        /** @noinspection PhpUnhandledExceptionInspection */
        $keyId = TestKeys::keyId();
        unset(app(CachedKeySet::class)[$keyId]);
    })->throws(BadMethodCallException::class, 'Cannot modify JWKS keys');

    it('throws exception when connection to JWKS endpoint fails', function () {
        JwksEndpoint::connectionRefused();

        /** @noinspection PhpUnhandledExceptionInspection */
        $keyId = TestKeys::keyId();
        app(CachedKeySet::class)[$keyId];
    })->throws(IdentityProviderConnectionException::class);

    it('throws exception when the JWKS endpoint returns an error', function () {
        JwksEndpoint::fail();

        /** @noinspection PhpUnhandledExceptionInspection */
        $keyId = TestKeys::keyId();
        app(CachedKeySet::class)[$keyId];
    })->throws(UnableToRetrieveKeysException::class);
});
