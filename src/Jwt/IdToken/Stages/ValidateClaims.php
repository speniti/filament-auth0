<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Jwt\IdToken\Stages;

use Closure;
use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Validator;
use Peniti\FilamentAuth0\Jwt\IdToken;
use Peniti\FilamentAuth0\Jwt\Rules\Matches;

readonly class ValidateClaims
{
    public function __construct(
        #[Config('filament-auth0.client_id')] private string $clientId,
        #[Config('filament-auth0.domain')] private string $domain
    ) {}

    public function handle(IdToken $idToken, Closure $next): Authenticatable
    {
        Validator::make($idToken->toArray(), [
            'aud' => ['required', new Matches($this->clientId)],
            'iss' => ['required', new Matches("https://$this->domain/")],
        ])->validate();

        return $next($idToken);
    }
}
