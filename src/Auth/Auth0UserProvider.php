<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Peniti\FilamentAuth0\Actions\DecodeIdToken;
use SensitiveParameter;

readonly class Auth0UserProvider implements UserProvider
{
    private const string ID_TOKEN_KEY = 'id_token';

    public function __construct(
        private DecodeIdToken $decoder
    ) {}

    /** @param array<string, string> $credentials */
    public function addRememberToken(Authenticatable $user, array $credentials): void
    {
        // Auth0 handles session persistence
    }

    /** @param array<string, mixed> $credentials */
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        // Auth0 manages passwords
    }

    /**
     * @param  array<string, mixed>  $credentials
     *
     * @throws ValidationException
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        return $this->authenticate();
    }

    /** @throws ValidationException */
    public function retrieveById($identifier): ?Authenticatable
    {
        $identity = $this->authenticate();

        if ($identity?->getAuthIdentifier() !== $identifier) {
            return null;
        }

        return $identity;
    }

    public function retrieveByToken($identifier, #[SensitiveParameter] $token): ?Authenticatable
    {
        return null; // Auth0 handles session persistence
    }

    public function updateRememberToken(Authenticatable $user, #[SensitiveParameter] $token): void
    {
        // Auth0 handles session persistence
    }

    /** @param array<string, mixed> $credentials */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return true; // Auth0 already validated credentials
    }

    /** @throws ValidationException */
    private function authenticate(): ?Authenticatable
    {
        if (! $token = Session::get(self::ID_TOKEN_KEY)) {
            return null;
        }

        assert(is_string($token), 'ID token is not a string');

        return $this->decoder->decode($token);
    }
}
