<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace Tests\Feature\Jwt\IdToken\Stages;

use function action;
use function app;

use App\Models\User;

use function bin2hex;
use function compact;
use function describe;
use function fake;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Peniti\FilamentAuth0\Contracts\ProvisionsUser;
use Peniti\FilamentAuth0\Http\Controllers\CallbackController;
use Peniti\FilamentAuth0\Jwt\IdToken;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;
use function random_bytes;
use function session;

use Tests\Feature\Support\Mocks\Auth0\DiscoveryEndpoint;
use Tests\Feature\Support\Mocks\Auth0\JwksEndpoint;
use Tests\Feature\Support\Mocks\Auth0\TokenEndpoint;
use Tests\Feature\Support\TestJwt;

class ProvisionsUserTest implements ProvisionsUser
{
    public function handle(IdToken $idToken): Authenticatable
    {
        User::unguard();

        /**
         * @noinspection PhpUnhandledExceptionInspection
         * @noinspection LaravelEloquentGuardedAttributeAssignmentInspection
         *
         * @var Authenticatable
         */
        return User::firstOrCreate(['auth0_sub' => $idToken->sub], [
            'name' => $idToken->name,
            'email' => $idToken->email,
            'password' => Hash::make(bin2hex(random_bytes(8))),
            'email_verified_at' => now(),
        ]);
    }
}

describe('User provisioning', function () {
    test('users can be provisioned in application database', function () {
        /** @noinspection PhpUnhandledExceptionInspection */
        app()->bind(ProvisionsUser::class, ProvisionsUserTest::class);

        $code = fake()->md5();
        $sub = 'auth0|'.fake()->md5();

        session(['auth0_state' => $state = fake()->md5()]);
        /** @noinspection PhpUnhandledExceptionInspection */
        $idToken = TestJwt::createIdToken(compact('sub'));

        DiscoveryEndpoint::fake();
        JwksEndpoint::fake();
        TokenEndpoint::fake(['id_token' => $idToken]);

        assertDatabaseMissing(User::class, ['auth0_sub' => $sub]);

        /** @noinspection PhpUnhandledExceptionInspection */
        get(action(CallbackController::class, compact('code', 'state')))
            ->assertRedirect(Config::string('filament-auth0.routes.home'))
            ->assertSessionHasNoErrors();

        assertDatabaseHas(User::class, ['auth0_sub' => $sub]);
    });
});
