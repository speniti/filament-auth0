<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Commands;

use function data_get;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

use function Laravel\Prompts\error;
use function Laravel\Prompts\table;

use Peniti\FilamentAuth0\Exceptions\EndpointNotFoundException;
use Peniti\FilamentAuth0\Exceptions\IdentityProviderConnectionException;
use Peniti\FilamentAuth0\OpenID\CachedMetadata;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'auth0:test')]
class Auth0Healthcheck extends Command
{
    protected $description = 'Auth0 healthcheck.';

    protected $signature = 'auth0:test';

    public function handle(CachedMetadata $metadata): int
    {
        $this->checkConfiguration();
        $this->checkEndpoints($metadata);

        return self::SUCCESS;
    }

    private function checkConfiguration(): void
    {
        table(
            headers: ['Setting', 'Value'],
            rows: [
                ['Domain', Config::string('filament-auth0.domain')],
                ['Client ID', Config::string('filament-auth0.client_id')],
                ['Client Secret', Str::mask(Config::string('filament-auth0.client_secret'), '*', 0)],
            ]
        );
    }

    private function checkEndpoints(CachedMetadata $metadata): void
    {
        try {
            $rows = array_map(static function ($endpoint) use ($metadata) {
                [$name, $key] = $endpoint;

                try {
                    return [$name, $metadata->getEndpoint($key)];
                } catch (EndpointNotFoundException) {
                    return [$name, ''];
                }
            }, [
                ['Authorization Endpoint', 'authorization'],
                ['Token Endpoint', 'token'],
                ['Revocation Endpoint', 'revocation'],
                ['UserInfo Endpoint', 'userinfo'],
            ]);

            table(
                headers: ['Endpoint', 'URL'],
                rows: [...$rows, ['JWKS URI', data_get($metadata, 'jwks_uri')]]
            );
        } catch (IdentityProviderConnectionException $e) {
            error('❌ Failed to connect to Auth0');
            error($e->getMessage());
        }
    }
}
