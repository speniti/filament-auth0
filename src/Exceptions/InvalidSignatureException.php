<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Exceptions;

class InvalidSignatureException extends Auth0Exception
{
    public function __construct(string $message = 'The ID token signature is invalid.')
    {
        parent::__construct(401, $message);
    }

    public static function create(): self
    {
        return new self();
    }
}
