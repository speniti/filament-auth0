<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace Tests\Unit\Jwt;

use function describe;

use Peniti\FilamentAuth0\Jwt\Claims;
use Peniti\FilamentAuth0\Jwt\IdToken;

describe(IdToken::class, function () {
    describe('constructor', function () {
        it('can be instantiated with token and claims', function () {
            $claims = new Claims(['sub' => 'auth0|123', 'name' => 'John Doe']);
            $token = new IdToken('jwt-token', $claims);

            expect($token)->toBeInstanceOf(IdToken::class);
        });

        it('stores token and claims as readonly properties', function () {
            $claims = new Claims(['sub' => 'auth0|123']);
            $token = new IdToken('jwt-token', $claims);

            expect($token->claim('sub'))->toBe('auth0|123');
        });
    });

    describe('virtual properties', function () {
        it('provides access to the sub claim', function () {
            $claims = new Claims(['sub' => 'auth0|1234567890']);
            $token = new IdToken('jwt-token', $claims);

            expect($token->sub)->toBe('auth0|1234567890');
        });

        it('provides access to the name claim', function () {
            $claims = new Claims(['name' => 'John Doe']);
            $token = new IdToken('jwt-token', $claims);

            expect($token->name)->toBe('John Doe');
        });

        it('provides access to the email claim when present', function () {
            $claims = new Claims(['email' => 'john@example.com']);
            $token = new IdToken('jwt-token', $claims);

            expect($token->email)->toBe('john@example.com');
        });

        it('returns null when email claim is absent', function () {
            $claims = new Claims([]);
            $token = new IdToken('jwt-token', $claims);

            expect($token->email)->toBeNull();
        });

        it('provides access to the picture claim when present', function () {
            $claims = new Claims(['picture' => 'https://example.com/avatar.jpg']);
            $token = new IdToken('jwt-token', $claims);

            expect($token->picture)->toBe('https://example.com/avatar.jpg');
        });

        it('returns null when picture claim is absent', function () {
            $claims = new Claims([]);
            $token = new IdToken('jwt-token', $claims);

            expect($token->picture)->toBeNull();
        });

        it('provides access to the sid claim', function () {
            $claims = new Claims(['sid' => 'session-id-123']);
            $token = new IdToken('jwt-token', $claims);

            expect($token->sid)->toBe('session-id-123');
        });

        it('provides access to the org_id claim when present', function () {
            $claims = new Claims(['org_id' => 'org_123']);
            $token = new IdToken('jwt-token', $claims);

            expect($token->orgId)->toBe('org_123');
        });

        it('returns null when org_id claim is absent', function () {
            $claims = new Claims([]);
            $token = new IdToken('jwt-token', $claims);

            expect($token->orgId)->toBeNull();
        });
    });

    describe('Stringable interface', function () {
        it('returns the token string when cast to string', function () {
            $claims = new Claims(['sub' => 'auth0|123']);
            $token = new IdToken('my-jwt-token', $claims);

            expect((string) $token)->toBe('my-jwt-token');
        });

        it('works with string concatenation by calling __toString', function () {
            $claims = new Claims(['sub' => 'auth0|123']);
            $token = new IdToken('jwt-token', $claims);

            expect('Token: '.$token)->toBe('Token: jwt-token');
        });
    });

    describe('claim() method', function () {
        it('returns the claim value when it exists', function () {
            $claims = new Claims(['custom_claim' => 'custom_value']);
            $token = new IdToken('jwt-token', $claims);

            expect($token->claim('custom_claim'))->toBe('custom_value');
        });

        it('returns null for non-existent claim', function () {
            $claims = new Claims([]);
            $token = new IdToken('jwt-token', $claims);

            expect($token->claim('non_existent'))->toBeNull();
        });

        it('supports dot notation for nested claims', function () {
            $claims = new Claims(['user' => ['profile' => ['role' => 'admin']]]);
            $token = new IdToken('jwt-token', $claims);

            expect($token->claim('user.profile.role'))->toBe('admin');
        });
    });

    describe('Authenticatable interface', function () {
        it('returns the sub claim as the auth identifier', function () {
            $claims = new Claims(['sub' => 'auth0|1234567890']);
            $token = new IdToken('jwt-token', $claims);

            expect($token->getAuthIdentifier())->toBe('auth0|1234567890');
        });

        it('returns "sub" as the auth identifier name', function () {
            $claims = new Claims([]);
            $token = new IdToken('jwt-token', $claims);

            expect($token->getAuthIdentifierName())->toBe('sub');
        });

        it('returns a placeholder for the auth password', function () {
            $claims = new Claims([]);
            $token = new IdToken('jwt-token', $claims);

            expect($token->getAuthPassword())->toBe('empty');
        });

        it('returns "password" as the auth password name', function () {
            $claims = new Claims([]);
            $token = new IdToken('jwt-token', $claims);

            expect($token->getAuthPasswordName())->toBe('password');
        });

        it('returns a placeholder for the remember token', function () {
            $claims = new Claims([]);
            $token = new IdToken('jwt-token', $claims);

            expect($token->getRememberToken())->toBe('not-managed');
        });

        it('returns "remember_me" as the remember token name', function () {
            $claims = new Claims([]);
            $token = new IdToken('jwt-token', $claims);

            expect($token->getRememberTokenName())->toBe('remember_me');
        });

        it('does nothing when setting the remember token', function () {
            $claims = new Claims(['sub' => 'auth0|123']);
            $token = new IdToken('jwt-token', $claims);

            $token->setRememberToken('some-token');

            // Should not throw or change state
            expect($token->sub)->toBe('auth0|123');
        });

        it('returns the auth identifier as the key', function () {
            $claims = new Claims(['sub' => 'auth0|123']);
            $token = new IdToken('jwt-token', $claims);

            expect($token->getKey())->toBe('auth0|123');
        });
    });

    describe('HasAvatar interface (Filament)', function () {
        it('returns the picture as the Filament avatar URL when present', function () {
            $claims = new Claims(['picture' => 'https://example.com/avatar.jpg']);
            $token = new IdToken('jwt-token', $claims);

            expect($token->getFilamentAvatarUrl())->toBe('https://example.com/avatar.jpg');
        });

        it('returns null when the picture is absent', function () {
            $claims = new Claims([]);
            $token = new IdToken('jwt-token', $claims);

            expect($token->getFilamentAvatarUrl())->toBeNull();
        });
    });

    describe('HasName interface (Filament)', function () {
        it('returns the name as the Filament name', function () {
            $claims = new Claims(['name' => 'John Doe']);
            $token = new IdToken('jwt-token', $claims);

            expect($token->getFilamentName())->toBe('John Doe');
        });
    });

    describe('Arrayable interface', function () {
        it('returns the claims as an array', function () {
            $claimsData = [
                'sub' => 'auth0|123',
                'name' => 'John',
                'email' => 'john@example.com',
            ];

            $claims = new Claims($claimsData);
            $token = new IdToken('jwt-token', $claims);

            expect($token->toArray())->toBe($claimsData);
        });

        it('includes all claims when converted to an array', function () {
            $claimsData = [
                'sub' => 'auth0|123',
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'picture' => 'https://example.com/avatar.jpg',
                'org_id' => 'org_123',
            ];

            $claims = new Claims($claimsData);
            $token = new IdToken('jwt-token', $claims);

            expect($token->toArray())->toBe($claimsData);
        });
    });

    describe('integration scenarios', function () {
        it('handles a typical Auth0 ID token with all standard claims', function () {
            $claimsData = [
                'iss' => 'https://tenant.auth0.com/',
                'aud' => 'client-id',
                'sub' => 'auth0|1234567890',
                'sid' => 'session-id-123',
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'picture' => 'https://example.com/avatar.jpg',
                'org_id' => 'org_123',
            ];
            $claims = new Claims($claimsData);
            $token = new IdToken('eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...', $claims);

            expect($token->sub)->toBe('auth0|1234567890')
                ->and($token->name)->toBe('John Doe')
                ->and($token->email)->toBe('john@example.com')
                ->and($token->picture)->toBe('https://example.com/avatar.jpg')
                ->and($token->sid)->toBe('session-id-123')
                ->and($token->orgId)->toBe('org_123')
                ->and($token->getAuthIdentifier())->toBe('auth0|1234567890')
                ->and($token->getFilamentName())->toBe('John Doe')
                ->and($token->getFilamentAvatarUrl())->toBe('https://example.com/avatar.jpg')
                ->and((string) $token)->toBe('eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...');
        });

        it('handles a minimal ID token with only required claims', function () {
            $claimsData = [
                'sub' => 'auth0|1234567890',
                'name' => 'Test User',
            ];
            $claims = new Claims($claimsData);
            $token = new IdToken('minimal-token', $claims);

            expect($token->sub)->toBe('auth0|1234567890')
                ->and($token->name)->toBe('Test User')
                ->and($token->email)->toBeNull()
                ->and($token->picture)->toBeNull()
                ->and($token->orgId)->toBeNull();
        });
    });
});
