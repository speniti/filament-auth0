<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Actions;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use Peniti\FilamentAuth0\Contracts\DecodesIdToken;
use Peniti\FilamentAuth0\Exceptions\ExpiredIdTokenException;
use Peniti\FilamentAuth0\Exceptions\IdentityProviderConnectionException;
use Peniti\FilamentAuth0\Exceptions\InvalidSignatureException;
use Peniti\FilamentAuth0\Exceptions\MalformedIdTokenException;
use Peniti\FilamentAuth0\Exceptions\UnableToRetrieveKeysException;
use Peniti\FilamentAuth0\Jwks\CachedKeySet;
use Peniti\FilamentAuth0\Jwt\Claims;
use Peniti\FilamentAuth0\Jwt\IdToken;
use Throwable;

readonly class DecodeIdToken implements DecodesIdToken
{
    public function __construct(private CachedKeySet $jwks) {}

    public function decode(string $token): IdToken
    {
        try {
            $payload = JWT::decode($token, $this->jwks);

            return new IdToken($token, new Claims($payload));
        } catch (ExpiredException) {
            throw ExpiredIdTokenException::create();
        } catch (SignatureInvalidException) {
            throw InvalidSignatureException::create();
        } catch (UnableToRetrieveKeysException|IdentityProviderConnectionException $exception) {
            // JWKS endpoint exceptions must be handled elsewhere.
            throw $exception;
        } catch (Throwable $exception) {
            throw MalformedIdTokenException::create($exception);
        }
    }
}
