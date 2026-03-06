<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Events;

readonly class Auth0TokenRefreshed
{
    /** @param  array<string, mixed>  $tokens */
    public function __construct(
        public string|int|null $userId,
        public array $tokens
    ) {}
}
