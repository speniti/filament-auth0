<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Contracts;

use Peniti\FilamentAuth0\Jwt\IdToken;

interface DecodesIdToken
{
    public function decode(string $token): IdToken;
}
