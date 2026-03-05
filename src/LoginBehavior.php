<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0;

enum LoginBehavior: string
{
    case DEFAULT = 'default';
    case REDIRECT = 'redirect';

    public function shouldRedirect(): bool
    {
        return $this === self::REDIRECT;
    }
}
