<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

use Peniti\FilamentAuth0\Actions\DecodeIdToken;
use Peniti\FilamentAuth0\Exceptions\ExpiredIdTokenException;
use Peniti\FilamentAuth0\Exceptions\IdentityProviderConnectionException;
use Peniti\FilamentAuth0\Exceptions\InvalidSignatureException;
use Peniti\FilamentAuth0\Exceptions\MalformedIdTokenException;
use Peniti\FilamentAuth0\Exceptions\UnableToRetrieveKeysException;
use Peniti\FilamentAuth0\Jwt\IdToken;
use Tests\Feature\Support\Mocks\Auth0\DiscoveryEndpoint;
use Tests\Feature\Support\Mocks\Auth0\JwksEndpoint;
use Tests\Feature\Support\TestJwt;

describe(DecodeIdToken::class, function () {
    beforeEach(function () {
        DiscoveryEndpoint::fake();
    });

    it('successfully decodes a valid JWT token and returns IdToken instance', function () {
        JwksEndpoint::fake();

        /** @noinspection PhpUnhandledExceptionInspection */
        $idToken = TestJwt::createIdToken();
        $token = app(DecodeIdToken::class)->decode($idToken);

        expect($token)->toBeInstanceOf(IdToken::class)
            ->and($token->sub)->toBe('auth0|1234567890')
            ->and($token->name)->toBe('Test User')
            ->and($token->email)->toBe('test@example.com')
            ->and((string) $token)->toBe($idToken);
    });

    it('throws ExpiredIdTokenException when token has expired', function () {
        JwksEndpoint::fake();

        /** @noinspection PhpUnhandledExceptionInspection */
        $idToken = TestJwt::createIdToken(['exp' => strtotime('-1 hour')]);
        app(DecodeIdToken::class)->decode($idToken);
    })->throws(ExpiredIdTokenException::class, 'The ID token has expired.');

    it('throws InvalidSignatureException when token signature is invalid', function () {
        JwksEndpoint::fake();

        /** @noinspection PhpUnhandledExceptionInspection */
        $idToken = TestJwt::createIdToken();
        app(DecodeIdToken::class)->decode(mb_substr($idToken, 0, -1).'!');
    })->throws(InvalidSignatureException::class, 'The ID token signature is invalid.');

    it('throws UnableToRetrieveKeysException when JWKS endpoint fails', function () {
        JwksEndpoint::fail();

        /** @noinspection PhpUnhandledExceptionInspection */
        $idToken = TestJwt::createIdToken();
        app(DecodeIdToken::class)->decode($idToken);
    })->throws(UnableToRetrieveKeysException::class);

    it('throws IdentityProviderConnectionException when JWKS endpoint connection fails', function () {
        JwksEndpoint::connectionRefused();

        /** @noinspection PhpUnhandledExceptionInspection */
        $idToken = TestJwt::createIdToken();
        app(DecodeIdToken::class)->decode($idToken);
    })->throws(IdentityProviderConnectionException::class);

    /** @noinspection PhpUnhandledExceptionInspection */
    it('throws MalformedIdTokenException if token :dataset', function (string $token) {
        app(DecodeIdToken::class)->decode($token);
    })->with([
        'is not base64 encoded' => ['invalid.jwt.token'],
        'has only two segments' => ['only-two.segments'],
        'is empty string' => [''],
        'algorithm not supported' => [fn () => TestJwt::createIdToken(algorithm: 'RS512')],
    ])->throws(MalformedIdTokenException::class);
});
