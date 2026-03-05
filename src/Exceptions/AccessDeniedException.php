<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Exceptions;

class AccessDeniedException extends Auth0Exception
{
    public function __construct(string $message = 'Access denied')
    {
        parent::__construct(400, $message);
    }

    public static function create(string $reason): self
    {
        return new self("Access denied: $reason");
    }
}
