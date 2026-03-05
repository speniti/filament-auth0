<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Jwt;

use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Arrayable;
use Stringable;

/** @implements Arrayable<string, mixed> */
class IdToken implements Arrayable, Authenticatable, HasAvatar, HasName, Stringable
{
    public ?string $email {
        get => $this->claims->string('email', optional: true);
    }

    public string $name {
        get => $this->claims->string('name');
    }

    public ?string $orgId {
        get => $this->claims->string('org_id', optional: true);
    }

    public ?string $picture {
        get => $this->claims->string('picture', optional: true);
    }

    public string $sid {
        get => $this->claims->string('sid');
    }

    public string $sub {
        get => $this->claims->string('sub');
    }

    public function __construct(
        private readonly string $token,
        private readonly Claims $claims
    ) {}

    public function __toString()
    {
        return $this->token;
    }

    public function claim(string $name): mixed
    {
        return $this->claims->get($name);
    }

    public function getAuthIdentifier()
    {
        return $this->claims->string($this->getAuthIdentifierName());
    }

    public function getAuthIdentifierName(): string
    {
        return 'sub';
    }

    public function getAuthPassword(): string
    {
        return 'empty'; // Auth0 manages passwords
    }

    public function getAuthPasswordName(): string
    {
        return 'password'; // Auth0 manages passwords
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->picture;
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function getKey(): mixed
    {
        return $this->getAuthIdentifier();
    }

    public function getRememberToken(): string
    {
        return 'not-managed'; // Auth0 handles session persistence
    }

    public function getRememberTokenName(): string
    {
        return 'remember_me'; // Auth0 handles session persistence
    }

    public function setRememberToken($value): void
    {
        // Auth0 handles session persistence
    }

    public function toArray(): array
    {
        return $this->claims->toArray();
    }
}
