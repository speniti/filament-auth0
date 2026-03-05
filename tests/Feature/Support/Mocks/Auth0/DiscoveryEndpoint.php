<?php

declare(strict_types=1);

namespace Tests\Feature\Support\Mocks\Auth0;

class DiscoveryEndpoint implements MocksEndpoint
{
    use MockEndpoint;

    public array $body {
        get => [
            'issuer' => "$this->baseUrl/",
            'authorization_endpoint' => "$this->baseUrl/authorize",
            'token_endpoint' => "$this->baseUrl/oauth/token",
            'jwks_uri' => "$this->baseUrl/.well-known/jwks.json",
        ];
    }

    public string $endpoint {
        get => "$this->baseUrl/.well-known/openid-configuration";
    }

    public string $method {
        get => 'get';
    }
}
