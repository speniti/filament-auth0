<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Peniti\FilamentAuth0\Exceptions\AccessDeniedException;
use Peniti\FilamentAuth0\Jwt\Rules\Matches;

use function session;

class CallbackRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var ?string $state */
        $state = session('auth0_state');

        return [
            'code' => ['required', 'min:1'],
            'state' => ['required', 'min:1', new Matches($state, 'parameter')],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->query('error')) {
            throw AccessDeniedException::create((string) $this->query('error_description'));
        }
    }
}
