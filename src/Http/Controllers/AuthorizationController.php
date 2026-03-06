<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Http\Controllers;

use function action;
use function bin2hex;
use function hash;
use function http_build_query;

use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Peniti\FilamentAuth0\OpenID\CachedMetadata;
use Random\RandomException;

use function random_bytes;
use function redirect;
use function request;
use function session;

class AuthorizationController extends Controller
{
    public function __construct(
        private readonly CachedMetadata $metadata,
        #[Config('filament-auth0.client_id')] private readonly string $clientId,
        #[Config('filament-auth0.scope')] private readonly string $scope,
    ) {}

    /** @throws RandomException|LockTimeoutException */
    public function __invoke(): RedirectResponse
    {
        $endpoint = $this->metadata->getEndpoint('authorization');
        [$verifier, $challenge] = $this->codeChallenge();

        $state = bin2hex(random_bytes(16));

        session(['auth0_code_verifier' => $verifier, 'auth0_state' => $state]);

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => action(CallbackController::class),
            'scope' => $this->scope,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
            'state' => $state,
            'organization' => request('org_id'),
        ]);

        return redirect()->away("$endpoint?$query");
    }

    /**
     * @return array{non-empty-string, non-empty-string}
     *
     * @throws RandomException
     */
    public function codeChallenge(): array
    {
        $verifier = bin2hex(random_bytes(32));
        $hash = hash('sha256', $verifier, true);

        /** @var non-empty-string $challenge */
        $challenge = (string) str($hash)->toBase64()
            ->replace(['+', '/'], ['-', '_'])
            ->rtrim('=');

        return [$verifier, $challenge];
    }
}
