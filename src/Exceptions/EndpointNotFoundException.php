<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Exceptions;

class EndpointNotFoundException extends Auth0Exception
{
    public function __construct(string $endpoint, int $code = 502)
    {
        parent::__construct(
            $code,
            "Required endpoint '$endpoint' not found in OpenID configuration metadata."
        );
    }

    public static function create(string $endpoint): self
    {
        return new self($endpoint);
    }
}
