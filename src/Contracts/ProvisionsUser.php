<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Peniti\FilamentAuth0\Jwt\IdToken;

interface ProvisionsUser
{
    public function handle(IdToken $idToken): Authenticatable;
}
