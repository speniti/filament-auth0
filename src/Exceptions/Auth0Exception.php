<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Exceptions;

use function auth;

use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class Auth0Exception extends HttpException
{
    /** @return array<string, mixed> */
    public function context(): array
    {
        return [
            'user_id' => auth()->id(),
        ];
    }
}
