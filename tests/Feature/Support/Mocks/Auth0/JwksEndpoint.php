<?php

declare(strict_types=1);

namespace Tests\Feature\Support\Mocks\Auth0;

use function json_decode;

use JsonException;
use Tests\Feature\Support\TestKeys;

class JwksEndpoint implements MocksEndpoint
{
    use MockEndpoint;

    public array $body {
        /** @throws JsonException */
        get {
            /** @var string $publicKey */
            $publicKey = TestKeys::publicKey('JWK');

            /** @var array<string, mixed> */
            return json_decode($publicKey, true, 512, JSON_THROW_ON_ERROR);
        }
    }

    public string $endpoint {
        get => "$this->baseUrl/.well-known/jwks.json";
    }

    public string $method {
        get => 'get';
    }
}
