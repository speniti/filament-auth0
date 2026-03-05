<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Jwt\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

use function is_null;

readonly class Matches implements ValidationRule
{
    public function __construct(private ?string $expected, private string $type = 'claim') {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_null($this->expected) || $value !== $this->expected) {
            $fail("The :attribute $this->type does not match the expected value.")->translate();
        }
    }
}
