<?php

declare(strict_types=1);

namespace Peniti\FilamentAuth0\Exceptions;

use Illuminate\Http\Client\Response;

class IdentityProviderConnectionException extends Auth0Exception
{
    public function __construct(private readonly ?Response $response = null, int $code = 502)
    {
        parent::__construct(
            $code,
            $this->response
                ? "Identity provider returned an error: {$this->response->body()}"
                : 'Failed to connect to identity provider.'
        );
    }

    public static function create(?Response $response = null): self
    {
        return new self($response);
    }

    /**
     * @return array{
     *     response_status: int|null,
     *     response_body: string|null
     * }
     */
    public function context(): array
    {
        return [
            'response_status' => $this->response?->status(),
            'response_body' => $this->response?->body(),
        ];
    }
}
