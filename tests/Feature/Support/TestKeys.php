<?php

declare(strict_types=1);

namespace Tests\Feature\Support;

use function bin2hex;

use phpseclib3\Crypt\RSA;
use Random\RandomException;

class TestKeys
{
    private static ?string $keyId = null;

    private static ?RSA\PrivateKey $privateKey = null;

    /**
     * Get the key ID for the test key pair.
     *
     * @throws RandomException
     */
    public static function keyId(): string
    {
        self::initializeKeyPair();

        return self::$keyId;
    }

    /**
     * Get the private key for testing.
     *
     * @param  array<string, mixed>  $options
     *
     * @throws RandomException
     */
    public static function privateKey(?string $format = null, array $options = []): RSA\PrivateKey|string
    {
        self::initializeKeyPair();

        if ($format) {
            /** @var string */
            return self::$privateKey->toString($format, $options);
        }

        return self::$privateKey;
    }

    /**
     * Get the public key for testing.
     *
     * @param  array<string, mixed>  $options
     *
     * @throws RandomException
     */
    public static function publicKey(?string $format = null, array $options = []): RSA\PublicKey|string
    {
        self::initializeKeyPair();

        /** @var RSA\PublicKey $publicKey */
        $publicKey = self::$privateKey->getPublicKey();

        if ($format) {
            /** @var string */
            return $publicKey->toString($format, ['alg' => 'RS256', 'kid' => self::$keyId, ...$options]);
        }

        return $publicKey;
    }

    /**
     * Initialize the key pair on-demand.
     *
     * @throws RandomException
     */
    private static function initializeKeyPair(): void
    {
        if (self::$privateKey === null) {
            self::$privateKey = RSA::createKey();
            self::$keyId = bin2hex(random_bytes(8));
        }
    }
}
