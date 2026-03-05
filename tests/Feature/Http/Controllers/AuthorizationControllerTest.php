<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

use Peniti\FilamentAuth0\Http\Controllers\AuthorizationController;
use Peniti\FilamentAuth0\OpenID\CachedMetadata;

use function Pest\Laravel\get;

use Symfony\Component\HttpFoundation\Response;
use Tests\Feature\Support\Mocks\Auth0\DiscoveryEndpoint;

describe(AuthorizationController::class, function () {
    it('redirects users to Auth0 login page', function () {
        DiscoveryEndpoint::fake();

        /** @var string $endpoint */
        $endpoint = data_get(app(CachedMetadata::class), 'authorization_endpoint');

        get(action(AuthorizationController::class))->assertRedirectContains($endpoint);

        expect(session()?->has('auth0_state'))->toBeTrue()
            ->and(session()?->has('auth0_code_verifier'))->toBeTrue();
    });

    it('respond with 502 if connection to discovery endpoint fails', function () {
        DiscoveryEndpoint::connectionRefused();

        get(action(AuthorizationController::class))->assertStatus(Response::HTTP_BAD_GATEWAY);

        expect(session()?->has('auth0_state'))->toBeFalse()
            ->and(session()?->has('auth0_code_verifier'))->toBeFalse();

        $url = sprintf('https://%s/.well-known/openid-configuration', config('filament-auth0.domain'));
    });

    it('respond with 502 if discovery endpoint returns an error', function () {
        DiscoveryEndpoint::fail();

        get(action(AuthorizationController::class))->assertStatus(Response::HTTP_BAD_GATEWAY);

        expect(session()?->has('auth0_state'))->toBeFalse()
            ->and(session()?->has('auth0_code_verifier'))->toBeFalse();
    });

    it('respond with 502 if token_endpoint is missing from metadata', function () {
        DiscoveryEndpoint::fake(['authorization_endpoint' => null]);

        get(action(AuthorizationController::class))->assertStatus(Response::HTTP_BAD_GATEWAY);

        expect(session()?->has('auth0_state'))->toBeFalse()
            ->and(session()?->has('auth0_code_verifier'))->toBeFalse();
    });
});
