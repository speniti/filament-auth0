<?php

declare(strict_types=1);

namespace Tests\Feature\Support;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Config;
use Random\RandomException;

use function strtotime;
use function time;
use function urlencode;

class TestJwt
{
    /**
     * Create a signed JWT token for testing purposes.
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws RandomException
     */
    public static function create(array $payload, string $algorithm = 'RS256'): string
    {
        /** @var string $privateKey */
        $privateKey = TestKeys::privateKey('PKCS8');

        return JWT::encode(
            payload: $payload,
            key: $privateKey,
            alg: $algorithm,
            keyId: TestKeys::keyId(),
        );
    }

    /**
     * Create an ID token with standard claims.
     *
     * @param  array<string, string>  $overrides
     *
     * @throws RandomException
     */
    public static function createIdToken(array $overrides = [], string $algorithm = 'RS256'): string
    {
        $domain = Config::string('filament-auth0.domain', 'tenant-id.auth0.com');
        $clientId = Config::string('filament-auth0.client_id', 'test-client-id');

        /** @var array<string, string> $payload */
        $payload = [
            'iss' => "https://$domain/",
            'aud' => $clientId,
            'sub' => 'auth0|1234567890',
            'sid' => 'test-session-id',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'iat' => (string) time(),
            'exp' => (string) strtotime('+1 hour'),

            ...$overrides,
        ];

        return self::create([
            ...$payload,
            'picture' => 'https://ui-avatars.com/api/?name='.urlencode($payload['name']),
        ], $algorithm);
    }
}
