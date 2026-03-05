<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Peniti\FilamentAuth0\Jwt\IdToken;

class Auth0Authenticated
{
    public function __construct(
        public readonly IdToken $token,
        public readonly Authenticatable $user
    ) {}
}
