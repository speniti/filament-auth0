<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace Tests\Feature\Auth\Stores;

use function app;

use App\Models\User;

use function describe;
use function expect;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;

use function it;

use Peniti\FilamentAuth0\Auth\Stores\CacheTokenStore;
use Peniti\FilamentAuth0\Exceptions\RefreshAccessTokenFailedException;
use Tests\Feature\Support\Mocks\Auth0\DiscoveryEndpoint;
use Tests\Feature\Support\Mocks\Auth0\TokenEndpoint;

describe(CacheTokenStore::class, function () {
    beforeEach(function () {
        Cache::flush();
        DiscoveryEndpoint::fake();
    });

    describe('save()', function () {
        it('stores access token in cache with correct TTL', function () {
            app(CacheTokenStore::class)->save([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
                'id_token' => 'test-id-token',
                'refresh_token' => 'test-refresh-token',
                'scope' => 'openid profile',
                'token_type' => 'Bearer',
            ], 'user-123');

            $cached = Cache::get('auth0_tokens:user-123');

            expect($cached)->not->toBeNull()
                ->and($cached['access_token'])->toBe('test-access-token')
                ->and(Crypt::decryptString($cached['refresh_token']))->toBe('test-refresh-token');
        });

        it('calculates needs_refresh_at using expires_in and refresh_buffer', function () {
            app(CacheTokenStore::class)->save([
                'access_token' => 'test-access-token',
                'expires_in' => 3600, // 1 hour
                'id_token' => 'test-id-token',
                'refresh_token' => 'test-refresh-token',
                'scope' => 'openid profile',
                'token_type' => 'Bearer',
            ], 'user-123');

            // With a 5-minute buffer (default), needs_refresh_at should be 55 minutes from now
            $expectedMinutes = 55; // 3600 - 300 = 3300 seconds = 55 minutes
            $needsRefreshAt = Cache::get('auth0_tokens:user-123')['needs_refresh_at'];

            expect(now()->diffInMinutes($needsRefreshAt))->toBeBetween($expectedMinutes - 1, $expectedMinutes + 1);
        });

        it('stores only access_token and refresh_token from the token response', function () {
            /** @noinspection PhpArrayKeyDoesNotMatchArrayShapeInspection */
            app(CacheTokenStore::class)->save([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
                'id_token' => 'test-id-token',
                'refresh_token' => 'test-refresh-token',
                'scope' => 'openid profile',
                'token_type' => 'Bearer',
                'other_field' => 'should_not_be_stored',
            ], 'user-123');

            expect(Cache::get('auth0_tokens:user-123'))
                ->toHaveKey('access_token')
                ->toHaveKey('refresh_token')
                ->toHaveKey('needs_refresh_at')
                ->not->toHaveKey('id_token')
                ->not->toHaveKey('scope')
                ->not->toHaveKey('token_type')
                ->not->toHaveKey('other_field');
        });

        it('works without refresh_token in response', function () {
            app(CacheTokenStore::class)->save([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
                'id_token' => 'test-id-token',
                'scope' => 'openid profile',
                'token_type' => 'Bearer',
            ], 'user-123');

            expect(Cache::get('auth0_tokens:user-123'))
                ->toHaveKey('access_token', 'test-access-token')
                ->not->toHaveKey('refresh_token');
        });

        it('uses the configured cache store when specified', function () {
            Config::set('filament-auth0.tokens.stores.cache.store', 'database');

            app(CacheTokenStore::class)->save([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
                'id_token' => 'test-id-token',
                'refresh_token' => 'test-refresh-token',
                'scope' => 'openid profile',
                'token_type' => 'Bearer',
            ], 'user-123');

            /** @noinspection PhpUnhandledExceptionInspection */
            expect(Cache::store('database')->get('auth0_tokens:user-123')['access_token'])->toBe('test-access-token');
        });

        it('uses custom cache prefix from config', function () {
            Config::set('filament-auth0.tokens.prefix', 'custom_prefix:');

            app(CacheTokenStore::class)->save([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
                'id_token' => 'test-id-token',
                'refresh_token' => 'test-refresh-token',
                'scope' => 'openid profile',
                'token_type' => 'Bearer',
            ], 'user-123');

            expect(Cache::get('custom_prefix:user-123')['access_token'])->toBe('test-access-token');
        });
    });

    describe('getAccessToken()', function () {
        it('returns the stored access token', function () {
            $store = app(CacheTokenStore::class);

            $store->save([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
                'id_token' => 'test-id-token',
                'refresh_token' => 'test-refresh-token',
                'scope' => 'openid profile',
                'token_type' => 'Bearer',
            ], 'user-123');

            /** @noinspection PhpUnhandledExceptionInspection */
            expect($store->getAccessToken('user-123'))->toBe('test-access-token');
        });

        it('returns null when no token is stored', function () {
            /** @noinspection PhpUnhandledExceptionInspection */
            expect(app(CacheTokenStore::class)->getAccessToken('user-123'))->toBeNull();
        });
    });

    describe('forgetToken()', function () {
        it('removes the stored token from cache', function () {
            $store = app(CacheTokenStore::class);

            $store->save([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
                'id_token' => 'test-id-token',
                'refresh_token' => 'test-refresh-token',
                'scope' => 'openid profile',
                'token_type' => 'Bearer',
            ], 'user-123');

            /** @noinspection PhpUnhandledExceptionInspection */
            expect($store->getAccessToken('user-123'))->not->toBeNull();

            $store->forget('user-123');

            /** @noinspection PhpUnhandledExceptionInspection */
            expect($store->getAccessToken('user-123'))->toBeNull();
        });

        it('does nothing when token does not exist', function () {
            app(CacheTokenStore::class)->forget('user-123');
        })->throwsNoExceptions();
    });

    describe('needsRefresh()', function () {
        it('returns true when needs_refresh_at time has passed', function () {
            Cache::put('auth0_tokens:user-123', [
                'access_token' => 'test-access-token',
                'refresh_token' => Crypt::encryptString('test-refresh-token'),
                'needs_refresh_at' => now()->subMinute(), // 1 minute ago
            ], 3600);

            /** @noinspection PhpUnhandledExceptionInspection */
            $needsRefresh = app(CacheTokenStore::class)->needsRefresh('user-123');
            expect($needsRefresh)->toBeTrue();
        });

        it('returns false when needs_refresh_at time has not passed', function () {
            Cache::put('auth0_tokens:user-123', [
                'access_token' => 'test-access-token',
                'refresh_token' => Crypt::encryptString('test-refresh-token'),
                'needs_refresh_at' => now()->addHour(), // 1 hour from now
            ], 3600);

            /** @noinspection PhpUnhandledExceptionInspection */
            $needsRefresh = app(CacheTokenStore::class)->needsRefresh('user-123');
            expect($needsRefresh)->toBeFalse();
        });

        it('returns true when no token is stored', function () {
            /** @noinspection PhpUnhandledExceptionInspection */
            expect(app(CacheTokenStore::class)->needsRefresh('user-123'))->toBeTrue();
        });

        it('returns true when needs_refresh_at is missing from stored data', function () {
            $store = app(CacheTokenStore::class);

            Cache::put('auth0_tokens:user-123', [
                'access_token' => 'test-access-token',
                'refresh_token' => 'test-refresh-token',
            ], 3600);

            /** @noinspection PhpUnhandledExceptionInspection */
            $needsRefresh = $store->needsRefresh('user-123');
            expect($needsRefresh)->toBeTrue();
        });
    });

    describe('refreshToken()', function () {
        it('refreshes the token using the refresher and saves it', function () {
            TokenEndpoint::fake([
                'access_token' => 'new-access-token',
                'expires_in' => 7200,
                'id_token' => 'test-id-token',
                'refresh_token' => 'new-refresh-token',
                'scope' => 'openid profile',
                'token_type' => 'Bearer',
            ]);

            Cache::put('auth0_tokens:user-123', [
                'access_token' => 'old-access-token',
                'refresh_token' => Crypt::encryptString('old-refresh-token'),
                'needs_refresh_at' => now()->subMinute(),
            ], 3600);

            /** @noinspection PhpUnhandledExceptionInspection */
            app(CacheTokenStore::class)->refresh('user-123');

            $cached = Cache::get('auth0_tokens:user-123');
            expect($cached['access_token'])->toBe('new-access-token')
                ->and(Crypt::decryptString($cached['refresh_token']))->toBe('new-refresh-token');

            TokenEndpoint::assertCalled(function ($request) {
                return $request->data()['grant_type'] === 'refresh_token'
                    && $request->data()['refresh_token'] === 'old-refresh-token';
            });
        });

        it('throws exception when token refresh fails', function () {
            TokenEndpoint::fail([
                'error' => 'invalid_grant',
                'error_description' => 'Invalid refresh token',
            ], 400);

            $store = app(CacheTokenStore::class);

            Cache::put('auth0_tokens:user-123', [
                'access_token' => 'old-access-token',
                'refresh_token' => Crypt::encryptString('invalid-refresh-token'),
                'needs_refresh_at' => now()->subMinute(),
            ], 3600);

            /** @noinspection PhpUnhandledExceptionInspection */
            $store->refresh('user-123');
        })->throws(RefreshAccessTokenFailedException::class);
    });

    describe('getDefaultIdentifier()', function () {
        it('returns the authenticated user ID from Auth facade', function () {
            Auth::login($user = User::factory()->create());

            expect(app(CacheTokenStore::class)->getDefaultIdentifier())
                ->toBe($user->getAuthIdentifier());
        });

        it('returns "guest" when no user is authenticated', function () {
            Auth::logout();

            expect(app(CacheTokenStore::class)->getDefaultIdentifier())->toBe('guest');
        });
    });

    describe('integration with default identifier', function () {
        it('uses default identifier when none is provided to save()', function () {
            Auth::login($user = User::factory()->create());

            app(CacheTokenStore::class)->save([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
                'id_token' => 'test-id-token',
                'refresh_token' => 'test-refresh-token',
                'scope' => 'openid profile',
                'token_type' => 'Bearer',
            ]);

            $cached = Cache::get('auth0_tokens:'.$user->getAuthIdentifier());
            expect($cached)->not->toBeNull()
                ->and($cached['access_token'])->toBe('test-access-token');
        });

        it('uses default identifier when none is provided to getAccessToken()', function () {
            Auth::login($user = User::factory()->create());

            Cache::put('auth0_tokens:'.$user->getAuthIdentifier(), [
                'access_token' => 'test-access-token',
                'refresh_token' => 'test-refresh-token',
                'needs_refresh_at' => now()->addHour(),
            ], 3600);

            /** @noinspection PhpUnhandledExceptionInspection */
            expect(app(CacheTokenStore::class)->getAccessToken())->toBe('test-access-token');
        });

        it('uses default identifier when none is provided to forgetToken()', function () {
            Auth::login($user = User::factory()->create());

            Cache::put('auth0_tokens:'.$user->getAuthIdentifier(), [
                'access_token' => 'test-access-token',
                'refresh_token' => 'test-refresh-token',
                'needs_refresh_at' => now()->addHour(),
            ], 3600);

            app(CacheTokenStore::class)->forget();

            expect(Cache::get('auth0_tokens:'.$user->getAuthIdentifier()))->toBeNull();
        });
    });

    describe('custom refresh buffer', function () {
        it('uses custom refresh buffer from config', function () {
            Config::set('filament-auth0.tokens.refresh_buffer', '10 minutes');

            $store = app(CacheTokenStore::class);
            $store->save([
                'access_token' => 'test-access-token',
                'expires_in' => 3600, // 1 hour
                'id_token' => 'test-id-token',
                'refresh_token' => 'test-refresh-token',
                'scope' => 'openid profile',
                'token_type' => 'Bearer',
            ], 'user-123');

            $cached = Cache::get('auth0_tokens:user-123');
            $needsRefreshAt = $cached['needs_refresh_at'];

            // With 10 minute buffer, needs_refresh_at should be 50 minutes from now
            $expectedMinutes = 50; // 3600 - 600 = 3000 seconds = 50 minutes
            $diffInMinutes = now()->diffInMinutes($needsRefreshAt);

            expect($diffInMinutes)->toBeBetween($expectedMinutes - 1, $expectedMinutes + 1);
        });
    });

    describe('token expiration edge cases', function () {
        it('handles tokens with very short expiration', function () {
            $store = app(CacheTokenStore::class);

            $store->save([
                'access_token' => 'test-access-token',
                'expires_in' => 60, // 1 minute
                'id_token' => 'test-id-token',
                'refresh_token' => 'test-refresh-token',
                'scope' => 'openid profile',
                'token_type' => 'Bearer',
            ], 'user-123');

            $cached = Cache::get('auth0_tokens:user-123');

            /** @noinspection PhpUnhandledExceptionInspection */
            expect($cached)->not->toBeNull()
                // Token should need refresh immediately due to 5 minute buffer > 1 minute expiration
                ->and($store->needsRefresh('user-123'))->toBeTrue();
        });
    });
});
