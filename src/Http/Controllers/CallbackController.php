<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Http\Controllers;

use function action;

use Illuminate\Container\Attributes\Config;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Peniti\FilamentAuth0\Actions\DecodeIdToken;
use Peniti\FilamentAuth0\Contracts\Auth0TokenStore;
use Peniti\FilamentAuth0\Events\Auth0Authenticated;
use Peniti\FilamentAuth0\Exceptions\IdentityProviderConnectionException;
use Peniti\FilamentAuth0\Exceptions\InvalidAuthorizationCodeException;
use Peniti\FilamentAuth0\Facades\Auth0Tokens;
use Peniti\FilamentAuth0\Http\Requests\CallbackRequest;
use Peniti\FilamentAuth0\Jwt\IdToken\Pipeline;
use Peniti\FilamentAuth0\OpenID\CachedMetadata;

use function session;

/** @phpstan-import-type TokenExchangeResponse from Auth0TokenStore */
class CallbackController extends Controller
{
    public function __construct(
        private readonly CachedMetadata $metadata,
        private readonly DecodeIdToken $decoder,
        #[Config('filament-auth0.client_id')] private readonly string $clientId,
        #[Config('filament-auth0.client_secret')] private readonly string $clientSecret,
        #[Config('filament-auth0.routes.home')] private readonly string $home,
        #[Config('filament-auth0.http.timeout')] private readonly int $timeout,
        #[Config('filament-auth0.http.retry_times')] private readonly int $retryTimes,
        #[Config('filament-auth0.http.retry_sleep')] private readonly int $retrySleep,
    ) {}

    public function __invoke(CallbackRequest $request): RedirectResponse
    {
        $response = $this->exchange($request->query('code'));

        $token = $this->decoder->decode($response['id_token']);
        Session::put('id_token', (string) $token);

        Auth::login($user = Pipeline::process($token));
        Auth0Tokens::save($response);

        event(new Auth0Authenticated($token, $user));

        return redirect()->intended($this->home);
    }

    /** @return TokenExchangeResponse */
    public function exchange(string $code): array
    {
        try {
            $endpoint = $this->metadata->getEndpoint('token');

            /** @var Response $response */
            $response = Http::timeout($this->timeout)
                ->retry($this->retryTimes, $this->retrySleep, throw: false)
                ->asForm()
                ->post($endpoint, [
                    'grant_type' => 'authorization_code',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'code' => $code,
                    'redirect_uri' => action(self::class),
                    'code_verifier' => session('auth0_code_verifier'),
                ]);

            if ($response->failed()) {
                if ($response->clientError()) {
                    throw InvalidAuthorizationCodeException::create($response);
                }

                throw IdentityProviderConnectionException::create($response);
            }

            /** @var TokenExchangeResponse */
            return $response->json();
        } catch (ConnectionException) {
            throw IdentityProviderConnectionException::create();
        } finally {
            Session::forget(['auth0_code_verifier', 'auth0_state']);
        }
    }
}
