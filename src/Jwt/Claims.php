<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Jwt;

use Closure;

use function data_get;
use function gettype;

use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;

use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function sprintf;

use stdClass;

/** @implements Arrayable<string, mixed> */
readonly class Claims implements Arrayable
{
    /** @param array<string, mixed>|stdClass $claims */
    public function __construct(private array|stdClass $claims) {}

    /** @return ($optional is true ? bool|null : bool) */
    public function boolean(string $claim, bool|Closure|null $default = null, bool $optional = false): ?bool
    {
        $value = $this->get($claim, $default);

        if (! is_bool($value)) {
            if ($optional) {
                return null;
            }

            throw new InvalidArgumentException(
                sprintf('The value of the claim [%s] must be a boolean, %s given.', $claim, gettype($value))
            );
        }

        return $value;
    }

    /** @return ($optional is true ? float|null : float) */
    public function float(string $claim, float|Closure|null $default = null, bool $optional = false): ?float
    {
        $value = $this->get($claim, $default);

        if (! is_float($value)) {
            if ($optional) {
                return null;
            }

            throw new InvalidArgumentException(
                sprintf('The value of the claim [%s] must be a float, %s given.', $claim, gettype($value))
            );
        }

        return $value;
    }

    public function get(string $claim, mixed $default = null): mixed
    {
        return data_get($this->claims, $claim, $default);
    }

    /** @return ($optional is true ? int|null : int) */
    public function integer(string $claim, int|Closure|null $default = null, bool $optional = false): ?int
    {
        $value = $this->get($claim, $default);

        if (! is_int($value)) {
            if ($optional) {
                return null;
            }

            throw new InvalidArgumentException(
                sprintf('The value of the claim [%s] must be an integer, %s given.', $claim, gettype($value))
            );
        }

        return $value;
    }

    /** @return ($optional is true ? string|null : string) */
    public function string(string $claim, string|Closure|null $default = null, bool $optional = false): ?string
    {
        $value = $this->get($claim, $default);

        if (! is_string($value)) {
            if ($optional) {
                return null;
            }

            throw new InvalidArgumentException(
                sprintf('The value of the claim [%s] must be a string, %s given.', $claim, gettype($value))
            );
        }

        return $value;
    }

    public function toArray(): array
    {
        return (array) $this->claims;
    }
}
