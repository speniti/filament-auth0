<?php

declare(strict_types=1);

namespace Tests\Feature\Support\Mocks\Auth0;

use function strtotime;

class TokenEndpoint implements MocksEndpoint
{
    use MockEndpoint;

    public array $body {
        get => [
            'access_token' => 'test-access-token',
            'expires_in' => (string) strtotime('+1 hour', 0),
            'id_token' => 'test-id-token',
            'scope' => 'openid profile email',
            'token_type' => 'Bearer',
        ];
    }

    public string $endpoint {
        get => "$this->baseUrl/oauth/token";
    }

    public string $method {
        get => 'POST';
    }
}
