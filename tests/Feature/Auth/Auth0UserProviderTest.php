<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace Tests\Feature\Auth;

use function app;
use function describe;
use function expect;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Session;

use function it;

use Peniti\FilamentAuth0\Auth\Auth0UserProvider;
use Peniti\FilamentAuth0\Jwt\Claims;
use Peniti\FilamentAuth0\Jwt\IdToken;
use Tests\Feature\Support\Mocks\Auth0\DiscoveryEndpoint;
use Tests\Feature\Support\Mocks\Auth0\JwksEndpoint;
use Tests\Feature\Support\TestJwt;

describe(Auth0UserProvider::class, function () {
    beforeEach(function () {
        DiscoveryEndpoint::fake();
        JwksEndpoint::fake();
    });

    describe('retrieveByCredentials', function () {
        it('returns the authenticated user from session', function () {
            /** @noinspection PhpUnhandledExceptionInspection */
            Session::put('id_token', TestJwt::createIdToken(['sub' => 'auth0|1234567890']));

            /** @var Authenticatable $user */
            $user = app(Auth0UserProvider::class)->retrieveByCredentials([]);

            expect($user)->toBeInstanceOf(Authenticatable::class)
                ->and($user->getAuthIdentifier())->toBe('auth0|1234567890');
        });

        it('returns null when no token in session', function () {
            Session::forget('id_token');

            /** @var Authenticatable $user */
            $user = app(Auth0UserProvider::class)->retrieveByCredentials([]);

            expect($user)->toBeNull();
        });
    });

    describe('retrieveById', function () {
        it('returns the authenticated user when identifier matches', function () {
            /** @noinspection PhpUnhandledExceptionInspection */
            Session::put('id_token', TestJwt::createIdToken(['sub' => 'auth0|1234567890']));

            /** @var Authenticatable $user */
            $user = app(Auth0UserProvider::class)->retrieveById('auth0|1234567890');

            expect($user)->toBeInstanceOf(Authenticatable::class)
                ->and($user->getAuthIdentifier())->toBe('auth0|1234567890');
        });

        it('returns null when identifier does not match', function () {
            /** @noinspection PhpUnhandledExceptionInspection */
            Session::put('id_token', TestJwt::createIdToken(['sub' => 'auth0|123']));

            /** @var Authenticatable $user */
            $user = app(Auth0UserProvider::class)->retrieveById('auth0|456');

            expect($user)->toBeNull();
        });

        it('returns null when no authenticated user', function () {
            Session::forget('id_token');

            /** @var Authenticatable $user */
            $user = app(Auth0UserProvider::class)->retrieveById('auth0|123');

            expect($user)->toBeNull();
        });
    });

    describe('retrieveByToken', function () {
        it('always returns null because Auth0 handles session persistence', function () {
            /** @noinspection PhpUnhandledExceptionInspection */
            Session::put('id_token', TestJwt::createIdToken(['sub' => 'auth0|123']));

            /** @var Authenticatable $user */
            $user = app(Auth0UserProvider::class)->retrieveByToken('auth0|123', 'remember-token');

            expect($user)->toBeNull();
        });
    });

    describe('validateCredentials', function () {
        it('always returns true because Auth0 already validated credentials', function () {
            $user = new IdToken('token', new Claims(['sub' => 'auth0|123']));
            $result = app(Auth0UserProvider::class)->validateCredentials($user, []);

            expect($result)->toBeTrue();
        });
    });

    describe('updateRememberToken', function () {
        it('does nothing because Auth0 handles session persistence', function () {
            $user = new IdToken('token', new Claims(['sub' => 'auth0|123']));
            app(Auth0UserProvider::class)->updateRememberToken($user, 'new-token');
        })->throwsNoExceptions();
    });

    describe('addRememberToken', function () {
        it('does nothing because Auth0 handles session persistence', function () {
            $user = new IdToken('token', new Claims(['sub' => 'auth0|123']));
            app(Auth0UserProvider::class)->addRememberToken($user, []);
        })->throwsNoExceptions();
    });

    describe('rehashPasswordIfRequired', function () {
        it('does nothing because Auth0 manages passwords', function () {
            $user = new IdToken('token', new Claims(['sub' => 'auth0|123']));
            app(Auth0UserProvider::class)->rehashPasswordIfRequired($user, []);
        })->throwsNoExceptions();
    });
});
