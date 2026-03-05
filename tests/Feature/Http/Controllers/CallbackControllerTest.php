<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Peniti\FilamentAuth0\Http\Controllers\CallbackController;

use function Pest\Laravel\get;

use Symfony\Component\HttpFoundation\Response;
use Tests\Feature\Support\Mocks\Auth0\DiscoveryEndpoint;
use Tests\Feature\Support\Mocks\Auth0\JwksEndpoint;
use Tests\Feature\Support\Mocks\Auth0\TokenEndpoint;
use Tests\Feature\Support\TestJwt;

describe(CallbackController::class, function () {
    it('successfully authenticates user and redirects to home page', function () {
        $code = fake()->md5();
        $sub = 'auth0|'.fake()->md5();

        session(['auth0_state' => $state = fake()->md5()]);
        $idToken = TestJwt::createIdToken(compact('sub'));

        DiscoveryEndpoint::fake();
        JwksEndpoint::fake();
        TokenEndpoint::fake(['id_token' => $idToken]);

        /** @noinspection PhpUnhandledExceptionInspection */
        get(action(CallbackController::class, compact('code', 'state')))
            ->assertRedirect(Config::string('filament-auth0.home'))
            ->assertSessionHasNoErrors();

        expect(auth()->check())->toBeTrue()
            ->and(auth()->user()?->getAuthIdentifier())->toBe($sub);

        DiscoveryEndpoint::assertCalled();
        JwksEndpoint::assertCalled();
        TokenEndpoint::assertCalled();

        expect(session('id_token'))->toBe($idToken);
    });

    it('respond with 400 if token exchange fails', function () {
        $state = fake()->md5();
        session(['auth0_state' => $state]);

        DiscoveryEndpoint::fake();
        JwksEndpoint::fake();
        TokenEndpoint::fail(
            body: ['error' => 'invalid_grant', 'error_description' => 'Invalid code'],
            status: Response::HTTP_BAD_REQUEST
        );

        get(action(CallbackController::class, ['code' => fake()->md5(), 'state' => $state]))
            ->assertBadRequest();
    });

    it('respond with 400 bad request if there is an error during authorization code generation', function () {
        DiscoveryEndpoint::fake();

        get(action(CallbackController::class, ['error' => 'access_denied']))
            ->assertBadRequest();
    });

    it('validates that the received id token :dataset', function (array $claims, array $errors) {
        $code = fake()->md5();
        session(['auth0_state' => $state = fake()->md5()]);

        DiscoveryEndpoint::fake();
        JwksEndpoint::fake();

        TokenEndpoint::fake(['id_token' => TestJwt::createIdToken($claims)]);

        get(action(CallbackController::class, compact('code', 'state')))
            ->assertSessionHasErrors($errors);
    })->with([
        'iss claim must match the configured domain' => [
            ['iss' => 'https://example.com'],
            ['iss' => 'The iss claim does not match the expected value.'],
        ],
        'aud claim must match the configured client id' => [
            ['aud' => 'invalid'],
            ['aud' => 'The aud claim does not match the expected value.'],
        ],
    ]);

    it('validates that :dataset', function (array $params, array $errors) {
        DiscoveryEndpoint::fake();
        session(['auth0_state' => $state = fake()->md5()]);

        get(action(CallbackController::class, ['code' => 'code', 'state' => $state, ...$params]))
            ->assertSessionHasErrors($errors);
    })->with([
        'code parameter is required' => [['code' => null], ['code' => 'The code field is required.']],
        'code parameter must not be empty' => [['code' => ''], ['code' => 'The code field is required.']],
        'state parameter is required' => [['state' => null], ['state' => 'The state field is required.']],
        'state parameter must not be empty' => [['state' => ''], ['state' => 'The state field is required.']],
        'state parameter must match the one stored in session' => [
            ['state' => '1234567890'],
            ['state' => 'The state parameter does not match the expected value.'],
        ],
    ]);

    it('respond with 502 if connection to discovery endpoint fails', function () {
        $state = fake()->md5();
        session(['auth0_state' => $state]);

        DiscoveryEndpoint::connectionRefused();

        get(action(CallbackController::class, ['code' => fake()->md5(), 'state' => $state]))
            ->assertStatus(502);
    });

    it('respond with 502 if connection to jwks endpoint fails', function () {
        DiscoveryEndpoint::fake();

        /** @noinspection PhpUnhandledExceptionInspection */
        $idToken = TestJwt::createIdToken();
        TokenEndpoint::fake(['id_token' => $idToken]);

        $state = fake()->md5();
        session(['auth0_state' => $state]);

        JwksEndpoint::connectionRefused();

        get(action(CallbackController::class, ['code' => fake()->md5(), 'state' => $state]))
            ->assertStatus(502);
    });

    it('respond with 502 if connection to token endpoint fails', function () {
        DiscoveryEndpoint::fake();

        $state = fake()->md5();
        session(['auth0_state' => $state]);

        TokenEndpoint::connectionRefused();

        get(action(CallbackController::class, ['code' => fake()->md5(), 'state' => $state]))
            ->assertStatus(502);
    });

    it('respond with 502 if token endpoint returns a server error', function () {
        DiscoveryEndpoint::fake();

        $state = fake()->md5();
        session(['auth0_state' => $state]);

        TokenEndpoint::fail(status: Response::HTTP_INTERNAL_SERVER_ERROR);

        get(action(CallbackController::class, ['code' => fake()->md5(), 'state' => $state]))
            ->assertStatus(502);
    });

    it('respond with 502 if token_endpoint is missing from metadata', function () {
        $state = fake()->md5();
        session(['auth0_state' => $state]);

        DiscoveryEndpoint::fake(['token_endpoint' => null]);

        get(action(CallbackController::class, ['code' => fake()->md5(), 'state' => $state]))
            ->assertStatus(502);
    });
});
