<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Events;

use Throwable;

class Auth0AuthenticationFailed
{
    public function __construct(
        public readonly Throwable $exception
    ) {}
}
