<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Contracts;

interface ValidatesMetadata
{
    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function validate(array $metadata): array;
}
