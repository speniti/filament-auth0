<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Actions;

use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Peniti\FilamentAuth0\Contracts\ValidatesMetadata;
use Peniti\FilamentAuth0\Jwt\Rules\Matches;

readonly class ValidateMetadata implements ValidatesMetadata
{
    public function __construct(
        #[Config('filament-auth0.domain')] private string $domain,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validate(array $metadata): array
    {

        Validator::make($metadata, [
            'issuer' => ['required', new Matches("https://$this->domain/")],
        ])->validate();

        return $metadata;
    }
}
